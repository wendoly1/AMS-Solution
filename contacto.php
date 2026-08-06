<?php
require 'includes/database.php';

$mensaje = '';
$exito = false;

$areas = ['Derecho Corporativo', 'Derecho Laboral', 'Derecho Penal', 'Derecho Civil', 'Familia y Sucesiones', 'Propiedad Intelectual'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $area     = trim($_POST['area'] ?? '');
    $consulta = trim($_POST['consulta'] ?? '');

    if ($nombre === '' || $email === '' || $consulta === '') {
        $mensaje = 'Por favor completa los campos obligatorios (nombre, correo y consulta).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'El correo electrónico no es válido.';
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO contactos (nombre, email, telefono, area, consulta, creado_en)
             VALUES (:nombre, :email, :telefono, :area, :consulta, NOW())"
        );
        $stmt->execute([
            ':nombre'   => $nombre,
            ':email'    => $email,
            ':telefono' => $telefono,
            ':area'     => $area !== '' ? $area : null,
            ':consulta' => $consulta,
        ]);
        $exito = true;
        $mensaje = '¡Gracias! Hemos recibido tu solicitud y te contactaremos pronto.';
    }
}

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
                    <span class="info-value">contacto@amslegalsolutions.hn</span>
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
                <div class="form-field">
                    <label for="nombre">Nombre completo</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label for="email">Correo</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-field">
                        <label for="telefono">Teléfono</label>
                        <input type="tel" id="telefono" name="telefono">
                    </div>
                </div>

                <div class="form-field">
                    <label for="area">Área de interés</label>
                    <select id="area" name="area">
                        <option value="" selected>Selecciona un área</option>
                        <?php foreach ($areas as $opcion): ?>
                            <option value="<?= htmlspecialchars($opcion) ?>"><?= htmlspecialchars($opcion) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label for="consulta">Cuéntanos tu caso</label>
                    <textarea id="consulta" name="consulta" rows="5" required></textarea>
                </div>

                <button type="submit" class="btn btn-dark btn-block">Enviar solicitud</button>
            </form>
        </div>
    </div>
</section>

<?php require 'includes/footer.php'; ?>
