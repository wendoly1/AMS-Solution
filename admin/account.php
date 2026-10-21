<?php
require __DIR__ . '/bootstrap.php';
admin_require_login();
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: frame-ancestors 'none'; form-action 'self'");
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    try {
        $action = admin_auth_string($_POST, 'action');
        admin_update_account((int)$_SESSION['admin_user_id'], $action, $_POST);
        header('Location: account.php?saved=' . ($action === 'email' ? 'email' : 'password'), true, 303);
        exit;
    } catch (Throwable $failure) {
        $error = $failure instanceof PDOException && ($failure->errorInfo[1] ?? 0) === 1062 ? 'Ese correo no está disponible para esta cuenta.' : ($failure instanceof RuntimeException && !$failure instanceof PDOException ? $failure->getMessage() : 'No se pudo actualizar tu cuenta. Intenta nuevamente.');
    }
}
$query = $pdo->prepare('SELECT username, email FROM admin_users WHERE id = ?'); $query->execute([$_SESSION['admin_user_id']]); $account = $query->fetch();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Mi cuenta | AMS Legal Solutions</title><link rel="stylesheet" href="admin.css?v=<?= filemtime(__DIR__ . '/admin.css') ?>"><link rel="stylesheet" href="theme.css?v=<?= filemtime(__DIR__ . '/theme.css') ?>"></head>
<body class="auth-page"><main class="auth-card account-card"><div class="admin-brand">AMS <span>Legal Solutions</span></div><div class="auth-eyebrow">ACCESO Y RECUPERACIÓN</div><h1>Mi cuenta</h1><p>Usuario: <strong><?= site_e($account['username']) ?></strong>. Administra tu correo de recuperación y tu contraseña.</p><a href="new-user.php" class="button button-secondary">+ Registrar nuevo usuario</a>
<?php if (isset($_GET['saved'])): ?><div class="notice success" role="status"><?= $_GET['saved'] === 'email' ? 'Correo de recuperación actualizado.' : 'Contraseña actualizada.' ?> Las otras sesiones se cerraron.</div><?php endif; ?><?php if ($error): ?><div class="notice error" role="alert"><?= site_e($error) ?></div><?php endif; ?>
<section class="account-section"><h2>1. Correo de recuperación</h2><p>Usa un correo al que tengas acceso. Allí recibirás los enlaces si olvidas tu contraseña. Es independiente del correo de contacto de la página.</p><form method="post"><input type="hidden" name="csrf" value="<?= site_e(admin_csrf_token()) ?>"><input type="hidden" name="action" value="email"><label>Correo de recuperación<input type="email" name="email" value="<?= site_e($account['email'] ?? '') ?>" autocomplete="email" maxlength="190" required></label><label>Repetir correo de recuperación<input type="email" name="email_confirmation" autocomplete="email" maxlength="190" required></label><label>Tu contraseña actual<input type="password" name="current_password" autocomplete="current-password" required></label><button type="submit">Guardar correo de recuperación</button></form></section>
<section class="account-section"><h2>2. Cambiar contraseña</h2><p>Elige una nueva contraseña de al menos 12 caracteres. Al guardar, se cerrarán las otras sesiones.</p><form method="post"><input type="hidden" name="csrf" value="<?= site_e(admin_csrf_token()) ?>"><input type="hidden" name="action" value="password"><label>Contraseña actual<input type="password" name="current_password" autocomplete="current-password" required></label><label>Nueva contraseña<input type="password" name="password" autocomplete="new-password" minlength="12" maxlength="72" required></label><label>Confirmar nueva contraseña<input type="password" name="password_confirmation" autocomplete="new-password" minlength="12" maxlength="72" required></label><button type="submit">Actualizar contraseña</button></form></section><a class="auth-back" href="index.php">← Volver al panel</a></main></body></html>
