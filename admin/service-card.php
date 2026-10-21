<?php if (!isset($index, $serviceNumber, $service) || !function_exists('site_e')) { http_response_code(404); exit; } ?>
<div class="repeat-card service-editor-card" data-services-card data-services-index="<?= site_e((string) $index) ?>">
    <div class="editor-card-heading">
        <div><span class="eyebrow" data-services-number>ÁREA LEGAL <?= site_e((string) $serviceNumber) ?></span><h3 data-services-title><?= site_e($service['name'] ?? 'Nueva área legal') ?></h3></div>
        <div class="service-card-actions">
            <?php if (!empty($service['slug'])): ?><a href="../servicios.php#<?= site_e($service['slug']) ?>" target="_blank" rel="noopener">Ver esta área ↗</a><?php endif; ?>
            <button type="button" class="button-remove" data-services-remove hidden>Eliminar área</button>
        </div>
    </div>
    <input type="hidden" name="services[<?= site_e((string) $index) ?>][slug]" value="<?= site_e($service['slug'] ?? '') ?>">
    <div class="editor-subgroup"><h4>Nombre y resumen</h4><p>El nombre identifica el área en el sitio. El resumen aparece en la lista de especialidades de Inicio.</p>
        <div class="grid-2">
            <label>Nombre del área<input name="services[<?= site_e((string) $index) ?>][name]" value="<?= site_e($service['name'] ?? '') ?>" required></label>
            <label>Resumen breve para Inicio<input name="services[<?= site_e((string) $index) ?>][summary]" value="<?= site_e($service['summary'] ?? '') ?>"></label>
        </div>
    </div>
    <div class="editor-subgroup"><h4>Información completa del área</h4><p>Este contenido aparece en la página Áreas legales, dentro de esta especialidad.</p>
        <label>Descripción completa<textarea name="services[<?= site_e((string) $index) ?>][description]"><?= site_e($service['description'] ?? '') ?></textarea></label>
        <div class="grid-2">
            <label>Lista de servicios incluidos<textarea name="services[<?= site_e((string) $index) ?>][services]"><?= site_e(implode("\n", $service['services'] ?? [])) ?></textarea><span class="help">Escribe un servicio por línea.</span></label>
            <label>Pasos de nuestra forma de trabajo<textarea name="services[<?= site_e((string) $index) ?>][steps]"><?= site_e(implode("\n", $service['steps'] ?? [])) ?></textarea><span class="help">Escribe un paso por línea, en el orden en que se realiza.</span></label>
        </div>
    </div>
</div>
