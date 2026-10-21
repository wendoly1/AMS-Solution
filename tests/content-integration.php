<?php
// Ejecutar: C:\xampp\php\php.exe tests/content-integration.php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
chdir(dirname(__DIR__));

if (($argv[1] ?? '') === '--render') {
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['PHP_SELF'] = '/' . $argv[2];
    // Carga real del conector una sola vez; la prueba de vistas usa SQLite en memoria.
    putenv('AMS_ENV=local');
    putenv('AMS_DB_HOST=127.0.0.1');
    putenv('AMS_DB_PORT=1');
    require_once 'includes/database.php';
    $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $pdo->exec('CREATE TABLE site_settings (setting_key VARCHAR(120) PRIMARY KEY, setting_value TEXT NOT NULL)');
    $fixture = $argv[3];
    $settings = [];
    if ($fixture === 'panel') {
        $settings = [
            'site.name' => 'Firma de prueba & Asociados',
            'site.logo' => 'uploads/branding/logo-prueba.png',
            'site.favicon' => 'uploads/branding/favicon-prueba.png',
            'site.office_image' => 'uploads/branding/oficina-prueba.webp',
            'site.url' => 'https://firma.example',
            'site.footer_description' => 'Texto del pie desde MySQL',
            'contact.phone' => '+504 2222 3333',
            'contact.email' => 'prueba@example.com',
            'contact.address' => 'Dirección de prueba',
            'home.hero_title' => "Título desde el panel\nSegunda línea <script>alert(1)</script>",
            'home.about_p1' => 'Historia actualizada desde el panel',
            'firm.history_p1' => 'Historia actualizada desde el panel',
            'content.services' => json_encode([['slug' => 'asesoria-nueva', 'name' => 'Asesoría nueva', 'summary' => 'Resumen nuevo', 'description' => 'Descripción nueva', 'services' => ['Servicio nuevo'], 'steps' => ['Paso nuevo']]]),
            'content.team' => json_encode([['slug' => 'abogada-nueva', 'name' => 'Abogada nueva', 'role' => 'Socia nueva', 'area' => 'Área nueva', 'bio' => 'Biografía nueva', 'image' => 'uploads/team/abogada.webp']]),
        ];
        foreach (['home', 'services', 'firm', 'team', 'contact'] as $page) {
            $settings['seo.' . $page . '_title'] = 'SEO de prueba ' . $page;
            $settings['seo.' . $page . '_description'] = 'Descripción SEO de prueba ' . $page;
        }
    } elseif ($fixture === 'invalid') {
        $settings = ['content.services' => '{"incorrecto":true}', 'content.team' => '[{"name":"Incompleto"}]', 'site.favicon' => 'javascript:alert(1)', 'site.logo' => '../privado.png'];
    } elseif ($fixture === 'empty') {
        $settings = ['content.services' => '[]', 'content.team' => '[]'];
    }
    $statement = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)');
    foreach ($settings as $key => $value) {
        $statement->execute([$key, $value]);
    }
    set_error_handler(static function ($severity, $message, $file, $line) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });
    require $argv[2];
    exit;
}

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$pages = ['index.php' => 'home', 'servicios.php' => 'services', 'sobre_la_firma.php' => 'firm', 'equipo.php' => 'team', 'contacto.php' => 'contact'];
$checks = 0;
foreach (['default', 'panel', 'invalid', 'empty'] as $fixture) {
    foreach ($pages as $page => $seo) {
        $process = proc_open([PHP_BINARY, __FILE__, '--render', $page, $fixture], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $html = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        check(proc_close($process) === 0, $page . ': ' . $errors);
        check(str_contains($html, '</html>'), 'Página incompleta: ' . $page);
        if ($fixture === 'panel') {
            foreach (['Firma de prueba &amp; Asociados', 'uploads/branding/logo-prueba.png', 'uploads/branding/favicon-prueba.png', 'SEO de prueba ' . $seo, 'Descripción SEO de prueba ' . $seo, 'https://firma.example/', 'Texto del pie desde MySQL', 'servicios.php#asesoria-nueva', '+504 2222 3333', 'prueba@example.com'] as $expected) {
                check(str_contains($html, $expected), $page . ': falta ' . $expected);
            }
            check(!str_contains($html, 'servicios.php#corporativo'), 'Enlace antiguo de servicio: ' . $page);
            if (in_array($page, ['index.php', 'equipo.php'], true)) {
                check(str_contains($html, 'Abogada nueva') && str_contains($html, 'uploads/team/abogada.webp'), 'Abogados no actualizados');
            }
            if (in_array($page, ['index.php', 'sobre_la_firma.php'], true)) {
                check(str_contains($html, 'uploads/branding/oficina-prueba.webp') && str_contains($html, 'Historia actualizada desde el panel'), 'Firma no actualizada');
            }
            if (in_array($page, ['index.php', 'contacto.php'], true)) {
                check(str_contains($html, '<option value="Asesoría nueva"'), 'Formulario con áreas antiguas');
            }
            if ($page === 'index.php') {
                check(str_contains($html, '&lt;script&gt;') && !str_contains($html, '<script>alert(1)</script>'), 'Texto sin escapar');
            }
        } elseif ($fixture === 'invalid') {
            check(str_contains($html, 'href="img/favicon.png"') && str_contains($html, 'src="img/ams-logo-header.png"'), 'Fallback de imágenes incorrecto');
            if ($page === 'equipo.php') {
                check(str_contains($html, 'A. Martínez'), 'Fallback de abogados incorrecto');
            }
        } elseif ($fixture === 'empty') {
            check(!str_contains($html, 'servicios.php#corporativo'), 'La lista vacía debe respetarse');
            if ($page === 'equipo.php') {
                check(!str_contains($html, 'A. Martínez'), 'La lista vacía de abogados debe respetarse');
            }
        } elseif ($page === 'index.php') {
            check(str_contains($html, 'class="accent-gold">decisiones</span> <em>más importantes.</em>'), 'Cambió el título aprobado');
        }
        $checks++;
    }
}
echo "OK: $checks renders; contenido del panel, SEO, imágenes, formularios, escape y valores de respaldo.\n";
