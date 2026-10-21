<?php
if (!isset($sections, $tab) || !function_exists('site_setting')) { http_response_code(404); exit; }
$contentGroups = [
    'general' => [
        ['Identidad de la firma', 'Nombre y ubicación que identifican a AMS en el encabezado y el pie del sitio.', ['site.name', 'site.location', 'site.url']],
        ['Datos de contacto', 'Aparecen en el contacto de Inicio, en la página Contacto y en el pie del sitio.', ['contact.address', 'contact.phone', 'contact.email', 'contact.hours']],
        ['Presentación del contacto', 'El título y el párrafo que acompañan al formulario de consulta en Inicio y Contacto.', ['contact.heading', 'contact.intro']],
        ['Pie de página', 'El texto breve de presentación que aparece al final de todas las páginas.', ['site.footer_description']],
        ['WhatsApp y ubicación', 'Enlaces de contacto que aparecen en Inicio, Contacto y el pie. Deja un campo vacío para ocultarlo.', ['contact.whatsapp', 'contact.map_url']],
        ['Redes sociales', 'Se muestran en Inicio, Contacto y el pie del sitio. Deja vacías las redes que no quieras mostrar.', ['social.facebook', 'social.instagram', 'social.linkedin']],
        ['Botón del encabezado', 'El botón de contacto que aparece arriba en todas las páginas y en el menú móvil.', ['header.cta_label', 'header.cta_url']],
    ],
    'home' => [
        ['Portada principal', 'Es el primer bloque de Inicio: el título grande y el texto que aparece debajo.', ['home.hero_title', 'home.hero_description']],
        ['Botones de la portada', 'Los dos botones bajo la presentación de Inicio. Puedes cambiar su texto y su destino.', ['home.primary_label', 'home.primary_url', 'home.secondary_label', 'home.secondary_url']],
        ['Cifras de la firma', 'Los cuatro números destacados que aparecen en Inicio y en la página La firma.', ['stats.years', 'stats.cases', 'stats.lawyers', 'stats.satisfaction']],
        ['Presentación de las áreas legales', 'El encabezado de la lista de especialidades en Inicio. Los servicios se editan en Áreas legales.', ['home.services_title', 'home.services_text']],
        ['Resumen sobre la firma', 'El título y los dos párrafos junto a la fotografía de la oficina en Inicio.', ['home.about_title', 'home.about_p1', 'home.about_p2']],
        ['Presentación del equipo', 'El título sobre las tarjetas de abogados de Inicio. Los perfiles se editan en Abogados.', ['home.team_title']],
    ],
    'firm' => [
        ['Portada de La firma', 'El título y el párrafo que abren la página La firma.', ['firm.hero_title', 'firm.hero_text']],
        ['Nuestra historia', 'El bloque de texto que aparece junto a la fotografía de la oficina en La firma.', ['firm.history_title', 'firm.history_p1', 'firm.history_p2']],
        ['Encabezado de valores', 'El título que presenta los cuatro valores de la firma.', ['firm.values_title']],
        ['Valor 1', 'Primer valor de la página La firma: su nombre y su explicación.', ['firm.value_1_name', 'firm.value_1_description']],
        ['Valor 2', 'Segundo valor de la página La firma: su nombre y su explicación.', ['firm.value_2_name', 'firm.value_2_description']],
        ['Valor 3', 'Tercer valor de la página La firma: su nombre y su explicación.', ['firm.value_3_name', 'firm.value_3_description']],
        ['Valor 4', 'Cuarto valor de la página La firma: su nombre y su explicación.', ['firm.value_4_name', 'firm.value_4_description']],
        ['Frase institucional', 'La cita destacada y su autor, cerca del final de la página La firma.', ['firm.quote', 'firm.quote_author']],
    ],
];
$fieldHelp = [
    'site.name' => 'Nombre público de la firma.',
    'site.location' => 'Una ubicación corta, por ejemplo: Tegucigalpa · Honduras.',
    'site.url' => 'Dirección principal del sitio para buscadores. No es un texto visible de la portada.',
    'contact.email' => 'Correo visible para tus clientes y destinatario de las solicitudes del formulario.',
    'contact.whatsapp' => 'Ejemplo: +504 9999 9999. Incluye el código del país; el sitio crea el enlace a WhatsApp.',
    'contact.map_url' => 'Abre la ubicación en Google Maps, pulsa Compartir y pega aquí el enlace completo.',
    'social.facebook' => 'Ejemplo: https://www.facebook.com/nombre-de-tu-firma',
    'social.instagram' => 'Ejemplo: https://www.instagram.com/nombre-de-tu-firma/',
    'social.linkedin' => 'Ejemplo: https://www.linkedin.com/company/nombre-de-tu-firma/',
    'header.cta_url' => 'Ejemplo: contacto.php o https://wa.me/50499999999.',
    'home.primary_url' => 'Ejemplo: #contacto para el formulario de Inicio, o contacto.php para la página Contacto.',
    'home.secondary_url' => 'Ejemplo: #areas para las especialidades de Inicio, o servicios.php para la página completa.',
    'contact.heading' => 'Ejemplo: Agenda tu consulta inicial.',
    'contact.intro' => 'Invita al visitante a completar el formulario que aparece al lado.',
    'home.hero_title' => 'Título grande de la portada. Puedes usar saltos de línea para separar las frases.',
    'home.hero_description' => 'El párrafo de presentación que acompaña al título de portada.',
    'home.about_p1' => 'Primer párrafo del resumen en Inicio.',
    'home.about_p2' => 'Segundo párrafo del mismo resumen.',
    'home.team_title' => 'Solo cambia el título del bloque; para agregar o quitar perfiles, entra en Abogados.',
    'stats.years' => 'Cifra de años de ejercicio. El sitio añade el signo +.',
    'stats.cases' => 'Cifra de casos resueltos. El sitio añade el signo +.',
    'stats.lawyers' => 'Cifra destacada de abogados. Se edita por separado de la lista de perfiles.',
    'stats.satisfaction' => 'Porcentaje de satisfacción, de 0 a 100. El sitio añade el signo %.',
    'firm.history_p1' => 'Primer párrafo de la historia completa de la firma.',
    'firm.history_p2' => 'Segundo párrafo de la historia completa de la firma.',
    'firm.quote' => 'Escribe la frase sin añadir comillas; el sitio las muestra automáticamente.',
    'firm.quote_author' => 'Nombre de la persona o del equipo al que se atribuye la frase.',
];
$groups = $contentGroups[$tab] ?? [];
// Conserva visibles también los campos que se incorporen en el panel más adelante.
$mapped = [];
foreach ($groups as $group) { $mapped = array_merge($mapped, $group[2]); }
$remaining = array_values(array_diff(array_keys($sections[$tab]['fields']), $mapped));
if ($remaining) { $groups[] = ['Otros ajustes', 'Campos adicionales de esta sección.', $remaining]; }
?>
<nav class="section-jumps" aria-label="Bloques de esta sección">
<?php foreach ($groups as $groupIndex => [$groupTitle]): ?>
    <a href="#content-group-<?= $groupIndex ?>"><?= site_e($groupTitle) ?></a>
