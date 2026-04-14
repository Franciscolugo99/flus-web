<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Sistema POS para comercios con caja y stock integrado | FLUS';
$pageDescription = 'FLUS funciona como sistema POS para comercios que necesitan vender, cobrar, revisar caja y operar con stock dentro de una misma logica comercial.';
$pageBreadcrumbs = [
    ['name' => 'Inicio', 'url' => page_url()],
    ['name' => 'Sistema POS', 'url' => page_url('sistema-pos.php')],
];
$pageSchemas = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'FLUS — Sistema POS',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web',
        'description' => 'Sistema POS para comercios con punto de venta ágil, control de caja, múltiples medios de pago y stock integrado.',
        'url' => page_url('sistema-pos.php'),
        'featureList' => 'Punto de venta, Control de caja, Medios de pago, Stock integrado, Historial de clientes, Facturación',
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
        <span aria-current="page">Sistema POS</span>
      </nav>
      <span class="eyebrow">Pantalla de cobro</span>
      <h1>Un sistema POS para vender y cobrar con m&aacute;s control, no solo m&aacute;s r&aacute;pido</h1>
      <p>
        FLUS ayuda a trabajar el mostrador con agilidad, pero tambi&eacute;n con mejor contexto sobre caja,
        medios de pago, stock y continuidad comercial.
      </p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Solicitar demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-de-gestion.php')) ?>">Ver sistema completo</a>
      </div>
    </div>

    <aside class="hero-panel">
      <h2>Qu&eacute; deber&iacute;a aportar un POS serio</h2>
      <ul class="check-list">
        <li>M&aacute;s agilidad en caja sin perder control.</li>
        <li>Claridad sobre medios de pago y movimientos.</li>
        <li>Relaci&oacute;n directa con stock y seguimiento.</li>
        <li>Trazabilidad sobre cada operaci&oacute;n.</li>
      </ul>
    </aside>
  </div>
</section>

<section class="section" data-reveal>
  <div class="container">
    <span class="section-kicker">Uso real en mostrador</span>
    <h2>El problema aparece cuando el POS resuelve el cobro y deja todo lo dem&aacute;s afuera</h2>
    <div class="feature-grid">
      <article class="feature-card">
        <h3>Cobro sin contexto</h3>
        <p>La venta se completa, pero despu&eacute;s cuesta seguir stock, cliente o comprobante.</p>
      </article>
      <article class="feature-card">
        <h3>Caja reactiva</h3>
        <p>Si el control diario llega tarde, los desv&iacute;os se entienden cuando el problema ya pas&oacute;.</p>
      </article>
      <article class="feature-card">
        <h3>Medios de pago poco claros</h3>
        <p>Efectivo, transferencias y otros cobros necesitan una base m&aacute;s prolija para revisar.</p>
      </article>
      <article class="feature-card">
        <h3>Venta desconectada del stock</h3>
        <p>Cuando el mostrador no conversa bien con disponibilidad, el negocio trabaja con fricci&oacute;n.</p>
      </article>
    </div>
  </div>
</section>

<section class="section" data-reveal>
  <div class="container feature-showcase">
    <div class="feature-copy">
      <span class="eyebrow">Operaci&oacute;n en caja</span>
      <h2>Una pantalla pensada para vender y cobrar sin fricci&oacute;n</h2>
      <p>
        B&uacute;squeda r&aacute;pida, ticket visible, medios de pago claros y total a cobrar en el mismo flujo
        para que la caja trabaje con m&aacute;s orden.
      </p>
    </div>

    <figure class="product-shot">
      <img
        src="<?= e(asset_url('img/flus-caja-pos.png')) ?>"
        alt="Interfaz de caja de FLUS con ticket, medios de pago y total a cobrar"
        width="1608"
        height="978"
        loading="lazy"
        decoding="async"
      >
    </figure>
  </div>
</section>

<section class="section section-dark" data-reveal>
  <div class="container split-grid">
    <div class="surface-card">
      <h3>Qu&eacute; valor aporta FLUS como POS</h3>
      <ul class="plain-list">
        <li>Venta m&aacute;s &aacute;gil y mejor registrada.</li>
        <li>Caja diaria con m&aacute;s orden y visibilidad.</li>
        <li>V&iacute;nculo directo con stock y clientes.</li>
        <li>Mejor continuidad entre cobro y facturaci&oacute;n.</li>
      </ul>
    </div>

    <div class="surface-card">
      <h3>Qu&eacute; conviene mirar despu&eacute;s del cobro</h3>
      <p>
        Ah&iacute; se ve si el POS sirve de verdad: cuando la operaci&oacute;n sigue bien cerrada y no obliga a reconstruir informaci&oacute;n.
      </p>
      <ul class="link-list">
        <li><a href="<?= e(site_url('control-de-stock.php')) ?>">Ver c&oacute;mo trabaja el stock</a></li>
        <li><a href="<?= e(site_url('facturacion.php')) ?>">Ver la facturaci&oacute;n integrada</a></li>
      </ul>
    </div>
  </div>
</section>

<section class="section" data-reveal>
  <div class="container">
    <div class="cta-box">
      <h2>Si busc&aacute;s un software para ventas, conviene mirar tambi&eacute;n lo que pasa despu&eacute;s de cobrar</h2>
      <p>
        FLUS apunta a que el mostrador no quede desconectado del resto de la gesti&oacute;n comercial.
      </p>
      <div class="inline-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Pedir una demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url()) ?>#precios">Ver planes y precios</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
