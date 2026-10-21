<?php
require __DIR__ . '/bootstrap.php';
if (!admin_exists()) { http_response_code(403); exit('El administrador todavía no ha sido creado. Utiliza el enlace privado de configuración inicial.'); }
if (admin_is_logged_in()) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $username = trim(admin_auth_string($_POST, 'username'));
    $password = admin_auth_string($_POST, 'password');
    $ipAllowed = admin_auth_allow('login-ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 30);
    $userAllowed = admin_auth_allow('login-user:' . strtolower($username), 15);
    if ((int)($_SESSION['admin_login_locked_until'] ?? 0) > time() || !$ipAllowed || !$userAllowed) {
        $error = 'Demasiados intentos. Espera unos minutos antes de volver a intentar.';
    } else {
        $statement = $pdo->prepare('SELECT id, username, password_hash, auth_version FROM admin_users WHERE username = ? LIMIT 1');
        $statement->execute([$username]); $user = $statement->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION = ['admin_user_id' => (int)$user['id'], 'admin_username' => $user['username'], 'admin_auth_version' => (int)$user['auth_version']];
            $pdo->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);
            header('Location: index.php'); exit;
        }
        $attempts = (int)($_SESSION['admin_login_attempts'] ?? 0) + 1;
        $_SESSION['admin_login_attempts'] = $attempts;
        if ($attempts >= 5) { $_SESSION['admin_login_locked_until'] = time() + 300; $_SESSION['admin_login_attempts'] = 0; }
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Acceso administrativo</title><link rel="stylesheet" href="admin.css?v=<?= filemtime(__DIR__ . '/admin.css') ?>"><link rel="stylesheet" href="theme.css?v=<?= filemtime(__DIR__ . '/theme.css') ?>"></head>
<body class="auth-page"><main class="auth-card"><div class="admin-brand">AMS <span>Legal Solutions</span></div><div class="auth-eyebrow">ESPACIO PRIVADO DE LA FIRMA</div><h1>Panel administrativo</h1><p>Ingresa para actualizar el contenido del sitio.</p>
<?php if (isset($_GET['reset'])): ?><div class="notice success" role="status">Contraseña actualizada. Ingresa con tu nueva contraseña.</div><?php endif; ?><?php if (isset($_GET['created'])): ?><div class="notice success" role="status">Cuenta creada. Ya puedes ingresar.</div><?php endif; ?><?php if ($error): ?><div class="notice error" role="alert"><?= site_e($error) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= site_e(admin_csrf_token()) ?>"><label>Usuario<input name="username" autocomplete="username" maxlength="80" required></label><label>Contraseña<input type="password" name="password" autocomplete="current-password" required></label><a class="auth-recovery-link" href="forgot-password.php">¿Olvidaste tu contraseña?</a><button type="submit">Ingresar</button></form><a class="auth-back" href="../index.php">← Volver al sitio público</a></main></body></html>
