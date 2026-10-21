<?php

function admin_prepare_auth(): void
{
    global $pdo;
    $columns = $pdo->query('SHOW COLUMNS FROM admin_users')->fetchAll(PDO::FETCH_COLUMN);
    foreach (['email' => 'VARCHAR(190) NULL', 'auth_version' => 'INT UNSIGNED NOT NULL DEFAULT 1'] as $name => $definition) {
        if (!in_array($name, $columns, true)) {
            try { $pdo->exec("ALTER TABLE admin_users ADD COLUMN $name $definition"); }
            catch (PDOException $error) { if (($error->errorInfo[1] ?? 0) !== 1060) throw $error; }
        }
    }
    $index = $pdo->query("SHOW INDEX FROM admin_users WHERE Key_name = 'admin_email_unique'")->fetch();
    if (!$index) {
        try { $pdo->exec('ALTER TABLE admin_users ADD UNIQUE INDEX admin_email_unique (email)'); }
        catch (PDOException $error) { if (($error->errorInfo[1] ?? 0) !== 1061) throw $error; }
    }
    foreach (explode(';', file_get_contents(__DIR__ . '/../database-auth.sql')) as $sql) {
        if (trim($sql) !== '') $pdo->exec($sql);
    }
}

function admin_auth_string(array $data, string $key): string
{
    return is_string($data[$key] ?? null) ? $data[$key] : '';
}

