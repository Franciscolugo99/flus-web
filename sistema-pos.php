<?php
$pageTitle = 'Sistema POS para comercios con stock y caja integrada | FLUS';
$pageDescription = 'FLUS funciona como sistema POS para comercios que necesitan ventas, caja, medios de pago, stock y clientes dentro de una misma operacion comercial.';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container two-col">
    <div>
      <span class="eyebrow">Sistema POS</span>
      <h1>Un sistema POS para vender y cobrar con m&aacute;s control, no solo m&aacute;s r&aacute;pido</h1>
      <p>
        FLUS ayuda a trabajar el mostrador con agilidad, pero tambi&eacute;n con mejor contexto sobre stock,
        caja, medios de pago y seguimiento comercial.
      </p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Solicitar demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-de-gestion.php')) ?>">Ver sistema completo</a>
      </div>
    </div>

    <aside class="hero-panel">
      <h2>Qu&eacute; deber&iacute;a aportar un POS serio</h2>
      <ul class="check-list">
        <li>M&aacute;s agilidad en caja sin perder control</li>
        <li>Claridad sobre medios de pago y movimientos</li>
        <li>Relaci&oacute;n directa con stock y clientes</li>
        <li>M&aacute;s trazabilidad sobre cada operaci&oacute;n</li>
      </ul>
      <div class="panel-note">
        Un POS queda corto cuando resuelve el cobro pero deja el resto del negocio afuera.
      </div>
    </aside>
  </div>
</section>

<section class="section">
  <div class="container">
    <span class="section-kicker">Uso real en caja</span>
    <h2>Los problemas aparecen cuando el sistema POS queda aislado</h2>
    <div class="metrics">
      <article class="metric">
        <strong>Se cobra, pero despu&eacute;s falta contexto</strong>
        <p>La venta se completa, pero cuesta seguir qu&eacute; pas&oacute; con stock, cliente o comprobante.</p>
      </article>
      <article class="metric">
        <strong>La caja se vuelve un cierre tard&iacute;o</strong>
        <p>Si el control diario no es claro, los desvios se descubren cuando el problema ya pas&oacute;.</p>
      </article>
      <article class="metric">
        <strong>Los medios de pago quedan poco ordenados</strong>
        <p>Efectivo, transferencias y otros cobros necesitan una l&oacute;gica m&aacute;s prolija para revisar la operaci&oacute;n.</p>
      </article>
      <article class="metric">
        <strong>El mostrador no conversa con stock</strong>
        <p>Cuando la venta y la disponibilidad no est&aacute;n bien vinculadas, el negocio trabaja con m&aacute;s fricci&oacute;n.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-dark">
  <div class="container split-grid">
    <div class="surface-card">
      <h3>Qu&eacute; valor aporta FLUS como sistema POS</h3>
      <ul class="plain-list">
        <li>Venta m&aacute;s &aacute;gil y mejor registrada</li>
        <li>Caja diaria con m&aacute;s orden y m&aacute;s visibilidad</li>
        <li>V&iacute;nculo directo con stock y clientes</li>
        <li>Mejor continuidad entre cobro y facturaci&oacute;n</li>
      </ul>
    </div>

    <div class="surface-card">
      <h3>Qu&eacute; conviene revisar en una demo</h3>
      <p>
        Vale la pena mirar c&oacute;mo se comporta el circuito completo: atenci&oacute;n, cobro, caja,
        medios de pago, stock y comprobantes dentro de la misma l&oacute;gica.
      </p>
      <ul class="link-list">
        <li><a href="<?= e(site_url('control-de-stock.php')) ?>">Ver c&oacute;mo trabaja el stock</a></li>
        <li><a href="<?= e(site_url('facturacion.php')) ?>">Ver la facturaci&oacute;n integrada</a></li>
      </ul>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta-box">
      <h2>Si busc&aacute;s un software para ventas, mir&aacute; tambi&eacute;n lo que pasa despu&eacute;s del cobro</h2>
      <p>
        Ah&iacute; es donde un sistema POS suma o decepciona. FLUS apunta a que la caja no quede separada del resto de la gesti&oacute;n comercial.
      </p>
      <div class="inline-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Pedir una demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-de-gestion.php')) ?>">Ver visi&oacute;n completa</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
