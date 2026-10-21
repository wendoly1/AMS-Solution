<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
chdir(dirname(__DIR__));
$_SERVER['HTTP_HOST'] = 'localhost'; putenv('AMS_ENV=local');
require 'includes/database.php';
require 'admin/auth.php';
if (!$pdo instanceof PDO || !in_array($database['host'], ['localhost','127.0.0.1'], true)) throw new RuntimeException('Se requiere MySQL local.');
$server = $pdo; $testName = 'ams_auth_test_' . bin2hex(random_bytes(6)); $created = false;
$httpProcess = null; $checks = 0;
$tempFiles = []; $jars = [];
foreach (['a','b','guest','new'] as $name) { $jars[$name] = tempnam(sys_get_temp_dir(),'ams_auth_'); $tempFiles[]=$jars[$name]; }
$mailFile = tempnam(sys_get_temp_dir(),'ams_mail_'); $logFile = tempnam(sys_get_temp_dir(),'ams_http_'); $tempFiles[]=$mailFile; $tempFiles[]=$logFile;
$base = 'http://127.0.0.1:18084/';
function auth_check(bool $value, string $message): void { global $checks; if (!$value) throw new RuntimeException($message); $checks++; }
function auth_request(string $path, ?array $post = null, string $jar = 'guest', array $headers = []): array {
    global $base,$jars;
    $curl=curl_init($base.$path);
    curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>15,CURLOPT_COOKIEFILE=>$jars[$jar],CURLOPT_COOKIEJAR=>$jars[$jar],CURLOPT_HTTPHEADER=>$headers]);
    if ($post!==null) curl_setopt($curl,CURLOPT_POSTFIELDS,http_build_query($post));
    $response=curl_exec($curl); if ($response===false) { curl_close($curl); throw new RuntimeException('No responde el servidor de prueba.'); }
    $size=curl_getinfo($curl,CURLINFO_HEADER_SIZE); $code=curl_getinfo($curl,CURLINFO_HTTP_CODE); curl_close($curl);
    $head=substr($response,0,$size); preg_match('/^Location:\s*(.+)$/mi',$head,$location);
    return [$code,substr($response,$size),trim($location[1]??''),$head];
}
function auth_csrf(string $html): string { preg_match('/name="csrf" value="([^"]+)"/',$html,$match); if (!isset($match[1])) throw new RuntimeException('Falta CSRF.'); return $match[1]; }
function auth_signin(string $password, string $jar, string $username='auth-test'): int {
    [, $html]=auth_request('admin/login.php',null,$jar);
    if (!str_contains($html,'name="csrf"')) { auth_request('admin/logout.php',null,$jar); [, $html]=auth_request('admin/login.php',null,$jar); }
    [$code]=auth_request('admin/login.php',['csrf'=>auth_csrf($html),'username'=>$username,'password'=>$password],$jar); return $code;
}
function auth_messages(): array {
    global $mailFile; $rows=[];
    clearstatcache(true, $mailFile);
    foreach (file($mailFile,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)?:[] as $line) $rows[]=json_decode($line,true);
    return $rows;
}
function auth_last_token(): string {
    $found=''; foreach (auth_messages() as $message) if (preg_match('~/reset-password\.php\?token=([a-f0-9]{64})~',$message,$match)) $found=$match[1];
    if ($found==='') throw new RuntimeException('No se capturó el correo de recuperación.'); return $found;
}
function auth_capture_request(string $email): void {
    global $mailFile;
    admin_request_password_reset($email, static function (string $to, string $subject, string $body) use ($mailFile): bool {
        file_put_contents($mailFile, json_encode($body, JSON_THROW_ON_ERROR) . "\n", FILE_APPEND | LOCK_EX);
        return true;
    });
}
function auth_user(): array { global $db; return $db->query("SELECT * FROM admin_users WHERE username='auth-test'")->fetch(); }
try {
    $server->exec('CREATE DATABASE `'.$testName.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'); $created=true;
    $db=new PDO('mysql:host='.$database['host'].';port='.($database['port']?:'3306').';dbname='.$testName.';charset=utf8mb4',$database['user'],$database['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $pdo=$db;
    putenv('AMS_ADMIN_URL=' . $base . 'admin');
    putenv('AMS_MAIL_FROM=test-sender@example.com');
    $_SERVER['REMOTE_ADDR']='127.0.0.1';
    $db->exec('CREATE TABLE admin_users (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,username VARCHAR(80) UNIQUE,password_hash VARCHAR(255),created_at DATETIME DEFAULT CURRENT_TIMESTAMP,last_login_at DATETIME NULL)');
    $oldPassword=bin2hex(random_bytes(16)); $newPassword=bin2hex(random_bytes(16)); $finalPassword=bin2hex(random_bytes(16));
    $originalHash=password_hash($oldPassword,PASSWORD_DEFAULT);
    $db->prepare('INSERT INTO admin_users (username,password_hash) VALUES (?,?)')->execute(['auth-test',$originalHash]);
    $env=getenv(); $env['AMS_ENV']='local'; $env['AMS_DB_NAME']=$testName; $env['AMS_ADMIN_URL']=$base.'admin'; $env['AMS_MAIL_FROM']='test-sender@example.com'; $env['AMS_SETUP_TOKEN']=bin2hex(random_bytes(24));
    $httpProcess=proc_open([PHP_BINARY,'-d','SMTP=127.0.0.1','-d','smtp_port=1','-d','sendmail_path=','-S','127.0.0.1:18084','-t',getcwd()],[0=>['pipe','r'],1=>['file',$logFile,'a'],2=>['file',$logFile,'a']],$pipes,getcwd(),$env); fclose($pipes[0]);
    for ($i=0;$i<30;$i++) { try { [$code,$html]=auth_request('admin/login.php'); break; } catch (RuntimeException $failure) { usleep(100000); } }
    auth_check(($code??0)===200,'El acceso no respondió.');
    auth_check(auth_user()['password_hash']===$originalHash && auth_user()['email']===null,'La migración cambió credenciales existentes.');
    auth_check(str_contains($html,'forgot-password.php'),'Falta el acceso a recuperación.');
    auth_check(auth_signin($oldPassword,'a')===302 && auth_signin($oldPassword,'b')===302,'No ingresan las cuentas existentes.');
    [, $account]=auth_request('admin/account.php',null,'a'); $csrf=auth_csrf($account);
    [$code]=auth_request('admin/account.php',['csrf'=>'invalid','action'=>'email'],'a'); auth_check($code===403,'Falta CSRF en Mi cuenta.');
    $emailPost=['csrf'=>$csrf,'action'=>'email','email'=>'Owner@Example.com','email_confirmation'=>'owner@example.com','current_password'=>'incorrecta'];
    [, $failure]=auth_request('admin/account.php',$emailPost,'a'); auth_check(str_contains($failure,'actual no es correcta') && auth_user()['email']===null,'Se cambió el correo sin la contraseña actual.');
    $emailPost['current_password']=$oldPassword;
    [$code,, $redirect]=auth_request('admin/account.php',$emailPost,'a'); auth_check($code===303 && str_contains($redirect,'saved=email') && auth_user()['email']==='owner@example.com','No se registra el correo de recuperación.');
    [$code]=auth_request('admin/index.php',null,'b'); auth_check($code===302,'La actualización del correo debe cerrar otras sesiones.');
    $db->prepare('INSERT INTO site_settings (setting_key,setting_value) VALUES (?,?)')->execute(['contact.email','public-contact@example.com']);
    [, $forgot]=auth_request('admin/forgot-password.php'); $guestCsrf=auth_csrf($forgot);
    [$code]=auth_request('admin/forgot-password.php',['csrf'=>'incorrecto','email'=>'owner@example.com']); auth_check($code===403,'Falta CSRF en recuperación.');
    [$unknownCode, $unknown]=auth_request('admin/forgot-password.php',['csrf'=>$guestCsrf,'email'=>'public-contact@example.com']);
    auth_check($unknownCode === 200, 'Falló la solicitud de correo no registrado.');
    auth_check((int)$db->query('SELECT COUNT(*) FROM admin_password_resets')->fetchColumn()===0 && !auth_messages(),'El correo público no debe recuperar la cuenta.');
    $cookieHeader = '';
    foreach (file($jars['guest'], FILE_IGNORE_NEW_LINES) as $line) {
        $parts = explode("\t", $line);
        if (count($parts) === 7 && $parts[5] === 'PHPSESSID') $cookieHeader = 'Cookie: ' . $parts[5] . '=' . $parts[6];
    }
    [$knownCode, $known]=auth_request('admin/forgot-password.php',['csrf'=>$guestCsrf,'email'=>'owner@example.com'],'guest',['Host: untrusted.example', $cookieHeader]);
    auth_check($knownCode === 200, 'Falló la solicitud con Host no confiable.');
    auth_check($known===$unknown,'La respuesta revela si el correo está registrado.');
    auth_capture_request('owner@example.com');
    $token=auth_last_token(); $record=$db->query('SELECT * FROM admin_password_resets')->fetch();
    auth_check($record['token_hash']===hash('sha256',$token) && $record['token_hash']!==$token,'El token no se almacena como hash.');
    auth_check((int)$record['expires_at']-time()<=1200 && (int)$record['expires_at']>time(),'Vencimiento de recuperación incorrecto.');
    auth_check(str_contains(implode('',auth_messages()),$base.'admin/reset-password.php?token=') && !str_contains(implode('',auth_messages()),'untrusted.example'),'El enlace confía en Host.');
    auth_check(password_verify($oldPassword,auth_user()['password_hash']),'Solicitar recuperación cambió la contraseña.');
    auth_check(auth_signin($oldPassword,'b')===302,'El acceso actual dejó de funcionar antes de usar el enlace.');
    [$code,, $redirect,$headers]=auth_request('admin/reset-password.php?token='.$token);
    auth_check($code===303 && $redirect==='reset-password.php' && str_contains($headers,'no-referrer'),'No se retira el token de la URL.');
    [, $reset]=auth_request('admin/reset-password.php'); $resetCsrf=auth_csrf($reset);
    auth_check(str_contains($reset,'name="password_confirmation"') && !str_contains($reset,$token),'No se muestra un formulario de contraseña seguro.');
    [$code]=auth_request('admin/reset-password.php',['csrf'=>'incorrecto','password'=>$newPassword,'password_confirmation'=>$newPassword]); auth_check($code===403,'Falta CSRF al restablecer.');
    [, $weak]=auth_request('admin/reset-password.php',['csrf'=>$resetCsrf,'password'=>'corta','password_confirmation'=>'corta']); auth_check(str_contains($weak,'al menos 12') && password_verify($oldPassword,auth_user()['password_hash']),'Acepta contraseñas cortas.');
    [, $mismatch]=auth_request('admin/reset-password.php',['csrf'=>$resetCsrf,'password'=>$newPassword,'password_confirmation'=>'otra contraseña diferente']); auth_check(str_contains($mismatch,'no coinciden'),'Acepta confirmaciones diferentes.');
    [$code,, $redirect]=auth_request('admin/reset-password.php',['csrf'=>$resetCsrf,'password'=>$newPassword,'password_confirmation'=>$newPassword]);
    auth_check($code===303 && $redirect==='login.php?reset=1' && password_verify($newPassword,auth_user()['password_hash']),'No establece la contraseña nueva.');
    auth_check(!password_verify($oldPassword,auth_user()['password_hash']),'La contraseña anterior sigue activa.');
    [$code]=auth_request('admin/index.php'); auth_check($code===302,'La recuperación no debe iniciar sesión automáticamente.');
    foreach (['a','b'] as $jar) { [$code]=auth_request('admin/index.php',null,$jar); auth_check($code===302,'Una sesión anterior sigue activa.'); }
    auth_request('admin/reset-password.php?token='.$token); [, $reused]=auth_request('admin/reset-password.php'); auth_check(str_contains($reused,'ya fue utilizado'),'Permite reutilizar el enlace.');
    $expired=bin2hex(random_bytes(32)); $user=auth_user();
    $db->prepare('INSERT INTO admin_password_resets (token_hash,user_id,auth_version,expires_at,created_at) VALUES (?,?,?,?,?)')->execute([hash('sha256',$expired),$user['id'],$user['auth_version'],time()-1,time()-1300]);
    auth_request('admin/reset-password.php?token='.$expired); [, $expiredPage]=auth_request('admin/reset-password.php'); auth_check(!str_contains($expiredPage,'name="password_confirmation"'),'Acepta un enlace vencido.');
    auth_check(auth_signin($newPassword,'new')===302,'No inicia sesión con la nueva contraseña.');
    $db->exec('DELETE FROM admin_auth_limits');
    [, $forgot]=auth_request('admin/forgot-password.php'); $guestCsrf=auth_csrf($forgot);
    auth_capture_request('owner@example.com'); $second=auth_last_token();
    auth_capture_request('owner@example.com'); $third=auth_last_token();
    auth_check($second!==$third && (int)$db->query("SELECT COUNT(*) FROM admin_password_resets WHERE used_at IS NULL AND expires_at > UNIX_TIMESTAMP()")->fetchColumn()===1,'Un enlace nuevo no invalida los anteriores.');
    auth_capture_request('owner@example.com'); $third=auth_last_token();
    $mailCount=count(auth_messages()); auth_capture_request('owner@example.com'); auth_check(count(auth_messages())===$mailCount,'El límite de solicitudes se evade cambiando de sesión.');
    [, $account]=auth_request('admin/account.php',null,'new'); $accountCsrf=auth_csrf($account);
    $change=['csrf'=>$accountCsrf,'action'=>'password','current_password'=>$newPassword,'password'=>$finalPassword,'password_confirmation'=>$finalPassword];
    [$code]=auth_request('admin/account.php',$change,'new'); auth_check($code===303 && password_verify($finalPassword,auth_user()['password_hash']),'No permite cambiar contraseña desde Mi cuenta.');
    auth_request('admin/reset-password.php?token='.$third); [, $cancelled]=auth_request('admin/reset-password.php'); auth_check(!str_contains($cancelled,'name="password_confirmation"'),'Cambiar contraseña no cancela enlaces pendientes.');
    auth_check(!str_contains(implode('',auth_messages()),$newPassword) && !str_contains(implode('',auth_messages()),$finalPassword),'Se envió una contraseña por correo.');
    [$code] = auth_request('admin/new-user.php', null, 'guest');
    auth_check($code === 302, 'El registro no debe ser público.');
    [, $registration] = auth_request('admin/new-user.php', null, 'new');
    $registerCsrf = auth_csrf($registration);
    $registeredPassword = bin2hex(random_bytes(16));
    $register = ['csrf'=>$registerCsrf, 'username'=>'cliente-nuevo', 'email'=>'cliente-nuevo@example.com', 'email_confirmation'=>'cliente-nuevo@example.com', 'password'=>$registeredPassword, 'password_confirmation'=>$registeredPassword, 'current_password'=>$finalPassword];
    $badCsrf = $register; $badCsrf['csrf'] = 'incorrecto';
    [$code] = auth_request('admin/new-user.php', $badCsrf, 'new');
    auth_check($code === 403, 'El registro no valida CSRF.');
    $wrongPassword = $register; $wrongPassword['current_password'] = 'incorrecta';
    [, $rejected] = auth_request('admin/new-user.php', $wrongPassword, 'new');
    auth_check(str_contains($rejected, 'actual no es correcta') && (int)$db->query('SELECT COUNT(*) FROM admin_users')->fetchColumn() === 1, 'Permite registrar sin confirmar la contraseña del administrador.');
    [$code, , $redirect] = auth_request('admin/new-user.php', $register, 'new');
    $registered = $db->query("SELECT * FROM admin_users WHERE username='cliente-nuevo'")->fetch();
    auth_check($code === 303 && $redirect === 'new-user.php?created=1' && $registered && password_verify($registeredPassword, $registered['password_hash']) && $registered['email'] === 'cliente-nuevo@example.com', 'No se registra la cuenta nueva correctamente.');
    [$code] = auth_request('admin/index.php', null, 'new');
    auth_check($code === 200, 'Registrar una cuenta cerró la sesión del administrador.');
    [, $duplicate] = auth_request('admin/new-user.php', $register, 'new');
    auth_check(str_contains($duplicate, 'ya está registrado') && (int)$db->query('SELECT COUNT(*) FROM admin_users')->fetchColumn() === 2, 'Permite usuarios duplicados.');
    $duplicateEmail = $register; $duplicateEmail['username'] = 'otro-usuario';
    [, $duplicate] = auth_request('admin/new-user.php', $duplicateEmail, 'new');
    auth_check(str_contains($duplicate, 'ya está registrado') && (int)$db->query('SELECT COUNT(*) FROM admin_users')->fetchColumn() === 2, 'Permite correos duplicados.');
    $weakRegistration = $register; $weakRegistration['username']='otro-usuario'; $weakRegistration['email']='otro@example.com'; $weakRegistration['email_confirmation']='otro@example.com'; $weakRegistration['password']='corta'; $weakRegistration['password_confirmation']='corta';
    [, $weak] = auth_request('admin/new-user.php', $weakRegistration, 'new');
    auth_check(str_contains($weak, 'al menos 12') && (int)$db->query('SELECT COUNT(*) FROM admin_users')->fetchColumn() === 2, 'Permite registrar con una clave débil.');
    auth_check(auth_signin($registeredPassword, 'b', 'cliente-nuevo') === 302, 'El nuevo usuario no puede ingresar.');
    // Prueba de instalación nueva; solo se eliminan usuarios de esta base temporal.
    $db->exec('DELETE FROM admin_users');
    [$code]=auth_request('admin/setup.php'); auth_check($code===404,'La configuración inicial debe requerir el enlace privado.');
    [, $setup]=auth_request('admin/setup.php?token='.$env['AMS_SETUP_TOKEN']); $setupCsrf=auth_csrf($setup);
    $setupPost=['csrf'=>$setupCsrf,'username'=>'nuevo-admin','email'=>'nuevo@example.com','email_confirmation'=>'nuevo@example.com','password'=>$finalPassword,'password_confirmation'=>$finalPassword];
    [$code]=auth_request('admin/setup.php?token='.$env['AMS_SETUP_TOKEN'],$setupPost); auth_check($code===302 && $db->query('SELECT email FROM admin_users')->fetchColumn()==='nuevo@example.com','La instalación nueva no guarda el correo.');
    // Fallo de entrega: no publica el enlace ni cambia la contraseña.
    $db->exec('DELETE FROM admin_auth_limits');
    [, $forgot]=auth_request('admin/forgot-password.php'); $guestCsrf=auth_csrf($forgot);
    admin_request_password_reset('nuevo@example.com', static fn () => false);
    $failedMail=$unknown;
    $newId=(int)$db->query('SELECT id FROM admin_users')->fetchColumn();
    auth_check(str_contains($failedMail,'Si el correo está registrado') && (int)$db->query('SELECT COUNT(*) FROM admin_password_resets WHERE user_id='.$newId)->fetchColumn()===0,'El fallo de correo expone o deja activo un token sin entregar.');
    echo "OK: $checks comprobaciones de recuperación, cambio de contraseña, transporte simulado, límites y sesiones.\n";
} catch (Throwable $failure) {
    // Diagnóstico sin destinatarios, contraseñas, rutas de tokens ni cuerpos de correo.
    foreach (file($logFile, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
        if (str_contains($line, 'No se pudo') && !str_contains($line, 'token=')) echo preg_replace('/^\[[^\]]+\] /', '', $line) . "\n";
    }
    throw $failure;
} finally {
    if (is_resource($httpProcess)) { proc_terminate($httpProcess); proc_close($httpProcess); }
    if ($created && preg_match('/^ams_auth_test_[a-f0-9]{12}$/D',$testName)) $server->exec('DROP DATABASE `'.$testName.'`');
    foreach ($tempFiles as $path) if (is_file($path)) unlink($path);
}