function admin_normalize_email(string $email): string
{
    $email = strtolower(trim($email));
    if (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Ingresa un correo válido al que tengas acceso.');
    return $email;
}

function admin_validate_password(string $password, string $confirmation): void
{
    if (mb_strlen($password, 'UTF-8') < 12) throw new RuntimeException('La contraseña debe tener al menos 12 caracteres.');
    if (strlen($password) > 72 || str_contains($password, "\0")) throw new RuntimeException('La contraseña es demasiado larga o contiene caracteres no admitidos.');
    if ($password !== $confirmation) throw new RuntimeException('Las contraseñas no coinciden.');
}

function admin_setup_token(): string
{
    $token = getenv('AMS_SETUP_TOKEN');
    if (is_string($token) && $token !== '') return $token;
    global $environment;
    $path = __DIR__ . '/../config/security.local.php';
    $config = is_file($path) ? require $path : [];
    $token = $config[$environment === 'local' ? 'local' : 'production']['setup_token'] ?? '';
    return is_string($token) && preg_match('/^[a-f0-9]{64}$/D', $token) ? $token : '';
}

function admin_auth_config(): array
{
    global $environment;
    $path = __DIR__ . '/../config/security.local.php';
    $config = is_file($path) ? require $path : [];
    $local = $environment === 'local';
    $settings = $config[$local ? 'local' : 'production'] ?? [];
    $url = getenv('AMS_ADMIN_URL') ?: ($settings['admin_url'] ?? ($local ? 'http://127.0.0.1:8000/admin' : 'https://amslegalsolutions.com/admin'));
    $from = getenv('AMS_MAIL_FROM') ?: ($settings['mail_from'] ?? 'no-reply@amslegalsolutions.com');
    $parts = parse_url($url);
    $loopback = $local && in_array(strtolower($parts['host'] ?? ''), ['127.0.0.1', 'localhost', '[::1]'], true);
    if (!filter_var($url, FILTER_VALIDATE_URL) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment']) || (($parts['scheme'] ?? '') !== 'https' && !(($parts['scheme'] ?? '') === 'http' && $loopback))) {
        throw new RuntimeException('Revisa la dirección del panel en la configuración privada.');
    }
    if (!filter_var($from, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $from)) throw new RuntimeException('Revisa el remitente de recuperación en la configuración privada.');
    return ['admin_url' => rtrim($url, '/'), 'mail_from' => $from];
}

function admin_auth_mail(string $to, string $subject, string $body): bool
{
    try {
        $config = admin_auth_config();
        $headers = 'From: AMS Legal Solutions <' . $config['mail_from'] . ">\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8";
        $sent = @mail($to, mb_encode_mimeheader($subject, 'UTF-8', 'B', "\r\n"), $body, $headers);
        if (!$sent) error_log('No se pudo entregar un correo de acceso administrativo. Revisar mail() del servidor.');
        return $sent;
    } catch (Throwable $error) {
        // No registrar destinatarios, enlaces ni tokens de recuperación.
        error_log('No se pudo enviar el correo de acceso administrativo.');
        return false;
    }
}

function admin_auth_allow(string $bucket, int $limit, int $window = 900): bool
{
    global $pdo;
    $hash = hash('sha256', $bucket);
    $now = time();
    $statement = $pdo->prepare('INSERT INTO admin_auth_limits (bucket_hash, window_started, attempts) VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE attempts = IF(window_started + ? <= ?, 1, attempts + 1), window_started = IF(window_started + ? <= ?, ?, window_started)');
    $statement->execute([$hash, $now, $window, $now, $window, $now, $now]);
    $statement = $pdo->prepare('SELECT attempts FROM admin_auth_limits WHERE bucket_hash = ?');
    $statement->execute([$hash]);
    return (int) $statement->fetchColumn() <= $limit;
}

function admin_request_password_reset(string $email, ?callable $sender = null): void
{
    global $pdo;
    $email = strtolower(trim($email));
    $ipAllowed = admin_auth_allow('recover-ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 10);
    $emailAllowed = admin_auth_allow('recover-email:' . $email, 3);
    $pdo->prepare('DELETE FROM admin_password_resets WHERE expires_at < ?')->execute([time() - 86400]);
    $pdo->prepare('DELETE FROM admin_auth_limits WHERE window_started < ?')->execute([time() - 86400]);
    if (!$ipAllowed || !$emailAllowed || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) return;
    $config = admin_auth_config();
    $pdo->beginTransaction();
    try {
        $query = $pdo->prepare('SELECT id, email, auth_version FROM admin_users WHERE email = ? FOR UPDATE');
        $query->execute([$email]);
        $user = $query->fetch();
        if (!$user) { $pdo->commit(); return; }
        $token = bin2hex(random_bytes(32));
        $pdo->prepare('DELETE FROM admin_password_resets WHERE user_id = ?')->execute([$user['id']]);
        $pdo->prepare('INSERT INTO admin_password_resets (token_hash, user_id, auth_version, expires_at, created_at) VALUES (?, ?, ?, ?, ?)')
            ->execute([hash('sha256', $token), $user['id'], $user['auth_version'], time() + 1200, time()]);
        $pdo->commit();
    } catch (Throwable $error) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $error; }
    $link = $config['admin_url'] . '/reset-password.php?token=' . $token;
    $deliver = $sender ?? 'admin_auth_mail';
    $sent = $deliver($user['email'], 'Recuperar acceso a AMS Legal Solutions',
        "Recibimos una solicitud para establecer una nueva contraseña del panel de AMS Legal Solutions.\n\nAbre este enlace:\n" . $link . "\n\nEs válido durante 20 minutos y solo se puede usar una vez. Si no solicitaste el cambio, ignora este correo. Tu contraseña sigue igual.\n");
    if (!$sent) $pdo->prepare('DELETE FROM admin_password_resets WHERE token_hash = ?')->execute([hash('sha256', $token)]);
}

function admin_reset_record(string $token): ?array
{
    global $pdo;
    if (!preg_match('/^[a-f0-9]{64}$/D', $token)) return null;
    $query = $pdo->prepare('SELECT r.user_id, r.auth_version, r.expires_at, r.used_at FROM admin_password_resets r JOIN admin_users u ON u.id = r.user_id AND u.auth_version = r.auth_version WHERE r.token_hash = ?');
    $query->execute([hash('sha256', $token)]);
    $row = $query->fetch();
    return $row && $row['used_at'] === null && (int) $row['expires_at'] > time() ? $row : null;
}

function admin_reset_password(string $token, string $password, string $confirmation): void
{
    global $pdo;
    admin_validate_password($password, $confirmation);
    $record = admin_reset_record($token);
    if (!$record) throw new RuntimeException('El enlace ya no es válido. Solicita uno nuevo.');
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $pdo->beginTransaction();
    try {
        $query = $pdo->prepare('SELECT * FROM admin_users WHERE id = ? FOR UPDATE');
        $query->execute([$record['user_id']]); $user = $query->fetch();
        $query = $pdo->prepare('SELECT * FROM admin_password_resets WHERE token_hash = ? FOR UPDATE');
        $query->execute([hash('sha256', $token)]); $locked = $query->fetch();
        if (!$user || !$locked || $locked['used_at'] !== null || (int) $locked['expires_at'] <= time() || (int) $locked['auth_version'] !== (int) $user['auth_version']) throw new RuntimeException('El enlace ya no es válido. Solicita uno nuevo.');
        $pdo->prepare('UPDATE admin_users SET password_hash = ?, auth_version = auth_version + 1 WHERE id = ?')->execute([$hash, $user['id']]);
        $pdo->prepare('UPDATE admin_password_resets SET used_at = ? WHERE user_id = ? AND used_at IS NULL')->execute([time(), $user['id']]);
        $pdo->commit();
    } catch (Throwable $error) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $error; }
    if ($user['email']) admin_auth_mail($user['email'], 'Se actualizó tu contraseña de AMS Legal Solutions', "La contraseña de tu cuenta del panel fue actualizada. Todas las sesiones anteriores han quedado cerradas.\n\nSi no reconoces este cambio, solicita recuperar el acceso desde la página de ingreso.\n");
}

