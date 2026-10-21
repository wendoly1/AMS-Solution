<?php
require __DIR__ . '/bootstrap.php';
admin_require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
admin_verify_csrf();
try {
    admin_prepare_editor();
    $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $version = $_POST['version'] ?? '';
    if (!$id || !in_array($version, ['before', 'after'], true)) throw new RuntimeException('Versión inválida.');
    $pdo->beginTransaction();
    $statement = $pdo->prepare('SELECT * FROM site_revisions WHERE id = ?');
    $statement->execute([$id]);
    $revision = $statement->fetch();
    if (!$revision || !in_array($revision['section'], admin_editable_sections(), true)) throw new RuntimeException('La versión no está disponible.');
    $changes = json_decode($revision[$version . '_data'], true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($changes)) throw new RuntimeException('El contenido de la versión no es válido.');
    admin_publish_changes($revision['section'], $changes, 'Restauración de #' . $id . ($version === 'before' ? ' (versión anterior)' : ''));
    $pdo->commit();
    header('Location: index.php?tab=history&restored=1');
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Restauración: ' . $error->getMessage());
    $_SESSION['admin_error'] = 'No se pudo restaurar esa versión. Tu contenido actual se conserva.';
    header('Location: index.php?tab=history');
}
exit;
