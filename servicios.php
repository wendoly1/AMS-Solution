<?php require 'includes/header.php'; ?>
<?php $services = site_services(); ?>

<section class="bg-dark areas-intro" id="top">
    <div class="container">
        <span class="overline">Áreas de práctica</span>
        <h1><?= site_e(site_setting('services.intro_title', 'Seis especialidades, un mismo estándar de exigencia.')) ?></h1>
        <p><?= site_e(site_setting('services.intro_text', 'Cada área cuenta con un equipo dedicado y experiencia acreditada ante tribunales, entidades regulatorias y contrapartes en Honduras y la región.')) ?></p>
        <div class="area-pills">
            <?php foreach ($services as $service): ?>
                <a href="#<?= site_e($service['slug']) ?>"><?= site_e($service['name']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php foreach ($services as $index => $service): ?>
<section class="bg-dark area-block" id="<?= site_e($service['slug']) ?>">
    <div class="container area-block-inner">
        <div><span class="area-num"><?= sprintf('%02d', $index + 1) ?></span></div>
        <div class="area-content">
            <h2><?= site_e($service['name']) ?></h2>
            <p><?= site_e($service['description']) ?></p>
            <div class="area-columns">
                <div>
                    <div class="area-list-label">Servicios que ofrecemos</div>
                    <?php foreach (($service['services'] ?? []) as $item): ?><div class="area-list-item"><?= site_e($item) ?></div><?php endforeach; ?>
                </div>
                <div>
                    <div class="area-list-label">Cómo trabajamos</div>
                    <?php foreach (($service['steps'] ?? []) as $stepIndex => $step): ?><div class="area-list-item"><span class="step"><?= sprintf('%02d', $stepIndex + 1) ?></span><?= site_e($step) ?></div><?php endforeach; ?>
                </div>
            </div>
            <div class="area-cta-row"><a href="contacto.php" class="area-consult-btn">Consultar sobre <?= site_e($service['name']) ?></a></div>
        </div>
    </div>
</section>
<?php endforeach; ?>

<section class="areas-final-cta">
    <div class="container">
        <span class="overline">Consulta inicial</span>
        <h2>¿No sabes qué área corresponde a tu caso?</h2>
        <p>Cuéntanos brevemente tu situación y te orientaremos hacia el equipo adecuado.</p>
        <a href="contacto.php" class="btn btn-dark">Agendar consulta</a>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
