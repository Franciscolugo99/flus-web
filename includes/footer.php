  </main>

  <footer class="site-footer">
    <div class="container footer-grid">
      <div class="footer-brand">
        <a class="footer-brand-lockup" href="<?= e(site_url()) ?>">
          <img src="<?= e(asset_url('img/flus-mark.png')) ?>" alt="" width="30" height="34">
          <span>FLUS</span>
        </a>
        <p class="footer-copy">
          Sistema de gestión comercial para comercios y pymes que necesitan vender,
          controlar caja, seguir stock y trabajar la facturación con más orden operativo.
        </p>
        <div class="footer-cta-row">
          <a href="<?= e(site_url('contacto.php')) ?>">Solicitar demo</a>
          <?php if ($site['whatsapp_number'] !== ''): ?>
            <a class="footer-whatsapp" href="<?= e(whatsapp_url('Hola, quiero conocer FLUS.')) ?>" target="_blank" rel="noopener">Escribinos por WhatsApp</a>
          <?php endif; ?>
        </div>
      </div>

      <div>
        <strong class="footer-title">Soluciones</strong>
        <ul class="footer-links">
          <li><a href="<?= e(site_url('sistema-de-gestion.php')) ?>">Sistema de gestión</a></li>
          <li><a href="<?= e(site_url('sistema-pos.php')) ?>">Sistema POS</a></li>
          <li><a href="<?= e(site_url('control-de-stock.php')) ?>">Control de stock</a></li>
          <li><a href="<?= e(site_url('facturacion.php')) ?>">Facturación</a></li>
        </ul>
      </div>

      <div>
        <strong class="footer-title">Explorar</strong>
        <ul class="footer-links">
          <li><a href="<?= e(site_url()) ?>">Inicio</a></li>
          <li><a href="<?= e(site_url('contacto.php')) ?>">Contacto y demo</a></li>
          <li><a href="<?= e(site_url('sistema-de-gestion.php')) ?>">Cómo trabaja FLUS</a></li>
        </ul>
      </div>

      <div>
        <strong class="footer-title">Contacto</strong>
        <ul class="footer-links">
          <?php if ($site['contact_email'] !== ''): ?>
            <li><a href="mailto:<?= e($site['contact_email']) ?>"><?= e($site['contact_email']) ?></a></li>
          <?php endif; ?>
          <?php if ($site['contact_phone'] !== ''): ?>
            <li><a href="tel:<?= e(phone_href($site['contact_phone'])) ?>"><?= e($site['contact_phone']) ?></a></li>
          <?php endif; ?>
          <?php if ($site['whatsapp_number'] !== ''): ?>
            <li><a href="<?= e(whatsapp_url('Hola, quiero conocer FLUS.')) ?>" target="_blank" rel="noopener">Escribinos por WhatsApp</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>

    <div class="container footer-bottom">
      <p>&copy; <?= date('Y') ?> FLUS. Gestión comercial para una operación más ordenada, trazable y profesional.</p>
      <p><?= e($site['domain']) ?></p>
    </div>
  </footer>

  <script src="<?= e(asset_url('js/main.js')) ?>"></script>
</body>
</html>
