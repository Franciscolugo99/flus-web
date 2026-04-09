<?php
$pageTitle = 'FLUS | Sistema de gestión comercial para negocios';
$pageDescription = 'FLUS es un sistema de gestión comercial para ventas, stock, clientes, caja y facturación, pensado para comercios y pymes.';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="container hero-grid">
    <div>
      <span class="eyebrow">Software para ventas, stock, caja y facturación</span>
      <h1>FLUS, sistema de gestión comercial para negocios</h1>
      <p>
        Centralizá la operación diaria del negocio en una sola plataforma. FLUS ayuda a vender más ordenado,
        controlar stock, gestionar clientes, operar caja y trabajar con información más clara.
      </p>

      <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Solicitar demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-de-gestion.php')) ?>">Ver funcionalidades</a>
      </div>
    </div>

    <aside class="hero-panel">
      <h2>Qué puede resolver FLUS</h2>
      <ul class="check-list">
        <li>Ventas más rápidas y trazables</li>
        <li>Control de stock y movimientos</li>
        <li>Seguimiento de clientes e historial</li>
        <li>Caja diaria con más orden operativo</li>
        <li>Facturación y reportes desde un mismo entorno</li>
      </ul>
    </aside>
  </div>
</section>

<section class="section">
  <div class="container">
    <h2>Una base sólida para la operación comercial</h2>
    <p class="section-lead">
      El objetivo de FLUS es concentrar los procesos más importantes del negocio en una única herramienta,
      reduciendo errores manuales y mejorando el control operativo del día a día.
    </p>

    <div class="cards">
      <article class="card">
        <h3>Sistema de ventas</h3>
        <p>Registrá operaciones con mayor velocidad, mejor detalle y más consistencia.</p>
      </article>

      <article class="card">
        <h3>Control de stock</h3>
        <p>Seguimiento de productos, movimientos y disponibilidad en una sola vista operativa.</p>
      </article>

      <article class="card">
        <h3>Caja y cobranzas</h3>
        <p>Más claridad para aperturas, cierres, medios de pago y movimientos diarios.</p>
      </article>

      <article class="card">
        <h3>Clientes</h3>
        <p>Información comercial, historial y mejor seguimiento para cada operación.</p>
      </article>

      <article class="card">
        <h3>Facturación</h3>
        <p>Gestión de comprobantes y procesos fiscales dentro del flujo de trabajo comercial.</p>
      </article>

      <article class="card">
        <h3>Reportes</h3>
        <p>Datos más claros para tomar decisiones con mejor contexto operativo.</p>
      </article>
    </div>
  </div>
</section>

<section class="section">
  <div class="container two-col">
    <div>
      <h2>Para quién está pensado FLUS</h2>
      <p class="section-lead">
        FLUS apunta a comercios y pymes que necesitan un sistema de gestión comercial con foco en la operación real:
        vender, controlar stock, administrar caja y sostener trazabilidad sin depender de planillas dispersas.
      </p>

      <ul class="feature-list">
        <li>Locales comerciales que trabajan con caja y stock</li>
        <li>Negocios que necesitan un sistema POS más integrado</li>
        <li>Comercios que buscan más orden en ventas y facturación</li>
        <li>Pymes que necesitan información operativa más clara</li>
      </ul>
    </div>

    <div class="cta-box">
      <h3>Base SEO inicial</h3>
      <p>
        Esta versión ya deja creadas páginas específicas para búsquedas como sistema de gestión,
        sistema POS, control de stock y facturación.
      </p>
      <div class="inline-actions">
        <a class="btn btn-primary" href="<?= e(site_url('sistema-pos.php')) ?>">Ver página POS</a>
        <a class="btn btn-secondary" href="<?= e(site_url('control-de-stock.php')) ?>">Ver página de stock</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
