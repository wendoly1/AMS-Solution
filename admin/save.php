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
    if (in_array($section, ['general', 'home', 'firm', 'seo'], true)) {
        $sections = admin_content_sections();
        $submitted = $_POST['settings'] ?? [];
        foreach ($sections[$section]['fields'] as $key => $definition) {
            $value = trim((string) ($submitted[$key] ?? ''));
            admin_save_setting($key, $value);
        }

        if ($section === 'general') {
            $logo = admin_upload_image('logo_upload', 'branding');
            $office = admin_upload_image('office_upload', 'branding');
            if ($logo) {
                admin_save_setting('site.logo', $logo);
            }
            if ($office) {
                admin_save_setting('site.office_image', $office);
            }
        }
    } elseif ($section === 'services') {
        admin_save_setting('services.intro_title', trim((string) ($_POST['services_intro_title'] ?? '')));
        admin_save_setting('services.intro_text', trim((string) ($_POST['services_intro_text'] ?? '')));
        $services = [];
        foreach (($_POST['services'] ?? []) as $item) {
            $services[] = [
                'slug' => preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($item['slug'] ?? ''))),
                'name' => trim((string) ($item['name'] ?? '')),
                'summary' => trim((string) ($item['summary'] ?? '')),
                'description' => trim((string) ($item['description'] ?? '')),
                'services' => site_lines((string) ($item['services'] ?? '')),
                'steps' => site_lines((string) ($item['steps'] ?? '')),
            ];
        }
        admin_save_setting('content.services', json_encode($services, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    } elseif ($section === 'team') {
        admin_save_setting('team.intro_title', trim((string) ($_POST['team_intro_title'] ?? '')));
        admin_save_setting('team.intro_text', trim((string) ($_POST['team_intro_text'] ?? '')));
        $team = [];
        foreach (($_POST['team'] ?? []) as $index => $item) {
            $image = admin_upload_image('team_image_' . $index, 'team') ?: trim((string) ($item['image'] ?? ''));
            $team[] = [
                'slug' => preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($item['slug'] ?? ''))),
                'name' => trim((string) ($item['name'] ?? '')),
                'role' => trim((string) ($item['role'] ?? '')),
                'area' => trim((string) ($item['area'] ?? '')),
                'bio' => trim((string) ($item['bio'] ?? '')),
                'image' => $image,
            ];
        }
        admin_save_setting('content.team', json_encode($team, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    header('Location: index.php?tab=' . urlencode($section) . '&saved=1');
    exit;
} catch (Throwable $error) {
    error_log('Error al guardar contenido: ' . $error->getMessage());
    $_SESSION['admin_error'] = $error->getMessage();
    header('Location: index.php?tab=' . urlencode($section));
    exit;
}

