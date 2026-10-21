<?php if (!isset($index, $cardNumber, $member) || !function_exists('site_e')) { http_response_code(404); exit; } ?>
<div class="repeat-card" data-team-card data-team-index="<?= site_e((string) $index) ?>">
    <div class="team-card-heading"><h3 data-team-title>Abogado <?= site_e((string) $cardNumber) ?></h3><button type="button" class="button-remove" data-team-remove hidden>Eliminar espacio</button></div>
    <input type="hidden" name="team[<?= site_e((string) $index) ?>][slug]" value="<?= site_e($member['slug'] ?? '') ?>">
    <input type="hidden" name="team[<?= site_e((string) $index) ?>][image]" value="<?= site_e($member['image'] ?? '') ?>">
    <div class="editor-subgroup"><h4>Datos del perfil</h4><p>Información que identifica a este abogado en Inicio y en la página Abogados.</p><div class="grid-3">
        <label>Nombre público<input name="team[<?= site_e((string) $index) ?>][name]" value="<?= site_e($member['name'] ?? '') ?>" required></label>
        <label>Cargo en la firma<input name="team[<?= site_e((string) $index) ?>][role]" value="<?= site_e($member['role'] ?? '') ?>" placeholder="Ejemplo: Socia fundadora"></label>
        <label>Área de especialidad<input name="team[<?= site_e((string) $index) ?>][area]" value="<?= site_e($member['area'] ?? '') ?>" placeholder="Ejemplo: Derecho Corporativo"></label>
    </div></div>
    <div class="editor-subgroup"><h4>Biografía profesional</h4><p>Descripción de su experiencia, visible en la página Abogados.</p><label>Texto de la biografía<textarea name="team[<?= site_e((string) $index) ?>][bio]"><?= site_e($member['bio'] ?? '') ?></textarea></label></div>
    <div class="editor-subgroup"><h4>Fotografía del perfil</h4><p>La misma fotografía se utiliza en Inicio y en la página Abogados. Se recomienda un retrato vertical de al menos 800 × 1000 px.</p><div class="profile-photo-editor"><div><?php if (!empty($member['image'])): ?><img class="image-preview" src="<?= site_e(admin_image_preview($member['image'])) ?>" alt="<?= site_e($member['name'] ?? '') ?>"><?php else: ?><span class="photo-placeholder">Sin fotografía</span><?php endif; ?></div><label><?= empty($member['image']) ? 'Seleccionar fotografía' : 'Reemplazar fotografía' ?><input class="file-input" type="file" name="team_image_<?= site_e((string) $index) ?>" accept="image/jpeg,image/png,image/webp"><span class="help">JPG, PNG o WebP · Máximo 5 MB. Si no eliges un archivo, se conserva la foto actual.</span></label></div></div>
</div>
