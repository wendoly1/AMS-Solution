<?php
require_once __DIR__ . '/includes/database.php';
require 'includes/contact_handler.php';

require 'includes/header.php';
?>

<section class="hero">
    <div class="hero-inner container">
        <span class="hero-ring r1" aria-hidden="true"></span>
        <span class="hero-ring r2" aria-hidden="true"></span>
        <div class="hero-grid reveal-group" data-stagger="0.22">
            <?php
            $heroTitle = site_setting('home.hero_title', 'Estrategia legal a la altura de tus decisiones más importantes.');
            $isOriginalHeroTitle = preg_replace('/\s+/u', ' ', trim($heroTitle)) === 'Estrategia legal a la altura de tus decisiones más importantes.';
            ?>
            <h1><?php if ($isOriginalHeroTitle): ?><span class="hero-title-line">Estrategia legal</span> <span class="hero-title-line">a la altura de tus</span> <span class="hero-title-line"><span class="accent-gold">decisiones</span> <em>más importantes.</em></span><?php else: ?><?= nl2br(site_e($heroTitle)) ?><?php endif; ?></h1>
            <div class="hero-cta-row">
                <p><?= site_e(site_setting('home.hero_description', 'Asesoría jurídica integral para empresas y personas en Honduras. Unimos rigor técnico, discreción y un trato cercano para proteger lo que más importa.')) ?></p>
                <div class="hero-actions">
                    <a href="<?= site_e(site_button_url('home.primary_url', '#contacto')) ?>" class="btn btn-light"><?= site_e(site_setting('home.primary_label', 'Solicitar consulta →')) ?></a>
                    <a href="<?= site_e(site_button_url('home.secondary_url', '#areas')) ?>" class="btn btn-outline-light"><?= site_e(site_setting('home.secondary_label', 'Ver áreas')) ?></a>
                </div>
            </div>
        </div>
    </div>
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

