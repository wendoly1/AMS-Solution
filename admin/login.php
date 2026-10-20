<?php
require __DIR__ . '/bootstrap.php';

if (!admin_exists()) {
    http_response_code(403);
    exit('El administrador todavía no ha sido creado. Utiliza el enlace privado de configuración inicial.');
}

if (admin_is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $lockedUntil = (int) ($_SESSION['admin_login_locked_until'] ?? 0);
    if ($lockedUntil > time()) {
        $error = 'Demasiados intentos. Espera unos minutos antes de volver a intentar.';
    } else {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $statement = $pdo->prepare('SELECT id, username, password_hash FROM admin_users WHERE username = :username LIMIT 1');
    $statement->execute([':username' => $username]);
    $user = $statement->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_user_id'] = (int) $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        unset($_SESSION['admin_login_attempts'], $_SESSION['admin_login_locked_until']);
        $pdo->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = :id')->execute([':id' => $user['id']]);
        header('Location: index.php');
        exit;
    }

        $attempts = (int) ($_SESSION['admin_login_attempts'] ?? 0) + 1;
        $_SESSION['admin_login_attempts'] = $attempts;
        if ($attempts >= 5) {
            $_SESSION['admin_login_locked_until'] = time() + 300;
            $_SESSION['admin_login_attempts'] = 0;
        }
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Acceso administrativo</title><link rel="stylesheet" href="admin.css"></head>
<body class="auth-page"><main class="auth-card"><div class="admin-brand">AMS <span>Legal</span></div><h1>Panel administrativo</h1><p>Ingresa para actualizar el contenido del sitio.</p><?php if ($error): ?><div class="notice error"><?= site_e($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= site_e(admin_csrf_token()) ?>"><label>Usuario<input name="username" autocomplete="username" required></label><label>Contraseña<input type="password" name="password" autocomplete="current-password" required></label><button type="submit">Ingresar</button></form></main></body></html>

