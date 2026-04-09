<?php
$pageTitle = 'Facturacion integrada a ventas, caja y control comercial | FLUS';
$pageDescription = 'FLUS integra la facturacion al flujo comercial para acompanar ventas, caja, clientes y seguimiento operativo con mas continuidad y menos tareas manuales.';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container two-col">
    <div>
      <span class="eyebrow">Facturaci&oacute;n</span>
      <h1>Facturaci&oacute;n integrada para que la venta no se corte a mitad de camino</h1>
      <p>
        Cuando la facturaci&oacute;n forma parte del mismo circuito comercial, el negocio gana continuidad,
        reduce pasos manuales y sostiene mejor la trazabilidad de cada operaci&oacute;n.
      </p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Solicitar demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-de-gestion.php')) ?>">Ver sistema completo</a>
      </div>
    </div>

    <aside class="hero-panel">
      <h2>Qu&eacute; mejora cuando la facturaci&oacute;n est&aacute; integrada</h2>
      <ul class="check-list">
        <li>M&aacute;s continuidad entre venta y comprobante</li>
        <li>Mayor trazabilidad sobre clientes y operaciones</li>
        <li>Menos saltos entre herramientas separadas</li>
        <li>M&aacute;s orden para revisar la gesti&oacute;n comercial</li>
      </ul>
    </aside>
  </div>
</section>

<section class="section">
  <div class="container">
    <span class="section-kicker">Valor operativo</span>
    <h2>La facturaci&oacute;n suma mucho m&aacute;s cuando acompa&ntilde;a el trabajo diario</h2>
    <div class="cards">
      <article class="card">
        <h3>Menos fricci&oacute;n operativa</h3>
        <p>La venta avanza con m&aacute;s continuidad y sin tener que rearmar informaci&oacute;n en otra herramienta.</p>
      </article>
      <article class="card">
        <h3>Mejor trazabilidad comercial</h3>
        <p>Cliente, caja, operaci&oacute;n y comprobante quedan mejor conectados dentro del mismo contexto.</p>
      </article>
      <article class="card">
        <h3>M&aacute;s control administrativo</h3>
        <p>La informaci&oacute;n queda m&aacute;s prolija para revisar movimientos y sostener seguimiento posterior.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-dark">
  <div class="container split-grid">
    <div class="surface-card">
      <h3>Qu&eacute; suele pasar cuando est&aacute; por separado</h3>
      <p>
        El negocio termina duplicando pasos, moviendo informaci&oacute;n de un lado a otro y perdiendo
        tiempo en tareas que deber&iacute;an resolverse dentro del mismo flujo comercial.
      </p>
    </div>

    <div class="surface-card">
      <h3>C&oacute;mo encaja FLUS</h3>
      <p>
        FLUS cobra m&aacute;s valor cuando la facturaci&oacute;n acompa&ntilde;a la din&aacute;mica real de ventas, caja,
        clientes y seguimiento del negocio.
      </p>
      <ul class="link-list">
        <li><a href="<?= e(site_url('sistema-pos.php')) ?>">Ver relaci&oacute;n con el sistema POS</a></li>
        <li><a href="<?= e(site_url('control-de-stock.php')) ?>">Ver relaci&oacute;n con stock</a></li>
      </ul>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta-box">
      <h2>Si la facturaci&oacute;n hoy obliga a salir de la operaci&oacute;n, hay margen claro para mejorar</h2>
      <p>
        La idea no es sumar complejidad. La idea es que el proceso comercial quede mejor cerrado, m&aacute;s claro y m&aacute;s profesional.
      </p>
      <div class="inline-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Pedir una demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-de-gestion.php')) ?>">Volver al sistema de gesti&oacute;n</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
