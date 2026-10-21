<?php
require __DIR__ . '/bootstrap.php';
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: frame-ancestors 'none'");
$submitted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $started = microtime(true);
    try { admin_request_password_reset(admin_auth_string($_POST, 'email')); }
    catch (Throwable $error) { error_log('No se pudo procesar la solicitud de recuperación administrativa.'); }
    // Evita una salida inmediata diferente para correos desconocidos o limitados.
    $wait = 0.6 - (microtime(true) - $started);
    if ($wait > 0) usleep((int)($wait * 1000000));
    $submitted = true;
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Recuperar contraseña | AMS Legal Solutions</title><link rel="stylesheet" href="admin.css?v=<?= filemtime(__DIR__ . '/admin.css') ?>"><link rel="stylesheet" href="theme.css?v=<?= filemtime(__DIR__ . '/theme.css') ?>"></head>
<body class="auth-page"><main class="auth-card"><div class="admin-brand">AMS <span>Legal Solutions</span></div><div class="auth-eyebrow">RECUPERAR ACCESO</div><h1>¿Olvidaste tu contraseña?</h1><p>Escribe el correo de recuperación registrado en tu cuenta del panel.</p>
<?php if ($submitted): ?><div class="notice success" role="status">Si el correo está registrado y puede recibir solicitudes, te enviaremos un enlace para establecer una nueva contraseña. Revisa también la carpeta de spam.</div><p class="auth-help">El enlace dura 20 minutos. Si no llega, espera unos minutos o contacta a quien administra el sitio.</p><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= site_e(admin_csrf_token()) ?>"><label>Correo de recuperación<input type="email" name="email" autocomplete="email" maxlength="190" required></label><button type="submit">Enviar enlace de recuperación</button></form><a class="auth-back" href="login.php">← Volver al acceso</a></main></body></html>
