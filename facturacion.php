<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Facturación integrada a ventas, caja y control comercial | FLUS';
$pageDescription = 'FLUS integra la facturación al flujo comercial para acompañar ventas, caja, clientes y seguimiento operativo con más continuidad y menos tareas manuales.';
$pageBreadcrumbs = [
    ['name' => 'Inicio', 'url' => page_url()],
    ['name' => 'Facturación', 'url' => page_url('facturacion.php')],
];
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container page-hero-grid">
    <div>
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="<?= e(site_url()) ?>">Inicio</a>
        <span>/</span>
        <span aria-current="page">Facturación</span>
      </nav>
      <span class="eyebrow">Orden documental</span>
      <h1>Facturación integrada para que la venta no se corte a mitad de camino</h1>
      <p>
        Cuando la facturación acompaña el mismo circuito comercial, el negocio gana continuidad,
        reduce pasos manuales y sostiene mejor la trazabilidad de cada operación.
      </p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Solicitar demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-de-gestion.php')) ?>">Ver sistema completo</a>
      </div>
    </div>

    <aside class="hero-panel">
      <h2>Dónde aporta más orden</h2>
      <ul class="check-list">
        <li>Mayor continuidad entre venta y comprobante.</li>
        <li>Mejor trazabilidad sobre clientes y operaciones.</li>
        <li>Menos saltos entre herramientas separadas.</li>
        <li>Más contexto para revisar la gestión comercial.</li>
      </ul>
    </aside>
  </div>
</section>

<section class="section">
  <div class="container">
    <span class="section-kicker">Valor operativo</span>
    <h2>La facturación aporta más cuando acompaña el trabajo diario</h2>
    <div class="feature-grid">
      <article class="feature-card">
        <h3>Menos fricción operativa</h3>
        <p>La venta avanza con más continuidad y sin tener que rearmar información en otra herramienta.</p>
      </article>
      <article class="feature-card">
        <h3>Mejor trazabilidad comercial</h3>
        <p>Cliente, caja, operación y comprobante quedan mejor conectados dentro del mismo contexto.</p>
      </article>
      <article class="feature-card">
        <h3>Más orden administrativo</h3>
        <p>La información queda más prolija para revisar movimientos y sostener seguimiento posterior.</p>
      </article>
      <article class="feature-card">
        <h3>Menos trabajo manual</h3>
        <p>Se reduce el ida y vuelta entre sistemas o pasos que cortan el flujo comercial.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-dark">
  <div class="container split-grid">
    <div class="surface-card">
      <h3>Qué suele pasar cuando está separada</h3>
      <p>
        El negocio termina duplicando pasos, moviendo información de un lado a otro y perdiendo tiempo
        en tareas que deberían resolverse dentro del mismo flujo.
      </p>
    </div>

    <div class="surface-card">
      <h3>Cómo encaja FLUS</h3>
      <p>
        FLUS cobra más sentido cuando la facturación acompaña la dinámica real de ventas, caja,
        clientes y seguimiento del negocio.
      </p>
      <ul class="link-list">
        <li><a href="<?= e(site_url('sistema-pos.php')) ?>">Ver relación con el sistema POS</a></li>
        <li><a href="<?= e(site_url('control-de-stock.php')) ?>">Ver relación con stock</a></li>
      </ul>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta-box">
      <h2>Si hoy la facturación obliga a salir de la operación, hay una mejora clara para trabajar</h2>
      <p>
        La idea no es sumar complejidad. La idea es cerrar mejor el proceso comercial y sostener una gestión más prolija.
      </p>
      <div class="inline-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Pedir una demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-de-gestion.php')) ?>">Volver al sistema</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
