<?php
$pageTitle = 'Sistema POS para comercios | FLUS';
$pageDescription = 'FLUS como sistema POS para comercios: ventas, caja, medios de pago, clientes y control operativo.';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container two-col">
    <div>
      <span class="eyebrow">Sistema POS</span>
      <h1>Un sistema POS pensado para vender con más orden</h1>
      <p>
        Esta página apunta a posicionar FLUS como sistema POS para comercios que necesitan rapidez en caja,
        seguimiento de operaciones y mejor control de la información comercial.
      </p>
    </div>

    <aside class="hero-panel">
      <h2>Claves de un buen POS</h2>
      <ul class="check-list">
        <li>Velocidad de uso</li>
        <li>Claridad en medios de pago</li>
        <li>Trazabilidad de la operación</li>
        <li>Integración con stock y facturación</li>
      </ul>
    </aside>
  </div>
</section>

<section class="section">
  <div class="container">
    <h2>Qué debería resolver tu sistema POS</h2>
    <div class="cards">
      <article class="card">
        <h3>Venta ágil</h3>
        <p>Menos fricción al cobrar y mejor experiencia operativa en mostrador.</p>
      </article>
      <article class="card">
        <h3>Control de caja</h3>
        <p>Orden en movimientos, cierres y seguimiento diario.</p>
      </article>
      <article class="card">
        <h3>Medios de pago</h3>
        <p>Base clara para administrar efectivo, transferencias y otras formas de cobro.</p>
      </article>
      <article class="card">
        <h3>Clientes</h3>
        <p>Historial e información útil para seguimiento comercial.</p>
      </article>
      <article class="card">
        <h3>Stock enlazado</h3>
        <p>Un POS aislado se queda corto; FLUS suma control operativo real.</p>
      </article>
      <article class="card">
        <h3>Escalabilidad</h3>
        <p>Una base más sólida para acompañar el crecimiento del negocio.</p>
      </article>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta-box">
      <h2>FLUS no debería venderse solo como caja</h2>
      <p>
        Comercialmente te conviene mostrarlo como sistema POS, pero sin reducirlo a eso.
        El diferencial está en que no se quede solo en cobrar: también integra stock, clientes, caja y facturación.
      </p>
      <div class="inline-actions">
        <a class="btn btn-primary" href="<?= e(site_url('sistema-de-gestion.php')) ?>">Ver visión completa</a>
        <a class="btn btn-secondary" href="<?= e(site_url('contacto.php')) ?>">Pedir demo</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
