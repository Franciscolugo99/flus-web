<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Sistema de gestión comercial para ventas, stock, caja y facturación | FLUS';
$pageDescription = 'FLUS es un sistema de gestión comercial para comercios y pymes que necesitan ordenar ventas, stock, caja, clientes y facturación con más control diario y menos planillas.';
$pageSchemas = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'FLUS',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web',
        'description' => 'Sistema de gestión comercial para ventas, stock, caja, clientes y facturación.',
        'url' => page_url(),
    ],
    [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => [
            [
                '@type' => 'Question',
                'name' => '¿FLUS es solo un sistema POS?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'No. También ayuda a ordenar ventas, caja, stock, clientes y facturación dentro de una misma lógica comercial.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => '¿Para qué tipo de negocio sirve mejor?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Para comercios y pymes que necesitan menos planillas, más control diario y mejor trazabilidad operativa.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => '¿Qué conviene mirar en una demo?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'El circuito real: cómo se vende, cómo se cobra, cómo impacta en stock y cómo se sostiene el seguimiento comercial.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => '¿Se puede coordinar una demo de FLUS?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Sí. Desde la página de contacto se puede iniciar una conversación para evaluar FLUS sobre la operación real del negocio.',
                ],
            ],
        ],
    ],
];
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="container hero-grid">
    <div class="hero-copy">
      <span class="eyebrow">Sistema de gestión comercial para comercios y pymes</span>
      <h1>FLUS ordena ventas, caja, stock y facturación dentro de un circuito comercial más claro</h1>
      <p>
        Pensado para negocios que ya necesitan una operación más clara, con menos trabajo manual,
        más trazabilidad y mejor continuidad entre lo que se vende, lo que se cobra y lo que después hay que seguir.
      </p>

      <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Solicitar demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-de-gestion.php')) ?>">Ver cómo trabaja FLUS</a>
      </div>

      <ul class="hero-points">
        <li>Menos planillas sueltas y menos doble carga.</li>
        <li>Más control sobre ventas, caja y stock en el día a día.</li>
        <li>Mejor seguimiento comercial y más orden documental.</li>
      </ul>
    </div>

    <aside class="product-frame" aria-label="Vista conceptual del circuito operativo de FLUS">
      <div class="product-topbar">
        <span></span><span></span><span></span>
      </div>

      <div class="product-body">
        <div class="product-sidebar">
          <strong>FLUS</strong>
          <a class="is-active" href="#">Ventas</a>
          <a href="#">Caja</a>
          <a href="#">Stock</a>
          <a href="#">Facturación</a>
        </div>

        <div class="product-main">
          <div class="preview-summary">
            <div>
              <strong>Vista conceptual del trabajo diario</strong>
              <small>Venta, caja, stock y seguimiento dentro de una misma lógica</small>
            </div>
            <span class="status-pill">Operación conectada</span>
          </div>

          <div class="preview-grid">
            <article class="preview-card">
              <span class="preview-label">Venta registrada</span>
              <strong>Mostrador + caja</strong>
              <small>Cobro y operación dentro del mismo flujo.</small>
            </article>
            <article class="preview-card">
              <span class="preview-label">Stock visible</span>
              <strong>Disponibilidad</strong>
              <small>Más contexto para vender y reponer.</small>
            </article>
            <article class="preview-card">
              <span class="preview-label">Seguimiento</span>
              <strong>Clientes y comprobantes</strong>
              <small>Más trazabilidad después de cada operación.</small>
            </article>
          </div>

          <div class="flow-track">
            <span>Venta</span>
            <span>Caja</span>
            <span>Stock</span>
            <span>Facturación</span>
          </div>

          <div class="preview-note">
            <strong>La lógica es una sola:</strong>
            atender, cobrar, controlar y seguir la operación sin repartir el trabajo entre herramientas aisladas.
          </div>
        </div>
      </div>
    </aside>
  </div>
</section>

