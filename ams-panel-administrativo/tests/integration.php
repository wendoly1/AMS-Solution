<?php
// Prueba HTTP aislada. SQLite adapta únicamente el SQL específico de MySQL.
// No utiliza ni modifica la base de datos real del sitio.
$root = dirname(__DIR__);
$publicContent = dirname($root) . '/includes/content.php';
if (!is_file($publicContent)) throw new RuntimeException('No se encontró el archivo compartido del sitio público.');
$runtime = __DIR__ . '/.runtime/' . bin2hex(random_bytes(6));
mkdir($runtime . '/admin', 0755, true);
mkdir($runtime . '/includes', 0755, true);
mkdir($runtime . '/uploads', 0755, true);
foreach (glob($root . '/admin/*') as $file) {
    copy($file, $runtime . '/admin/' . basename($file));
}
// Solo un cargador de prueba: usa el archivo público original, sin copiarlo ni modificarlo.
file_put_contents($runtime . '/includes/content.php', '<?php require_once ' . var_export($publicContent, true) . ';');
file_put_contents($runtime . '/includes/database.php', <<<'PHP'
<?php
class TestPDO extends PDO {
    public function exec(string $statement): int|false {
        if (strpos($statement, 'CREATE TABLE IF NOT EXISTS') === 0) return 0;
        return parent::exec($statement);
    }
    public function prepare(string $query, array $options = []): PDOStatement|false {
        $query = str_replace('ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
            'ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value', $query);
        return parent::prepare($query, $options);
    }
}
$pdo = new TestPDO('sqlite:' . dirname(__DIR__) . '/test.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->sqliteCreateFunction('NOW', static fn () => date('Y-m-d H:i:s'));
PHP
);
file_put_contents($runtime . '/public.php', <<<'PHP'
<?php
require __DIR__ . '/includes/database.php';
require __DIR__ . '/includes/content.php';
echo site_e(site_setting('home.hero_title', 'Original'));
echo json_encode(site_services(), JSON_UNESCAPED_UNICODE);
echo json_encode(site_team(), JSON_UNESCAPED_UNICODE);
echo '<link rel="icon" href="' . site_e(site_media('site.favicon', 'img/favicon.png')) . '">';
echo '<link rel="canonical" href="' . site_e(site_setting('site.url')) . '">';
echo site_e(site_setting('site.footer_description'));
PHP
);
$db = new PDO('sqlite:' . $runtime . '/test.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('CREATE TABLE admin_users (id INTEGER PRIMARY KEY, username TEXT UNIQUE, password_hash TEXT, last_login_at TEXT)');
$db->exec('CREATE TABLE site_settings (setting_key TEXT PRIMARY KEY, setting_value TEXT NOT NULL)');
$db->exec("INSERT INTO site_settings VALUES ('external.unknown', 'Conservar esta clave')");
$password = bin2hex(random_bytes(20));
$db->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?)')->execute(['test-admin', password_hash($password, PASSWORD_DEFAULT)]);
$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
$address = stream_socket_get_name($socket, false);
fclose($socket);
$server = proc_open([PHP_BINARY, '-S', $address, '-t', $runtime],
    [0 => ['pipe', 'r'], 1 => ['file', $runtime . '/server.log', 'a'], 2 => ['file', $runtime . '/server.log', 'a']], $pipes, $runtime);
if (!is_resource($server)) throw new RuntimeException('No se pudo iniciar el servidor de prueba.');
$base = 'http://' . $address;
$cookie = $runtime . '/cookies.txt';
$checks = 0;
function check(bool $condition, string $label): void {
    global $checks;
    if (!$condition) throw new RuntimeException('FALLÓ: ' . $label);
    $checks++;
    echo 'OK: ' . $label . PHP_EOL;
}
function request(string $path, ?array $post = null, bool $multipart = false): array {
    global $base, $cookie;
    $ch = curl_init($base . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
        CURLOPT_COOKIEJAR => $cookie, CURLOPT_COOKIEFILE => $cookie, CURLOPT_TIMEOUT => 10]);
    if ($post !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $multipart ? $post : http_build_query($post));
    $response = curl_exec($ch);
    if ($response === false) throw new RuntimeException('Error HTTP en la prueba.');
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    return [$status, substr($response, $size), substr($response, 0, $size)];
}
function token(string $html): string {
    preg_match('/name="csrf" value="([a-f0-9]+)"/', $html, $match);
    return $match[1] ?? '';
}
try {
    for ($attempt = 0; $attempt < 50; $attempt++) {
        $probe = @stream_socket_client('tcp://' . $address, $errno, $errstr, .1);
        if ($probe) { fclose($probe); break; }
        usleep(50000);
    }
    [$status, , $headers] = request('/admin/index.php');
    check($status === 302 && strpos($headers, 'login.php') !== false, 'Panel protegido sin sesión');
    [$status] = request('/admin/save.php', ['section' => 'home']);
    check($status === 302 && (int) $db->query('SELECT COUNT(*) FROM site_settings')->fetchColumn() === 1, 'Guardado protegido sin sesión');
    [$status] = request('/admin/setup.php?token=invalido');
    check($status === 404, 'Configuración inicial cerrada sin token privado');
    [, $html] = request('/admin/login.php');
    $csrf = token($html);
    check(strlen($csrf) === 64, 'Formulario con token CSRF');
    [$status] = request('/admin/login.php', ['csrf' => $csrf, 'username' => 'test-admin', 'password' => $password]);
    check($status === 302, 'Inicio de sesión con hash existente');
    foreach (['general', 'home', 'firm', 'services', 'team', 'seo'] as $tab) {
        [$status, $html] = request('/admin/?tab=' . $tab);
        check($status === 200 && strpos($html, 'name="csrf"') !== false, 'Sección ' . $tab);
        if ($tab === 'general') {
            check(strpos($html, 'name="settings[site.url]"') !== false
                && strpos($html, 'name="settings[site.footer_description]"') !== false
                && strpos($html, 'name="favicon_upload"') !== false, 'Controles de URL, pie y favicon');
        }
    }
    [$status] = request('/admin/save.php', ['csrf' => 'invalido', 'section' => 'home']);
    check($status === 403, 'CSRF inválido rechazado');

    // Obtener definiciones reales sin ejecutar el bootstrap de conexión/sesión.
    $bootstrap = file_get_contents($root . '/admin/bootstrap.php');
    eval(substr($bootstrap, strpos($bootstrap, 'function admin_exists()')));
    $sections = admin_content_sections();
    $settings = [];
    foreach ($sections['home']['fields'] as $key => $definition) $settings[$key] = $definition[1];
    $settings['home.hero_title'] = '<b>Texto desde panel</b>';
    [$status, , $headers] = request('/admin/save.php', ['csrf' => $csrf, 'section' => 'home', 'settings' => $settings]);
    check($status === 302 && strpos($headers, 'saved=1') !== false, 'Guardado completo');
    [, $html] = request('/public.php');
    check(strpos($html, '&lt;b&gt;Texto desde panel&lt;/b&gt;') !== false, 'Página pública lee contenido compartido y escapa HTML');
    $settings['home.hero_title'] = 'No debe persistir';
    $settings['stats.satisfaction'] = '101';
    request('/admin/save.php', ['csrf' => $csrf, 'section' => 'home', 'settings' => $settings]);
    check($db->query("SELECT setting_value FROM site_settings WHERE setting_key='home.hero_title'")->fetchColumn() === '<b>Texto desde panel</b>', 'Error de validación revierte cambios parciales');

    require $publicContent;
    $services = default_services();
    foreach ($services as &$item) {
        $item['services'] = implode("\n", $item['services']);
        $item['steps'] = implode("\n", $item['steps']);
    }
    unset($item);
    $services[0]['name'] = 'Área de prueba';
    [$status, , $headers] = request('/admin/save.php', ['csrf' => $csrf, 'section' => 'services', 'services_intro_title' => 'Áreas', 'services_intro_text' => 'Introducción', 'services' => $services]);
    check($status === 302 && strpos($headers, 'saved=1') !== false, 'Áreas guardadas como JSON');
    $team = default_team();
    $team[0]['name'] = 'Abogada de prueba';
    request('/admin/save.php', ['csrf' => $csrf, 'section' => 'team', 'team_intro_title' => 'Equipo', 'team_intro_text' => 'Introducción', 'team' => $team]);
    [, $html] = request('/public.php');
    check(strpos($html, 'Área de prueba') !== false && strpos($html, 'Abogada de prueba') !== false, 'Página pública recibe áreas y abogados actualizados');

    $general = ['csrf' => $csrf, 'section' => 'general'];
    foreach ($sections['general']['fields'] as $key => $definition) $general['settings[' . $key . ']'] = $definition[1];
    $general['settings[site.url]'] = 'https://firma.example';
    $general['settings[site.footer_description]'] = 'Descripción del pie desde el panel';
    file_put_contents($runtime . '/sample.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
    $general['favicon_upload'] = new CURLFile($runtime . '/sample.png', 'image/png', 'favicon.png');
    [$status, , $headers] = request('/admin/save.php', $general, true);
    $image = $db->query("SELECT setting_value FROM site_settings WHERE setting_key='site.favicon'")->fetchColumn();
    check($status === 302 && strpos($headers, 'saved=1') !== false && is_file($runtime . '/' . $image), 'Favicon subido a uploads/branding');
    [, $html] = request('/public.php');
    check(strpos($html, 'href="' . $image . '"') !== false, 'Favicon leído por funciones públicas');
    check(strpos($html, 'href="https://firma.example"') !== false && strpos($html, 'Descripción del pie desde el panel') !== false, 'URL canónica y descripción del pie guardadas');
    check($db->query("SELECT setting_value FROM site_settings WHERE setting_key='external.unknown'")->fetchColumn() === 'Conservar esta clave', 'Claves desconocidas preservadas');
    $beforeFiles = glob($runtime . '/uploads/branding/*');
    file_put_contents($runtime . '/invalid.png', '<?php echo "invalid";');
    $general['office_upload'] = new CURLFile($runtime . '/invalid.png', 'image/png', 'imagen.png');
    [$status, , $headers] = request('/admin/save.php', $general, true);
    check(strpos($headers, 'saved=1') === false && glob($runtime . '/uploads/branding/*') === $beforeFiles, 'Imagen falsa rechazada y subida parcial eliminada');
    check(admin_image_path('../admin/index.php') === '' && admin_image_path('uploads/../x.png') === '' && admin_image_path('C:\\tmp\\x.png') === '' && admin_image_path('public_html/uploads/a.png') === '', 'Rutas de disco y recorridos rechazados');
    check(admin_image_preview('img/abogado-martinez.jpg') === '../img/abogado-martinez.jpg' && admin_image_preview('https://otro.test/a.png') === 'https://otro.test/a.png', 'Vistas previas relativas y URL absoluta');
    unset($general['office_upload'], $general['favicon_upload']);
    $general['settings[site.url]'] = 'javascript:alert(1)';
    request('/admin/save.php', $general, true);
    check($db->query("SELECT setting_value FROM site_settings WHERE setting_key='site.url'")->fetchColumn() === 'https://firma.example', 'URL canónica inválida rechazada');
    $general['settings[site.url]'] = 'https://firma.example';
    $general['settings[site.footer_description]'] = '';
    request('/admin/save.php', $general, true);
    check($db->query("SELECT setting_value FROM site_settings WHERE setting_key='site.footer_description'")->fetchColumn() === '', 'Cadena vacía guardada como texto vacío');

    // Un integrante con URL absoluta conserva su imagen si no se sube otra.
    $team[0]['image'] = 'https://otro.test/foto.webp';
    request('/admin/save.php', ['csrf' => $csrf, 'section' => 'team', 'team_intro_title' => 'Equipo', 'team_intro_text' => 'Texto', 'team' => $team]);
    $savedTeam = json_decode($db->query("SELECT setting_value FROM site_settings WHERE setting_key='content.team'")->fetchColumn(), true);
    check($savedTeam[0]['image'] === 'https://otro.test/foto.webp', 'Fotografía absoluta preservada al guardar abogado');
    // Probar subida real al directorio team y mantener el resto de los datos.
    $teamPost = ['csrf' => $csrf, 'section' => 'team', 'team_intro_title' => 'Equipo', 'team_intro_text' => 'Texto'];
    foreach ($team as $i => $member) foreach ($member as $key => $value) $teamPost['team[' . $i . '][' . $key . ']'] = $value;
    $teamPost['team_image_0'] = new CURLFile($runtime . '/sample.png', 'image/png', 'foto.png');
    request('/admin/save.php', $teamPost, true);
    $savedTeam = json_decode($db->query("SELECT setting_value FROM site_settings WHERE setting_key='content.team'")->fetchColumn(), true);
    check(strpos($savedTeam[0]['image'], 'uploads/team/') === 0 && is_file($runtime . '/' . $savedTeam[0]['image']), 'Foto del abogado guardada en uploads/team');
    $services[1]['slug'] = $services[0]['slug'];
    $previous = $db->query("SELECT setting_value FROM site_settings WHERE setting_key='content.services'")->fetchColumn();
    request('/admin/save.php', ['csrf' => $csrf, 'section' => 'services', 'services_intro_title' => 'Áreas', 'services_intro_text' => 'Texto', 'services' => $services]);
    check($db->query("SELECT setting_value FROM site_settings WHERE setting_key='content.services'")->fetchColumn() === $previous, 'Slugs duplicados rechazados sin cambiar la lista');
    foreach (['services', 'team'] as $collection) {
        request('/admin/save.php', ['csrf' => $csrf, 'section' => $collection, $collection . '_present' => '1', $collection . '_intro_title' => 'Título', $collection . '_intro_text' => 'Texto']);
        check($db->query("SELECT setting_value FROM site_settings WHERE setting_key='content." . $collection . "'")->fetchColumn() === '[]', 'Lista vacía JSON preservada: ' . $collection);
        [$status, $html] = request('/admin/?tab=' . $collection);
        check($status === 200 && strpos($html, 'repeat-card') === false, 'Panel respeta lista vacía: ' . $collection);
    }
    request('/admin/logout.php');
    [$status] = request('/admin/index.php');
    check($status === 302, 'Cierre de sesión');
    echo $checks . ' comprobaciones completadas.' . PHP_EOL;
} finally {
    proc_terminate($server);
    proc_close($server);
}
