<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Sistema de gestión comercial para comercios y pymes | FLUS';
$pageDescription = 'Conocé FLUS como sistema de gestión comercial para ventas, stock, caja, clientes y facturación, pensado para ordenar la operación diaria con más control y trazabilidad.';
$pageBreadcrumbs = [
    ['name' => 'Inicio', 'url' => page_url()],
    ['name' => 'Sistema de gestión', 'url' => page_url('sistema-de-gestion.php')],
];
$pageSchemas = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'FLUS — Sistema de gestión comercial',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web',
        'description' => 'Sistema de gestión comercial para comercios y pymes: ventas, stock, caja, clientes y facturación integrados en una sola plataforma.',
        'url' => page_url('sistema-de-gestion.php'),
        'featureList' => 'Gestión de ventas, Control de stock, Caja y cobranzas, Clientes, Facturación, Reportes operativos',
    ],
];
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container page-hero-grid">
    <div>
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="<?= e(site_url()) ?>">Inicio</a>
        <span>/</span>
        <span aria-current="page">Sistema de gestión</span>
      </nav>
      <span class="eyebrow">Gesti&oacute;n comercial</span>
      <h1>Una base más clara para vender, controlar y seguir la operación comercial</h1>
      <p>
        FLUS está pensado para comercios y pymes que necesitan ordenar la gestión diaria con una lógica más conectada entre ventas,
        stock, caja, clientes y facturación.
      </p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Solicitar demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-pos.php')) ?>">Ver sistema POS</a>
      </div>
    </div>

    <aside class="hero-panel">
      <h2>Qué debería aportar un sistema serio</h2>
      <ul class="check-list">
        <li>Menos dispersión entre áreas y herramientas.</li>
        <li>Más visibilidad sobre ventas, caja y stock.</li>
        <li>Mejor continuidad entre operación y seguimiento.</li>
        <li>Una base más profesional para crecer con orden.</li>
      </ul>
    </aside>
  </div>
</section>

<section class="section" data-reveal>
  <div class="container">
    <span class="section-kicker">Qué ordena</span>
    <h2>La gestión comercial vale más cuando une criterios, no cuando suma pantallas</h2>
    <div class="feature-grid">
      <article class="feature-card">
        <h3>Ventas y atención</h3>
        <p>La venta deja de quedar aislada y pasa a formar parte del seguimiento comercial completo.</p>
      </article>
      <article class="feature-card">
        <h3>Stock y disponibilidad</h3>
        <p>Los productos y movimientos se leen mejor cuando están conectados a la operación real.</p>
      </article>
      <article class="feature-card">
        <h3>Caja y cobros</h3>
        <p>Más claridad para revisar medios de pago, cierres y control diario sin trabajar a ciegas.</p>
      </article>
      <article class="feature-card">
        <h3>Clientes y comprobantes</h3>
        <p>Mejor continuidad entre lo que pasó en la operación y lo que después hay que seguir.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-dark" data-reveal>
  <div class="container split-grid">
    <div class="surface-card">
      <h3>Problemas que busca corregir</h3>
      <ul class="plain-list">
        <li>Información repartida entre planillas y herramientas sueltas.</li>
        <li>Stock poco confiable para operar con seguridad.</li>
        <li>Caja desconectada del resto del circuito comercial.</li>
        <li>Seguimiento débil sobre clientes, movimientos y comprobantes.</li>
      </ul>
    </div>

    <div class="surface-card">
      <h3>Qué conviene evaluar en una demo</h3>
      <p>
        La conversación tiene más sentido cuando se mira cómo se conecta el flujo completo y no solo un módulo suelto.
      </p>
      <ul class="link-list">
        <li><a href="<?= e(site_url('sistema-pos.php')) ?>">Profundizar en el sistema POS</a></li>
        <li><a href="<?= e(site_url('control-de-stock.php')) ?>">Ver control de stock</a></li>
        <li><a href="<?= e(site_url('facturacion.php')) ?>">Ver facturación</a></li>
      </ul>
    </div>
  </div>
</section>

<section class="section" data-reveal>
  <div class="container">
    <div class="cta-box">
      <h2>Si querés evaluar FLUS con criterio real, mirá cómo se comporta la operación completa</h2>
      <p>
        Ahí es donde un sistema de gestión comercial suma valor de verdad: cuando ayuda a vender, controlar y seguir mejor el negocio.
      </p>
      <div class="inline-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Pedir una demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url()) ?>#precios">Ver planes y precios</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
