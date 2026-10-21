<?php
require __DIR__ . '/bootstrap.php';
admin_require_login();

$allowedTabs = ['general', 'home', 'firm', 'services', 'team', 'seo', 'inbox', 'history'];
$tab = $_GET['tab'] ?? 'general';
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'general';
}

$sections = admin_content_sections();
$recoveryQuery = $pdo->prepare('SELECT email FROM admin_users WHERE id = ?');
$recoveryQuery->execute([$_SESSION['admin_user_id']]);
$hasRecoveryEmail = (bool) $recoveryQuery->fetchColumn();
$tabTitles = ['general' => 'Datos generales', 'home' => 'Página de inicio', 'firm' => 'Sobre la firma', 'services' => 'Áreas legales', 'team' => 'Abogados', 'seo' => 'SEO y Google', 'inbox' => 'Consultas recibidas', 'history' => 'Historial de cambios'];
$services = site_services();
$team = site_team();
$saved = isset($_GET['saved']);
$error = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_error']);
$tabDescriptions = [
    'general' => 'Identidad, contacto e imágenes que se comparten en el sitio.',
    'home' => 'Edita la página de inicio, siguiendo el orden de sus bloques.',
    'firm' => 'Organiza la historia, los valores y la presentación de tu firma.',
    'services' => 'Edita la presentación y el contenido de cada especialidad legal.',
    'team' => 'Edita la presentación del equipo y los perfiles de sus abogados.',
    'seo' => 'Configura el título y la descripción de cada página para buscadores.',
    'inbox' => 'Lee las solicitudes que tus clientes envían desde la página.',
    'history' => 'Consulta los guardados anteriores y recupera tu contenido.',
];
$previewPage = ['general' => 'index.php', 'home' => 'index.php', 'firm' => 'sobre_la_firma.php', 'services' => 'servicios.php', 'team' => 'equipo.php', 'seo' => 'index.php'][$tab] ?? 'index.php';

