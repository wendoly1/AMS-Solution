</main>

<footer class="site-footer" id="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <span class="footer-logo"><?= site_e(site_setting('site.name', 'AMS Legal Solutions')) ?></span>
            <span class="footer-tagline"><?= site_e(site_setting('site.location', 'Tegucigalpa · Honduras')) ?></span>
            <p class="footer-desc"><?= site_e(site_setting('site.footer_description', 'Asesoría jurídica integral para empresas y personas. Defendemos tus intereses con criterio y discreción.')) ?></p>
        </div>
        <div class="footer-col">
            <h4>Firma</h4>
            <ul>
                <li><a href="sobre_la_firma.php">Sobre nosotros</a></li>
                <li><a href="equipo.php">Abogados</a></li>
                <li><a href="contacto.php">Agendar cita</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Áreas</h4>
            <ul>
                <?php
                $footerServices = site_setting('content.services', '') === ''
                    ? [['slug' => 'corporativo', 'name' => 'Corporativo'], ['slug' => 'laboral', 'name' => 'Laboral'], ['slug' => 'penal', 'name' => 'Penal'], ['slug' => 'familia', 'name' => 'Familia']]
                    : array_slice(site_services(), 0, 4);
                foreach ($footerServices as $service): ?>
                <li><a href="servicios.php#<?= site_e($service['slug']) ?>"><?= site_e($service['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Contacto</h4>
            <ul>
                <?php $footerPhone = site_setting('contact.phone', '+504 0000 0000'); $footerEmail = site_setting('contact.email', 'gmoncada@amslegalsolutions.com'); ?>
                <li><a href="tel:<?= site_e(preg_replace('/[^\d+]/', '', $footerPhone)) ?>"><?= site_e($footerPhone) ?></a></li>
                <li><a href="mailto:<?= site_e($footerEmail) ?>"><?= site_e($footerEmail) ?></a></li>
                <li><span><?= site_e(site_setting('contact.address', 'Boulevard Morazán, Tegucigalpa, Honduras')) ?></span></li>
                <?php foreach (site_contact_links() as $label => $url): ?><li><a href="<?= site_e($url) ?>" target="_blank" rel="noopener noreferrer"><?= site_e($label) ?> ↗</a></li><?php endforeach; ?>
            </ul>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>&copy; <?= date('Y') ?> <?= site_e(site_setting('site.name', 'AMS Legal Solutions')) ?>. Todos los derechos reservados.</span>
        <span class="footer-legal"><a href="#">Aviso legal</a><a href="#">Privacidad</a></span>
    </div>
</footer>

<script src="includes/js/script.js?v=<?= filemtime(__DIR__ . '/js/script.js') ?>"></script>
</body>
</html>
