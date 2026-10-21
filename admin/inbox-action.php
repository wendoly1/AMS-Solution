<?php
require __DIR__ . '/bootstrap.php';
admin_require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
admin_verify_csrf();
try {
    admin_prepare_editor();
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $state = $_POST['estado'] ?? '';
    if (!$id || !in_array($state, ['leido', 'pendiente'], true)) throw new RuntimeException('Consulta inválida.');
    $statement = $pdo->prepare('SELECT id FROM contactos WHERE id = ?');
    $statement->execute([$id]);
    if (!$statement->fetchColumn()) throw new RuntimeException('La consulta ya no está disponible.');
    $pdo->prepare('INSERT INTO contacto_estado (contacto_id, estado) VALUES (?, ?) ON DUPLICATE KEY UPDATE estado = VALUES(estado)')->execute([$id, $state]);
} catch (Throwable $error) {
    error_log('Bandeja: ' . $error->getMessage());
    $_SESSION['admin_error'] = 'No se pudo actualizar el estado de la consulta.';
}
header('Location: index.php?tab=inbox&page=' . max(1, (int) ($_POST['page'] ?? 1)));
exit;
