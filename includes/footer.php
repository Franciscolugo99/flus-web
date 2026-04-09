  </main>

  <footer class="site-footer">
    <div class="container footer-grid">
      <div class="footer-brand">
        <div class="footer-logo-panel">
          <img src="<?= e(asset_url('img/logo1.png')) ?>" alt="Logo FLUS" class="footer-logo-image">
        </div>
        <p class="footer-brand-copy">
          Sistema de gesti&oacute;n comercial para comercios y pymes que necesitan menos planillas,
          m&aacute;s control diario y una operaci&oacute;n mejor conectada entre ventas, stock, caja y facturaci&oacute;n.
        </p>
        <div class="footer-actions">
          <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Solicitar demo</a>
          <?php if ($site['whatsapp_number'] !== ''): ?>
            <a class="btn btn-secondary" href="<?= e(whatsapp_url('Hola, quiero conocer FLUS.')) ?>" target="_blank" rel="noopener">Hablar por WhatsApp</a>
          <?php endif; ?>
        </div>
      </div>

      <div>
        <strong class="footer-title">Soluciones</strong>
        <ul class="footer-links">
          <li><a href="<?= e(site_url('sistema-de-gestion.php')) ?>">Sistema de gesti&oacute;n comercial</a></li>
          <li><a href="<?= e(site_url('sistema-pos.php')) ?>">Sistema POS</a></li>
          <li><a href="<?= e(site_url('control-de-stock.php')) ?>">Control de stock</a></li>
          <li><a href="<?= e(site_url('facturacion.php')) ?>">Facturaci&oacute;n integrada</a></li>
        </ul>
      </div>

      <div>
        <strong class="footer-title">Explorar</strong>
        <ul class="footer-links">
          <li><a href="<?= e(site_url()) ?>">Inicio</a></li>
          <li><a href="<?= e(site_url('contacto.php')) ?>">Contacto y demo</a></li>
          <li><a href="<?= e(site_url('sistema-de-gestion.php')) ?>">Software para comercios</a></li>
          <li><a href="<?= e(site_url('sistema-pos.php')) ?>">Software para ventas</a></li>
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
            <li><a href="<?= e(whatsapp_url('Hola, quiero conocer FLUS.')) ?>" target="_blank" rel="noopener">Escribinos por WhatsApp</a></li>
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
