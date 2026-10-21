<?php require 'includes/header.php'; ?>

<section class="bg-dark firma-hero">
    <span class="firma-hero-ring" aria-hidden="true"></span>
    <div class="container">
        <span class="overline">Sobre la firma</span>
        <h1><?= site_e(site_setting('firm.hero_title', 'Un despacho construido sobre la confianza y los resultados.')) ?></h1>
        <p><?= site_e(site_setting('firm.hero_text', 'Desde 2008, AMS Legal Solutions acompaña a empresas y familias en Tegucigalpa con una convicción clara: la excelencia jurídica también se mide por la cercanía.')) ?></p>
    </div>
</section>

<section class="bg-dark firma-history">
    <div class="container about-grid">
        <div class="firma-history-photo">
            <img src="<?= site_e(site_media('site.office_image', 'img/ams-office-reception.jpg')) ?>" alt="Recepción de las oficinas de AMS Legal">
        </div>
        <div class="firma-history-text">
            <span class="overline">Nuestra historia</span>
            <h2><?= site_e(site_setting('firm.history_title', 'Diecisiete años defendiendo lo que importa.')) ?></h2>
            <p><?= site_e(site_setting('firm.history_p1', 'AMS Legal Solutions nació con un grupo reducido de abogados decididos a ofrecer un servicio jurídico distinto: técnico, cercano y con visión de largo plazo. Hoy somos un equipo multidisciplinario que representa a empresas nacionales, inversionistas extranjeros y familias hondureñas.')) ?></p>
            <p><?= site_e(site_setting('firm.history_p2', 'Cada caso —grande o pequeño— recibe la misma exigencia: entender a fondo, actuar con estrategia y comunicar con claridad en cada paso del proceso.')) ?></p>
        </div>
    </div>
</section>

<?php
$firmValues = [];
$firmValueDefaults = [
    ['Ética', 'La integridad guía cada consejo y cada actuación ante tribunales.'],
    ['Rigor', 'Análisis profundo y estrategia clara en cada asunto que tomamos.'],
    ['Cercanía', 'Comunicación directa y disponibilidad real con cada cliente.'],
    ['Resultados', 'Medimos nuestro éxito por el impacto real en quienes confían en nosotros.'],
];
foreach ($firmValueDefaults as $valueIndex => [$defaultName, $defaultDescription]) {
    $number = $valueIndex + 1;
    $firmValues[] = [
        'name' => site_setting('firm.value_' . $number . '_name', $defaultName),
        'description' => site_setting('firm.value_' . $number . '_description', $defaultDescription),
    ];
}
?>
<section class="bg-dark values-section">
    <div class="container">
        <span class="overline">Nuestros valores</span>
        <h2><?= site_e(site_setting('firm.values_title', 'Lo que guía cada decisión que tomamos')) ?></h2>
        <div class="values-lattice">
            <?php foreach ($firmValues as $valueIndex => $value): ?>
            <div class="value-cell">
                <div class="num"><?= sprintf('%02d', $valueIndex + 1) ?></div>
                <div class="title"><?= site_e($value['name']) ?></div>
                <p><?= site_e($value['description']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="bg-dark firma-stats-section" id="firma-stats">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-block">
                <span class="stat-number" data-count="<?= site_e(site_setting('stats.years', '16')) ?>" data-suffix="+">0+</span>
                <span class="stat-label">Años de ejercicio</span>
            </div>
            <div class="stat-block">
                <span class="stat-number" data-count="<?= site_e(site_setting('stats.cases', '900')) ?>" data-suffix="+">0+</span>
                <span class="stat-label">Casos resueltos</span>
            </div>
            <div class="stat-block">
                <span class="stat-number" data-count="<?= site_e(site_setting('stats.lawyers', '12')) ?>" data-suffix="">0</span>
                <span class="stat-label">Abogados expertos</span>
            </div>
            <div class="stat-block">
                <span class="stat-number" data-count="<?= site_e(site_setting('stats.satisfaction', '98')) ?>" data-suffix="%">0%</span>
                <span class="stat-label">Clientes satisfechos</span>
            </div>
        </div>
    </div>
</section>

<section class="bg-cream">
    <div class="firma-quote-wrap">
        <div class="firma-quote-text">&ldquo;<?= site_e(site_setting('firm.quote', 'Creemos que la excelencia jurídica se mide tanto por el resultado como por la confianza con que acompañamos cada decisión de nuestros clientes.')) ?>&rdquo;</div>
        <div class="firma-quote-attribution">— <?= site_e(site_setting('firm.quote_author', 'Socios fundadores, AMS Legal Solutions')) ?></div>
    </div>
</section>

<section class="bg-dark firma-cta-final">
    <div class="container firma-cta-row">
        <div>
            <h2>¿Quieres conocer a nuestro equipo?</h2>
            <p>Conoce a los abogados detrás de cada caso.</p>
        </div>
        <a href="equipo.php" class="btn btn-light">Ver abogados →</a>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