<section class="bg-dark practice-section" id="areas">
    <div class="container">
        <div class="practice-header reveal">
            <div>
                <span class="overline">Áreas de práctica</span>
                <h2><?= site_e(site_setting('home.services_title', 'Especialidades de la firma')) ?></h2>
            </div>
            <p><?= site_e(site_setting('home.services_text', 'Seis áreas de práctica con equipos dedicados, cada una respaldada por años de experiencia ante tribunales hondureños.')) ?></p>
        </div>
        <div class="practice-list reveal-group">
            <?php foreach (site_services() as $index => $service): ?>
            <a href="servicios.php#<?= site_e($service['slug']) ?>" class="practice-row">
                <span class="practice-num"><?= sprintf('%02d', $index + 1) ?></span>
                <span class="practice-name"><?= site_e($service['name']) ?></span>
                <span class="practice-desc"><?= site_e($service['summary']) ?></span>
                <span class="practice-arrow">→</span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="about-section" id="firma">
    <div class="container about-grid reveal-group">
        <div class="about-photo">
            <img src="<?= site_e(site_media('site.office_image', 'img/ams-office-reception.jpg')) ?>" alt="Recepción de las oficinas de AMS Legal">
        
        </div>
        <div class="about-text">
            <span class="overline">Sobre la firma</span>
            <h2><?= site_e(site_setting('home.about_title', 'Un despacho construido sobre la confianza y los resultados.')) ?></h2>
            <p><?= site_e(site_setting('home.about_p1', 'AMS Legal Solutions nace en Tegucigalpa con una convicción: la excelencia jurídica también se mide por la cercanía. Acompañamos a empresas y familias en cada decisión, con una mirada estratégica y profundamente ética.')) ?></p>
            <p><?= site_e(site_setting('home.about_p2', 'Nuestro equipo multidisciplinario combina experiencia litigiosa, visión de negocio y un compromiso real con cada cliente.')) ?></p>
            <div class="values-trio">
                <div class="values-trio-item">
                    <div class="num">Ética</div>
                    <div class="label">Por principio</div>
                </div>
                <div class="values-trio-item">
                    <div class="num">Rigor</div>
                    <div class="label">En cada caso</div>
                </div>
                <div class="values-trio-item">
                    <div class="num">Cercanía</div>
                    <div class="label">Con cada cliente</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-dark">
    <div class="container">
        <div class="team-section-header reveal">
            <div>
                <span class="overline">El equipo</span>
                <h2><?= site_e(site_setting('home.team_title', 'Abogados que te representan')) ?></h2>
            </div>
            <a href="equipo.php" class="view-all">Ver todo el equipo</a>
        </div>
        <div class="team-grid reveal-group">
            <?php foreach (site_team() as $member): ?>
            <a href="equipo.php#<?= site_e($member['slug']) ?>" class="team-card team-card-link">
                <div class="team-photo-wrap"><img src="<?= site_e(site_media_url($member['image'])) ?>" alt="<?= site_e($member['name']) ?>"></div>
                <h3><?= site_e($member['name']) ?></h3>
                <p class="team-role"><?= site_e($member['role']) ?></p>
                <p class="team-area"><?= site_e($member['area']) ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="contact-section" id="contacto">
    <div class="container contact-grid reveal-group">
        <div class="contact-intro">
            <span class="overline">Contacto</span>
            <h1><?= site_e(site_setting('contact.heading', 'Agenda tu consulta inicial.')) ?></h1>
            <p><?= site_e(site_setting('contact.intro', 'Cuéntanos tu caso. Te responderemos con discreción y agendaremos una primera reunión sin compromiso.')) ?></p>

            <div class="contact-info-list">
                <div class="contact-info-item">
                    <span class="info-label">Oficina</span>
                    <span class="info-value"><?= site_e(site_setting('contact.address', 'Boulevard Morazán, Tegucigalpa, Honduras')) ?></span>
                </div>
                <div class="contact-info-item">
                    <span class="info-label">Teléfono</span>
                    <span class="info-value"><?= site_e(site_setting('contact.phone', '+504 0000 0000')) ?></span>
                </div>
                <div class="contact-info-item">
                    <span class="info-label">Correo</span>
                    <?php $contactEmail = site_setting('contact.email', 'gmoncada@amslegalsolutions.com'); ?>
                    <a class="info-value" href="mailto:<?= site_e($contactEmail) ?>"><?= site_e($contactEmail) ?></a>
                </div>
                <div class="contact-info-item">
                    <span class="info-label">Horario</span>
                    <span class="info-value"><?= site_e(site_setting('contact.hours', 'Lunes a viernes · 8:00 – 17:00')) ?></span>
                </div>
                <?php foreach (site_contact_links() as $label => $url): ?><div class="contact-info-item"><span class="info-label"><?= site_e($label) ?></span><a class="info-value" href="<?= site_e($url) ?>" target="_blank" rel="noopener noreferrer"><?= site_e($label) ?> ↗</a></div><?php endforeach; ?>
            </div>
        </div>

        <div class="contact-card">
            <?php if ($mensaje): ?>
                <div class="alert <?= $exito ? 'alert-success' : 'alert-error' ?>">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php#contacto" class="contact-form">
                <div class="form-honeypot" aria-hidden="true">
                    <label for="sitio_web_inicio">Sitio web</label>
                    <input type="text" id="sitio_web_inicio" name="sitio_web" tabindex="-1" autocomplete="off">
                </div>
                <div class="form-field">
                    <label for="nombre">Nombre completo</label>
                    <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($formData['nombre'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label for="email">Correo</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($formData['email'], ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="form-field">
                        <label for="telefono">Teléfono</label>
                        <input type="tel" id="telefono" name="telefono" value="<?= htmlspecialchars($formData['telefono'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="form-field">
                    <label for="area">Área de interés</label>
                    <select id="area" name="area">
                        <option value="" <?= $formData['area'] === '' ? 'selected' : '' ?>>Selecciona un área</option>
                        <?php foreach ($areas as $opcion): ?>
                            <option value="<?= htmlspecialchars($opcion) ?>" <?= $formData['area'] === $opcion ? 'selected' : '' ?>><?= htmlspecialchars($opcion) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label for="consulta">Cuéntanos tu caso</label>
                    <textarea id="consulta" name="consulta" rows="5" required><?= htmlspecialchars($formData['consulta'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <button type="submit" class="btn btn-dark btn-block">Enviar solicitud</button>
            </form>
        </div>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
