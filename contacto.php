<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Contacto y demo de FLUS | Sistema de gestión comercial';
$pageDescription = 'Solicitá una demo de FLUS y conocé cómo puede ayudarte a ordenar ventas, stock, caja, clientes y facturación con una operación más profesional.';
$pageBreadcrumbs = [
    ['name' => 'Inicio', 'url' => page_url()],
    ['name' => 'Contacto y demo', 'url' => page_url('contacto.php')],
];
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero page-hero-contact">
  <div class="container page-hero-grid">
    <div>
      <span class="eyebrow">Contacto y demo</span>
      <h1>Coordiná una demo de FLUS y revisá cómo encaja en tu operación</h1>
      <p>
        La mejor forma de evaluar FLUS es verlo aplicado a tu dinámica diaria: ventas, caja, stock,
        clientes y facturación dentro del mismo flujo de trabajo.
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
      <h2>Qué podés esperar de la conversación</h2>
      <ul class="check-list">
        <li>Revisar si FLUS encaja con tu operación.</li>
        <li>Mirar ventas, stock, caja y facturación con foco real.</li>
        <li>Entender dónde habría más orden y trazabilidad.</li>
        <li>Definir próximos pasos para una demo más profunda.</li>
      </ul>
    </aside>
  </div>
</section>

<section class="section">
  <div class="container">
    <span class="section-kicker">Canales de contacto</span>
    <h2>Elegí la vía más cómoda para avanzar</h2>
    <p class="section-lead">
      La página está pensada para que la conversación arranque rápido y con contexto. Cuanto más nos cuentes del negocio, mejor.
    </p>

    <div class="contact-grid">
      <?php if ($site['whatsapp_number'] !== ''): ?>
        <article class="contact-card">
          <h3>WhatsApp</h3>
          <p><a class="contact-link" href="<?= e(whatsapp_url('Hola, quiero conocer FLUS y coordinar una demo.')) ?>" target="_blank" rel="noopener">Iniciar conversación</a></p>
          <p class="muted">La vía más rápida para una primera charla comercial.</p>
        </article>
      <?php endif; ?>

      <?php if ($site['contact_email'] !== ''): ?>
        <article class="contact-card">
          <h3>Correo</h3>
          <p><a class="contact-link" href="mailto:<?= e($site['contact_email']) ?>"><?= e($site['contact_email']) ?></a></p>
          <p class="muted">Útil para consultas, seguimiento y coordinación de demo.</p>
        </article>
      <?php endif; ?>

      <?php if ($site['contact_phone'] !== ''): ?>
        <article class="contact-card">
          <h3>Teléfono</h3>
          <p><a class="contact-link" href="tel:<?= e(phone_href($site['contact_phone'])) ?>"><?= e($site['contact_phone']) ?></a></p>
          <p class="muted">Si preferís una primera conversación directa.</p>
        </article>
      <?php endif; ?>

      <article class="contact-card">
        <h3>Qué conviene contarnos</h3>
        <p>Tu rubro, cómo trabajan hoy ventas y stock, y qué parte de la operación quieren ordenar primero.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-dark">
  <div class="container split-grid">
    <div class="surface-card">
      <h3>Qué revisar antes de la demo</h3>
      <ul class="plain-list">
        <li>Cómo venden hoy y qué pasa en caja.</li>
        <li>Qué nivel de control real tienen sobre stock.</li>
        <li>Cómo siguen clientes y comprobantes.</li>
        <li>Cuánto trabajo manual depende de planillas.</li>
      </ul>
    </div>

    <div class="surface-card">
      <h3>Páginas que te conviene mirar</h3>
      <p>
        Si querés llegar mejor orientado a la conversación, podés revisar primero las páginas que explican
        cómo trabaja FLUS en cada parte de la operación.
      </p>
      <ul class="link-list">
        <li><a href="<?= e(site_url('sistema-de-gestion.php')) ?>">Sistema de gestión</a></li>
        <li><a href="<?= e(site_url('sistema-pos.php')) ?>">Sistema POS</a></li>
        <li><a href="<?= e(site_url('control-de-stock.php')) ?>">Control de stock</a></li>
        <li><a href="<?= e(site_url('facturacion.php')) ?>">Facturación</a></li>
      </ul>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
