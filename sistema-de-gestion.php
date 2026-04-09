<?php
$pageTitle = 'Sistema de gestión comercial | FLUS';
$pageDescription = 'Conocé FLUS como sistema de gestión comercial para ventas, stock, clientes, caja y facturación.';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container two-col">
    <div>
      <span class="eyebrow">Sistema de gestión comercial</span>
      <h1>Una solución para ordenar la operación del negocio</h1>
      <p>
        FLUS reúne procesos clave como ventas, stock, clientes, caja y facturación para que el negocio trabaje con
        más consistencia, menos errores manuales y mejor información para decidir.
      </p>
    </div>

    <aside class="hero-panel">
      <h2>Qué aporta un sistema de gestión</h2>
      <ul class="check-list">
        <li>Centralización de la operación diaria</li>
        <li>Menos tareas duplicadas</li>
        <li>Mayor control sobre ventas y productos</li>
        <li>Mejor trazabilidad de movimientos</li>
      </ul>
    </aside>
  </div>
</section>

<section class="section">
  <div class="container">
    <h2>Áreas que cubre FLUS</h2>
    <p class="section-lead">
      El sitio quedó armado para explicar con claridad qué hace FLUS y atacar búsquedas relevantes desde páginas separadas,
      en vez de meter todo en una sola home.
    </p>

    <div class="cards">
      <article class="card">
        <h3>Ventas</h3>
        <p>Flujo operativo más claro para registrar y seguir operaciones comerciales.</p>
      </article>
      <article class="card">
        <h3>Stock</h3>
        <p>Control de disponibilidad, movimientos y productos desde una misma base.</p>
      </article>
      <article class="card">
        <h3>Clientes</h3>
        <p>Historial e información comercial útil para mejorar el seguimiento.</p>
      </article>
      <article class="card">
        <h3>Caja</h3>
        <p>Mayor orden en aperturas, cierres y movimientos diarios.</p>
      </article>
      <article class="card">
        <h3>Facturación</h3>
        <p>Integración con comprobantes y procesos fiscales dentro del mismo flujo.</p>
      </article>
      <article class="card">
        <h3>Reportes</h3>
        <p>Información resumida para entender mejor el negocio y tomar decisiones.</p>
      </article>
    </div>
  </div>
</section>

<section class="section">
  <div class="container two-col">
    <div class="surface-card card">
      <h3>Beneficios concretos</h3>
      <ul class="plain-list">
        <li>Más orden en la operación del negocio</li>
        <li>Menos dependencia de planillas o procesos dispersos</li>
        <li>Mejor visibilidad de ventas, caja y stock</li>
        <li>Una base más profesional para crecer</li>
      </ul>
    </div>

    <div class="surface-card card">
      <h3>Siguiente paso recomendado</h3>
      <p>
        Personalizá esta página con capturas reales de FLUS, casos de uso y llamados a la acción con tu contacto real.
      </p>
      <div class="inline-actions" style="margin-top:16px;">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Ir a contacto</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
