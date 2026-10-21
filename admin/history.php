<?php
if (!function_exists('admin_require_login')) { http_response_code(404); exit; }
admin_prepare_editor();
$historyCount = (int) $pdo->query('SELECT COUNT(*) FROM site_revisions')->fetchColumn();
$historyPages = max(1, (int) ceil($historyCount / 20));
$historyPage = min($historyPages, max(1, (int) ($_GET['page'] ?? 1)));
$offset = ($historyPage - 1) * 20;
$revisions = $pdo->query("SELECT * FROM site_revisions ORDER BY id DESC LIMIT 20 OFFSET $offset")->fetchAll();
$fieldLabels = [];
foreach (admin_content_sections() as $definition) foreach ($definition['fields'] as $key => $field) $fieldLabels[$key] = $field[0];
$fieldLabels += ['site.logo' => 'Logo', 'site.office_image' => 'Fotografía de oficina', 'site.favicon' => 'Icono del navegador', 'content.team' => 'Perfiles de abogados', 'content.services' => 'Áreas legales', 'team.intro_title' => 'Título del equipo', 'team.intro_text' => 'Introducción del equipo', 'services.intro_title' => 'Título de áreas', 'services.intro_text' => 'Introducción de áreas'];
$sectionLabels = ['general' => 'Datos generales', 'home' => 'Inicio', 'firm' => 'La firma', 'services' => 'Áreas legales', 'team' => 'Abogados', 'seo' => 'SEO y Google'];
?>
<section class="panel"><div class="panel-heading"><span class="eyebrow">RECUPERAR CONTENIDO</span><h2>Historial de cambios</h2><p>Cada guardado conserva los campos modificados. Puedes recuperar los valores de esa versión o los que había antes. La restauración también queda registrada.</p></div>
<?php if (!$revisions): ?><p class="empty-state">El historial comenzará con el próximo cambio que guardes. El contenido actual se conservará como versión anterior.</p><?php endif; ?>
<?php foreach ($revisions as $revision):
    $before = json_decode($revision['before_data'], true) ?: [];
    $after = json_decode($revision['after_data'], true) ?: [];
?>
<details class="revision-card"><summary><span class="revision-id">#<?= (int) $revision['id'] ?></span><span class="inbox-sender"><strong><?= site_e($sectionLabels[$revision['section']] ?? $revision['section']) ?></strong><span><?= site_e($revision['action_label']) ?> · <?= site_e($revision['username']) ?></span></span><time><?= site_e($revision['created_at']) ?></time></summary>
<div class="inbox-content"><h3>Contenido de esta versión</h3><ul class="revision-fields"><?php foreach ($after as $key => $value): if (($before[$key] ?? null) === $value) continue; ?><li><?= site_e($fieldLabels[$key] ?? 'Contenido de la página') ?></li><?php endforeach; ?></ul><p class="field-description">Restaura únicamente el contenido incluido en este guardado. Las consultas de clientes se conservan.</p>
<div class="revision-actions"><?php foreach (['after' => 'Restaurar esta versión', 'before' => 'Restaurar versión anterior'] as $version => $label): ?><form method="post" action="restore.php" data-confirm="<?= site_e($label) ?> de <?= site_e($sectionLabels[$revision['section']] ?? '') ?>? Los valores actuales quedarán en el historial."><input type="hidden" name="csrf" value="<?= site_e(admin_csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $revision['id'] ?>"><input type="hidden" name="version" value="<?= $version ?>"><button type="submit" class="button-secondary"><?= $label ?></button></form><?php endforeach; ?></div></div></details>
<?php endforeach; ?>
<div class="pagination"><?php if ($historyPage > 1): ?><a href="?tab=history&amp;page=<?= $historyPage - 1 ?>">← Anterior</a><?php endif; ?><span>Página <?= $historyPage ?> de <?= $historyPages ?></span><?php if ($historyPage < $historyPages): ?><a href="?tab=history&amp;page=<?= $historyPage + 1 ?>">Siguiente →</a><?php endif; ?></div></section>
