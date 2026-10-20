<?php

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/content.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $sessionDirectory = sys_get_temp_dir();
    if (is_dir($sessionDirectory) && is_writable($sessionDirectory)) {
        session_save_path($sessionDirectory);
    }
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Strict',
    ]);
    session_start();
}

if (!$pdo instanceof PDO) {
    http_response_code(503);
    exit('No fue posible conectar con la base de datos. Revisa las credenciales de Hostinger.');
}

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS admin_users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(80) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        last_login_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS site_settings (
        setting_key VARCHAR(120) PRIMARY KEY,
        setting_value LONGTEXT NOT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

function admin_exists(): bool
{
    global $pdo;
    return (int) $pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn() > 0;
}

function admin_is_logged_in(): bool
{
    return !empty($_SESSION['admin_user_id']);
}

function admin_require_login(): void
{
    if (!admin_is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function admin_csrf_token(): string
{
    if (empty($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf'];
}

function admin_verify_csrf(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals(admin_csrf_token(), $token)) {
        http_response_code(403);
        exit('La sesión expiró. Regresa al panel e inténtalo nuevamente.');
    }
}

function admin_save_setting(string $key, string $value): void
{
    global $pdo;
    $statement = $pdo->prepare(
        'INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $statement->execute([':key' => $key, ':value' => $value]);
}

function admin_upload_image(string $field, string $folder): ?string
{
    if (empty($_FILES[$field]['tmp_name']) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK || $_FILES[$field]['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('La imagen no pudo subirse o supera los 5 MB.');
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES[$field]['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Solo se permiten imágenes JPG, PNG o WebP.');
    }

    $relativeFolder = 'uploads/' . trim($folder, '/');
    $absoluteFolder = dirname(__DIR__) . '/' . $relativeFolder;
    if (!is_dir($absoluteFolder) && !mkdir($absoluteFolder, 0755, true) && !is_dir($absoluteFolder)) {
        throw new RuntimeException('No se pudo crear la carpeta de imágenes.');
    }

    $filename = bin2hex(random_bytes(12)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $absoluteFolder . '/' . $filename)) {
        throw new RuntimeException('No se pudo guardar la imagen.');
    }

    return $relativeFolder . '/' . $filename;
}

function admin_content_sections(): array
{
    return [
        'general' => [
            'title' => 'Datos generales',
            'fields' => [
                'site.name' => ['Nombre de la firma', 'AMS Legal Solutions', 'text'],
                'site.location' => ['Ubicación corta', 'Tegucigalpa · Honduras', 'text'],
                'contact.address' => ['Dirección', 'Boulevard Morazán, Tegucigalpa, Honduras', 'text'],
                'contact.phone' => ['Teléfono', '+504 0000 0000', 'text'],
                'contact.email' => ['Correo', 'gmoncada@amslegalsolutions.com', 'email'],
                'contact.hours' => ['Horario', 'Lunes a viernes · 8:00 – 17:00', 'text'],
                'contact.heading' => ['Título de contacto', 'Agenda tu consulta inicial.', 'text'],
                'contact.intro' => ['Texto de contacto', 'Cuéntanos tu caso. Te responderemos con discreción y agendaremos una primera reunión sin compromiso.', 'textarea'],
            ],
        ],
        'home' => [
            'title' => 'Página de inicio',
            'fields' => [
                'home.hero_title' => ['Título principal', 'Estrategia legal a la altura de tus decisiones más importantes.', 'textarea'],
                'home.hero_description' => ['Descripción principal', 'Asesoría jurídica integral para empresas y personas en Honduras. Unimos rigor técnico, discreción y un trato cercano para proteger lo que más importa.', 'textarea'],
                'home.services_title' => ['Título de áreas', 'Especialidades de la firma', 'text'],
                'home.services_text' => ['Introducción de áreas', 'Seis áreas de práctica con equipos dedicados, cada una respaldada por años de experiencia ante tribunales hondureños.', 'textarea'],
                'home.about_title' => ['Título sobre la firma', 'Un despacho construido sobre la confianza y los resultados.', 'textarea'],
                'home.about_p1' => ['Primer párrafo', 'AMS Legal Solutions nace en Tegucigalpa con una convicción: la excelencia jurídica también se mide por la cercanía. Acompañamos a empresas y familias en cada decisión, con una mirada estratégica y profundamente ética.', 'textarea'],
                'home.about_p2' => ['Segundo párrafo', 'Nuestro equipo multidisciplinario combina experiencia litigiosa, visión de negocio y un compromiso real con cada cliente.', 'textarea'],
                'home.team_title' => ['Título del equipo', 'Abogados que te representan', 'text'],
                'stats.years' => ['Años de ejercicio', '16', 'number'],
                'stats.cases' => ['Casos resueltos', '900', 'number'],
                'stats.lawyers' => ['Abogados expertos', '12', 'number'],
                'stats.satisfaction' => ['Clientes satisfechos (%)', '98', 'number'],
            ],
        ],
        'firm' => [
            'title' => 'Sobre la firma',
            'fields' => [
                'firm.hero_title' => ['Título principal', 'Un despacho construido sobre la confianza y los resultados.', 'textarea'],
                'firm.hero_text' => ['Introducción', 'Desde 2008, AMS Legal Solutions acompaña a empresas y familias en Tegucigalpa con una convicción clara: la excelencia jurídica también se mide por la cercanía.', 'textarea'],
                'firm.history_title' => ['Título de historia', 'Diecisiete años defendiendo lo que importa.', 'text'],
                'firm.history_p1' => ['Historia, párrafo 1', 'AMS Legal Solutions nació con un grupo reducido de abogados decididos a ofrecer un servicio jurídico distinto: técnico, cercano y con visión de largo plazo. Hoy somos un equipo multidisciplinario que representa a empresas nacionales, inversionistas extranjeros y familias hondureñas.', 'textarea'],
                'firm.history_p2' => ['Historia, párrafo 2', 'Cada caso —grande o pequeño— recibe la misma exigencia: entender a fondo, actuar con estrategia y comunicar con claridad en cada paso del proceso.', 'textarea'],
                'firm.quote' => ['Frase institucional', 'Creemos que la excelencia jurídica se mide tanto por el resultado como por la confianza con que acompañamos cada decisión de nuestros clientes.', 'textarea'],
                'firm.quote_author' => ['Autor de la frase', 'Socios fundadores, AMS Legal Solutions', 'text'],
                'firm.values_title' => ['Título de valores', 'Lo que guía cada decisión que tomamos', 'text'],
                'firm.value_1_name' => ['Nombre del valor 1', 'Ética', 'text'],
                'firm.value_1_description' => ['Descripción del valor 1', 'La integridad guía cada consejo y cada actuación ante tribunales.', 'textarea'],
                'firm.value_2_name' => ['Nombre del valor 2', 'Rigor', 'text'],
                'firm.value_2_description' => ['Descripción del valor 2', 'Análisis profundo y estrategia clara en cada asunto que tomamos.', 'textarea'],
                'firm.value_3_name' => ['Nombre del valor 3', 'Cercanía', 'text'],
                'firm.value_3_description' => ['Descripción del valor 3', 'Comunicación directa y disponibilidad real con cada cliente.', 'textarea'],
                'firm.value_4_name' => ['Nombre del valor 4', 'Resultados', 'text'],
                'firm.value_4_description' => ['Descripción del valor 4', 'Medimos nuestro éxito por el impacto real en quienes confían en nosotros.', 'textarea'],
            ],
        ],
        'seo' => [
            'title' => 'SEO y Google',
            'fields' => [
                'seo.home_title' => ['Título de Inicio', 'Abogados en Tegucigalpa, Honduras | AMS Legal Solutions', 'text'],
                'seo.home_description' => ['Descripción de Inicio', 'Firma de abogados en Tegucigalpa con asesoría corporativa, laboral, penal, civil, familiar y de propiedad intelectual.', 'textarea'],
                'seo.services_title' => ['Título de Áreas', 'Servicios legales en Honduras | AMS Legal Solutions', 'text'],
                'seo.services_description' => ['Descripción de Áreas', 'Conoce nuestras áreas de práctica legal para empresas y personas en Honduras.', 'textarea'],
                'seo.firm_title' => ['Título de Firma', 'Nuestra firma de abogados | AMS Legal Solutions', 'text'],
                'seo.firm_description' => ['Descripción de Firma', 'Conoce la historia, los valores y el enfoque profesional de AMS Legal Solutions en Tegucigalpa.', 'textarea'],
                'seo.team_title' => ['Título de Abogados', 'Abogados en Tegucigalpa | AMS Legal Solutions', 'text'],
                'seo.team_description' => ['Descripción de Abogados', 'Conoce al equipo de abogados de AMS Legal Solutions y sus áreas de especialidad.', 'textarea'],
                'seo.contact_title' => ['Título de Contacto', 'Consulta legal en Tegucigalpa | AMS Legal Solutions', 'text'],
                'seo.contact_description' => ['Descripción de Contacto', 'Agenda una consulta legal con AMS Legal Solutions en Tegucigalpa, Honduras.', 'textarea'],
            ],
        ],
    ];
}

