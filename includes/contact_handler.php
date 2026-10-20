<?php

require_once __DIR__ . '/content.php';
$contactRecipient = site_setting('contact.email', 'gmoncada@amslegalsolutions.com');

$areas = array_column(site_json_setting('content.services', default_services()), 'name');

$mensaje = '';
$exito = false;
$formData = [
    'nombre' => '',
    'email' => '',
    'telefono' => '',
    'area' => '',
    'consulta' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($formData as $campo => $valor) {
        $formData[$campo] = trim($_POST[$campo] ?? '');
    }

    $honeypot = trim($_POST['sitio_web'] ?? '');

    if ($honeypot !== '') {
        $exito = true;
        $mensaje = '¡Gracias! Hemos recibido tu solicitud y te contactaremos pronto.';
    } elseif ($formData['nombre'] === '' || $formData['email'] === '' || $formData['consulta'] === '') {
        $mensaje = 'Por favor completa los campos obligatorios (nombre, correo y consulta).';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $mensaje = 'El correo electrónico no es válido.';
    } elseif ($formData['area'] !== '' && !in_array($formData['area'], $areas, true)) {
        $mensaje = 'Selecciona un área de interés válida.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO contactos (nombre, email, telefono, area, consulta, creado_en)
                 VALUES (:nombre, :email, :telefono, :area, :consulta, NOW())'
            );
            $stmt->execute([
                ':nombre' => $formData['nombre'],
                ':email' => $formData['email'],
                ':telefono' => $formData['telefono'],
                ':area' => $formData['area'] !== '' ? $formData['area'] : null,
                ':consulta' => $formData['consulta'],
            ]);
        } catch (Throwable $error) {
            error_log('No se pudo guardar el contacto: ' . $error->getMessage());
        }

        $nombreSeguro = preg_replace('/[\r\n]+/', ' ', $formData['nombre']);
        $asunto = 'Nueva solicitud de contacto - AMS Legal Solutions';
        $cuerpo = implode("\r\n", [
            'Nueva solicitud recibida desde el sitio web',
            '',
            'Nombre: ' . $nombreSeguro,
            'Correo: ' . $formData['email'],
            'Teléfono: ' . ($formData['telefono'] !== '' ? $formData['telefono'] : 'No indicado'),
            'Área de interés: ' . ($formData['area'] !== '' ? $formData['area'] : 'No indicada'),
            '',
            'Consulta:',
            $formData['consulta'],
            '',
            'Enviado: ' . date('d/m/Y H:i'),
        ]);

        $cabeceras = implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: AMS Legal Solutions <' . $contactRecipient . '>',
            'Reply-To: ' . $formData['email'],
            'X-Mailer: PHP/' . phpversion(),
        ]);

        // XAMPP no incluye un servidor SMTP local. El operador @ evita que una
        // advertencia de infraestructura exponga rutas internas al visitante;
        // el resultado se gestiona con el mensaje de error del formulario.
        $correoEnviado = @mail($contactRecipient, $asunto, $cuerpo, $cabeceras);

        if ($correoEnviado) {
            $exito = true;
            $mensaje = '¡Gracias! Tu solicitud fue enviada correctamente. Te contactaremos pronto.';
            $formData = array_fill_keys(array_keys($formData), '');
        } else {
            error_log('PHP no pudo enviar la solicitud de contacto a ' . $contactRecipient);
            $mensaje = 'No pudimos enviar tu solicitud en este momento. Inténtalo nuevamente o escríbenos directamente a ' . $contactRecipient . '.';
        }
    }
}
