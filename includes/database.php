<?php
// Un solo punto de conexión para el sitio público y public_html/admin.
$hostActual = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
$esLocal = in_array($hostActual, ['localhost', '127.0.0.1', '::1', '[::1]'], true);
$environment = getenv('AMS_ENV') ?: ($esLocal ? 'local' : 'production');
$pdo = null;

try {
    $configFile = __DIR__ . '/../config/database.local.php';
    $config = is_file($configFile) ? require $configFile : [];
    $database = $config[$environment] ?? [];
    $defaults = ['host' => 'localhost', 'port' => '3306', 'name' => 'ams_legal', 'user' => 'root', 'pass' => ''];
    foreach ($defaults as $key => $default) {
        $value = getenv('AMS_DB_' . strtoupper($key));
        $database[$key] = $value !== false ? $value : ($database[$key] ?? ($environment === 'local' ? $default : ''));
    }
    if ($database['host'] === '' || $database['name'] === '' || $database['user'] === '') {
        throw new RuntimeException('Falta configurar la conexión compartida de MySQL.');
    }

    $pdo = new PDO(
        "mysql:host={$database['host']};port=" . ($database['port'] ?: '3306') . ";dbname={$database['name']};charset=utf8mb4",
        $database['user'],
        $database['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (Throwable $error) {
    error_log('Error de conexión a la base de datos: ' . $error->getMessage());
}
