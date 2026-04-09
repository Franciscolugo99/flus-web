<?php
$pageTitle = 'FLUS | Sistema de gestion comercial para ventas, stock, caja y facturacion';
$pageDescription = 'FLUS es un sistema de gestion comercial para comercios y pymes que necesitan ordenar ventas, stock, caja, clientes y facturacion con mas control diario y menos planillas.';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="container hero-grid">
    <div>
      <span class="eyebrow">Sistema de gesti&oacute;n comercial para comercios y pymes</span>
      <h1>Orden&aacute; ventas, stock, caja y facturaci&oacute;n con un mismo sistema de gesti&oacute;n comercial</h1>
      <p>
        FLUS est&aacute; pensado para negocios que ya necesitan una operaci&oacute;n m&aacute;s seria: menos planillas sueltas,
        mejor seguimiento comercial y m&aacute;s trazabilidad en el trabajo diario.
      </p>

      <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Solicitar demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-de-gestion.php')) ?>">Ver sistema de gesti&oacute;n</a>
      </div>

      <div class="hero-highlights">
        <div class="mini-stat">
          <strong>Ventas y caja</strong>
          <span>Operaci&oacute;n diaria m&aacute;s ordenada en mostrador, cobros y control de caja.</span>
        </div>
        <div class="mini-stat">
          <strong>Stock y trazabilidad</strong>
          <span>M&aacute;s claridad para seguir productos, movimientos y disponibilidad real.</span>
        </div>
        <div class="mini-stat">
          <strong>Clientes y facturaci&oacute;n</strong>
          <span>Mejor continuidad entre seguimiento comercial, comprobantes e historial.</span>
        </div>
      </div>
    </div>

    <aside class="hero-panel">
      <h2>Pensado para la operaci&oacute;n real del negocio</h2>
      <p>
        FLUS no busca sumar pantallas por sumar. Busca ordenar el circuito comercial para que vender,
        cobrar, controlar stock y facturar respondan a una misma l&oacute;gica.
      </p>
      <ul class="check-list">
        <li>Menos pasos manuales entre venta, caja, stock y facturaci&oacute;n</li>
        <li>M&aacute;s control diario sobre lo que pas&oacute; en cada operaci&oacute;n</li>
        <li>Menos dependencia de planillas y memoria operativa</li>
        <li>Una base m&aacute;s profesional para seguir creciendo con orden</li>
      </ul>
      <div class="panel-note">
        Hace m&aacute;s sentido cuando el negocio ya no puede trabajar bien con informaci&oacute;n repartida.
      </div>
    </aside>
  </div>
</section>

<section class="section">
  <div class="container">
    <span class="section-kicker">Problemas reales</span>
    <h2>Cuando la operaci&oacute;n va por separado, el negocio pierde control</h2>
    <p class="section-lead">
      El problema no es solo administrativo. Cuando ventas, stock, caja y facturaci&oacute;n no est&aacute;n conectados,
      se trabaja con dudas, se corrige tarde y el seguimiento comercial queda flojo.
    </p>

    <div class="metrics">
      <article class="metric">
        <strong>Se vende sin contexto</strong>
        <p>La atenci&oacute;n avanza, pero despu&eacute;s cuesta reconstruir qu&eacute; se vendi&oacute;, c&oacute;mo se cobr&oacute; y qu&eacute; pas&oacute; con el cliente.</p>
      </article>
      <article class="metric">
        <strong>La caja se revisa tarde</strong>
        <p>Si aperturas, cierres y medios de pago quedan poco claros, el control diario se vuelve reactivo.</p>
      </article>
      <article class="metric">
        <strong>El stock deja dudas</strong>
        <p>Cuando la disponibilidad no es confiable, se vende con inseguridad y se repone sin buen contexto.</p>
      </article>
      <article class="metric">
        <strong>El seguimiento comercial depende de memoria</strong>
        <p>Clientes, comprobantes e historial quedan dispersos y la operaci&oacute;n pierde profesionalismo.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-dark">
  <div class="container">
    <span class="section-kicker">Circuito integrado</span>
    <h2>FLUS conecta las &aacute;reas que sostienen la gesti&oacute;n comercial diaria</h2>
    <p class="section-lead">
      El valor est&aacute; en que la informaci&oacute;n no quede aislada. Eso mejora el uso diario y tambi&eacute;n la calidad del control.
    </p>

    <div class="cards">
      <article class="card">
        <h3>Ventas y sistema POS</h3>
        <p>Atenci&oacute;n, cobro y registro dentro del mismo flujo. <a class="text-link" href="<?= e(site_url('sistema-pos.php')) ?>">Ver sistema POS</a>.</p>
      </article>
      <article class="card">
        <h3>Caja y medios de pago</h3>
        <p>M&aacute;s claridad para seguir movimientos diarios, aperturas, cierres y cobros sin desorden.</p>
      </article>
      <article class="card">
        <h3>Control de stock</h3>
        <p>Productos, movimientos y disponibilidad con mejor contexto operativo. <a class="text-link" href="<?= e(site_url('control-de-stock.php')) ?>">Ver stock</a>.</p>
      </article>
      <article class="card">
        <h3>Clientes</h3>
        <p>Historial comercial y seguimiento mejor organizados para sostener una relaci&oacute;n m&aacute;s prolija.</p>
      </article>
      <article class="card">
        <h3>Facturaci&oacute;n</h3>
        <p>Comprobantes vinculados al circuito comercial para evitar saltos innecesarios entre herramientas. <a class="text-link" href="<?= e(site_url('facturacion.php')) ?>">Ver facturaci&oacute;n</a>.</p>
      </article>
      <article class="card">
        <h3>Reportes y control</h3>
        <p>Informaci&oacute;n m&aacute;s ordenada para revisar el negocio con mejor criterio y menos improvisaci&oacute;n.</p>
      </article>
    </div>
  </div>