function admin_register_user(int $creatorId, array $post): string
{
    global $pdo;
    if (!admin_auth_allow('register-user:' . $creatorId, 10)) throw new RuntimeException('Demasiados intentos. Espera 15 minutos antes de volver a intentar.');
    $pdo->beginTransaction();
    try {
        $query = $pdo->prepare('SELECT password_hash, auth_version FROM admin_users WHERE id = ? FOR UPDATE');
        $query->execute([$creatorId]); $creator = $query->fetch();
        if (!$creator || (int)$creator['auth_version'] !== (int)($_SESSION['admin_auth_version'] ?? 0) || !password_verify(admin_auth_string($post, 'current_password'), $creator['password_hash'])) throw new RuntimeException('La contraseña del administrador actual no es correcta.');
        $username = trim(admin_auth_string($post, 'username'));
        if (!preg_match('/^[\p{L}\p{N}_.-]{4,80}$/uD', $username)) throw new RuntimeException('El usuario debe tener entre 4 y 80 caracteres: letras, números, puntos o guiones.');
        $email = admin_normalize_email(admin_auth_string($post, 'email'));
        if ($email !== strtolower(trim(admin_auth_string($post, 'email_confirmation')))) throw new RuntimeException('Los correos del nuevo usuario no coinciden.');
        $password = admin_auth_string($post, 'password');
        admin_validate_password($password, admin_auth_string($post, 'password_confirmation'));
        $query = $pdo->prepare('INSERT INTO admin_users (username, email, password_hash) VALUES (?, ?, ?)');
        $query->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
        $pdo->commit();
        return $username;
    } catch (Throwable $error) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $error; }
}

function admin_update_account(int $id, string $action, array $post): void
{
    global $pdo;
    if (!admin_auth_allow('account:' . $id, 10)) throw new RuntimeException('Demasiados intentos. Espera 15 minutos antes de volver a intentar.');
    $pdo->beginTransaction();
    try {
        $query = $pdo->prepare('SELECT * FROM admin_users WHERE id = ? FOR UPDATE'); $query->execute([$id]); $user = $query->fetch();
        if (!$user || (int)$user['auth_version'] !== (int)($_SESSION['admin_auth_version'] ?? 0) || !password_verify(admin_auth_string($post, 'current_password'), $user['password_hash'])) throw new RuntimeException('La contraseña actual no es correcta.');
        if ($action === 'email') {
            $email = admin_normalize_email(admin_auth_string($post, 'email'));
            if ($email !== strtolower(trim(admin_auth_string($post, 'email_confirmation')))) throw new RuntimeException('Los correos no coinciden.');
            $pdo->prepare('UPDATE admin_users SET email = ?, auth_version = auth_version + 1 WHERE id = ?')->execute([$email, $id]);
        } elseif ($action === 'password') {
            $password = admin_auth_string($post, 'password');
            admin_validate_password($password, admin_auth_string($post, 'password_confirmation'));
            if (password_verify($password, $user['password_hash'])) throw new RuntimeException('Elige una contraseña diferente a la actual.');
            $pdo->prepare('UPDATE admin_users SET password_hash = ?, auth_version = auth_version + 1 WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
        } else throw new RuntimeException('Acción inválida.');
        $pdo->prepare('UPDATE admin_password_resets SET used_at = ? WHERE user_id = ? AND used_at IS NULL')->execute([time(), $id]);
        $pdo->commit();
    } catch (Throwable $error) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $error; }
    session_regenerate_id(true);
    $_SESSION['admin_auth_version'] = (int)$user['auth_version'] + 1;
    unset($_SESSION['reset_password_token'], $_SESSION['editor_preview']);
    if ($user['email']) admin_auth_mail($user['email'], 'Cambio de seguridad en tu cuenta AMS', "Se actualizó " . ($action === 'email' ? 'el correo de recuperación' : 'la contraseña') . " de tu cuenta del panel. Las otras sesiones se cerraron. Si no reconoces el cambio, contacta al responsable del sitio.\n");
}
