<?php
require __DIR__ . '/bootstrap.php';
admin_require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
admin_verify_csrf();
$section = $_POST['section'] ?? '';
if (!is_string($section) || !in_array($section, admin_editable_sections(), true)) { http_response_code(400); exit('Sección inválida.'); }
try {
    admin_prepare_editor();
    $GLOBALS['admin_new_uploads'] = [];
    $pdo->beginTransaction();
    $changes = admin_build_changes($section, $_POST);
    admin_publish_changes($section, $changes);
    $pdo->commit();
    header('Location: index.php?tab=' . urlencode($section) . '&saved=1');
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    foreach (($GLOBALS['admin_new_uploads'] ?? []) as $path) { if (is_file($path)) unlink($path); }
    error_log('Error al guardar contenido: ' . $error->getMessage());
    $_SESSION['admin_error'] = $error instanceof RuntimeException && !$error instanceof PDOException ? $error->getMessage() : 'No se pudieron guardar los cambios. Intenta nuevamente.';
    header('Location: index.php?tab=' . urlencode($section));
}
exit;
