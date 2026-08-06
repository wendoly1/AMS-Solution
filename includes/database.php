<?php
/**
 * Conexión a la base de datos - AMS Legal Solutions
 * 
 * IMPORTANTE: Cuando trabajes en local con XAMPP, los valores por defecto
 * suelen ser:
 *   host: localhost
 *   usuario: root
 *   password: (vacío)
 *   base de datos: crea una llamada "ams_legal" en phpMyAdmin
 *
 * Cuando subas al hosting, cambia estos valores por los que te dé
 * el panel de Hostinger (hPanel > Bases de datos MySQL).
 */

$DB_HOST = 'localhost';
$DB_NAME = 'ams_legal';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    // En producción esto no debe mostrar el error real al usuario
    die('Error de conexión a la base de datos: ' . $e->getMessage());
}
