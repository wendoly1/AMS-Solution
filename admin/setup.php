<?php
require __DIR__ . '/bootstrap.php';

$setupToken = '7c7c33679c6c13194f35f60571693588';
if (!isset($_GET['token']) || !hash_equals($setupToken, (string) $_GET['token'])) {
    http_response_code(404);
    exit('Página no encontrada.');
}

if (admin_exists()) {
    header('Location: login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmation = $_POST['password_confirmation'] ?? '';

    if (strlen($username) < 4) {
        $error = 'El usuario debe tener al menos 4 caracteres.';
    } elseif (strlen($password) < 12) {
        $error = 'La contraseña debe tener al menos 12 caracteres.';
    } elseif ($password !== $confirmation) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $statement = $pdo->prepare('INSERT INTO admin_users (username, password_hash) VALUES (:username, :password)');
        $statement->execute([':username' => $username, ':password' => password_hash($password, PASSWORD_DEFAULT)]);
        header('Location: login.php?created=1');
        exit;
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Crear administrador</title><link rel="stylesheet" href="admin.css"></head>
<body class="auth-page"><main class="auth-card"><div class="admin-brand">AMS <span>Legal</span></div><h1>Crear administrador</h1><p>Este formulario solo estará disponible una vez.</p><?php if ($error): ?><div class="notice error"><?= site_e($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= site_e(admin_csrf_token()) ?>"><label>Usuario<input name="username" minlength="4" autocomplete="username" required></label><label>Contraseña<input type="password" name="password" minlength="12" autocomplete="new-password" required></label><label>Confirmar contraseña<input type="password" name="password_confirmation" minlength="12" autocomplete="new-password" required></label><button type="submit">Crear acceso</button></form></main></body></html>

