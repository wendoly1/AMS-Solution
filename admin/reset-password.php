<?php
require __DIR__ . '/bootstrap.php';
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: frame-ancestors 'none'; form-action 'self'");
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])) {
    $token = admin_auth_string($_GET, 'token');
    unset($_SESSION['reset_password_token']);
    if (admin_reset_record($token)) $_SESSION['reset_password_token'] = $token;
    header('Location: reset-password.php', true, 303);
    exit;
}
$token = $_SESSION['reset_password_token'] ?? '';
$valid = is_string($token) && admin_reset_record($token) !== null;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    try {
        if (!$valid) throw new RuntimeException('El enlace ya no es válido. Solicita uno nuevo.');
        if (!admin_auth_allow('reset-ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 15)) throw new RuntimeException('Demasiados intentos. Espera 15 minutos antes de volver a intentar.');
        admin_reset_password($token, admin_auth_string($_POST, 'password'), admin_auth_string($_POST, 'password_confirmation'));
        $_SESSION = [];
        session_regenerate_id(true);
        header('Location: login.php?reset=1', true, 303);
        exit;
    } catch (Throwable $failure) {
        $error = $failure instanceof RuntimeException && !$failure instanceof PDOException ? $failure->getMessage() : 'No se pudo actualizar la contraseña. Intenta nuevamente.';
        $valid = admin_reset_record($token) !== null;
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Establecer contraseña | AMS Legal Solutions</title><link rel="stylesheet" href="admin.css?v=<?= filemtime(__DIR__ . '/admin.css') ?>"><link rel="stylesheet" href="theme.css?v=<?= filemtime(__DIR__ . '/theme.css') ?>"></head>
<body class="auth-page"><main class="auth-card"><div class="admin-brand">AMS <span>Legal Solutions</span></div><div class="auth-eyebrow">NUEVA CONTRASEÑA</div><h1>Establecer contraseña</h1>
<?php if (!$valid): ?><div class="notice error">El enlace venció, ya fue utilizado o no es válido. Solicita uno nuevo para continuar.</div><a class="button" href="forgot-password.php">Solicitar otro enlace</a>
<?php else: ?><p>Escribe tu nueva contraseña dos veces. Usa al menos 12 caracteres y una clave que no utilices en otros sitios.</p><?php if ($error): ?><div class="notice error" role="alert"><?= site_e($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= site_e(admin_csrf_token()) ?>"><label>Nueva contraseña<input type="password" name="password" autocomplete="new-password" minlength="12" maxlength="72" required></label><label>Confirmar nueva contraseña<input type="password" name="password_confirmation" autocomplete="new-password" minlength="12" maxlength="72" required></label><p class="auth-help">Al guardar, se cerrarán las sesiones anteriores. Después ingresa con tu nueva contraseña.</p><button type="submit">Establecer nueva contraseña</button></form><?php endif; ?><a class="auth-back" href="login.php">← Volver al acceso</a></main></body></html>
