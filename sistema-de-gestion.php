<?php
$pageTitle = 'Sistema de gestion comercial para comercios y pymes | FLUS';
$pageDescription = 'Conoce FLUS como sistema de gestion comercial para ventas, stock, clientes, caja y facturacion, pensado para ordenar la operacion diaria con mas control y trazabilidad.';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container two-col">
    <div>
      <span class="eyebrow">Sistema de gesti&oacute;n comercial</span>
      <h1>Un sistema de gesti&oacute;n comercial para ordenar la operaci&oacute;n y no solo registrar datos</h1>
      <p>
        FLUS est&aacute; orientado a comercios y pymes que necesitan trabajar ventas, stock, caja,
        clientes y facturaci&oacute;n desde una l&oacute;gica m&aacute;s conectada y profesional.
      </p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Solicitar demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-pos.php')) ?>">Ver sistema POS</a>
      </div>
    </div>

    <aside class="hero-panel">
      <h2>Qu&eacute; deber&iacute;a resolver un buen sistema</h2>
      <p>El objetivo no es cargar m&aacute;s pantallas. El objetivo es trabajar con una base m&aacute;s clara para la operaci&oacute;n diaria.</p>
      <ul class="check-list">
        <li>Centralizar procesos comerciales clave</li>
        <li>Dar m&aacute;s visibilidad sobre ventas, stock y caja</li>
        <li>Reducir cortes entre atenci&oacute;n, cobro y facturaci&oacute;n</li>
        <li>Mejorar el seguimiento comercial y la trazabilidad</li>
      </ul>
    </aside>
  </div>
</section>

<section class="section">
  <div class="container">
    <span class="section-kicker">Qu&eacute; ordena</span>
    <h2>Las &aacute;reas que un sistema de gesti&oacute;n comercial tiene que conectar bien</h2>
    <p class="section-lead">
      Cuando la gesti&oacute;n se apoya en un mismo criterio operativo, el negocio gana control, coherencia y capacidad de seguimiento.
    </p>

    <div class="metrics">
      <article class="metric">
        <strong>Ventas y atenci&oacute;n</strong>
        <p>La operaci&oacute;n se vuelve m&aacute;s consistente cuando la venta no queda separada del resto del circuito.</p>
      </article>
      <article class="metric">
        <strong>Stock</strong>
        <p>La disponibilidad y los movimientos dejan de ser un dato aislado y pasan a ser parte de la decisi&oacute;n diaria.</p>
      </article>
      <article class="metric">
        <strong>Caja y medios de pago</strong>
        <p>M&aacute;s orden para seguir aperturas, cierres, cobros y movimientos con menos fricci&oacute;n.</p>
      </article>
      <article class="metric">
        <strong>Clientes y facturaci&oacute;n</strong>
        <p>Historial, comprobantes y seguimiento quedan mejor alineados con la realidad comercial.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-dark">
  <div class="container split-grid">
    <div class="surface-card">
      <h3>Problemas que busca corregir</h3>
      <ul class="plain-list">
        <li>Informaci&oacute;n repartida entre planillas y herramientas sueltas</li>
        <li>Stock poco confiable para vender con seguridad</li>
        <li>Caja desconectada del resto de la operaci&oacute;n</li>
        <li>Poco contexto para revisar clientes, movimientos y comprobantes</li>
      </ul>
    </div>

    <div class="surface-card">
      <h3>Qu&eacute; cambia cuando est&aacute; bien planteado</h3>
      <p>
        Un sistema de gesti&oacute;n comercial vale m&aacute;s cuando mejora el trabajo diario. Eso implica menos tareas repetidas,
        mejor continuidad entre &aacute;reas y una base m&aacute;s clara para seguir el negocio.
      </p>
      <ul class="link-list">
        <li><a href="<?= e(site_url('sistema-pos.php')) ?>">Profundizar en el sistema POS</a></li>
        <li><a href="<?= e(site_url('control-de-stock.php')) ?>">Ver control de stock</a></li>
        <li><a href="<?= e(site_url('facturacion.php')) ?>">Ver facturaci&oacute;n integrada</a></li>
      </ul>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta-box">
      <h2>Evalu&aacute; FLUS mirando la operaci&oacute;n completa, no una lista aislada de funciones</h2>
      <p>
        Si quer&eacute;s revisar un sistema de gesti&oacute;n comercial con criterio real, lo importante es ver c&oacute;mo se conectan ventas,
        caja, stock, clientes y facturaci&oacute;n en el d&iacute;a a d&iacute;a.
      </p>
      <div class="inline-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Pedir una demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url()) ?>">Volver al inicio</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
