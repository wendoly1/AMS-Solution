<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AMS Legal Solutions</title>
    <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/../css/style.css') ?>">
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <a href="index.php" class="logo">
            <span class="logo-mark">
                <img src="img/ams-logo-header.png" alt="">
            </span>
            <span class="logo-copy">
                <span class="logo-title">AMS LEGAL</span>
                <span class="logo-location">TEGUCIGALPA · HONDURAS</span>
            </span>
        </a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="main-navigation" aria-label="Abrir menú">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <nav class="main-nav" id="main-navigation" aria-label="Navegación principal">
            <ul>
                <li><a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Inicio</a></li>
                <li><a href="servicios.php" class="<?= $currentPage === 'servicios.php' ? 'active' : '' ?>">Áreas</a></li>
                <li><a href="sobre_la_firma.php" class="<?= $currentPage === 'sobre_la_firma.php' ? 'active' : '' ?>">Firma</a></li>
                <li><a href="equipo.php" class="<?= $currentPage === 'equipo.php' ? 'active' : '' ?>">Abogados</a></li>
                <li class="mobile-nav-cta"><a href="contacto.php">Agendar cita</a></li>
            </ul>
        </nav>
        <a href="contacto.php" class="header-cta">Agendar cita</a>
    </div>
</header>

<main>
