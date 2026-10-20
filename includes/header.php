<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/content.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$metaByPage = [
    'index.php' => [site_setting('seo.home_title', 'Abogados en Tegucigalpa, Honduras | AMS Legal Solutions'), site_setting('seo.home_description', 'Firma de abogados en Tegucigalpa con asesoría corporativa, laboral, penal, civil, familiar y de propiedad intelectual.')],
    'servicios.php' => [site_setting('seo.services_title', 'Servicios legales en Honduras | AMS Legal Solutions'), site_setting('seo.services_description', 'Conoce nuestras áreas de práctica legal para empresas y personas en Honduras.')],
    'sobre_la_firma.php' => [site_setting('seo.firm_title', 'Nuestra firma de abogados | AMS Legal Solutions'), site_setting('seo.firm_description', 'Conoce la historia, los valores y el enfoque profesional de AMS Legal Solutions en Tegucigalpa.')],
    'equipo.php' => [site_setting('seo.team_title', 'Abogados en Tegucigalpa | AMS Legal Solutions'), site_setting('seo.team_description', 'Conoce al equipo de abogados de AMS Legal Solutions y sus áreas de especialidad.')],
    'contacto.php' => [site_setting('seo.contact_title', 'Consulta legal en Tegucigalpa | AMS Legal Solutions'), site_setting('seo.contact_description', 'Agenda una consulta legal con AMS Legal Solutions en Tegucigalpa, Honduras.')],
];
[$pageTitle, $pageDescription] = $metaByPage[$currentPage] ?? [site_setting('site.name', 'AMS Legal Solutions'), 'Asesoría jurídica en Honduras.'];
$canonicalPaths = ['index.php' => '', 'servicios.php' => 'servicios.php', 'sobre_la_firma.php' => 'sobre_la_firma.php', 'equipo.php' => 'equipo.php', 'contacto.php' => 'contacto.php'];
$canonicalUrl = 'https://amslegalsolutions.com/' . ($canonicalPaths[$currentPage] ?? '');
$siteLogo = site_setting('site.logo', 'img/ams-logo-header.png');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= site_e($pageTitle) ?></title>
    <meta name="description" content="<?= site_e($pageDescription) ?>">
    <link rel="canonical" href="<?= site_e($canonicalUrl) ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="img/favicon.png">
    <link rel="apple-touch-icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/../css/style.css') ?>">
</head>
<body>

<header class="site-header">
    <div class="container header-inner">
        <a href="index.php" class="logo">
            <span class="logo-mark">
                <img src="<?= site_e($siteLogo) ?>" alt="<?= site_e(site_setting('site.name', 'AMS Legal Solutions')) ?>">
            </span>
            <span class="logo-copy">
                <span class="logo-location"><?= site_e(site_setting('site.location', 'Tegucigalpa · Honduras')) ?></span>
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
