<?php
require 'includes/database.php';
require 'includes/contact_handler.php';

require 'includes/header.php';
?>

<section class="contact-section booking-section">
    <div class="container contact-grid booking-grid">
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

        <div class="contact-card booking-card">
            <?php if ($mensaje): ?>
                <div class="alert <?= $exito ? 'alert-success' : 'alert-error' ?>">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="contact-form">
                <div class="form-honeypot" aria-hidden="true">
                    <label for="sitio_web_contacto">Sitio web</label>
                    <input type="text" id="sitio_web_contacto" name="sitio_web" tabindex="-1" autocomplete="off">
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
