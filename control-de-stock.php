<?php
$pageTitle = 'Control de stock para comercios y pymes | FLUS';
$pageDescription = 'FLUS ayuda a controlar stock, productos, movimientos y disponibilidad dentro del flujo comercial del negocio, con menos incertidumbre y mas trazabilidad.';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container two-col">
    <div>
      <span class="eyebrow">Control de stock</span>
      <h1>M&aacute;s control de stock para vender con seguridad y operar con menos dudas</h1>
      <p>
        Un stock poco confiable no solo genera faltantes. Tambi&eacute;n afecta ventas, reposici&oacute;n,
        caja y la calidad de la informaci&oacute;n con la que el negocio decide.
      </p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Solicitar demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-de-gestion.php')) ?>">Ver sistema completo</a>
      </div>
    </div>

    <aside class="hero-panel">
      <h2>Qu&eacute; tiene que resolver el stock</h2>
      <ul class="check-list">
        <li>Dar claridad sobre disponibilidad real</li>
        <li>Permitir seguir movimientos con m&aacute;s trazabilidad</li>
        <li>Mejorar la relaci&oacute;n entre ventas y productos</li>
        <li>Ayudar a reponer con mejor criterio</li>
      </ul>
    </aside>
  </div>
</section>

<section class="section">
  <div class="container">
    <span class="section-kicker">S&iacute;ntomas comunes</span>
    <h2>Cuando el control de stock es flojo, la operaci&oacute;n trabaja a ciegas</h2>
    <div class="metrics">
      <article class="metric">
        <strong>Disponibilidad poco clara</strong>
        <p>Se vende con dudas, se responde tarde y el equipo pierde confianza en la informaci&oacute;n.</p>
      </article>
      <article class="metric">
        <strong>Movimientos dif&iacute;ciles de reconstruir</strong>
        <p>Entradas, salidas y ajustes quedan poco visibles y despu&eacute;s cuesta entender qu&eacute; pas&oacute;.</p>
      </article>
      <article class="metric">
        <strong>Reposici&oacute;n reactiva</strong>
        <p>Se compra tarde o sin suficiente contexto porque faltan referencias operativas confiables.</p>
      </article>
      <article class="metric">
        <strong>Demasiada dependencia de planillas</strong>
        <p>El control termina afuera del sistema y eso vuelve fr&aacute;gil toda la gesti&oacute;n diaria.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-dark">
  <div class="container split-grid">
    <div class="surface-card">
      <h3>Qu&eacute; aporta FLUS al control de stock</h3>
      <ul class="plain-list">
        <li>M&aacute;s orden sobre cat&aacute;logo, referencias y disponibilidad</li>
        <li>Seguimiento m&aacute;s claro de movimientos dentro de la operaci&oacute;n</li>
        <li>Mejor v&iacute;nculo entre ventas, productos y reposici&oacute;n</li>
        <li>Una base m&aacute;s confiable para tomar decisiones diarias</li>
      </ul>
    </div>

    <div class="surface-card">
      <h3>D&oacute;nde se nota m&aacute;s</h3>
      <p>
        Se nota cuando el negocio necesita vender con seguridad, evitar errores manuales
        y revisar stock sin estar reconstruyendo la informaci&oacute;n todo el tiempo.
      </p>
      <ul class="link-list">
        <li><a href="<?= e(site_url('sistema-pos.php')) ?>">Ver c&oacute;mo se conecta con el sistema POS</a></li>
        <li><a href="<?= e(site_url('facturacion.php')) ?>">Ver la relaci&oacute;n con facturaci&oacute;n</a></li>
      </ul>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta-box">
      <h2>Si hoy vend&eacute;s con dudas sobre la disponibilidad, ya hay un problema operativo para resolver</h2>
      <p>
        FLUS busca que el control de stock deje de ser una tarea paralela y pase a formar parte del circuito comercial normal del negocio.
      </p>
      <div class="inline-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Ver FLUS en una demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-de-gestion.php')) ?>">Volver al sistema de gesti&oacute;n</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
