<?php
require __DIR__ . '/bootstrap.php';
header('Referrer-Policy: no-referrer');
$setupToken = admin_setup_token();
if ($setupToken === '' || !is_string($_GET['token'] ?? null) || !hash_equals($setupToken, $_GET['token'])) { http_response_code(404); exit('Página no encontrada.'); }
if (admin_exists()) { header('Location: login.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    try {
        $username = trim(admin_auth_string($_POST, 'username'));
        $password = admin_auth_string($_POST, 'password');
        if (mb_strlen($username) < 4 || mb_strlen($username) > 80) throw new RuntimeException('El usuario debe tener entre 4 y 80 caracteres.');
        $email = admin_normalize_email(admin_auth_string($_POST, 'email'));
        if ($email !== strtolower(trim(admin_auth_string($_POST, 'email_confirmation')))) throw new RuntimeException('Los correos no coinciden.');
        admin_validate_password($password, admin_auth_string($_POST, 'password_confirmation'));
        $statement = $pdo->prepare('INSERT INTO admin_users (username, email, password_hash) VALUES (?, ?, ?)');
        $statement->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
        header('Location: login.php?created=1'); exit;
    } catch (Throwable $failure) { $error = $failure instanceof RuntimeException && !$failure instanceof PDOException ? $failure->getMessage() : 'No se pudo crear la cuenta. Intenta nuevamente.'; }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Crear administrador</title><link rel="stylesheet" href="admin.css?v=<?= filemtime(__DIR__ . '/admin.css') ?>"><link rel="stylesheet" href="theme.css?v=<?= filemtime(__DIR__ . '/theme.css') ?>"></head>
<body class="auth-page"><main class="auth-card"><div class="admin-brand">AMS <span>Legal Solutions</span></div><div class="auth-eyebrow">CONFIGURACIÓN INICIAL</div><h1>Crear administrador</h1><p>Registra el acceso y un correo para recuperar tu contraseña. Este formulario solo estará disponible una vez.</p><?php if ($error): ?><div class="notice error" role="alert"><?= site_e($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= site_e(admin_csrf_token()) ?>"><label>Usuario<input name="username" minlength="4" maxlength="80" autocomplete="username" required></label><label>Correo de recuperación<input type="email" name="email" maxlength="190" autocomplete="email" required></label><label>Repetir correo de recuperación<input type="email" name="email_confirmation" maxlength="190" required></label><label>Contraseña<input type="password" name="password" minlength="12" maxlength="72" autocomplete="new-password" required></label><label>Confirmar contraseña<input type="password" name="password_confirmation" minlength="12" maxlength="72" autocomplete="new-password" required></label><button type="submit">Crear acceso</button></form><a class="auth-back" href="../index.php">← Volver al sitio público</a></main></body></html>
