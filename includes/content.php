<?php

function cms_load_settings(): array
{
    global $pdo;
    static $loaded = false;
    static $settings = [];

    if ($loaded) {
        return $settings;
    }

    $loaded = true;
    if (!$pdo instanceof PDO) {
        return $settings;
    }

    try {
        $rows = $pdo->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll();
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Throwable $error) {
        // La tabla se crea desde /admin. Mientras tanto se usan los valores actuales.
        error_log('No se pudo leer site_settings: ' . $error->getMessage());
    }

    return $settings;
}

function site_setting(string $key, string $default = ''): string
{
    if (isset($GLOBALS['cms_preview_settings']) && array_key_exists($key, $GLOBALS['cms_preview_settings'])) {
        return $GLOBALS['cms_preview_settings'][$key];
    }
    $settings = cms_load_settings();
    return array_key_exists($key, $settings) ? $settings[$key] : $default;
}

function site_json_setting(string $key, array $default): array
{
    $value = site_setting($key, '');
    if ($value === '') {
        return $default;
    }

    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : $default;
}

function site_e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function site_lines(string $value): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/', $value) ?: []), static fn ($line) => $line !== ''));
}

// Las rutas guardadas son públicas: uploads/branding/archivo.png, no rutas del disco.
function site_media_url(string $value, string $default = ''): string
{
    if (!empty($GLOBALS['cms_preview_active']) && in_array($value, $GLOBALS['cms_preview_images'] ?? [], true)) return $value;
    $value = trim($value);
    if ($value === '' || preg_match('/[\x00-\x20\\\\]/', $value)) {
        return $default;
    }
    if (preg_match('~^https?://~i', $value)) {
        return filter_var($value, FILTER_VALIDATE_URL) ? $value : $default;
    }
    if (str_contains($value, ':') || str_starts_with($value, '//') || preg_match('~(^|/)\.\.?(/|$)~', $value)) {
        return $default;
    }
    return $value;
}

function site_external_link(string $value): string
{
    $value = trim($value);
    $parts = parse_url($value);
    return filter_var($value, FILTER_VALIDATE_URL) && is_array($parts)
        && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
        && !isset($parts['user']) && !isset($parts['pass']) ? $value : '';
}

function site_button_link(string $value): string
{
    $value = trim($value);
    if (site_external_link($value) !== '') return $value;
    return preg_match('/^(?:(?:index|servicios|equipo|contacto|sobre_la_firma)\.php(?:#[a-zA-Z0-9_-]+)?|#[a-zA-Z][a-zA-Z0-9_-]*)$/D', $value) ? $value : '';
}

function site_button_url(string $key, string $default): string
{
    return site_button_link(site_setting($key, $default)) ?: $default;
}

function site_contact_links(): array
{
    $links = [];
    $phone = preg_replace('/\D/', '', site_setting('contact.whatsapp', ''));
    if (strlen($phone) >= 8 && strlen($phone) <= 15) $links['WhatsApp'] = 'https://wa.me/' . $phone;
    foreach (['Cómo llegar' => 'contact.map_url', 'Facebook' => 'social.facebook', 'Instagram' => 'social.instagram', 'LinkedIn' => 'social.linkedin'] as $label => $key) {
        $url = site_external_link(site_setting($key, ''));
        if ($url !== '') $links[$label] = $url;
    }
    return $links;
}

function site_media(string $key, string $default): string
{
    return site_media_url(site_setting($key, $default), $default);
}

// Normaliza el contrato JSON del panel sin dejar índices ausentes en las vistas.
function site_collection(string $key, array $defaults, array $fields, array $listFields = []): array
{
    $raw = site_setting($key, '');
    if ($raw === '') {
        return $defaults;
    }
    $rows = json_decode($raw, true);
    if (!is_array($rows) || $rows !== array_values($rows) || !str_starts_with(ltrim($raw), '[')) {
        error_log('Colección inválida: ' . $key);
        return $defaults;
    }
    $items = [];
    $slugs = [];
    foreach ($rows as $row) {
        if (!is_array($row) || !is_string($row['slug'] ?? null) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $row['slug']) || isset($slugs[$row['slug']]) || !is_string($row['name'] ?? null) || trim($row['name']) === '') {
            error_log('Registro inválido: ' . $key);
            return $defaults;
        }
        $item = [];
        foreach ($fields as $field) {
            $item[$field] = is_string($row[$field] ?? null) ? $row[$field] : '';
        }
        foreach ($listFields as $field) {
            $item[$field] = is_array($row[$field] ?? null) ? array_values(array_filter($row[$field], 'is_string')) : [];
        }
        $slugs[$row['slug']] = true;
        $items[] = $item;
    }
    return $items;
}

function site_services(): array
{
    return site_collection('content.services', default_services(), ['slug', 'name', 'summary', 'description'], ['services', 'steps']);
}

function site_team(): array
{
    return site_collection('content.team', default_team(), ['slug', 'name', 'role', 'area', 'bio', 'image']);
}

