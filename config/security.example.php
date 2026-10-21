<?php
// Copiar como security.local.php para personalizar el dominio y remitente.
// No utilizar HTTP_HOST ni el correo público del sitio para recuperar cuentas.
return [
    'local' => [
        'admin_url' => 'http://127.0.0.1:8000/admin',
        'mail_from' => 'no-reply@amslegalsolutions.com',
        'setup_token' => '', // Solo para crear el primer administrador; luego dejar vacío.
    ],
    'production' => [
        'admin_url' => 'https://amslegalsolutions.com/admin',
        'mail_from' => 'no-reply@amslegalsolutions.com',
        'setup_token' => '', // Pegar aquí una clave de 64 caracteres hexadecimales.
    ],
];
