<?php
if (!isset($sections) || !function_exists('site_setting')) { http_response_code(404); exit; }
$seoPages = [
    'home' => ['Inicio', 'Página principal', 'index.php'],
    'services' => ['Áreas legales', 'Página de servicios y especialidades', 'servicios.php'],
    'firm' => ['La firma', 'Página sobre la historia y los valores de AMS', 'sobre_la_firma.php'],
    'team' => ['Abogados', 'Página del equipo profesional', 'equipo.php'],
    'contact' => ['Contacto', 'Página para solicitar una consulta', 'contacto.php'],
];
?>
<div class="seo-explanation"><span class="seo-info-icon" aria-hidden="true">i</span><p><strong>¿Dónde se usan estos textos?</strong> El título identifica la página en la pestaña del navegador y en los buscadores. La descripción resume su contenido. Google puede mostrar otros textos del sitio.</p></div>
<div class="seo-page-list">
<?php $seoNumber = 0; foreach ($seoPages as $pageKey => [$pageLabel, $pageDescription, $pagePath]): $seoNumber++; ?>
    <fieldset class="seo-page-card" aria-describedby="seo-page-<?= site_e($pageKey) ?>-help">
        <legend><span class="seo-page-number" aria-hidden="true"><?= sprintf('%02d', $seoNumber) ?></span><span>Página: <?= site_e($pageLabel) ?></span></legend>
        <div class="seo-page-meta"><p id="seo-page-<?= site_e($pageKey) ?>-help"><?= site_e($pageDescription) ?></p><a href="../<?= site_e($pagePath) ?>" target="_blank" rel="noopener" aria-label="Abrir página de <?= site_e($pageLabel) ?> en otra pestaña">Ver esta página <span aria-hidden="true">↗</span></a></div>
        <div class="seo-fields-grid">
        <?php foreach (['title' => ['Título para buscadores', 'El nombre con el que se identifica esta página.'], 'description' => ['Descripción para buscadores', 'Un resumen de lo que el visitante encontrará en esta página.']] as $field => [$fieldLabel, $fieldHelp]):
            $settingKey = 'seo.' . $pageKey . '_' . $field;
            $definition = $sections['seo']['fields'][$settingKey];
            $fieldId = 'seo-' . $pageKey . '-' . $field;
            $fieldValue = site_setting($settingKey, $definition[1]);
        ?>
            <div class="seo-field">
                <label for="<?= site_e($fieldId) ?>"><?= site_e($fieldLabel) ?><span class="seo-field-page"><?= site_e($pageLabel) ?></span></label>
                <?php if ($field === 'description'): ?>
                <textarea id="<?= site_e($fieldId) ?>" name="settings[<?= site_e($settingKey) ?>]" aria-describedby="<?= site_e($fieldId) ?>-help" rows="3"><?= site_e($fieldValue) ?></textarea>
                <?php else: ?>
                <input id="<?= site_e($fieldId) ?>" name="settings[<?= site_e($settingKey) ?>]" value="<?= site_e($fieldValue) ?>" aria-describedby="<?= site_e($fieldId) ?>-help">
                <?php endif; ?>
                <p class="seo-field-help" id="<?= site_e($fieldId) ?>-help"><?= site_e($fieldHelp) ?></p>
            </div>
        <?php endforeach; ?>
        </div>
    </fieldset>
<?php endforeach; ?>
</div>
