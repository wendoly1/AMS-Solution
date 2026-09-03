<?php
require 'includes/database.php';
require 'includes/contact_handler.php';

require 'includes/header.php';
?>

<section class="hero">
    <div class="hero-inner container">
        <span class="hero-ring r1" aria-hidden="true"></span>
        <span class="hero-ring r2" aria-hidden="true"></span>
        <div class="hero-grid reveal-group" data-stagger="0.22">
            <h1>Estrategia legal<br>a la altura de tus<br><span class="accent-gold">decisiones</span><em> más importantes.</em></h1>
            <div class="hero-cta-row">
                <p>Asesoría jurídica integral para empresas y personas en Honduras. Unimos rigor técnico, discreción y un trato cercano para proteger lo que más importa.</p>
                <div class="hero-actions">
                    <a href="#contacto" class="btn btn-light">Solicitar consulta →</a>
                    <a href="#areas" class="btn btn-outline-light">Ver áreas</a>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="stats-grid">
            <div class="stat-block">
                <span class="stat-number" data-count="16" data-suffix="+">0+</span>
                <span class="stat-label">Años de ejercicio</span>
            </div>
            <div class="stat-block">
                <span class="stat-number" data-count="900" data-suffix="+">0+</span>
                <span class="stat-label">Casos resueltos</span>
            </div>
            <div class="stat-block">
                <span class="stat-number" data-count="12" data-suffix="">0</span>
                <span class="stat-label">Abogados expertos</span>
            </div>
            <div class="stat-block">
                <span class="stat-number" data-count="98" data-suffix="%">0%</span>
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
                <h2>Especialidades de la firma</h2>
            </div>
            <p>Seis áreas de práctica con equipos dedicados, cada una respaldada por años de experiencia ante tribunales hondureños.</p>
        </div>
        <div class="practice-list reveal-group">
            <a href="servicios.php#corporativo" class="practice-row">
                <span class="practice-num">01</span>
                <span class="practice-name">Derecho Corporativo</span>
                <span class="practice-desc">Constitución de sociedades, gobernanza, fusiones y contratos mercantiles.</span>
                <span class="practice-arrow">→</span>
            </a>
            <a href="servicios.php#laboral" class="practice-row">
                <span class="practice-num">02</span>
                <span class="practice-name">Derecho Laboral</span>
                <span class="practice-desc">Relaciones laborales, conflictos, cumplimiento y negociación colectiva.</span>
                <span class="practice-arrow">→</span>
            </a>
            <a href="servicios.php#penal" class="practice-row">
                <span class="practice-num">03</span>
                <span class="practice-name">Derecho Penal</span>
                <span class="practice-desc">Defensa técnica, litigio estratégico y representación judicial.</span>
                <span class="practice-arrow">→</span>
            </a>
            <a href="servicios.php#civil" class="practice-row">
                <span class="practice-num">04</span>
                <span class="practice-name">Derecho Civil</span>
                <span class="practice-desc">Contratos, obligaciones, bienes y responsabilidad civil.</span>
                <span class="practice-arrow">→</span>
            </a>
            <a href="servicios.php#familia" class="practice-row">
                <span class="practice-num">05</span>
                <span class="practice-name">Familia y Sucesiones</span>
                <span class="practice-desc">Divorcios, custodia, herencias y régimen patrimonial.</span>
                <span class="practice-arrow">→</span>
            </a>
            <a href="servicios.php#propiedad-intelectual" class="practice-row">
                <span class="practice-num">06</span>
                <span class="practice-name">Propiedad Intelectual</span>
                <span class="practice-desc">Registro de marcas, patentes y protección de activos intangibles.</span>
                <span class="practice-arrow">→</span>
            </a>
        </div>
    </div>
</section>

<section class="about-section" id="firma">
    <div class="container about-grid reveal-group">
        <div class="about-photo">
            <img src="img/ams-office-reception.jpg" alt="Recepción de las oficinas de AMS Legal">
        
        </div>
        <div class="about-text">
            <span class="overline">Sobre la firma</span>
            <h2>Un despacho construido sobre la confianza y los resultados.</h2>
            <p>AMS Legal Solutions nace en Tegucigalpa con una convicción: la excelencia jurídica también se mide por la cercanía. Acompañamos a empresas y familias en cada decisión, con una mirada estratégica y profundamente ética.</p>
            <p>Nuestro equipo multidisciplinario combina experiencia litigiosa, visión de negocio y un compromiso real con cada cliente.</p>
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
                <h2>Abogados que te representan</h2>
            </div>
            <a href="equipo.php" class="view-all">Ver todo el equipo</a>
        </div>
        <div class="team-grid reveal-group">
            <a href="equipo.php#martinez" class="team-card team-card-link">
                <div class="team-photo-wrap"><img src="img/abogado-martinez.jpg" alt="A. Martínez"></div>
                <h3>A. Martínez</h3>
                <p class="team-role">Socio fundador</p>
                <p class="team-area">Corporativo y Mercantil</p>
            </a>
            <a href="equipo.php#andino" class="team-card team-card-link">
                <div class="team-photo-wrap"><img src="img/abogado-andino.jpg" alt="S. Andino"></div>
                <h3>S. Andino</h3>
                <p class="team-role">Socia</p>
                <p class="team-area">Laboral y Litigio</p>
            </a>
            <a href="equipo.php#calix" class="team-card team-card-link">
                <div class="team-photo-wrap"><img src="img/abogado-calix.jpg" alt="R. Cálix"></div>
                <h3>R. Cálix</h3>
                <p class="team-role">Asociado senior</p>
                <p class="team-area">Penal y Procesal</p>
            </a>
        </div>
    </div>
</section>

<section class="contact-section" id="contacto">
    <div class="container contact-grid reveal-group">
        <div class="contact-intro">
            <span class="overline">Contacto</span>
            <h1>Agenda tu<br>consulta inicial.</h1>
            <p>Cuéntanos tu caso. Te responderemos con discreción y agendaremos una primera reunión sin compromiso.</p>

            <div class="contact-info-list">
                <div class="contact-info-item">
                    <span class="info-label">Oficina</span>
                    <span class="info-value">Boulevard Morazán, Tegucigalpa, Honduras</span>
                </div>
                <div class="contact-info-item">
                    <span class="info-label">Teléfono</span>
                    <span class="info-value">+504 0000 0000</span>
                </div>
                <div class="contact-info-item">
                    <span class="info-label">Correo</span>
                    <a class="info-value" href="mailto:gmoncada@amslegalsolutions.com">gmoncada@amslegalsolutions.com</a>
                </div>
                <div class="contact-info-item">
                    <span class="info-label">Horario</span>
                    <span class="info-value">Lunes a viernes · 8:00 – 17:00</span>
                </div>
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
