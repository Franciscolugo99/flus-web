  </main><!-- #main-content -->

  <footer class="site-footer">
    <div class="container footer-grid">
      <div class="footer-brand">
        <a class="footer-brand-lockup" href="<?= e(site_url()) ?>">
          <picture>
            <source srcset="<?= e(asset_url('img/flus-mark.webp')) ?>" type="image/webp">
            <img src="<?= e(asset_url('img/flus-mark.png')) ?>" alt="" width="30" height="34" loading="lazy">
          </picture>
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
          <li><a href="<?= e(site_url()) ?>#precios">Planes y precios</a></li>
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

  <?php if ($site['whatsapp_number'] !== ''): ?>
  <a
    class="whatsapp-fab"
    id="whatsapp-fab"
    href="<?= e(whatsapp_url('Hola, quiero conocer FLUS y coordinar una demo.')) ?>"
    target="_blank"
    rel="noopener"
    aria-label="Escribinos por WhatsApp"
  >
    <span class="whatsapp-fab__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
      </svg>
    </span>
    <span class="whatsapp-fab__label">WhatsApp</span>
  </a>
  <?php endif; ?>

  <?php
  $scriptPath = __DIR__ . '/../assets/js/main.js';
  $scriptSrc = asset_url('js/main.js');
  if (is_file($scriptPath)) {
      $scriptSrc .= '?v=' . filemtime($scriptPath);
  }
  ?>
  <script src="<?= e($scriptSrc) ?>"></script>
</body>
</html>
