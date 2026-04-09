<?php
$pageTitle = 'Contacto y demo de FLUS | Sistema de gestion comercial';
$pageDescription = 'Solicita una demo de FLUS y conoce como puede ayudarte a ordenar ventas, stock, caja, clientes y facturacion con una operacion mas profesional.';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero page-hero-contact">
  <div class="container two-col">
    <div>
      <span class="eyebrow">Contacto y demo</span>
      <h1>Coordin&aacute; una demo de FLUS para evaluar tu operaci&oacute;n con criterio real</h1>
      <p>
        Si quer&eacute;s revisar FLUS en serio, lo importante es verlo aplicado a tu din&aacute;mica diaria:
        ventas, caja, stock, clientes y facturaci&oacute;n dentro del mismo flujo de trabajo.
      </p>
      <div class="hero-actions">
        <?php if ($site['whatsapp_number'] !== ''): ?>
          <a class="btn btn-primary" href="<?= e(whatsapp_url('Hola, quiero conocer FLUS y coordinar una demo.')) ?>" target="_blank" rel="noopener">Hablar por WhatsApp</a>
        <?php endif; ?>
        <?php if ($site['contact_email'] !== ''): ?>
          <a class="btn btn-secondary" href="mailto:<?= e($site['contact_email']) ?>">Enviar correo</a>
        <?php endif; ?>
      </div>
    </div>

    <aside class="hero-panel">
      <h2>Qu&eacute; pod&eacute;s esperar de la conversaci&oacute;n</h2>
      <ul class="check-list">
        <li>Revisar si FLUS encaja con tu tipo de operaci&oacute;n</li>
        <li>Mirar ventas, stock, caja y facturaci&oacute;n con foco real</li>
        <li>Entender d&oacute;nde habr&iacute;a m&aacute;s orden y m&aacute;s trazabilidad</li>
        <li>Definir pr&oacute;ximos pasos para una demo</li>
      </ul>
    </aside>
  </div>
</section>

<section class="section">
  <div class="container">
    <span class="section-kicker">Canales de contacto</span>
    <h2>Eleg&iacute; la v&iacute;a m&aacute;s c&oacute;moda para avanzar</h2>
    <p class="section-lead">
      Pod&eacute;s escribirnos para una primera consulta comercial o para coordinar una demo con foco en la operaci&oacute;n real de tu negocio.
    </p>

    <div class="contact-grid">
      <?php if ($site['contact_email'] !== ''): ?>
        <article class="contact-card">
          <h3>Correo</h3>
          <p><a class="contact-link" href="mailto:<?= e($site['contact_email']) ?>"><?= e($site['contact_email']) ?></a></p>
          <p class="muted" style="margin-top:10px;">Util para consultas comerciales, seguimiento y coordinaci&oacute;n.</p>
        </article>
      <?php endif; ?>

      <?php if ($site['whatsapp_number'] !== ''): ?>
        <article class="contact-card">
          <h3>WhatsApp</h3>
          <p><a class="contact-link" href="<?= e(whatsapp_url('Hola, quiero conocer FLUS y coordinar una demo.')) ?>" target="_blank" rel="noopener">Escribinos por WhatsApp</a></p>
          <p class="muted" style="margin-top:10px;">La v&iacute;a m&aacute;s r&aacute;pida para iniciar la conversaci&oacute;n.</p>
        </article>
      <?php endif; ?>

      <?php if ($site['contact_phone'] !== ''): ?>
        <article class="contact-card">
          <h3>Tel&eacute;fono</h3>
          <p><a class="contact-link" href="tel:<?= e(preg_replace('/\s+/', '', $site['contact_phone'])) ?>"><?= e($site['contact_phone']) ?></a></p>
          <p class="muted" style="margin-top:10px;">Conveniente si prefer&iacute;s una primera charla directa.</p>
        </article>
      <?php endif; ?>

      <article class="contact-card">
        <h3>Qu&eacute; conviene contarnos</h3>
        <p>Tu rubro, c&oacute;mo trabajan hoy ventas y stock, y qu&eacute; parte de la operaci&oacute;n quieren ordenar primero.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-dark">
  <div class="container split-grid">
    <div class="surface-card">
      <h3>Qu&eacute; revisar antes de la demo</h3>
      <ul class="plain-list">
        <li>C&oacute;mo venden hoy y qu&eacute; pasa en caja</li>
        <li>Qu&eacute; nivel de control real tienen sobre stock</li>
        <li>C&oacute;mo siguen clientes y comprobantes</li>
        <li>Cu&aacute;nto trabajo manual dependen de planillas</li>
      </ul>
    </div>

    <div class="surface-card">
      <h3>P&aacute;ginas que te conviene mirar</h3>
      <p>
        Si quer&eacute;s llegar mejor orientado a la conversaci&oacute;n, pod&eacute;s revisar primero las p&aacute;ginas
        que explican c&oacute;mo trabaja FLUS en cada parte de la operaci&oacute;n.
      </p>
      <ul class="link-list">
        <li><a href="<?= e(site_url('sistema-de-gestion.php')) ?>">Sistema de gesti&oacute;n comercial</a></li>
        <li><a href="<?= e(site_url('sistema-pos.php')) ?>">Sistema POS</a></li>
        <li><a href="<?= e(site_url('control-de-stock.php')) ?>">Control de stock</a></li>
        <li><a href="<?= e(site_url('facturacion.php')) ?>">Facturaci&oacute;n</a></li>
      </ul>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta-box">
      <h2>La mejor demo es la que se mira desde los problemas reales del negocio</h2>
      <p>
        FLUS se entiende mejor cuando se lo eval&uacute;a sobre la operaci&oacute;n diaria y no como una lista aislada de m&oacute;dulos.
      </p>
      <div class="contact-quick-links">
        <?php if ($site['contact_email'] !== ''): ?>
          <a class="quick-link" href="mailto:<?= e($site['contact_email']) ?>">Enviar correo</a>
        <?php endif; ?>
        <?php if ($site['whatsapp_number'] !== ''): ?>
          <a class="quick-link" href="<?= e(whatsapp_url('Hola, quiero coordinar una demo de FLUS.')) ?>" target="_blank" rel="noopener">Abrir WhatsApp</a>
        <?php endif; ?>
        <a class="quick-link" href="<?= e(site_url('sistema-de-gestion.php')) ?>">Ver sistema de gesti&oacute;n</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
