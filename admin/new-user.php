<?php
require __DIR__ . '/bootstrap.php';
admin_require_login();
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: frame-ancestors 'none'; form-action 'self'");
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    try {
        $_SESSION['registered_username'] = admin_register_user((int)$_SESSION['admin_user_id'], $_POST);
        header('Location: new-user.php?created=1', true, 303);
        exit;
    } catch (Throwable $failure) {
        $error = $failure instanceof PDOException && ($failure->errorInfo[1] ?? 0) === 1062 ? 'El nombre de usuario o el correo ya está registrado. Usa uno diferente.' : ($failure instanceof RuntimeException && !$failure instanceof PDOException ? $failure->getMessage() : 'No se pudo registrar el usuario. Intenta nuevamente.');
    }
}
$created = $_SESSION['registered_username'] ?? '';
unset($_SESSION['registered_username']);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Registrar usuario | AMS Legal Solutions</title><link rel="stylesheet" href="admin.css?v=<?= filemtime(__DIR__ . '/admin.css') ?>"><link rel="stylesheet" href="theme.css?v=<?= filemtime(__DIR__ . '/theme.css') ?>"></head>
<body class="auth-page"><main class="auth-card account-card"><div class="admin-brand">AMS <span>Legal Solutions</span></div><div class="auth-eyebrow">ADMINISTRACIÓN DE ACCESOS</div><h1>Registrar nuevo usuario</h1><p>La nueva cuenta podrá administrar todo el panel, consultar mensajes y registrar otros usuarios. Crea cuentas para las personas responsables del sitio.</p>
<?php if ($created): ?><div class="notice success" role="status">Cuenta <strong><?= site_e($created) ?></strong> creada. Ya puede ingresar con su usuario y contraseña. Tu sesión actual se conserva.</div><?php endif; ?>
<?php if ($error): ?><div class="notice error" role="alert"><?= site_e($error) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= site_e(admin_csrf_token()) ?>">
<fieldset class="registration-group"><legend>1. Datos de la nueva cuenta</legend>
<label>Nombre de usuario<input name="username" minlength="4" maxlength="80" autocomplete="off" value="<?= site_e(admin_auth_string($_POST, 'username')) ?>" required><span class="help">De 4 a 80 caracteres: letras, números, puntos o guiones.</span></label>
<label>Correo de recuperación del nuevo usuario<input type="email" name="email" maxlength="190" autocomplete="off" value="<?= site_e(admin_auth_string($_POST, 'email')) ?>" required></label>
<label>Repetir correo de recuperación<input type="email" name="email_confirmation" maxlength="190" autocomplete="off" required></label>
<label>Contraseña para el nuevo usuario<input type="password" name="password" minlength="12" maxlength="72" autocomplete="new-password" required><span class="help">Al menos 12 caracteres. La persona podrá cambiarla después en Mi cuenta.</span></label>
<label>Confirmar contraseña del nuevo usuario<input type="password" name="password_confirmation" minlength="12" maxlength="72" autocomplete="new-password" required></label></fieldset>
<fieldset class="registration-group"><legend>2. Autorizar el registro</legend><label>Tu contraseña actual de administrador<input type="password" name="current_password" autocomplete="current-password" required><span class="help">Es la contraseña con la que ingresaste al panel.</span></label></fieldset>
<button type="submit">Registrar usuario</button><p class="auth-help">El registro queda activo al guardar y no depende del envío de correo. Comparte las credenciales por un medio privado.</p></form><a class="auth-back" href="index.php">← Volver al panel</a><a class="auth-back" href="account.php">Mi cuenta y contraseña</a></main></body></html>