function default_services(): array
{
    return [
        ['slug' => 'corporativo', 'name' => 'Derecho Corporativo', 'summary' => 'Constitución de sociedades, gobernanza, fusiones y contratos mercantiles.', 'description' => 'Acompañamos a empresas hondureñas y a inversionistas extranjeros en cada etapa de su ciclo de vida corporativo: constitución, gobernanza, financiamiento, fusiones y adquisiciones. Nuestro objetivo es que cada decisión de negocio esté respaldada por una estructura legal sólida y anticipada al riesgo.', 'services' => ['Constitución de sociedades y sucursales', 'Gobernanza corporativa y actas', 'Fusiones, adquisiciones y due diligence', 'Contratos mercantiles y de inversión'], 'steps' => ['Diagnóstico legal del negocio y sus riesgos.', 'Diseño de estructura societaria a medida.', 'Acompañamiento continuo ante cambios regulatorios.']],
        ['slug' => 'laboral', 'name' => 'Derecho Laboral', 'summary' => 'Relaciones laborales, conflictos, cumplimiento y negociación colectiva.', 'description' => 'Asesoramos a empleadores y trabajadores en el diseño de relaciones laborales sólidas, la resolución de conflictos y el cumplimiento normativo. Prevenimos litigios mediante políticas claras y representamos a nuestros clientes cuando el conflicto es inevitable.', 'services' => ['Contratos y reglamentos internos', 'Auditorías de cumplimiento laboral', 'Negociación colectiva', 'Litigio ante juzgados de trabajo'], 'steps' => ['Revisión preventiva de riesgos laborales.', 'Mediación antes de escalar a litigio.', 'Representación firme cuando es necesario.']],
        ['slug' => 'penal', 'name' => 'Derecho Penal', 'summary' => 'Defensa técnica, litigio estratégico y representación judicial.', 'description' => 'Defendemos con rigor técnico y discreción en procesos penales complejos, desde la etapa de investigación hasta el juicio oral. Priorizamos una estrategia clara desde el primer momento para proteger la libertad y reputación de nuestros clientes.', 'services' => ['Defensa en investigación preliminar', 'Representación en juicio oral', 'Delitos económicos y de cuello blanco', 'Recursos y apelaciones'], 'steps' => ['Análisis inmediato del expediente y riesgos.', 'Estrategia procesal definida desde el inicio.', 'Acompañamiento personal en cada audiencia.']],
        ['slug' => 'civil', 'name' => 'Derecho Civil', 'summary' => 'Contratos, obligaciones, bienes y responsabilidad civil.', 'description' => 'Resolvemos conflictos patrimoniales, contractuales y de responsabilidad civil con una combinación de negociación estratégica y litigio cuando es necesario, siempre buscando la solución más eficiente para nuestros clientes.', 'services' => ['Redacción y revisión de contratos', 'Responsabilidad civil y daños', 'Derechos reales y arrendamientos', 'Cobro judicial y extrajudicial'], 'steps' => ['Revisión exhaustiva de antecedentes.', 'Negociación directa cuando conviene al cliente.', 'Litigio decidido cuando no hay acuerdo posible.']],
        ['slug' => 'familia', 'name' => 'Familia y Sucesiones', 'summary' => 'Divorcios, custodia, herencias y régimen patrimonial.', 'description' => 'Acompañamos momentos personales sensibles —divorcios, custodias, herencias— con empatía y firmeza legal, protegiendo el patrimonio y el bienestar familiar de nuestros clientes con la máxima discreción.', 'services' => ['Divorcios y separaciones', 'Custodia y pensiones alimenticias', 'Sucesiones testadas e intestadas', 'Planificación patrimonial familiar'], 'steps' => ['Escucha atenta antes de cualquier estrategia.', 'Priorizar acuerdos que protejan a la familia.', 'Litigio solo cuando es la mejor vía.']],
        ['slug' => 'propiedad-intelectual', 'name' => 'Propiedad Intelectual', 'summary' => 'Registro de marcas, patentes y protección de activos intangibles.', 'description' => 'Protegemos los activos intangibles de empresas y creadores: marcas, patentes, derechos de autor y secretos comerciales, tanto en el registro como en la defensa frente a infracciones.', 'services' => ['Registro de marcas y patentes', 'Contratos de licencia y franquicia', 'Defensa ante infracciones', 'Protección de secretos comerciales'], 'steps' => ['Búsqueda y análisis de viabilidad de registro.', 'Gestión activa de la cartera de marcas.', 'Acción rápida frente a infracciones.']],
    ];
}

function default_team(): array
{
    return [
        ['slug' => 'martinez', 'name' => 'A. Martínez', 'role' => 'Socio fundador', 'area' => 'Corporativo y Mercantil', 'bio' => 'Más de 16 años asesorando empresas nacionales y extranjeras en constitución, gobernanza y fusiones.', 'image' => 'img/abogado-martinez.jpg'],
        ['slug' => 'andino', 'name' => 'S. Andino', 'role' => 'Socia', 'area' => 'Laboral y Litigio', 'bio' => 'Especialista en relaciones laborales y negociación colectiva, con amplia trayectoria en litigio.', 'image' => 'img/abogado-andino.jpg'],
        ['slug' => 'calix', 'name' => 'R. Cálix', 'role' => 'Asociado senior', 'area' => 'Penal y Procesal', 'bio' => 'Defensa técnica en procesos penales complejos, con enfoque estratégico desde la investigación.', 'image' => 'img/abogado-calix.jpg'],
    ];
}

