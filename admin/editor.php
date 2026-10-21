<?php

function admin_prepare_editor(): void
{
    global $pdo;
    static $prepared = false;
    if ($prepared) return;
    foreach (explode(';', file_get_contents(__DIR__ . '/../database-editor.sql')) as $sql) {
        if (trim($sql) !== '') $pdo->exec($sql);
    }
    $prepared = true;
}

function admin_editable_sections(): array
{
    return ['general', 'home', 'firm', 'services', 'team', 'seo'];
}

function admin_section_keys(string $section): array
{
    $sections = admin_content_sections();
    if ($section === 'services') return ['services.intro_title', 'services.intro_text', 'content.services'];
    if ($section === 'team') return ['team.intro_title', 'team.intro_text', 'content.team'];
    $keys = array_keys($sections[$section]['fields'] ?? []);
    return $section === 'general' ? array_merge($keys, ['site.logo', 'site.office_image', 'site.favicon']) : $keys;
}

function admin_image_upload_info(string $field): ?array
{
    $file = $_FILES[$field] ?? null;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? -1) !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name']) || ($file['size'] ?? PHP_INT_MAX) > 5 * 1024 * 1024) {
        throw new RuntimeException('La imagen no pudo subirse o supera los 5 MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $dimensions = getimagesize($file['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || !$dimensions) {
        throw new RuntimeException('Selecciona una imagen JPG, PNG o WebP válida.');
    }
    return [$file, $mime];
}

function admin_editor_image(string $field, string $folder, bool $preview): ?string
{
    if (!$preview) return admin_upload_image($field, $folder);
    $info = admin_image_upload_info($field);
    if (!$info) return null;
    [$file, $mime] = $info;
    $GLOBALS['admin_preview_bytes'] = ($GLOBALS['admin_preview_bytes'] ?? 0) + $file['size'];
    if ($GLOBALS['admin_preview_bytes'] > 15 * 1024 * 1024) {
        throw new RuntimeException('La vista previa admite hasta 15 MB de imágenes a la vez.');
    }
    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($file['tmp_name']));
}

// El mismo contrato se valida tanto al publicar como al previsualizar.
function admin_build_changes(string $section, array $post, bool $preview = false): array
{
    if (!in_array($section, admin_editable_sections(), true)) throw new RuntimeException('Sección inválida.');
    $changes = [];
    if (in_array($section, ['general', 'home', 'firm', 'seo'], true)) {
        $definitions = admin_content_sections()[$section]['fields'];
        $submitted = $post['settings'] ?? [];
        foreach ($definitions as $key => $definition) {
            if (!is_array($submitted) || !isset($submitted[$key]) || !is_string($submitted[$key])) {
                throw new RuntimeException('El formulario está incompleto. Recarga la página e intenta nuevamente.');
            }
            $value = trim($submitted[$key]);
            admin_validate_setting($value, $definition);
            $changes[$key] = $value;
        }
        if ($section === 'general') {
            foreach (['logo' => 'site.logo', 'office' => 'site.office_image', 'favicon' => 'site.favicon'] as $field => $key) {
                $image = admin_editor_image($field . '_upload', 'branding', $preview);
                if ($image) $changes[$key] = $image;
            }
        }
        return $changes;
    }
    $isTeam = $section === 'team';
    $fields = $isTeam ? ['name', 'role', 'area', 'bio'] : ['name', 'summary', 'description', 'services', 'steps'];
    $items = admin_validate_items($post[$section] ?? (($post[$section . '_present'] ?? '') === '1' ? [] : null), $fields);
    foreach (['title', 'text'] as $suffix) {
        $field = $section . '_intro_' . $suffix;
        if (!is_string($post[$field] ?? null)) throw new RuntimeException('Falta la presentación de la página.');
        $changes[$section . '.intro_' . $suffix] = trim($post[$field]);
    }
    $rows = [];
    foreach ($items as $index => $item) {
        $row = ['slug' => $item['slug']];
        foreach ($fields as $field) {
            $row[$field] = !$isTeam && in_array($field, ['services', 'steps'], true) ? site_lines($item[$field]) : trim($item[$field]);
        }
        if ($isTeam) {
            if (!is_string($item['image'] ?? null)) throw new RuntimeException('La fotografía del perfil es inválida.');
            $row['image'] = admin_editor_image('team_image_' . $index, 'team', $preview) ?: admin_image_path(trim($item['image']));
        }
        $rows[] = $row;
    }
    $changes['content.' . $section] = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    return $changes;
}

function admin_publish_changes(string $section, array $changes, string $action = 'Contenido guardado'): void
{
    global $pdo;
    if (!$pdo->inTransaction()) throw new RuntimeException('El guardado requiere una transacción.');
    $allowed = admin_section_keys($section);
    if (!$allowed || array_diff(array_keys($changes), $allowed)) throw new RuntimeException('El contenido no corresponde a esta sección.');
    // Serializa las ediciones y obtiene una copia de los valores que serán reemplazados.
    $current = $pdo->query('SELECT setting_key, setting_value FROM site_settings FOR UPDATE')->fetchAll(PDO::FETCH_KEY_PAIR);
    $before = [];
    $after = [];
    foreach ($changes as $key => $value) {
        if ($value !== null && !is_string($value)) throw new RuntimeException('El contenido de la versión es inválido.');
        $before[$key] = $current[$key] ?? null;
        $after[$key] = $value;
    }
    if ($before === $after) return;
    foreach ($after as $key => $value) {
        if ($value === null) {
            $pdo->prepare('DELETE FROM site_settings WHERE setting_key = ?')->execute([$key]);
        } else {
            admin_save_setting($key, $value);
        }
    }
    $statement = $pdo->prepare('INSERT INTO site_revisions (section, username, action_label, before_data, after_data, created_at) VALUES (?, ?, ?, ?, ?, ?)');
    $statement->execute([$section, $_SESSION['admin_username'] ?? 'Administrador', $action,
        json_encode($before, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        json_encode($after, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), date('Y-m-d H:i:s')]);
}
