<?php
$pageTitle = 'Contacto y demo | FLUS';
$pageDescription = 'Solicitá una demo de FLUS y conocé cómo puede ayudarte a ordenar ventas, stock, caja y facturación.';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container two-col">
    <div>
      <span class="eyebrow">Contacto</span>
      <h1>Solicitá una demo de FLUS</h1>
      <p>
        Esta página está lista para funcionar como punto de contacto comercial. Ya quedó armada la estructura;
        solo falta completar tus datos reales antes de publicar.
      </p>
    </div>

    <aside class="hero-panel">
      <h2>Qué conviene mostrar acá</h2>
      <ul class="check-list">
        <li>WhatsApp comercial</li>
        <li>Correo real del dominio</li>
        <li>Texto corto orientado a demo</li>
        <li>Llamado a la acción claro</li>
      </ul>
    </aside>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="contact-grid">
      <article class="contact-card">
        <h3>Correo</h3>
        <?php if ($site['contact_email'] !== ''): ?>
          <p><a href="mailto:<?= e($site['contact_email']) ?>"><?= e($site['contact_email']) ?></a></p>
        <?php else: ?>
          <p>Completá <span class="code-inline">contact_email</span> en <span class="code-inline">includes/bootstrap.php</span>.</p>
        <?php endif; ?>
      </article>

      <article class="contact-card">
        <h3>WhatsApp</h3>
        <?php if ($site['whatsapp_number'] !== ''): ?>
          <p><a href="https://wa.me/<?= e(preg_replace('/\D+/', '', $site['whatsapp_number'])) ?>" target="_blank" rel="noopener">Escribinos por WhatsApp</a></p>
        <?php else: ?>
          <p>Completá <span class="code-inline">whatsapp_number</span> en <span class="code-inline">includes/bootstrap.php</span>.</p>
        <?php endif; ?>
      </article>
    </div>

    <?php if (!has_contact_info()): ?>
      <div class="notice">
        Falta completar tus datos reales de contacto. Antes de subir a Wiroos, editá <span class="code-inline">includes/bootstrap.php</span>
        y cargá correo, teléfono o WhatsApp.
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta-box">
      <h2>Qué te conviene agregar después</h2>
      <p>
        Cuando ya tengas correo del dominio y WhatsApp definidos, podés sumar un formulario real o integrarlo con envío por mail.
        Para esta primera versión dejé una base segura y simple, sin inventar integración que todavía no configuraste.
      </p>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