<?php endforeach; ?>
</nav>
<div class="content-group-list">
<?php foreach ($groups as $groupIndex => [$groupTitle, $groupDescription, $keys]): ?>
    <fieldset class="content-group" id="content-group-<?= $groupIndex ?>">
        <legend><span class="group-number" aria-hidden="true"><?= sprintf('%02d', $groupIndex + 1) ?></span><?= site_e($groupTitle) ?></legend>
        <p class="group-description"><?= site_e($groupDescription) ?></p>
        <div class="content-fields">
        <?php foreach ($keys as $key):
            [$label, $default, $type] = $sections[$tab]['fields'][$key];
            $value = site_setting($key, $default);
            $fieldId = 'setting-' . str_replace('.', '-', $key);
        ?>
            <div class="content-field<?= $type === 'textarea' ? ' content-field-wide' : '' ?>">
                <label for="<?= site_e($fieldId) ?>"><?= site_e($label) ?></label>
                <?php if ($type === 'textarea'): ?>
                <textarea id="<?= site_e($fieldId) ?>" name="settings[<?= site_e($key) ?>]"<?= isset($fieldHelp[$key]) ? ' aria-describedby="' . site_e($fieldId) . '-help"' : '' ?>><?= site_e($value) ?></textarea>
                <?php else: ?>
                <input id="<?= site_e($fieldId) ?>" type="<?= site_e($type === 'whatsapp' ? 'tel' : (in_array($type, ['link', 'button_link'], true) ? 'text' : $type)) ?>" name="settings[<?= site_e($key) ?>]" value="<?= site_e($value) ?>"<?= $type === 'number' ? ' required min="0"' : '' ?><?= $key === 'stats.satisfaction' ? ' max="100"' : '' ?><?= isset($fieldHelp[$key]) ? ' aria-describedby="' . site_e($fieldId) . '-help"' : '' ?>>
                <?php endif; ?>
                <?php if (isset($fieldHelp[$key])): ?><p class="field-description" id="<?= site_e($fieldId) ?>-help"><?= site_e($fieldHelp[$key]) ?></p><?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    </fieldset>
<?php endforeach; ?>
</div>