function admin_nav_link(string $key, string $label, string $active): string
{
    $icons = [
        'general' => '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
        'home' => '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1Z"/>',
        'firm' => '<path d="M4 21h16M6 21V7l6-4 6 4v14M10 21v-5h4v5M9 9h1m4 0h1m-6 3h1m4 0h1"/>',
        'services' => '<rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V4h8v3M3 12a21 21 0 0 0 18 0M12 11v4"/>',
        'team' => '<circle cx="9" cy="7" r="4"/><path d="M2 21v-3a7 7 0 0 1 14 0v3M17 4a4 4 0 0 1 0 8m2 3a6 6 0 0 1 3 6"/>',
        'seo' => '<circle cx="10" cy="10" r="7"/><path d="m15 15 6 6M7 11l2-3 2 2 2-3"/>',
        'inbox' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>',
        'history' => '<path d="M3 11a9 9 0 1 1 3 8M3 3v8h8M12 7v5l3 2"/>',
    ];
    return '<a class="' . ($key === $active ? 'active' : '') . '"' . ($key === $active ? ' aria-current="page"' : '') . ' href="?tab=' . site_e($key) . '"><svg viewBox="0 0 24 24" aria-hidden="true">' . $icons[$key] . '</svg><span>' . site_e($label) . '</span></a>';
}
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Administrar sitio | AMS Legal Solutions</title><link rel="stylesheet" href="admin.css?v=<?= filemtime(__DIR__ . '/admin.css') ?>"><link rel="stylesheet" href="theme.css?v=<?= filemtime(__DIR__ . '/theme.css') ?>"></head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-brand">AMS<span class="brand-dot">.</span><span class="brand-subtitle">LEGAL SOLUTIONS</span></div>
        <div class="workspace-label">Espacio de administración</div>
        <div class="nav-heading">CONTENIDO DEL SITIO</div>
        <nav aria-label="Secciones del panel">
            <?= admin_nav_link('general', 'Datos generales', $tab) ?>
            <?= admin_nav_link('home', 'Inicio', $tab) ?>
            <?= admin_nav_link('firm', 'La firma', $tab) ?>
            <?= admin_nav_link('services', 'Áreas legales', $tab) ?>
            <?= admin_nav_link('team', 'Abogados', $tab) ?>
            <?= admin_nav_link('seo', 'SEO y Google', $tab) ?>
            <div class="nav-section-label" aria-hidden="true">SEGUIMIENTO</div>
            <?= admin_nav_link('inbox', 'Consultas recibidas', $tab) ?>
            <?= admin_nav_link('history', 'Historial de cambios', $tab) ?>
            <div class="nav-section-label" aria-hidden="true">CUENTAS Y ACCESO</div>
            <a href="account.php"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg><span>Mi cuenta y contraseña</span></a>
            <a href="new-user.php"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="7" r="4"/><path d="M2 21v-3a7 7 0 0 1 14 0v3m3-13v8m-4-4h8"/></svg><span>Registrar usuario</span></a>
        </nav>
        <a class="mobile-logout" href="logout.php">Cerrar sesión <span aria-hidden="true">↗</span></a>
        <div class="sidebar-bottom"><a class="site-preview-link" href="../index.php" target="_blank" rel="noopener">Visitar sitio público <span aria-hidden="true">↗</span></a><div class="logout"><span class="session-avatar" aria-hidden="true"><?= site_e(mb_strtoupper(mb_substr($_SESSION['admin_username'] ?? 'A', 0, 1))) ?></span><div><strong><?= site_e($_SESSION['admin_username'] ?? 'Administrador') ?></strong><a href="logout.php">Cerrar sesión</a></div></div></div>
    </aside>
    <main class="admin-main">
        <div class="admin-toolbar"><span>Administración <span aria-hidden="true">/</span> <strong><?= site_e($tabTitles[$tab]) ?></strong></span><a href="../<?= site_e($previewPage) ?>" target="_blank" rel="noopener">Ver página pública <span aria-hidden="true">↗</span></a></div>
        <div class="admin-top"><div><span class="eyebrow">TU FIRMA, AL DÍA</span><h1><?= site_e($tabTitles[$tab]) ?></h1><p><?= site_e($tabDescriptions[$tab]) ?></p></div><span class="section-badge">Gestión de contenido</span></div>
        <?php if (in_array($tab, admin_editable_sections(), true)): ?><div class="editor-help"><p><strong>1.</strong> Edita el contenido <span>→</span> <strong>2.</strong> Revisa en Vista previa <span>→</span> <strong>3.</strong> Guarda para publicarlo</p><span class="editor-state" data-editor-state role="status">Sin cambios pendientes</span></div><?php endif; ?>
        <?php if ($tab === 'general'): ?>
        <div class="overview-grid">
            <a class="overview-card" href="?tab=home"><span class="overview-label">IDENTIDAD Y CONTENIDO</span><strong>Tu sitio, a tu medida <span aria-hidden="true">↗</span></strong><span>Edita los textos de la página de inicio</span></a>
            <a class="overview-card" href="?tab=services"><span class="overview-label">ÁREAS DE PRÁCTICA</span><strong><?= count($services) ?> <small>servicios</small><span aria-hidden="true">↗</span></strong><span>Organiza las especialidades de la firma</span></a>
            <a class="overview-card" href="?tab=team"><span class="overview-label">EQUIPO PROFESIONAL</span><strong><?= count($team) ?> <small>abogados</small><span aria-hidden="true">↗</span></strong><span>Gestiona perfiles y fotografías</span></a>
        </div>
        <?php endif; ?>
        <?php if ($saved): ?><div class="notice success">Cambios guardados correctamente.</div><?php endif; ?>
        <?php if (!$hasRecoveryEmail): ?><div class="notice recovery-reminder">Completa tu acceso: <a href="account.php">registra tu correo de recuperación en Mi cuenta</a> para poder restablecer tu contraseña si la olvidas.</div><?php endif; ?>
        <?php if (isset($_GET['restored'])): ?><div class="notice success">Contenido restaurado. La versión reemplazada se conserva en el historial.</div><?php endif; ?>
        <?php if ($error): ?><div class="notice error"><?= site_e($error) ?></div><?php endif; ?>

        <?php if (isset($sections[$tab])): ?>
            <form method="post" action="save.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= site_e(admin_csrf_token()) ?>">
                <input type="hidden" name="section" value="<?= site_e($tab) ?>">
                <section class="panel">
                    <div class="panel-heading"><span class="eyebrow">CONFIGURACIÓN</span><h2><?= site_e($sections[$tab]['title']) ?></h2><p><?= $tab === 'seo' ? 'Cada tarjeta corresponde a una página del sitio. Edita juntos su título y su descripción para buscadores.' : ($tab === 'general' ? 'La información que tus clientes verán sobre tu firma.' : 'Actualiza los textos y detalles que aparecen en esta sección.') ?></p></div>
                    <?php if ($tab === 'seo'): ?>
                        <?php require __DIR__ . '/seo-fields.php'; ?>
                    <?php else: ?>
                        <?php require __DIR__ . '/content-fields.php'; ?>
                    <?php endif; ?>
                    <?php if ($tab === 'general'): ?>
                        <fieldset class="content-group media-content-group"><legend>Imágenes de la firma</legend><p class="group-description">Selecciona un archivo para verlo antes de guardar. Tamaños recomendados: logo horizontal de 600 × 200 px con fondo transparente; icono cuadrado de 512 × 512 px; oficina de 1600 × 1000 px. Las medidas son orientativas.</p><div class="grid-3 media-grid">
                            <?php foreach (['logo' => ['Logo del encabezado', 'site.logo', 'img/ams-logo-header.png', 'Se muestra en la parte superior de todas las páginas.'], 'favicon' => ['Icono de la pestaña (favicon)', 'site.favicon', 'img/favicon.png', 'El pequeño icono junto al título en el navegador. Se recomienda PNG cuadrado.'], 'office' => ['Fotografía de la oficina', 'site.office_image', 'img/ams-office-reception.jpg', 'Imagen compartida por Inicio y La firma.']] as $field => [$label, $key, $default, $imageHelp]): ?>
                            <label><?= site_e($label) ?><span class="help"><?= site_e($imageHelp) ?></span><img class="image-preview" src="<?= site_e(admin_image_preview(site_setting($key, $default))) ?>" alt="<?= site_e($label) ?>"><input class="file-input" type="file" name="<?= site_e($field) ?>_upload" accept="image/jpeg,image/png,image/webp"><span class="help">JPG, PNG o WebP · Máximo 5 MB</span></label>
                            <?php endforeach; ?>
                        </div></fieldset>
                    <?php endif; ?>
                    <div class="save-row"><button type="submit"><?= site_e(['general' => 'Guardar datos generales', 'home' => 'Guardar página de inicio', 'firm' => 'Guardar página de la firma', 'seo' => 'Guardar SEO de las páginas'][$tab]) ?></button></div>
                </section>
            </form>
        <?php elseif ($tab === 'services'): ?>
            <form method="post" action="save.php">
                <input type="hidden" name="csrf" value="<?= site_e(admin_csrf_token()) ?>"><input type="hidden" name="section" value="services">
                <input type="hidden" name="services_present" value="1">
                <section class="panel"><div class="panel-heading"><span class="eyebrow">SERVICIOS DE LA FIRMA</span><h2>Áreas de práctica</h2><p>Primero edita la presentación de la página. Después encontrarás una tarjeta por cada área legal.</p></div>
                    <fieldset class="content-group introductory-group"><legend>Presentación de la página Áreas legales</legend><p class="group-description">Estos dos textos aparecen al comienzo de la página de servicios.</p><div class="content-fields"><label>Título de la página<input name="services_intro_title" value="<?= site_e(site_setting('services.intro_title', 'Seis especialidades, un mismo estándar de exigencia.')) ?>"></label><label class="content-field-wide">Párrafo de introducción<textarea name="services_intro_text"><?= site_e(site_setting('services.intro_text', 'Cada área cuenta con un equipo dedicado y experiencia acreditada ante tribunales, entidades regulatorias y contrapartes en Honduras y la región.')) ?></textarea></label></div></fieldset>
                    <div data-services-list>
                    <?php foreach ($services as $index => $service): $serviceNumber = $index + 1; ?>
                        <?php require __DIR__ . '/service-card.php'; ?>
                    <?php endforeach; ?>
                    </div>
                    <p class="team-empty" data-services-empty<?= $services ? ' hidden' : '' ?>>No hay áreas legales en la lista. Agrega un área para crear la primera especialidad.</p>
                    <div class="team-actions"><button type="button" class="button-secondary" data-services-add hidden>+ Agregar área</button><button type="button" class="button-secondary" data-services-undo hidden>Deshacer eliminación</button></div>
                    <p class="team-status" data-services-status role="status" aria-live="polite">Las áreas agregadas o eliminadas se publican al guardar.</p>
                    <template data-services-template><?php $index = '__INDEX__'; $serviceNumber = '__NUMBER__'; $service = []; require __DIR__ . '/service-card.php'; ?></template>
                    <div class="save-row"><button type="submit">Guardar áreas</button></div>
                </section>
            </form>
        <?php elseif ($tab === 'inbox'): ?>
            <?php require __DIR__ . '/inbox.php'; ?>
        <?php elseif ($tab === 'history'): ?>
            <?php require __DIR__ . '/history.php'; ?>
        <?php else: ?>
            <form method="post" action="save.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= site_e(admin_csrf_token()) ?>"><input type="hidden" name="section" value="team">
                <input type="hidden" name="team_present" value="1">
                <section class="panel"><div class="panel-heading"><span class="eyebrow">EQUIPO PROFESIONAL</span><h2>Abogados</h2><p>Primero edita la presentación de la página. Después agrega, edita o elimina los perfiles individuales.</p></div>
                    <fieldset class="content-group introductory-group"><legend>Presentación de la página Abogados</legend><p class="group-description">Estos dos textos aparecen encima de los perfiles en la página Abogados.</p><div class="content-fields"><label>Título de la página<input name="team_intro_title" value="<?= site_e(site_setting('team.intro_title', 'Abogados que te representan.')) ?>"></label><label class="content-field-wide">Párrafo de introducción<textarea name="team_intro_text"><?= site_e(site_setting('team.intro_text', 'Un equipo multidisciplinario con experiencia acreditada ante tribunales, entidades regulatorias y contrapartes en Honduras y la región.')) ?></textarea></label></div></fieldset>
                    <div class="profile-list-heading"><h3>Perfiles de los abogados</h3><p>Cada tarjeta representa un abogado. Nombre, cargo, especialidad y foto se comparten con Inicio; la biografía se muestra en la página Abogados.</p></div>
                    <div data-team-list>
                    <?php foreach ($team as $index => $member): $cardNumber = $index + 1; ?>
                        <?php require __DIR__ . '/team-card.php'; ?>
                    <?php endforeach; ?>
                    </div>
                    <p class="team-empty" data-team-empty<?= $team ? ' hidden' : '' ?>>No hay abogados en la lista. Agrega un espacio para crear el primer perfil.</p>
                    <div class="team-actions"><button type="button" class="button-secondary" data-team-add hidden>+ Agregar abogado</button><button type="button" class="button-secondary" data-team-undo hidden>Deshacer eliminación</button></div>
                    <p class="team-status" data-team-status role="status" aria-live="polite">Los perfiles agregados o eliminados se publican al guardar.</p>
                    <template data-team-template><?php $index = '__INDEX__'; $cardNumber = '__NUMBER__'; $member = []; require __DIR__ . '/team-card.php'; ?></template>
                    <div class="save-row"><button type="submit">Guardar abogados</button></div>
                </section>
            </form>
        <?php endif; ?>
        <div class="admin-footer-note"><span>AMS Legal Solutions <span aria-hidden="true">·</span> Administración</span><span>Los cambios se publican al guardar.</span></div>
    </main>
</div>
<?php if ($tab === 'team'): ?><script src="team.js?v=<?= filemtime(__DIR__ . '/team.js') ?>" defer></script><?php endif; ?>
<?php if ($tab === 'services'): ?><script src="services.js?v=<?= filemtime(__DIR__ . '/services.js') ?>" defer></script><?php endif; ?>
<script src="editor.js?v=<?= filemtime(__DIR__ . '/editor.js') ?>" defer></script>
</body></html>