</section>

<section class="section">
  <div class="container two-col">
    <div>
      <span class="section-kicker">Donde encaja mejor</span>
      <h2>FLUS tiene m&aacute;s sentido cuando la operaci&oacute;n ya necesita orden real</h2>
      <p class="section-lead">
        No es una web para prometer magia. Tiene sentido para comercios y pymes que necesitan trabajar mejor el d&iacute;a a d&iacute;a.
      </p>
      <ul class="feature-list">
        <li>Comercios con venta diaria, caja y control de stock</li>
        <li>Negocios que necesitan un software para ventas con m&aacute;s contexto operativo</li>
        <li>Pymes que quieren dejar de depender de planillas dispersas</li>
        <li>Equipos que buscan m&aacute;s trazabilidad y mejor seguimiento comercial</li>
      </ul>
    </div>

    <div class="surface-card">
      <h3>Qu&eacute; conviene mirar en una demo</h3>
      <p>
        La evaluaci&oacute;n tiene que ir por el flujo real del negocio: c&oacute;mo se vende, c&oacute;mo se cobra,
        c&oacute;mo se controla stock y c&oacute;mo se sostiene el seguimiento comercial despu&eacute;s.
      </p>
      <ul class="plain-list" style="margin-top:18px;">
        <li>C&oacute;mo se relacionan ventas, caja y facturaci&oacute;n</li>
        <li>Qu&eacute; nivel de visibilidad hay sobre stock y movimientos</li>
        <li>C&oacute;mo queda organizado el historial comercial</li>
        <li>Qu&eacute; tanto trabajo manual se puede reducir</li>
      </ul>
      <div class="inline-actions" style="margin-top:22px;">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Pedir una demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-pos.php')) ?>">Ver enfoque POS</a>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <span class="section-kicker">Preguntas frecuentes</span>
    <h2>Lo que suele evaluar una empresa antes de cambiar su sistema</h2>
    <div class="faq-grid">
      <article class="faq-item">
        <h3>&iquest;FLUS es solo un sistema POS?</h3>
        <p>No. Tambi&eacute;n ordena stock, caja, clientes y facturaci&oacute;n para que la operaci&oacute;n comercial no quede partida.</p>
      </article>
      <article class="faq-item">
        <h3>&iquest;Para qu&eacute; tipo de negocio sirve mejor?</h3>
        <p>Para comercios y pymes que ya necesitan m&aacute;s control diario, mejor trazabilidad y menos dependencia de planillas.</p>
      </article>
      <article class="faq-item">
        <h3>&iquest;Qu&eacute; problema resuelve con m&aacute;s claridad?</h3>
        <p>La dispersi&oacute;n operativa: cuando cada parte del negocio trabaja por separado y eso termina afectando control y seguimiento.</p>
      </article>
      <article class="faq-item">
        <h3>&iquest;Se puede solicitar una demo?</h3>
        <p>S&iacute;. Desde la p&aacute;gina de contacto pod&eacute;s coordinar una conversaci&oacute;n para evaluar FLUS sobre tu operaci&oacute;n real.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-dark">
  <div class="container">
    <div class="cta-box">
      <h2>Si tu operaci&oacute;n ya no entra c&oacute;moda en planillas, conviene verla con criterio real</h2>
      <p>
        FLUS est&aacute; orientado a negocios que necesitan una gesti&oacute;n comercial m&aacute;s clara, m&aacute;s conectada
        y m&aacute;s profesional para ventas, stock, caja y facturaci&oacute;n.
      </p>
      <div class="inline-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Solicitar demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-de-gestion.php')) ?>">Profundizar en el sistema</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
