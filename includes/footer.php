  </main>

  <footer class="site-footer">
    <div class="container footer-top">
      <a class="footer-brand-mini" href="<?= e(site_url()) ?>" aria-label="Ir al inicio de FLUS">
        <span class="footer-mark" aria-hidden="true">
          <img src="<?= e(asset_url('img/flus-mark.png')) ?>" alt="" class="footer-mark-image">
        </span>
        <span class="footer-brand-lockup">
          <span class="footer-wordmark">FLUS</span>
          <span class="footer-tagline">Ventas, stock, caja y facturaci&oacute;n conectadas</span>
        </span>
      </a>

      <div>
        <strong class="footer-title">Soluciones</strong>
        <ul class="footer-links">
          <li><a href="<?= e(site_url('sistema-de-gestion.php')) ?>">Sistema de gesti&oacute;n</a></li>
          <li><a href="<?= e(site_url('sistema-pos.php')) ?>">Sistema POS</a></li>
          <li><a href="<?= e(site_url('control-de-stock.php')) ?>">Control de stock</a></li>
          <li><a href="<?= e(site_url('facturacion.php')) ?>">Facturaci&oacute;n</a></li>
        </ul>
      </div>

      <div>
        <strong class="footer-title">Contacto</strong>
        <ul class="footer-links footer-contact-list">
          <?php if ($site['contact_email'] !== ''): ?>
            <li><a href="mailto:<?= e($site['contact_email']) ?>"><?= e($site['contact_email']) ?></a></li>
          <?php endif; ?>
          <?php if ($site['contact_phone'] !== ''): ?>
            <li><a href="tel:<?= e(preg_replace('/\s+/', '', $site['contact_phone'])) ?>"><?= e($site['contact_phone']) ?></a></li>
          <?php endif; ?>
          <?php if ($site['whatsapp_number'] !== ''): ?>
            <li><a href="<?= e(whatsapp_url('Hola, quiero conocer FLUS.')) ?>" target="_blank" rel="noopener">WhatsApp</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>

    <div class="container footer-bottom">
      <p>&copy; <?= date('Y') ?> FLUS. Sistema de gesti&oacute;n comercial para una operaci&oacute;n m&aacute;s ordenada, trazable y profesional.</p>
    </div>
  </footer>

  <script src="<?= e(asset_url('js/main.js')) ?>"></script>
</body>
</html>
