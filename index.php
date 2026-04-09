<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Sistema de gestion comercial para ventas, stock, caja y facturacion | FLUS';
$pageDescription = 'FLUS es un sistema de gestion comercial para comercios y pymes que necesitan ordenar ventas, stock, caja, clientes y facturacion con mas control diario y menos planillas.';
$pageSchemas = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'FLUS',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web',
        'description' => 'Sistema de gestion comercial para ventas, stock, caja, clientes y facturacion.',
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
                    'text' => 'No. Tambien ayuda a ordenar ventas, caja, stock, clientes y facturacion dentro de una misma logica comercial.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => '¿Para que tipo de negocio sirve mejor?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Para comercios y pymes que necesitan menos planillas, mas control diario y mejor trazabilidad operativa.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => '¿Que conviene mirar en una demo?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'El circuito real: como se vende, como se cobra, como impacta en stock y como se sostiene el seguimiento comercial.',
                ],
            ],
            [
                '@type' => 'Question',
                'name' => '¿Se puede coordinar una demo de FLUS?',
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => 'Si. Desde la pagina de contacto se puede iniciar una conversacion para evaluar FLUS sobre la operacion real del negocio.',
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
      <span class="eyebrow">Sistema de gesti&oacute;n comercial para comercios y pymes</span>
      <h1>FLUS conecta venta, cobro, stock y seguimiento en un mismo flujo de trabajo</h1>
      <p>
        Mostr&aacute; una operaci&oacute;n real desde caja: carga de productos, ticket, medios de pago y total a cobrar
        dentro de una interfaz clara para el cajero y conectada con el resto del negocio.
      </p>

      <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Solicitar demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-pos.php')) ?>">Ver sistema POS</a>
      </div>

      <ul class="hero-points">
        <li>Ticket visible y medios de pago claros para vender con m&aacute;s ritmo.</li>
        <li>Caja, stock y cobro dentro del mismo flujo operativo.</li>
        <li>Menos planillas y mejor seguimiento comercial despu&eacute;s de cada venta.</li>
      </ul>
    </div>

    <div class="hero-media">
      <figure class="product-shot product-shot--hero">
        <img
          src="<?= e(asset_url('img/flus-caja-pos.png')) ?>"
          alt="Pantalla de caja y cobro en FLUS con ticket, medios de pago y total a cobrar"
          width="1608"
          height="978"
          fetchpriority="high"
          decoding="async"
        >
      </figure>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <span class="section-kicker">Qu&eacute; resuelve</span>
    <h2>Cuando la operaci&oacute;n est&aacute; partida, el negocio pierde control</h2>
    <p class="section-lead">
      FLUS busca atacar un problema concreto: que ventas, caja, stock y facturaci&oacute;n no queden trabajando por separado.
      Esa dispersi&oacute;n siempre termina pegando en tiempo, control y seguimiento.
    </p>

    <div class="feature-grid">
      <article class="feature-card">
        <h3>Ventas con m&aacute;s contexto</h3>
        <p>La operaci&oacute;n no termina en el cobro. Despu&eacute;s importan caja, stock, cliente y comprobante.</p>
      </article>
      <article class="feature-card">
        <h3>Caja m&aacute;s clara</h3>
        <p>M&aacute;s visibilidad sobre medios de pago, movimientos diarios y cierres con criterio real.</p>
      </article>
      <article class="feature-card">
        <h3>Stock m&aacute;s confiable</h3>
        <p>Menos dudas sobre disponibilidad, movimientos y el impacto de cada venta.</p>
      </article>
      <article class="feature-card">
        <h3>Seguimiento comercial</h3>
        <p>Historial, clientes y facturaci&oacute;n con mejor continuidad operativa.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-dark">
  <div class="container">
    <span class="section-kicker">C&oacute;mo trabaja FLUS</span>
    <h2>Un circuito m&aacute;s ordenado para la gesti&oacute;n comercial diaria</h2>
    <div class="steps-grid">
      <article class="step-card">
        <span class="step-number">01</span>
        <h3>Venta y atenci&oacute;n</h3>
        <p>La operaci&oacute;n arranca con una base m&aacute;s prolija para vender, registrar y seguir cada movimiento.</p>
      </article>
      <article class="step-card">
        <span class="step-number">02</span>
        <h3>Caja y cobro</h3>
        <p>Los medios de pago y el control diario quedan m&aacute;s claros dentro del mismo circuito.</p>
      </article>
      <article class="step-card">
        <span class="step-number">03</span>
        <h3>Stock y disponibilidad</h3>
        <p>La venta conversa mejor con el stock y eso reduce incertidumbre en la operaci&oacute;n.</p>
      </article>
      <article class="step-card">
        <span class="step-number">04</span>
        <h3>Comprobante y seguimiento</h3>
        <p>Facturaci&oacute;n, cliente e historial comercial quedan mejor conectados para revisar lo que pas&oacute;.</p>
      </article>
    </div>
  </div>
</section>

<section class="section">
  <div class="container split-grid">
    <div>
      <span class="section-kicker">Soluciones</span>
      <h2>Explor&aacute; FLUS desde la parte de la operaci&oacute;n que m&aacute;s te preocupa</h2>
      <p class="section-lead">
        Cada p&aacute;gina interna est&aacute; pensada para bajar el problema a tierra y mostrar c&oacute;mo encaja FLUS en la gesti&oacute;n diaria.
      </p>

      <div class="link-stack">
        <a class="stack-link" href="<?= e(site_url('sistema-de-gestion.php')) ?>">
          <strong>Sistema de gesti&oacute;n</strong>
          <span>Visi&oacute;n completa para ventas, stock, caja, clientes y facturaci&oacute;n.</span>
        </a>
        <a class="stack-link" href="<?= e(site_url('sistema-pos.php')) ?>">
          <strong>Sistema POS</strong>
          <span>Mostrador, caja y cobro con m&aacute;s orden y mejor continuidad.</span>
        </a>
        <a class="stack-link" href="<?= e(site_url('control-de-stock.php')) ?>">
          <strong>Control de stock</strong>
          <span>Disponibilidad y movimientos con mejor contexto operativo.</span>
        </a>
        <a class="stack-link" href="<?= e(site_url('facturacion.php')) ?>">
          <strong>Facturaci&oacute;n</strong>
          <span>Comprobantes integrados a la operaci&oacute;n comercial.</span>
        </a>
      </div>
    </div>

    <div class="surface-card">
      <h3>D&oacute;nde tiene m&aacute;s sentido</h3>
      <p>
        FLUS encaja mejor en comercios y pymes donde la operaci&oacute;n ya no entra c&oacute;moda en planillas,
        memoria operativa o herramientas desconectadas.
      </p>
      <ul class="plain-list">
        <li>Comercios con ventas diarias, caja y stock.</li>
        <li>Equipos que necesitan menos tareas repetidas.</li>
        <li>Negocios que buscan m&aacute;s orden comercial y m&aacute;s trazabilidad.</li>
        <li>Operaciones que necesitan crecer con una base m&aacute;s seria.</li>
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
        <h3>&iquest;FLUS es solo un sistema POS?</h3>
        <p>No. Tambi&eacute;n ayuda a ordenar ventas, caja, stock, clientes y facturaci&oacute;n dentro de una misma l&oacute;gica comercial.</p>
      </article>
      <article class="faq-item">
        <h3>&iquest;Para qu&eacute; tipo de negocio sirve mejor?</h3>
        <p>Para comercios y pymes que necesitan menos planillas, m&aacute;s control diario y mejor trazabilidad operativa.</p>
      </article>
      <article class="faq-item">
        <h3>&iquest;Qu&eacute; conviene mirar en una demo?</h3>
        <p>El circuito real: c&oacute;mo se vende, c&oacute;mo se cobra, c&oacute;mo impacta en stock y c&oacute;mo se sostiene el seguimiento comercial.</p>
      </article>
      <article class="faq-item">
        <h3>&iquest;Se puede coordinar una demo de FLUS?</h3>
        <p>S&iacute;. Desde la p&aacute;gina de contacto se puede iniciar una conversaci&oacute;n para evaluar FLUS sobre la operaci&oacute;n real del negocio.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-dark">
  <div class="container">
    <div class="cta-box">
      <h2>Si tu operaci&oacute;n ya necesita m&aacute;s orden, conviene mirar FLUS sobre el flujo real del negocio</h2>
      <p>
        La mejor demo es la que se eval&uacute;a con ventas, caja, stock y facturaci&oacute;n como parte de una misma conversaci&oacute;n.
      </p>
      <div class="inline-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Solicitar demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-pos.php')) ?>">Ver sistema POS</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
