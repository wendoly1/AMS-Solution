<?php
require __DIR__ . '/bootstrap.php';
admin_require_login();
$pages = ['index.php' => 'Inicio', 'servicios.php' => 'Áreas legales', 'sobre_la_firma.php' => 'La firma', 'equipo.php' => 'Abogados', 'contacto.php' => 'Contacto'];
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        admin_verify_csrf();
        $section = $_POST['section'] ?? '';
        if (!is_string($section)) throw new RuntimeException('Sección inválida.');
        $changes = admin_build_changes($section, $_POST, true);
        $token = bin2hex(random_bytes(16));
        // Un borrador por sesión; no guarda archivos ni contenido en la base pública.
        $_SESSION['editor_preview'] = ['token' => $token, 'expires' => time() + 1200, 'changes' => $changes];
        $page = ['firm' => 'sobre_la_firma.php', 'services' => 'servicios.php', 'team' => 'equipo.php'][$section] ?? 'index.php';
        header('Location: preview.php?token=' . $token . '&page=' . urlencode($page), true, 303);
        exit;
    }
    $draft = $_SESSION['editor_preview'] ?? [];
    if (!is_string($_GET['token'] ?? null) || !isset($draft['token'], $draft['expires'], $draft['changes']) || !hash_equals($draft['token'], $_GET['token']) || $draft['expires'] < time()) {
        http_response_code(410);
        throw new RuntimeException('La vista previa venció. Vuelve al panel y pulsa Vista previa otra vez.');
    }
    $page = $_GET['page'] ?? 'index.php';
    if (!is_string($page) || !isset($pages[$page])) { http_response_code(400); throw new RuntimeException('Página inválida.'); }
    $GLOBALS['cms_preview_active'] = true;
    $GLOBALS['cms_preview_settings'] = $draft['changes'];
    $GLOBALS['cms_preview_images'] = [];
    foreach ($draft['changes'] as $value) {
        if (str_starts_with($value, 'data:image/')) $GLOBALS['cms_preview_images'][] = $value;
    }
    foreach (json_decode($draft['changes']['content.team'] ?? '[]', true) as $member) {
        if (str_starts_with($member['image'] ?? '', 'data:image/')) $GLOBALS['cms_preview_images'][] = $member['image'];
    }
    $token = $draft['token'];
    session_write_close();
    header("Content-Security-Policy: form-action 'none'; frame-ancestors 'self'; base-uri 'self'");
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['PHP_SELF'] = '/' . $page;
    $_POST = [];
    ob_start();
    require __DIR__ . '/../' . $page;
    $html = ob_get_clean();
    $html = str_replace('<head>', '<head><base href="../"><meta name="robots" content="noindex,nofollow">', $html);
    // Mantiene la misma vista previa al recorrer las páginas públicas.
    $html = preg_replace_callback('/href="(index|servicios|sobre_la_firma|equipo|contacto)\.php(#[^"]*)?"/', static function ($match) use ($token) {
        return 'href="admin/preview.php?token=' . $token . '&amp;page=' . $match[1] . '.php' . ($match[2] ?? '') . '"';
    }, $html);
    $html = preg_replace_callback('/href="#([^"]*)"/', static function ($match) use ($token, $page) {
        return 'href="admin/preview.php?token=' . $token . '&amp;page=' . $page . '#' . $match[1] . '"';
    }, $html);
    $bar = '<div class="editor-preview-bar"><strong>Vista previa · Sin publicar</strong><span>Los formularios están desactivados. Vuelve a la pestaña del panel para guardar.</span><nav aria-label="Páginas de vista previa">';
    foreach ($pages as $path => $label) $bar .= '<a href="admin/preview.php?token=' . $token . '&amp;page=' . $path . '">' . site_e($label) . '</a>';
    $bar .= '</nav></div>';
    $style = '<style>.editor-preview-bar{position:relative;z-index:9999;padding:15px 24px;background:#fff8e8;color:#513f20;font:13px/1.6 system-ui;border-bottom:1px solid #dfcfaa}.editor-preview-bar span{display:block}.editor-preview-bar nav{display:flex;gap:15px;flex-wrap:wrap;margin-top:6px}.editor-preview-bar a{color:#74582b;text-decoration:underline}.editor-preview-bar strong{font-size:15px}</style>';
    $html = str_replace('</head>', $style . '</head>', $html);
    $html = str_replace('<body>', '<body>' . $bar, $html);
    $html = preg_replace('/(<button\b[^>]*type="submit")/', '$1 disabled', $html);
    echo $html;
} catch (Throwable $error) {
    error_log('Vista previa: ' . $error->getMessage());
    if (http_response_code() < 400) http_response_code(400);
    $message = $error instanceof RuntimeException && !$error instanceof PDOException ? $error->getMessage() : 'No se pudo crear la vista previa. Regresa al panel e intenta nuevamente.';
    echo '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Vista previa</title><link rel="stylesheet" href="admin.css"><body class="auth-page"><main class="auth-card"><h1>Revisa tu contenido</h1><p>' . site_e($message) . '</p><button onclick="window.close()">Cerrar vista previa</button><a class="auth-back" href="index.php">Volver al panel</a></main></body></html>';
}
