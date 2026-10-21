<?php
require __DIR__ . '/bootstrap.php';
admin_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

admin_verify_csrf();
$section = $_POST['section'] ?? 'general';
$allowedSections = ['general', 'home', 'firm', 'services', 'team', 'seo'];
if (!in_array($section, $allowedSections, true)) {
    http_response_code(400);
    exit('Sección inválida.');
}

try {
    $pdo->beginTransaction();
    $GLOBALS['admin_new_uploads'] = [];
    if (in_array($section, ['general', 'home', 'firm', 'seo'], true)) {
        $sections = admin_content_sections();
        $submitted = $_POST['settings'] ?? [];
        foreach ($sections[$section]['fields'] as $key => $definition) {
            if (!is_array($submitted) || !isset($submitted[$key]) || !is_string($submitted[$key])) {
                throw new RuntimeException('El formulario está incompleto. Recarga la página e intenta nuevamente.');
            }
            $value = trim($submitted[$key]);
            admin_validate_setting($value, $definition);
            admin_save_setting($key, $value);
        }

        if ($section === 'general') {
            foreach (['logo' => 'site.logo', 'office' => 'site.office_image', 'favicon' => 'site.favicon'] as $field => $key) {
                $image = admin_upload_image($field . '_upload', 'branding');
                if ($image) {
                    admin_save_setting($key, $image);
                }
            }
        }
    } elseif ($section === 'services') {
        $items = admin_validate_items($_POST['services'] ?? (($_POST['services_present'] ?? '') === '1' ? [] : null), ['name', 'summary', 'description', 'services', 'steps']);
        foreach (['services_intro_title', 'services_intro_text'] as $field) {
            admin_validate_setting(trim((string) ($_POST[$field] ?? '')), ['Introducción de áreas', '', 'text']);
        }
        admin_save_setting('services.intro_title', trim((string) ($_POST['services_intro_title'] ?? '')));
        admin_save_setting('services.intro_text', trim((string) ($_POST['services_intro_text'] ?? '')));
        $services = [];
        foreach ($items as $item) {
            $services[] = [
                'slug' => preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($item['slug'] ?? ''))),
                'name' => trim((string) ($item['name'] ?? '')),
                'summary' => trim((string) ($item['summary'] ?? '')),
                'description' => trim((string) ($item['description'] ?? '')),
                'services' => site_lines((string) ($item['services'] ?? '')),
                'steps' => site_lines((string) ($item['steps'] ?? '')),
            ];
        }
        admin_save_setting('content.services', json_encode($services, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    } elseif ($section === 'team') {
        $items = admin_validate_items($_POST['team'] ?? (($_POST['team_present'] ?? '') === '1' ? [] : null), ['name', 'role', 'area', 'bio']);
        foreach (['team_intro_title', 'team_intro_text'] as $field) {
            admin_validate_setting(trim((string) ($_POST[$field] ?? '')), ['Introducción del equipo', '', 'text']);
        }
        admin_save_setting('team.intro_title', trim((string) ($_POST['team_intro_title'] ?? '')));
        admin_save_setting('team.intro_text', trim((string) ($_POST['team_intro_text'] ?? '')));
        $team = [];
        foreach ($items as $index => $item) {
            $image = admin_upload_image('team_image_' . $index, 'team') ?: admin_image_path(trim((string) ($item['image'] ?? '')));
            $team[] = [
                'slug' => preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($item['slug'] ?? ''))),
                'name' => trim((string) ($item['name'] ?? '')),
                'role' => trim((string) ($item['role'] ?? '')),
                'area' => trim((string) ($item['area'] ?? '')),
                'bio' => trim((string) ($item['bio'] ?? '')),
                'image' => $image,
            ];
        }
        admin_save_setting('content.team', json_encode($team, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    $pdo->commit();
    header('Location: index.php?tab=' . urlencode($section) . '&saved=1');
    exit;
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    foreach (($GLOBALS['admin_new_uploads'] ?? []) as $path) {
        if (is_file($path)) {
            unlink($path);
        }
    }
    error_log('Error al guardar contenido: ' . $error->getMessage());
    $_SESSION['admin_error'] = $error instanceof RuntimeException && !$error instanceof PDOException
        ? $error->getMessage() : 'No se pudieron guardar los cambios. Intenta nuevamente.';
    header('Location: index.php?tab=' . urlencode($section));
    exit;
}

