<?php
// Prueba real de HTTP + MySQL en una base temporal, sin tocar el contenido del sitio.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
chdir(dirname(__DIR__));
$_SERVER['HTTP_HOST'] = 'localhost';
putenv('AMS_ENV=local');
require 'includes/database.php';
if (!$pdo instanceof PDO || !in_array($database['host'], ['localhost', '127.0.0.1'], true)) throw new RuntimeException('Se requiere MySQL local disponible.');
$server = $pdo;
$testName = 'ams_editor_test_' . bin2hex(random_bytes(6));
$created = false;
$process = null;
$uploads = [];
$cookie = tempnam(sys_get_temp_dir(), 'ams_cookie_');
$log = tempnam(sys_get_temp_dir(), 'ams_server_');
$image = tempnam(sys_get_temp_dir(), 'ams_png_');
$base = 'http://127.0.0.1:18081/';
$checks = 0;
function check_editor(bool $condition, string $message): void {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}
function request_editor(string $path, ?array $data = null, bool $authenticated = true): array {
    global $base, $cookie;
    $curl = curl_init($base . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_TIMEOUT => 15, CURLOPT_FOLLOWLOCATION => false]);
    if ($authenticated) curl_setopt_array($curl, [CURLOPT_COOKIEFILE => $cookie, CURLOPT_COOKIEJAR => $cookie]);
    if ($data !== null) {
        $multipart = count(array_filter($data, static fn($value) => $value instanceof CURLFile)) > 0;
        curl_setopt($curl, CURLOPT_POSTFIELDS, $multipart ? $data : http_build_query($data));
    }
    $response = curl_exec($curl);
    if ($response === false) { $error = curl_error($curl); curl_close($curl); throw new RuntimeException($error); }
    $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    $header = substr($response, 0, $headerSize);
    preg_match('/^Location:\s*(.+)$/mi', $header, $location);
    return [$code, substr($response, $headerSize), trim($location[1] ?? ''), $header];
}
function csrf_editor(string $html): string {
    preg_match('/name="csrf" value="([^"]+)"/', $html, $match);
    if (!isset($match[1])) throw new RuntimeException('Falta el token CSRF.');
    return html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
}
function settings_editor(string $html): array {
    $doc = new DOMDocument(); @$doc->loadHTML($html);
    $xpath = new DOMXPath($doc); $data = [];
    foreach ($xpath->query('//input[starts-with(@name,"settings[")] | //textarea[starts-with(@name,"settings[")]') as $node) {
        $data[$node->getAttribute('name')] = $node->tagName === 'textarea' ? $node->textContent : $node->getAttribute('value');
    }
    return $data;
}
function setting_editor(string $key) {
    global $testDb;
    $query = $testDb->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ?'); $query->execute([$key]);
    return $query->fetchColumn();
}
try {
    $server->exec('CREATE DATABASE `' . $testName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    $testDb = new PDO('mysql:host=' . $database['host'] . ';port=' . ($database['port'] ?: '3306') . ';dbname=' . $testName . ';charset=utf8mb4', $database['user'], $database['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $testDb->exec('CREATE TABLE admin_users (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, username VARCHAR(80) UNIQUE, password_hash VARCHAR(255), created_at DATETIME DEFAULT CURRENT_TIMESTAMP, last_login_at DATETIME NULL)');
    $password = bin2hex(random_bytes(16));
    $testDb->prepare('INSERT INTO admin_users (username,password_hash) VALUES (?,?)')->execute(['editor-test', password_hash($password, PASSWORD_DEFAULT)]);
    $env = getenv(); $env['AMS_ENV'] = 'local'; $env['AMS_DB_NAME'] = $testName;
    // Desactiva toda entrega de correo: las pruebas solo pueden intentar SMTP local en un puerto cerrado.
    $process = proc_open([PHP_BINARY, '-d', 'SMTP=127.0.0.1', '-d', 'smtp_port=1', '-d', 'sendmail_path=', '-S', '127.0.0.1:18081', '-t', getcwd()], [0 => ['pipe','r'], 1 => ['file',$log,'a'], 2 => ['file',$log,'a']], $pipes, getcwd(), $env);
    if (!is_resource($process)) throw new RuntimeException('No se pudo iniciar el servidor de prueba.');
    fclose($pipes[0]);
    for ($i = 0; $i < 30; $i++) {
        try { [$code, $html] = request_editor('admin/login.php'); break; } catch (RuntimeException $error) { usleep(100000); }
    }
    check_editor(($code ?? 0) === 200, 'No responde el acceso de prueba.');
    [$code] = request_editor('admin/index.php', null, false);
    check_editor($code === 302, 'El panel debe exigir autenticación.');
    [$code, , $location] = request_editor('admin/login.php', ['csrf'=>csrf_editor($html), 'username'=>'editor-test', 'password'=>$password]);
    check_editor($code === 302 && $location === 'index.php', 'No se pudo iniciar sesión.');
    [$code, $generalHtml] = request_editor('admin/index.php?tab=general');
    $csrf = csrf_editor($generalHtml);
    $general = settings_editor($generalHtml) + ['csrf'=>$csrf,'section'=>'general'];
    $general['settings[site.name]'] = 'Firma inicial de prueba';
    $general['settings[contact.whatsapp]'] = '+504 9999 1111';
    $general['settings[contact.map_url]'] = 'https://maps.google.com/?q=Tegucigalpa';
    $general['settings[social.instagram]'] = 'https://www.instagram.com/firma-ejemplo/';
    [$code, , $location] = request_editor('admin/save.php', $general);
    check_editor($code === 302 && str_contains($location,'saved=1'), 'Fallo al publicar datos generales.');
    check_editor(setting_editor('site.name') === 'Firma inicial de prueba', 'Contenido no guardado.');
    check_editor((int)$testDb->query('SELECT COUNT(*) FROM site_revisions')->fetchColumn() === 1, 'Falta la primera versión.');
    request_editor('admin/save.php', $general);
    check_editor((int)$testDb->query('SELECT COUNT(*) FROM site_revisions')->fetchColumn() === 1, 'Guardado sin cambios generó una versión vacía.');
    [$code, $public] = request_editor('index.php');
    check_editor($code === 200 && str_contains($public,'https://wa.me/50499991111') && str_contains($public,'instagram.com/firma-ejemplo'), 'Contacto no reflejado en el sitio.');
    file_put_contents($image, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j7MsAAAAASUVORK5CYII='));
    $draft = $general; $draft['settings[site.name]'] = 'BORRADOR SIN PUBLICAR'; $draft['logo_upload'] = new CURLFile($image,'image/png','preview.png');
    [$code, , $location] = request_editor('admin/preview.php', $draft);
    check_editor($code === 303 && str_contains($location,'token='), 'No se generó la vista previa.');
    [$code, $preview, , $headers] = request_editor('admin/' . $location);
    check_editor($code === 200 && str_contains($preview,'BORRADOR SIN PUBLICAR') && str_contains($preview,'data:image/png;base64,'), 'No se previsualizan texto e imagen.');
    check_editor(str_contains($headers,"form-action 'none'") && str_contains($preview,'type="submit" disabled'), 'Los formularios deben estar desactivados en la vista previa.');
    check_editor(!str_contains($preview,'href="#contacto"') && str_contains($preview,'page=index.php#contacto'), 'Los enlaces internos deben conservar el borrador.');
    check_editor(setting_editor('site.name') === 'Firma inicial de prueba' && setting_editor('site.logo') === false, 'La vista previa publicó contenido.');
    [$code] = request_editor('admin/' . $location, null, false);
    check_editor($code === 302, 'Una vista previa no debe ser pública.');
    [$code] = request_editor('admin/preview.php?token=incorrecto&page=index.php');
    check_editor($code === 410, 'Un token inválido debe rechazarse.');
    $invalid = $general; $invalid['settings[header.cta_url]'] = 'javascript:alert(1)';
    request_editor('admin/save.php', $invalid);
    check_editor(setting_editor('header.cta_url') === 'contacto.php', 'Se publicó un enlace peligroso.');
    [$code] = request_editor('admin/save.php', ['section'=>'general','csrf'=>'incorrecto']);
    check_editor($code === 403, 'El guardado no comprueba CSRF.');
    [$code, $homeHtml] = request_editor('admin/index.php?tab=home');
    $home = settings_editor($homeHtml) + ['csrf'=>$csrf,'section'=>'home'];
    $home['settings[home.primary_label]'] = 'Hablar con AMS'; $home['settings[home.primary_url]'] = 'https://wa.me/50499991111';
    request_editor('admin/save.php', $home);
    [, $public] = request_editor('index.php');
    check_editor(str_contains($public,'>Hablar con AMS</a>'), 'No se actualiza el botón de la portada.');
    $services = ['csrf'=>$csrf,'section'=>'services','services_present'=>'1','services_intro_title'=>'Áreas de prueba','services_intro_text'=>'Presentación de prueba'];
    foreach ([0=>'primera',2=>'tercera',9=>'nueva'] as $index=>$slug) foreach (['slug'=>$slug,'name'=>'Área '.$slug,'summary'=>'Resumen','description'=>'Descripción','services'=>"Servicio 1\nServicio 2",'steps'=>'Paso 1'] as $key=>$value) $services['services['.$index.']['.$key.']']=$value;
    request_editor('admin/save.php',$services);
    $rows = json_decode(setting_editor('content.services'),true);
    check_editor(array_column($rows,'slug')===['primera','tercera','nueva'] && count($rows[2]['services'])===2,'No se guardan altas, bajas o listas de áreas.');
    $team = ['csrf'=>$csrf,'section'=>'team','team_present'=>'1','team_intro_title'=>'Equipo','team_intro_text'=>'Presentación'];
    foreach ([0=>'primero',2=>'tercero',8=>'nuevo'] as $index=>$slug) foreach (['slug'=>$slug,'name'=>'Abogado '.$slug,'role'=>'Socio','area'=>'Civil','bio'=>'Biografía','image'=>''] as $key=>$value) $team['team['.$index.']['.$key.']']=$value;
    $team['team_image_8'] = new CURLFile($image,'image/png','abogado.png');
    request_editor('admin/save.php',$team);
    $rows = json_decode(setting_editor('content.team'),true);
    if (is_array($rows)) foreach ($rows as $row) if (!empty($row['image'])) $uploads[]=$row['image'];
    check_editor(count($rows)===3 && $rows[0]['image']==='' && str_starts_with($rows[2]['image'],'uploads/team/'),'La fotografía se asignó al perfil equivocado.');
    [, $public] = request_editor('equipo.php');
    check_editor(str_contains($public,'Abogado nuevo') && str_contains($public,$rows[2]['image']),'El equipo no se refleja en el sitio.');
    $empty = ['csrf'=>$csrf,'section'=>'team','team_present'=>'1','team_intro_title'=>'Equipo','team_intro_text'=>'Presentación'];
    request_editor('admin/save.php',$empty);
    check_editor(setting_editor('content.team')==='[]','No permite vaciar el equipo.');
    $revision = (int)$testDb->query("SELECT MAX(id) FROM site_revisions WHERE section='team'")->fetchColumn();
    request_editor('admin/restore.php',['csrf'=>$csrf,'id'=>$revision,'version'=>'before']);
    check_editor(count(json_decode(setting_editor('content.team'),true))===3,'No restaura los perfiles eliminados.');
    $restoredTeam = json_decode(setting_editor('content.team'),true);
    check_editor($restoredTeam[2]['image'] === $rows[2]['image'] && is_file($restoredTeam[2]['image']), 'La imagen histórica no se conserva para la restauración.');
    $revisionCount = (int)$testDb->query('SELECT COUNT(*) FROM site_revisions')->fetchColumn();
    check_editor($revisionCount>4,'Las restauraciones también deben registrarse.');
    request_editor('admin/restore.php',['csrf'=>$csrf,'id'=>$revision,'version'=>'after']);
    check_editor(setting_editor('content.team')==='[]','No restaura la versión elegida.');
    request_editor('admin/restore.php',['csrf'=>$csrf,'id'=>1,'version'=>'before']);
    check_editor(setting_editor('site.name')===false,'Restaurar una clave originalmente ausente debe recuperar el respaldo.');
    [$code, $receipt] = request_editor('contacto.php', ['nombre'=>'Cliente Formulario','email'=>'noreply@example.com','telefono'=>'9999','area'=>'Área primera','consulta'=>'Solicitud de prueba, sin enviar correo.','sitio_web'=>'']);
    check_editor($code === 200 && str_contains($receipt, 'Hemos recibido tu solicitud') && (int)$testDb->query('SELECT COUNT(*) FROM contactos')->fetchColumn() === 1, 'Una consulta guardada debe confirmar recepción aunque SMTP esté desactivado.');
    $testDb->prepare('INSERT INTO contactos (nombre,email,telefono,area,consulta,creado_en) VALUES (?,?,?,?,?,NOW())')->execute(['Cliente <script>','cliente@example.com','9999','Civil','Consulta <script>alert(1)</script>']);
    $contactId = (int)$testDb->lastInsertId();
    [$code,$inbox] = request_editor('admin/index.php?tab=inbox');
    check_editor($code===200 && str_contains($inbox,'Cliente &lt;script&gt;') && !str_contains($inbox,'Consulta <script>'),'La bandeja debe escapar los mensajes.');
    request_editor('admin/inbox-action.php',['csrf'=>$csrf,'id'=>$contactId,'estado'=>'leido']);
    check_editor($testDb->query('SELECT estado FROM contacto_estado')->fetchColumn()==='leido','No marca la consulta como leída.');
    [$code,$history] = request_editor('admin/index.php?tab=history');
    check_editor($code===200 && str_contains($history,'Restaurar esta versión') && str_contains($history,'Restauración'),'El historial no está disponible.');
    foreach (['general','home','firm','services','team','seo','inbox','history'] as $tab) {
        [$code,$html] = request_editor('admin/index.php?tab='.$tab);
        check_editor($code===200 && str_contains($html,'</html>') && !str_contains($html,'Fatal error'), 'Fallo en sección '.$tab);
    }
    request_editor('admin/logout.php');
    echo "OK: $checks comprobaciones HTTP + MySQL en base temporal.\n";
} finally {
    if (is_resource($process)) { proc_terminate($process); proc_close($process); }
    foreach ($uploads as $path) if (preg_match('~^uploads/team/[a-f0-9]{24}\.png$~D',$path) && is_file($path)) unlink($path);
    if ($created && preg_match('/^ams_editor_test_[a-f0-9]{12}$/D',$testName)) $server->exec('DROP DATABASE `' . $testName . '`');
    foreach ([$cookie,$log,$image] as $path) if (is_file($path)) unlink($path);
}
