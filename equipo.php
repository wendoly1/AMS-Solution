<?php require 'includes/header.php'; ?>
<?php $team = site_team(); ?>

<section class="bg-dark firma-hero team-hero">
    <span class="firma-hero-ring" aria-hidden="true"></span>
    <div class="container">
        <span class="overline">El equipo</span>
        <h1><?= site_e(site_setting('team.intro_title', 'Abogados que te representan.')) ?></h1>
        <p><?= site_e(site_setting('team.intro_text', 'Un equipo multidisciplinario con experiencia acreditada ante tribunales, entidades regulatorias y contrapartes en Honduras y la región.')) ?></p>
    </div>
</section>

<section class="bg-dark team-grid-page">
    <div class="container team-grid team-grid-bios">
        <?php foreach ($team as $member): ?>
        <div class="team-card" id="<?= site_e($member['slug']) ?>">
            <div class="team-photo-wrap"><img src="<?= site_e(site_media_url($member['image'])) ?>" alt="<?= site_e($member['name']) ?>"></div>
            <h3><?= site_e($member['name']) ?></h3>
            <p class="team-role"><?= site_e($member['role']) ?></p>
            <p class="team-area"><?= site_e($member['area']) ?></p>
            <p class="team-bio"><?= site_e($member['bio']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="bg-dark firma-cta-final border-top-hairline">
    <div class="container firma-cta-row">
        <div><h2>¿Necesitas hablar con un abogado?</h2><p>Agenda una consulta inicial sin compromiso.</p></div>
        <a href="contacto.php" class="btn btn-light">Agendar cita →</a>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
