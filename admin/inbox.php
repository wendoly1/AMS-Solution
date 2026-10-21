<?php
if (!function_exists('admin_require_login')) { http_response_code(404); exit; }
admin_prepare_editor();
$inboxPage = max(1, (int) ($_GET['page'] ?? 1));
$inboxCount = (int) $pdo->query('SELECT COUNT(*) FROM contactos')->fetchColumn();
$inboxPages = max(1, (int) ceil($inboxCount / 20));
$inboxPage = min($inboxPage, $inboxPages);
$offset = ($inboxPage - 1) * 20;
$messages = $pdo->query("SELECT c.*, COALESCE(e.estado, 'pendiente') AS estado FROM contactos c LEFT JOIN contacto_estado e ON e.contacto_id = c.id ORDER BY c.id DESC LIMIT 20 OFFSET $offset")->fetchAll();
?>
<section class="panel"><div class="panel-heading"><span class="eyebrow">SOLICITUDES DEL SITIO</span><h2>Bandeja de consultas <span class="count-badge"><?= $inboxCount ?></span></h2><p>Mensajes recibidos desde los formularios de Inicio y Contacto. Abre una consulta para leerla y cambia su estado cuando la revises.</p></div>
<?php if (!$messages): ?><p class="empty-state">Todavía no hay consultas. Las nuevas solicitudes aparecerán aquí al enviarse desde el sitio.</p><?php endif; ?>
<?php foreach ($messages as $message): ?>
<details class="inbox-card"><summary><span class="message-state <?= $message['estado'] === 'leido' ? 'is-read' : '' ?>"><?= $message['estado'] === 'leido' ? 'Leída' : 'Pendiente' ?></span><span class="inbox-sender"><strong><?= site_e($message['nombre']) ?></strong><span><?= site_e($message['area'] ?: 'Consulta general') ?></span></span><time><?= site_e($message['creado_en']) ?></time></summary>
<div class="inbox-content"><dl><div><dt>Correo</dt><dd><?= site_e($message['email']) ?></dd></div><div><dt>Teléfono</dt><dd><?= site_e($message['telefono'] ?: 'No indicado') ?></dd></div></dl><h3>Consulta recibida</h3><p class="message-text"><?= nl2br(site_e($message['consulta'])) ?></p>
<form method="post" action="inbox-action.php"><input type="hidden" name="csrf" value="<?= site_e(admin_csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $message['id'] ?>"><input type="hidden" name="page" value="<?= $inboxPage ?>"><input type="hidden" name="estado" value="<?= $message['estado'] === 'leido' ? 'pendiente' : 'leido' ?>"><button type="submit" class="button-secondary"><?= $message['estado'] === 'leido' ? 'Marcar como pendiente' : 'Marcar como leída' ?></button></form></div></details>
<?php endforeach; ?>
<div class="pagination"><?php if ($inboxPage > 1): ?><a href="?tab=inbox&amp;page=<?= $inboxPage - 1 ?>">← Anterior</a><?php endif; ?><span>Página <?= $inboxPage ?> de <?= $inboxPages ?></span><?php if ($inboxPage < $inboxPages): ?><a href="?tab=inbox&amp;page=<?= $inboxPage + 1 ?>">Siguiente →</a><?php endif; ?></div></section>
