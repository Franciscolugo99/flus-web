  </main>

  <footer class="site-footer">
    <div class="container footer-grid">
      <div>
        <strong>FLUS</strong>
        <p>Sistema de gestión comercial para ventas, stock, caja, clientes y facturación.</p>
      </div>

      <div>
        <strong>Páginas</strong>
        <ul class="footer-links">
          <li><a href="<?= e(site_url()) ?>">Inicio</a></li>
          <li><a href="<?= e(site_url('sistema-de-gestion.php')) ?>">Sistema de gestión</a></li>
          <li><a href="<?= e(site_url('sistema-pos.php')) ?>">Sistema POS</a></li>
          <li><a href="<?= e(site_url('contacto.php')) ?>">Contacto</a></li>
        </ul>
      </div>

      <div>
        <strong>Dominio</strong>
        <p><?= e($site['domain']) ?></p>
        <?php if (has_contact_info()): ?>
          <p class="footer-contact">
            <?php if ($site['contact_email'] !== ''): ?>
              <span><?= e($site['contact_email']) ?></span>
            <?php endif; ?>
            <?php if ($site['contact_phone'] !== ''): ?>
              <span><?= e($site['contact_phone']) ?></span>
            <?php endif; ?>
          </p>
        <?php endif; ?>
      </div>
    </div>

    <div class="container footer-bottom">
      <p>© <?= date('Y') ?> FLUS. Sitio base listo para editar y publicar.</p>
    </div>
  </footer>

  <script src="<?= e(asset_url('js/main.js')) ?>"></script>
</body>
</html>