<section class="section">
  <div class="container">
    <span class="section-kicker">Qué resuelve</span>
    <h2>Cuando la operación está partida, el negocio pierde control</h2>
    <p class="section-lead">
      FLUS busca atacar un problema concreto: que ventas, caja, stock y facturación no queden trabajando por separado.
      Esa dispersión siempre termina pegando en tiempo, control y seguimiento.
    </p>

    <div class="feature-grid">
      <article class="feature-card">
        <h3>Ventas con más contexto</h3>
        <p>La operación no termina en el cobro. Después importan caja, stock, cliente y comprobante.</p>
      </article>
      <article class="feature-card">
        <h3>Caja más clara</h3>
        <p>Más visibilidad sobre medios de pago, movimientos diarios y cierres con criterio real.</p>
      </article>
      <article class="feature-card">
        <h3>Stock más confiable</h3>
        <p>Menos dudas sobre disponibilidad, movimientos y el impacto de cada venta.</p>
      </article>
      <article class="feature-card">
        <h3>Seguimiento comercial</h3>
        <p>Historial, clientes y facturación con mejor continuidad operativa.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-dark">
  <div class="container">
    <span class="section-kicker">Cómo trabaja FLUS</span>
    <h2>Un circuito más ordenado para la gestión comercial diaria</h2>
    <div class="steps-grid">
      <article class="step-card">
        <span class="step-number">01</span>
        <h3>Venta y atención</h3>
        <p>La operación arranca con una base más prolija para vender, registrar y seguir cada movimiento.</p>
      </article>
      <article class="step-card">
        <span class="step-number">02</span>
        <h3>Caja y cobro</h3>
        <p>Los medios de pago y el control diario quedan más claros dentro del mismo circuito.</p>
      </article>
      <article class="step-card">
        <span class="step-number">03</span>
        <h3>Stock y disponibilidad</h3>
        <p>La venta conversa mejor con el stock y eso reduce incertidumbre en la operación.</p>
      </article>
      <article class="step-card">
        <span class="step-number">04</span>
        <h3>Comprobante y seguimiento</h3>
        <p>Facturación, cliente e historial comercial quedan mejor conectados para revisar lo que pasó.</p>
      </article>
    </div>
  </div>
</section>

<section class="section">
  <div class="container split-grid">
    <div>
      <span class="section-kicker">Soluciones</span>
      <h2>Explorá FLUS desde la parte de la operación que más te preocupa</h2>
      <p class="section-lead">
        Cada página interna está pensada para bajar el problema a tierra y mostrar cómo encaja FLUS en la gestión diaria.
      </p>

      <div class="link-stack">
        <a class="stack-link" href="<?= e(site_url('sistema-de-gestion.php')) ?>">
          <strong>Sistema de gestión</strong>
          <span>Visión completa para ventas, stock, caja, clientes y facturación.</span>
        </a>
        <a class="stack-link" href="<?= e(site_url('sistema-pos.php')) ?>">
          <strong>Sistema POS</strong>
          <span>Mostrador, caja y cobro con más orden y mejor continuidad.</span>
        </a>
        <a class="stack-link" href="<?= e(site_url('control-de-stock.php')) ?>">
          <strong>Control de stock</strong>
          <span>Disponibilidad y movimientos con mejor contexto operativo.</span>
        </a>
        <a class="stack-link" href="<?= e(site_url('facturacion.php')) ?>">
          <strong>Facturación</strong>
          <span>Comprobantes integrados a la operación comercial.</span>
        </a>
      </div>
    </div>

    <div class="surface-card">
      <h3>Dónde tiene más sentido</h3>
      <p>
        FLUS encaja mejor en comercios y pymes donde la operación ya no entra cómoda en planillas,
        memoria operativa o herramientas desconectadas.
      </p>
      <ul class="plain-list">
        <li>Comercios con ventas diarias, caja y stock.</li>
        <li>Equipos que necesitan menos tareas repetidas.</li>
        <li>Negocios que buscan más orden comercial y más trazabilidad.</li>
        <li>Operaciones que necesitan crecer con una base más seria.</li>
      </ul>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <span class="section-kicker">Preguntas frecuentes</span>
    <h2>Lo que suele evaluarse antes de pedir una demo</h2>
    <div class="faq-grid">
      <article class="faq-item">
        <h3>¿FLUS es solo un sistema POS?</h3>
        <p>No. También ayuda a ordenar ventas, caja, stock, clientes y facturación dentro de una misma lógica comercial.</p>
      </article>
      <article class="faq-item">
        <h3>¿Para qué tipo de negocio sirve mejor?</h3>
        <p>Para comercios y pymes que necesitan menos planillas, más control diario y mejor trazabilidad operativa.</p>
      </article>
      <article class="faq-item">
        <h3>¿Qué conviene mirar en una demo?</h3>
        <p>El circuito real: cómo se vende, cómo se cobra, cómo impacta en stock y cómo se sostiene el seguimiento comercial.</p>
      </article>
      <article class="faq-item">
        <h3>¿Se puede coordinar una demo de FLUS?</h3>
        <p>Sí. Desde la página de contacto se puede iniciar una conversación para evaluar FLUS sobre la operación real del negocio.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-dark">
  <div class="container">
    <div class="cta-box">
      <h2>Si tu operación ya necesita más orden, conviene mirar FLUS sobre el flujo real del negocio</h2>
      <p>
        La mejor demo es la que se evalúa con ventas, caja, stock y facturación como parte de una misma conversación.
      </p>
      <div class="inline-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Solicitar demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-pos.php')) ?>">Ver sistema POS</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
