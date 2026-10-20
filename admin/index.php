<?php
require __DIR__ . '/bootstrap.php';
admin_require_login();

$allowedTabs = ['general', 'home', 'firm', 'services', 'team', 'seo'];
$tab = $_GET['tab'] ?? 'general';
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'general';
}

$sections = admin_content_sections();
$services = site_json_setting('content.services', default_services());
$team = site_json_setting('content.team', default_team());
$saved = isset($_GET['saved']);
$error = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_error']);

function admin_nav_link(string $key, string $label, string $active): string
{
    return '<a class="' . ($key === $active ? 'active' : '') . '" href="?tab=' . site_e($key) . '">' . site_e($label) . '</a>';
}
?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Administrar sitio | AMS Legal</title><link rel="stylesheet" href="admin.css"></head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-brand">AMS <span>Legal</span></div>
        <nav>
            <?= admin_nav_link('general', 'Datos generales', $tab) ?>
            <?= admin_nav_link('home', 'Inicio', $tab) ?>
            <?= admin_nav_link('firm', 'La firma', $tab) ?>
            <?= admin_nav_link('services', 'Áreas legales', $tab) ?>
            <?= admin_nav_link('team', 'Abogados', $tab) ?>
            <?= admin_nav_link('seo', 'SEO y Google', $tab) ?>
            <a href="../index.php" target="_blank" rel="noopener">Ver sitio ↗</a>
        </nav>
        <div class="logout"><a href="logout.php">Cerrar sesión</a></div>
    </aside>
    <main class="admin-main">
        <div class="admin-top"><div><h1><?= site_e($tab === 'services' ? 'Áreas legales' : ($tab === 'team' ? 'Abogados' : $sections[$tab]['title'])) ?></h1><p>Los cambios se reflejan en el sitio público al guardar.</p></div><span>Hola, <?= site_e($_SESSION['admin_username'] ?? 'admin') ?></span></div>
        <?php if ($saved): ?><div class="notice success">Cambios guardados correctamente.</div><?php endif; ?>
        <?php if ($error): ?><div class="notice error"><?= site_e($error) ?></div><?php endif; ?>

        <?php if (isset($sections[$tab])): ?>
            <form method="post" action="save.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= site_e(admin_csrf_token()) ?>">
                <input type="hidden" name="section" value="<?= site_e($tab) ?>">
                <section class="panel">
                    <h2><?= site_e($sections[$tab]['title']) ?></h2>
                    <div class="grid-2">
                    <?php foreach ($sections[$tab]['fields'] as $key => [$label, $default, $type]): $value = site_setting($key, $default); ?>
                        <label><?= site_e($label) ?>
                            <?php if ($type === 'textarea'): ?><textarea name="settings[<?= site_e($key) ?>]" required><?= site_e($value) ?></textarea>
                            <?php else: ?><input type="<?= site_e($type) ?>" name="settings[<?= site_e($key) ?>]" value="<?= site_e($value) ?>" required><?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                    </div>
                    <?php if ($tab === 'general'): ?>
                        <div class="grid-2">
                            <label>Logo del encabezado <span class="help">JPG, PNG o WebP; máximo 5 MB</span><input class="file-input" type="file" name="logo_upload" accept="image/jpeg,image/png,image/webp"></label>
                            <label>Fotografía de la oficina <span class="help">JPG, PNG o WebP; máximo 5 MB</span><input class="file-input" type="file" name="office_upload" accept="image/jpeg,image/png,image/webp"></label>
                        </div>
                    <?php endif; ?>
                    <div class="save-row"><button type="submit">Guardar cambios</button></div>
                </section>
            </form>
        <?php elseif ($tab === 'services'): ?>
            <form method="post" action="save.php">
                <input type="hidden" name="csrf" value="<?= site_e(admin_csrf_token()) ?>"><input type="hidden" name="section" value="services">
                <section class="panel"><h2>Áreas de práctica</h2><p>Edita los textos. Escribe un elemento por línea en servicios y forma de trabajo.</p>
                    <div class="grid-2"><label>Título principal<input name="services_intro_title" value="<?= site_e(site_setting('services.intro_title', 'Seis especialidades, un mismo estándar de exigencia.')) ?>" required></label><label>Introducción<textarea name="services_intro_text" required><?= site_e(site_setting('services.intro_text', 'Cada área cuenta con un equipo dedicado y experiencia acreditada ante tribunales, entidades regulatorias y contrapartes en Honduras y la región.')) ?></textarea></label></div>
                    <?php foreach ($services as $index => $service): ?>
                    <div class="repeat-card"><h3><?= sprintf('%02d', $index + 1) ?> · <?= site_e($service['name'] ?? '') ?></h3>
                        <input type="hidden" name="services[<?= $index ?>][slug]" value="<?= site_e($service['slug'] ?? 'area-' . ($index + 1)) ?>">
                        <div class="grid-2"><label>Nombre<input name="services[<?= $index ?>][name]" value="<?= site_e($service['name'] ?? '') ?>" required></label><label>Resumen<input name="services[<?= $index ?>][summary]" value="<?= site_e($service['summary'] ?? '') ?>" required></label></div>
                        <label>Descripción<textarea name="services[<?= $index ?>][description]" required><?= site_e($service['description'] ?? '') ?></textarea></label>
                        <div class="grid-2"><label>Servicios<textarea name="services[<?= $index ?>][services]" required><?= site_e(implode("\n", $service['services'] ?? [])) ?></textarea></label><label>Cómo trabajamos<textarea name="services[<?= $index ?>][steps]" required><?= site_e(implode("\n", $service['steps'] ?? [])) ?></textarea></label></div>
                    </div>
                    <?php endforeach; ?><div class="save-row"><button type="submit">Guardar áreas</button></div>
                </section>
            </form>
        <?php else: ?>
            <form method="post" action="save.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= site_e(admin_csrf_token()) ?>"><input type="hidden" name="section" value="team">
                <section class="panel"><h2>Equipo</h2><p>Actualiza los datos y reemplaza las fotografías cuando lo necesites.</p>
                    <div class="grid-2"><label>Título principal<input name="team_intro_title" value="<?= site_e(site_setting('team.intro_title', 'Abogados que te representan.')) ?>" required></label><label>Introducción<textarea name="team_intro_text" required><?= site_e(site_setting('team.intro_text', 'Un equipo multidisciplinario con experiencia acreditada ante tribunales, entidades regulatorias y contrapartes en Honduras y la región.')) ?></textarea></label></div>
                    <?php foreach ($team as $index => $member): ?>
                    <div class="repeat-card"><h3><?= site_e($member['name'] ?? 'Abogado') ?></h3>
                        <input type="hidden" name="team[<?= $index ?>][slug]" value="<?= site_e($member['slug'] ?? 'abogado-' . ($index + 1)) ?>"><input type="hidden" name="team[<?= $index ?>][image]" value="<?= site_e($member['image'] ?? '') ?>">
                        <div class="grid-3"><label>Nombre<input name="team[<?= $index ?>][name]" value="<?= site_e($member['name'] ?? '') ?>" required></label><label>Cargo<input name="team[<?= $index ?>][role]" value="<?= site_e($member['role'] ?? '') ?>" required></label><label>Especialidad<input name="team[<?= $index ?>][area]" value="<?= site_e($member['area'] ?? '') ?>" required></label></div>
                        <label>Biografía<textarea name="team[<?= $index ?>][bio]" required><?= site_e($member['bio'] ?? '') ?></textarea></label>
                        <div class="grid-2"><div><?php if (!empty($member['image'])): ?><img class="image-preview" src="../<?= site_e($member['image']) ?>" alt=""><?php endif; ?></div><label>Reemplazar fotografía<input class="file-input" type="file" name="team_image_<?= $index ?>" accept="image/jpeg,image/png,image/webp"></label></div>
                    </div>
                    <?php endforeach; ?><div class="save-row"><button type="submit">Guardar abogados</button></div>
                </section>
            </form>
        <?php endif; ?>
        <p class="admin-footer-note">Panel privado de AMS Legal Solutions. No compartas tus credenciales.</p>
    </main>
</div>
</body></html>

