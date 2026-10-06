<?php
require_once __DIR__ . '/includes/bootstrap.php';

/**
 * HOME reconciliada: el diseno, el copy y el orden de las secciones son los
 * que estan publicados HOY en https://flus.com.ar/ (ese markup no existe en
 * ninguna rama del repositorio). Sobre ese contenido se aplican los arreglos
 * de la auditoria: URLs por helpers, dimensiones reales de imagen, CTA
 * primario medido como cta_primary_click y un FAQPage cuyas preguntas son
 * exactamente las visibles en la pagina.
 */
$pageTitle = 'Sistema de gestión para comercios | FLUS Web y Server';
$pageDescription = 'Sistema de gestión para comercios: caja, ventas, stock y compras. Usá FLUS por internet o instalado en los equipos de tu negocio.';

// El schema Offer se construye desde el registro de planes.
// IMPORTANTE: si un plan todavia no tiene precio configurado NO se declara
// "price" — publicar un numero inventado en datos estructurados seria peor que
// no declararlo. Ver flus_plan_price() en includes/plans.php.
$planOffers = [];
foreach (flus_web_plans() as $planId => $plan) {
    $offer = [
        '@type' => 'Offer',
        'name' => $plan['name'],
        'description' => $plan['tagline'] . ' ' . $plan['audience'],
        'category' => 'Suscripción mensual',
    ];

    $capacity = flus_plan_capacity_label($planId);
    if ($capacity !== '') {
        $offer['description'] .= ' Incluye ' . $capacity . '.';
    }

    $price = flus_plan_price_value($planId);
    if ($price !== '') {
        // El schema debe llevar un valor NUMERICO ("24900"), nunca el texto
        // formateado ("$24.900").
        $offer['price'] = $price;
        $offer['priceCurrency'] = 'ARS';
    }

    $planOffers[] = $offer;
}

// Preguntas del schema == preguntas visibles en la HOME (extraidas del markup).
$faqItems = [
    ['question' => "¿Cuál es la diferencia entre FLUS Web y FLUS Server?", 'answer' => "FLUS Web se usa por internet y nosotros nos encargamos de mantenerlo funcionando. FLUS Server se instala en los equipos de tu negocio y se prepara a medida."],
    ['question' => "¿FLUS Web funciona en teléfono y tablet?", 'answer' => "Sí. Podés usar las funciones principales desde tu computadora, tablet o celular Android o iPhone."],
    ['question' => "¿Puedo usar lector de código de barras?", 'answer' => "Sí. Podés usar un lector de códigos de barras. En FLUS Web también podés usar la cámara de un celular compatible."],
    ['question' => "¿La facturación electrónica está incluida?", 'answer' => "La facturación electrónica con ARCA se confirma y presupuesta antes de contratar. El abono no la incluye automáticamente."],
    ['question' => "¿Puedo sumar más cajas o sucursales?", 'answer' => "Sí. Elegí un plan de FLUS Web con las cajas y locales que necesitás. Si elegís FLUS Server, acordamos la cantidad de computadoras al preparar la instalación."],
    ['question' => "¿Puedo probar FLUS antes de contratar?", 'answer' => "Sí. Podés probar las pantallas de venta, caja, stock y productos para conocer el sistema antes de contratar."],
];

$pageSchemas = [
    ['@context' => 'https://schema.org', '@type' => 'SoftwareApplication', 'name' => 'FLUS Web', 'applicationCategory' => 'BusinessApplication', 'operatingSystem' => 'Web, Windows, Server', 'description' => $pageDescription, 'url' => page_url(), 'offers' => $planOffers],
    ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(static function (array $item): array { return ['@type' => 'Question', 'name' => $item['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer']]]; }, $faqItems)],
];

require __DIR__ . '/includes/header.php';
?>
<section class="flus26-hero">
  <div class="container flus26-hero__grid">
    <div class="flus26-hero__copy">
      <div class="flus26-pill"><span></span> FLUS · Gestión comercial 2026</div>
      <h1>Sistema de gestión para comercios.<br><em>Tu negocio bajo control.</em></h1>
      <p class="flus26-hero__lead">
        Vendé, controlá el stock y registrá tus compras en un solo lugar. Empezá por internet con FLUS Web o elegí una instalación a medida para tu negocio con FLUS Server.
      </p>
      <div class="flus26-hero__actions">
        <a class="btn btn-primary" href="<?= e((string)($site['demo_url'] ?? site_url('contacto.php'))) ?>" target="_blank" rel="noopener" data-track-event="cta_primary_click" data-track-label="Hero probar FLUS Web">Probar FLUS Web</a>
        <a class="btn btn-secondary" href="#versiones">Ver versiones</a>
      </div>
      <div class="flus26-hero__trust" aria-label="Características principales">
        <span>PC + móvil + tablet</span>
        <span>Caja + stock + ventas</span>
        <span>Te ayudamos a empezar</span>
      </div>
    </div>

    <div class="flus26-hero__visual">
      <div class="flus26-orbit flus26-orbit--one" aria-hidden="true"></div>
      <div class="flus26-orbit flus26-orbit--two" aria-hidden="true"></div>

      <div class="flus26-desktop-stage">
        <div class="flus26-product-window flus26-product-window--main">
          <div class="flus26-windowbar"><span></span><span></span><span></span><b>FLUS · Caja</b></div>
          <picture>
            <source srcset="<?= e(asset_url('img/flus-caja-pos.webp')) ?>" type="image/webp">
            <img src="<?= e(asset_url('img/flus-caja-pos.png')) ?>" alt="Pantalla real de caja de ventas de FLUS en computadora" width="1608" height="978" fetchpriority="high" decoding="async">
          </picture>
        </div>
        <div class="flus26-float-card flus26-float-card--server">
          <span class="flus26-server-icon">S</span>
          <div><b>FLUS Server</b><small>En los equipos de tu negocio</small></div>
        </div>
      </div>

      <?php /* Mockup decorativo: el texto de adentro es contenido de ejemplo (productos,
              totales, stock) y un lector de pantalla lo anunciaria como si fuera real.
              La imagen real de la caja de ventas del stage de escritorio SI queda accesible:
              es el unico contenido con informacion verdadera de esta zona. */ ?>
      <div class="flus26-phone-wrap" role="img" aria-label="FLUS Web en el teléfono: caja, stock y resumen de ventas del día">
        <div class="flus26-phone-badge" aria-hidden="true"><span class="flus26-version-dot"></span><b>FLUS Web</b><small>También en tu celular</small></div>
        <div class="flus26-phone" aria-hidden="true">
          <div class="flus26-phone__island"></div>
          <div class="flus26-phone__screen">
            <div class="flus26-phone__status"><b>9:41</b><span>● ᴡɪꜰɪ ▰</span></div>
            <div class="flus26-phone__appbar">
              <div class="flus26-phone__brand"><span class="flus26-phone__mark">F</span><div><b>FLUS</b><small>Demo móvil</small></div></div>
              <span class="flus26-phone__online">EN LÍNEA</span>
            </div>

            <div class="flus26-phone__panels">
              <div class="flus26-phone-panel flus26-phone-panel--caja">
                <div class="flus26-phone__sectiontop"><div><small>CAJA ABIERTA</small><b>Caja 1</b></div><span>•••</span></div>
                <div class="flus26-phone__search"><span>⌕</span> Buscar o escanear producto <b>▣</b></div>
                <div class="flus26-phone__toast">✓ Producto agregado</div>
                <div class="flus26-phone__items">
                  <div><span class="flus26-phone__prodicon">G</span><p><b>Gaseosa 500 ml</b><small>1 × $1.560,00</small></p><strong>$1.560</strong></div>
                  <div><span class="flus26-phone__prodicon">B</span><p><b>Bebida Cola 1.25 L</b><small>1 × $2.600,00</small></p><strong>$2.600</strong></div>
                </div>
                <div class="flus26-phone__total"><span>Total</span><b>$4.160,00</b></div>
                <button type="button" tabindex="-1">Cobrar</button>
              </div>

              <div class="flus26-phone-panel flus26-phone-panel--stock">
                <div class="flus26-phone__sectiontop"><div><small>INVENTARIO</small><b>Stock</b></div><span>＋</span></div>
                <div class="flus26-phone__metricgrid">
                  <div><small>Productos</small><b>528</b><span>Activos</span></div>
                  <div><small>Stock bajo</small><b>7</b><span>Revisar</span></div>
                </div>
                <div class="flus26-phone__mini-title">Movimientos recientes</div>
                <div class="flus26-phone__stockrow"><span>Yerba 1 kg</span><b>24 u.</b></div>
                <div class="flus26-phone__stockrow"><span>Agua mineral</span><b>18 u.</b></div>
                <div class="flus26-phone__stockrow flus26-phone__stockrow--warn"><span>Jabón líquido</span><b>2 u.</b></div>
                <div class="flus26-phone__scan"><span>▦</span><div><b>Escanear código</b><small>Usar cámara del teléfono</small></div></div>
              </div>

              <div class="flus26-phone-panel flus26-phone-panel--dash">
                <div class="flus26-phone__sectiontop"><div><small>RESUMEN DE HOY</small><b>Resumen</b></div><span>↗</span></div>
                <div class="flus26-phone__salescard"><small>Ventas del día</small><b>$186.420</b><span>↑ 12,4% vs. ayer</span></div>
                <div class="flus26-phone__bars" aria-hidden="true">
                  <i style="--h:38%"></i><i style="--h:58%"></i><i style="--h:46%"></i><i style="--h:72%"></i><i style="--h:64%"></i><i style="--h:88%"></i><i style="--h:76%"></i>
                </div>
                <div class="flus26-phone__metricline"><span>Operaciones</span><b>42</b></div>
                <div class="flus26-phone__metricline"><span>Ticket promedio</span><b>$4.438</b></div>
                <div class="flus26-phone__metricline"><span>Caja</span><b class="is-ok">Abierta</b></div>
              </div>
            </div>

            <div class="flus26-phone__nav"><span class="is-active">⌂<small>Inicio</small></span><span>▦<small>Caja</small></span><span>▤<small>Stock</small></span><span>•••<small>Más</small></span></div>
          </div>
        </div>
      </div>

      <div class="flus26-sync-chip"><span>↻</span><div><b>La misma operación</b><small>Computadora, tablet y celular</small></div></div>
    </div>
  </div>
</section>

<section class="flus26-signalbar" aria-label="Resumen del producto">
  <div class="container flus26-signalbar__inner">
    <span><b>2</b> ediciones</span>
    <span><b>Todo</b> en un solo lugar</span>
    <span><b>Tus dispositivos</b> Computadora, tablet y celular</span>
    <span><b>Probalo</b> antes de decidir</span>
  </div>
</section>

<section class="flus26-section flus26-versions" id="versiones" data-reveal>
  <div class="container">
    <div class="flus26-sectionhead">
      <span class="section-kicker">Versiones</span>
      <h2>¿Qué FLUS le conviene a tu negocio?</h2>
      <p>Elegí cómo querés usar el sistema: por internet o instalado en los equipos de tu negocio. Después elegimos el plan que necesitás.</p>
    </div>

    <div class="flus26-edition-grid">
      <article class="flus26-edition flus26-edition--web" id="flus-web">
        <div class="flus26-edition__top">
          <span class="flus26-edition__tag">Recomendado para empezar</span>
          <span class="flus26-edition__code">POR INTERNET</span>
        </div>
        <h3>FLUS Web</h3>
        <p class="flus26-edition__lead">Entrá por internet desde tu computadora, tablet o celular. Nosotros nos encargamos de mantener el sistema funcionando.</p>
        <ul class="flus26-checks">
          <li>Usalo desde computadora, tablet o celular</li>
          <li>Nos encargamos del sistema y sus actualizaciones</li>
          <li>Soporte incluido para ayudarte en el día a día</li>
        </ul>
        <div class="flus26-edition__foot">
          <span>Te ayudamos a empezar y te acompañamos en el uso diario.</span>
          <a href="<?= e(site_url('flus-web.php')) ?>" class="flus26-arrowlink" data-track-event="click_contact" data-track-label="Conocer FLUS Web">Conocer FLUS Web <span>→</span></a>
        </div>
      </article>

      <article class="flus26-edition flus26-edition--server" id="flus-server">
        <div class="flus26-edition__top">
          <span class="flus26-edition__tag">Más control y personalización</span>
          <span class="flus26-edition__code">EN TU NEGOCIO</span>
        </div>
        <h3>FLUS Server</h3>
        <p class="flus26-edition__lead">FLUS instalado en los equipos de tu negocio, con una configuración a medida cuando necesitás una forma de trabajo más específica.</p>
        <ul class="flus26-checks">
          <li>Instalación en los equipos de tu negocio</li>
          <li>Configuración según tus cajas y forma de trabajar</li>
          <li>Carga de datos, capacitación y soporte acordados con vos</li>
        </ul>
        <div class="flus26-edition__foot">
          <span>Revisamos cómo trabajás para preparar la instalación que necesitás.</span>
          <a href="<?= e(site_url('flus-server.php')) ?>" class="flus26-arrowlink" data-track-event="click_contact" data-track-label="Conocer FLUS Server">Conocer FLUS Server <span>→</span></a>
        </div>
      </article>
    </div>
  </div>
</section>

<section class="flus26-section flus26-finder" id="que-flus-necesito" data-reveal>
  <div class="container">
    <div class="flus26-sectionhead flus26-sectionhead--split">
      <div>
        <span class="section-kicker">Elegí mejor</span>
        <h2>¿Qué FLUS necesitás?</h2>
      </div>
      <p>Contanos cómo querés trabajar y te orientamos sobre la opción más conveniente.</p>
    </div>

    <div class="flus26-finder__grid" data-flus-finder>
      <div class="flus26-finder__questions">
        <fieldset class="flus26-choicegroup" data-finder-group="access">
          <legend>1. ¿Cómo querés trabajar?</legend>
          <div class="flus26-choicegroup__options">
            <button type="button" class="is-selected" data-finder-choice="web">Desde computadora, tablet y celular</button>
            <button type="button" data-finder-choice="server">En los equipos de mi negocio</button>
          </div>
        </fieldset>
        <fieldset class="flus26-choicegroup" data-finder-group="scale">
          <legend>2. ¿Qué tan compleja es la operación?</legend>
          <div class="flus26-choicegroup__options">
            <button type="button" class="is-selected" data-finder-choice="web">Quiero empezar simple y rápido</button>
            <button type="button" data-finder-choice="server">Necesito una forma de trabajo a medida</button>
          </div>
        </fieldset>
        <fieldset class="flus26-choicegroup" data-finder-group="custom">
          <legend>3. ¿Cuánta personalización necesitás?</legend>
          <div class="flus26-choicegroup__options">
            <button type="button" class="is-selected" data-finder-choice="web">Quiero empezar con las funciones habituales</button>
            <button type="button" data-finder-choice="server">Necesito pasar datos o adaptar el sistema</button>
          </div>
        </fieldset>
      </div>

      <aside class="flus26-finder__result is-web" data-finder-result aria-live="polite">
        <span class="flus26-finder__eyebrow">Nuestra recomendación</span>
        <div class="flus26-finder__resulthead"><span class="flus26-finder__resulticon">W</span><div><small>PARA TU ESCENARIO</small><h3 data-finder-title>FLUS Web</h3></div></div>
        <p data-finder-copy>Por lo que marcaste, FLUS Web es una buena opción: empezás por internet desde tu computadora o celular y nosotros nos encargamos del sistema.</p>
        <ul data-finder-benefits>
          <li>Usalo desde computadora, tablet o celular</li>
          <li>Nos encargamos de mantenerlo funcionando</li>
          <li>Caja, ventas y stock desde el primer plan</li>
        </ul>
        <div class="flus26-finder__actions">
          <a class="btn btn-primary" data-finder-primary href="<?= e((string)($site['demo_url'] ?? site_url('contacto.php'))) ?>" target="_blank" rel="noopener">Probar FLUS Web</a>
          <a class="flus26-finder__secondary" data-finder-secondary href="<?= e(site_url('contacto.php')) ?>#formulario-contacto">Hablar con nosotros →</a>
        </div>
      </aside>
    </div>
  </div>
</section>

<section class="flus26-section flus26-section--dark" id="funciones" data-reveal>
  <div class="container">
    <div class="flus26-sectionhead flus26-sectionhead--dark">
      <span class="section-kicker">Producto real</span>
      <h2>Lo que usás todos los días, conectado.</h2>
      <p>Cada venta actualiza el stock y queda registrada. Consultá tus productos, compras y ventas desde el mismo sistema.</p>
    </div>

    <div class="flus26-feature-grid">
              <article class="flus26-feature">
          <span class="flus26-feature__number">01</span>
          <h3>Caja de ventas</h3>
          <p>Venta rápida, ticket, medios de pago, apertura y cierre de caja.</p>
        </article>
              <article class="flus26-feature">
          <span class="flus26-feature__number">02</span>
          <h3>Productos y stock</h3>
          <p>Precios, existencias, movimientos, alertas e inventario en un mismo circuito.</p>
        </article>
              <article class="flus26-feature">
          <span class="flus26-feature__number">03</span>
          <h3>Compras y proveedores</h3>
          <p>Ingresos de mercadería, costos y reposición conectados al stock.</p>
        </article>
              <article class="flus26-feature">
          <span class="flus26-feature__number">04</span>
          <h3>Ventas y reportes</h3>
          <p>Historial, filtros, indicadores y lectura comercial del negocio.</p>
        </article>
              <article class="flus26-feature">
          <span class="flus26-feature__number">05</span>
          <h3>Accesos y cajas</h3>
          <p>Dale a cada persona el acceso que necesita y organizá quién puede usar cada caja.</p>
        </article>
              <article class="flus26-feature">
          <span class="flus26-feature__number">06</span>
          <h3>Códigos de barras</h3>
          <p>Lector y cámara para buscar productos y agilizar la operación diaria.</p>
        </article>
          </div>
  </div>
</section>

<section class="flus26-section flus26-product-tour" data-reveal data-product-tour>
  <div class="container">
    <div class="flus26-sectionhead flus26-sectionhead--split flus26-product-tour__head">
      <div>
        <span class="section-kicker">Producto real</span>
        <h2>Conocé FLUS<br> por dentro.</h2>
      </div>
      <p>Mirá las pantallas que vas a usar todos los días para vender, revisar el stock y saber cómo va tu negocio.</p>
    </div>

    <div class="flus26-tour">
      <div class="flus26-tour__nav" role="tablist" aria-label="Pantallas de FLUS">
        <button class="flus26-tour__tab is-active" type="button" role="tab" aria-selected="true" aria-controls="flus-tour-caja" data-tour-target="caja">
          <span class="flus26-tour__num">01</span>
          <span class="flus26-tour__tabcopy"><strong>Caja</strong><small>Venta, ticket y cobro en una sola vista.</small></span>
          <span class="flus26-tour__arrow" aria-hidden="true">→</span>
        </button>
        <button class="flus26-tour__tab" type="button" role="tab" aria-selected="false" aria-controls="flus-tour-dashboard" data-tour-target="dashboard">
          <span class="flus26-tour__num">02</span>
          <span class="flus26-tour__tabcopy"><strong>Resumen</strong><small>Un resumen para saber cómo va tu negocio.</small></span>
          <span class="flus26-tour__arrow" aria-hidden="true">→</span>
        </button>
        <button class="flus26-tour__tab" type="button" role="tab" aria-selected="false" aria-controls="flus-tour-stock" data-tour-target="stock">
          <span class="flus26-tour__num">03</span>
          <span class="flus26-tour__tabcopy"><strong>Stock</strong><small>Disponibilidad, filtros, alertas y movimientos.</small></span>
          <span class="flus26-tour__arrow" aria-hidden="true">→</span>
        </button>
        <button class="flus26-tour__tab" type="button" role="tab" aria-selected="false" aria-controls="flus-tour-ventas" data-tour-target="ventas">
          <span class="flus26-tour__num">04</span>
          <span class="flus26-tour__tabcopy"><strong>Ventas</strong><small>Historial y seguimiento de cada operación.</small></span>
          <span class="flus26-tour__arrow" aria-hidden="true">→</span>
        </button>
      </div>

      <div class="flus26-tour__stage">
        <div class="flus26-tour__windowbar">
          <div class="flus26-tour__dots" aria-hidden="true"><i></i><i></i><i></i></div>
          <strong data-tour-window-title>FLUS · Caja</strong>
          <span><i></i> Producto real</span>
        </div>

        <div class="flus26-tour__viewport">
          <figure class="flus26-tour__panel is-active" id="flus-tour-caja" role="tabpanel" data-tour-panel="caja" data-tour-title="FLUS · Caja" data-tour-kicker="OPERACIÓN DIARIA" data-tour-caption="Vendé, elegí el medio de pago y cerrá la operación sin cambiar de pantalla.">
            <picture>
              <source srcset="<?= e(asset_url('img/flus-caja-pos.webp')) ?>" type="image/webp">
              <img src="<?= e(asset_url('img/flus-caja-pos.png')) ?>" alt="Pantalla de Caja de ventas de FLUS" width="1608" height="978" loading="lazy">
            </picture>
          </figure>
          <figure class="flus26-tour__panel" id="flus-tour-dashboard" role="tabpanel" hidden data-tour-panel="dashboard" data-tour-title="FLUS · Resumen" data-tour-kicker="CONTROL DEL NEGOCIO" data-tour-caption="Ventas, productos y medios de pago resumidos para entender qué está pasando.">
            <picture>
              <source srcset="<?= e(asset_url('img/Dashboard.webp')) ?>" type="image/webp">
              <img src="<?= e(asset_url('img/Dashboard.png')) ?>" alt="Resumen de ventas de FLUS" width="1536" height="1024" loading="lazy">
            </picture>
          </figure>
          <figure class="flus26-tour__panel" id="flus-tour-stock" role="tabpanel" hidden data-tour-panel="stock" data-tour-title="FLUS · Stock" data-tour-kicker="INVENTARIO" data-tour-caption="Detectá faltantes y revisá existencias con filtros y alertas en una misma vista.">
            <picture>
              <source srcset="<?= e(asset_url('img/Stock.webp')) ?>" type="image/webp">
              <img src="<?= e(asset_url('img/Stock.png')) ?>" alt="Control de stock de FLUS" width="1536" height="1024" loading="lazy">
            </picture>
          </figure>
          <figure class="flus26-tour__panel" id="flus-tour-ventas" role="tabpanel" hidden data-tour-panel="ventas" data-tour-title="FLUS · Ventas" data-tour-kicker="SEGUIMIENTO" data-tour-caption="Revisá las ventas anteriores y encontrá el detalle de cada compra.">
            <picture>
              <source srcset="<?= e(asset_url('img/ventas.webp')) ?>" type="image/webp">
              <img src="<?= e(asset_url('img/ventas.png')) ?>" alt="Historial de ventas de FLUS" width="1536" height="1024" loading="lazy">
            </picture>
          </figure>
        </div>

        <div class="flus26-tour__caption">
          <div><span data-tour-kicker-output>OPERACIÓN DIARIA</span><strong data-tour-caption-output>Vendé, elegí el medio de pago y cerrá la operación sin cambiar de pantalla.</strong></div>
          <span class="flus26-tour__hint">Elegí una pantalla para conocerla</span>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="flus26-section flus26-compare" id="comparar" data-reveal>
  <div class="container">
    <div class="flus26-sectionhead">
      <span class="section-kicker">Comparación</span>
      <h2>Web o Server, sin vueltas.</h2>
      <p>Las dos opciones te ayudan a vender y llevar el control del negocio. La diferencia está en cómo lo usás y quién se encarga de los equipos.</p>
    </div>

    <div class="flus26-tablewrap" role="region" aria-label="Comparación entre FLUS Web y FLUS Server" tabindex="0">
      <table class="flus26-table">
        <thead>
          <tr><th>Característica</th><th>FLUS Web</th><th>FLUS Server</th></tr>
        </thead>
        <tbody>
          <tr><th>Acceso</th><td>Por internet</td><td>En los equipos de tu negocio</td></tr>
          <tr><th>Dispositivos</th><td>PC, tablet, Android, iPhone</td><td>Computadoras acordadas para tu negocio</td></tr>
          <tr><th>¿Quién mantiene el sistema?</th><td>Nos encargamos nosotros</td><td>Se acuerda para tu instalación</td></tr>
          <tr><th>Actualizaciones</th><td>Incluidas en el abono</td><td>Acordadas para tu instalación</td></tr>
          <tr><th>Cómo empezás</th><td>Con ayuda de FLUS</td><td>Con una instalación a medida</td></tr>
          <tr><th>Facturación electrónica</th><td>Se confirma antes de contratar</td><td>Se confirma antes de contratar</td></tr>
          <tr><th>Ideal para</th><td>Comercios que quieren empezar rápido</td><td>Negocios que necesitan una instalación a medida</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</section>

<section class="flus26-section flus26-pricing" id="precios" data-reveal>
  <div class="container">
    <div class="flus26-sectionhead flus26-sectionhead--dark">
      <span class="section-kicker">Planes</span>
      <h2>Empezá con lo que necesitás hoy. FLUS crece con tu negocio.</h2>
      <p>Todos los planes incluyen caja, ventas, stock y compras. Elegí según cuántos locales, cajas y personas van a usar el sistema.</p>
    </div>

    <div class="flus26-price-grid flus26-price-grid--plans">
      <?php foreach (flus_web_plans() as $planId => $plan):
        $isHighlight = ($plan['highlight'] ?? null) !== null;
        $priceText = flus_plan_price_text($planId);
        $hasPrice = flus_plan_price($planId) !== '';
      ?>
      <article class="flus26-price-card flus26-price-card--plan<?= $isHighlight ? ' flus26-price-card--highlight' : '' ?>">
        <div class="flus26-price-card__top">
          <span class="flus26-price-card__label"><?= e($plan['name']) ?></span>
          <?php if ($isHighlight): ?>
            <span class="flus26-price-card__badge"><?= e((string) $plan['highlight']) ?></span>
          <?php endif; ?>
        </div>

        <p class="flus26-price-card__tagline"><?= e($plan['tagline']) ?></p>

        <div class="flus26-price-card__amount">
          <?php if ($hasPrice && $plan['price_from']): ?><small>Desde</small><?php elseif (!$hasPrice): ?><small>Según tu negocio</small><?php endif; ?>
          <strong class="<?= $hasPrice ? '' : 'is-quote' ?>"><?= e($priceText) ?></strong>
          <?php if ($hasPrice): ?><span>por mes</span><?php endif; ?>
        </div>

        <p class="flus26-price-card__audience"><?= e($plan['audience']) ?></p>

        <ul class="flus26-price-card__specs">
          <?php foreach ($plan['specs'] as $spec): ?>
            <li><?= e($spec) ?></li>
          <?php endforeach; ?>
        </ul>

        <a
          class="btn <?= ($plan['cta'] ?? 'demo') === 'consulta' ? 'btn-secondary' : 'btn-primary' ?>"
          href="<?= e(flus_plan_cta_url($planId)) ?>"
          <?= ($plan['cta'] ?? 'demo') === 'consulta' ? '' : 'target="_blank" rel="noopener"' ?>

          data-track-event="pricing_plan_click"
          data-track-plan="<?= e($planId) ?>"
          data-track-label="Plan <?= e($plan['name']) ?>"
        ><?= e(flus_plan_cta_label($planId)) ?></a>
      </article>
      <?php endforeach; ?>
    </div>

    <?php if (!flus_plans_have_prices()): ?>
      <p class="flus26-price-note flus26-price-note--pending">
        Los valores de los planes se están definiendo y todavía no están publicados.
        Escribinos y te pasamos la propuesta para tu operación.
      </p>
    <?php endif; ?>

    <p class="flus26-price-note flus26-price-note--terms">
      Abonos mensuales en pesos argentinos. La configuración inicial, el traspaso de datos y la
      facturación electrónica con ARCA se acuerdan y presupuestan antes de contratar.
    </p>

    <div class="flus26-compare">
      <h3 class="flus26-compare__title">Comparar todos los planes</h3>
      <div class="flus26-tablewrap" role="region" aria-label="Comparación de los planes de FLUS Web" tabindex="0">
        <table class="flus26-table flus26-table--plans">
          <caption class="sr-only">Capacidad incluida en cada plan de FLUS Web</caption>
          <thead>
            <tr>
              <th scope="col">Característica</th>
              <?php foreach (flus_web_plans() as $plan): ?>
                <th scope="col"><?= e($plan['name']) ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php
            $rows = [
                'locales' => 'Locales',
                'cajas' => 'Cajas de cobro',
                'usuarios' => 'Usuarios',
                'operacion' => 'Tipo de operación',
                'soporte' => 'Soporte',
                'crecimiento' => 'Crecimiento',
            ];
            foreach ($rows as $key => $label): ?>
              <tr>
                <th scope="row"><?= e($label) ?></th>
                <?php foreach (flus_web_plans() as $plan): ?>
                  <td><?= e((string) ($plan['comparison'][$key] ?? '')) ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
            <tr>
              <th scope="row">Catálogo</th>
              <?php foreach (flus_web_plans() as $plan): ?>
                <td><?= e((string) ($plan['catalog'] ?? '')) ?></td>
              <?php endforeach; ?>
            </tr>
            <tr>
              <th scope="row">Caja, ventas y stock</th>
              <?php foreach (flus_web_plans() as $plan): ?>
                <td>Sí</td>
              <?php endforeach; ?>
            </tr>
            <tr>
              <th scope="row">Facturación electrónica</th>
              <?php foreach (flus_web_plans() as $plan): ?>
                <td>Se acuerda antes de contratar</td>
              <?php endforeach; ?>
            </tr>
            <tr>
              <th scope="row">Ventas</th>
              <?php foreach (flus_web_plans() as $plan): ?>
                <td>Sin límite</td>
              <?php endforeach; ?>
            </tr>
          </tbody>
        </table>
      </div>
      <p class="flus26-compare__foot">
        Las cantidades de productos son orientativas: podés ampliar el catálogo sin cambiar de plan.
        Los planes se diferencian por la cantidad de locales, cajas y usuarios.
        Las ventas no tienen tope en ningún plan.
      </p>
    </div>

    <div class="flus26-server-note">
      <h3>¿Preferís instalar FLUS en tu negocio?</h3>
      <p><b>FLUS Server</b> se instala en los equipos de tu negocio. Si necesitás una forma de trabajo a medida, contanos tu caso y te preparamos una propuesta.</p>
      <a class="btn btn-secondary" href="<?= e(site_url('flus-server.php')) ?>" data-track-event="pricing_plan_click" data-track-plan="server" data-track-label="Plan FLUS Server">Consultar FLUS Server</a>
    </div>
  </div>
</section>

<section class="flus26-section flus26-onboarding" data-reveal>
  <div class="container">
    <div class="flus26-sectionhead">
      <span class="section-kicker">Puesta en marcha</span>
      <h2>De la consulta a la primera venta.</h2>
      <p>Te ayudamos a preparar el sistema y a dar los primeros pasos según cómo trabaja tu comercio.</p>
    </div>
    <div class="flus26-onboarding__grid">
      <article><span>01</span><h3>Nos contás tu operación</h3><p>Cajas, productos, medios de pago, stock, usuarios y qué necesitás resolver.</p></article>
      <article><span>02</span><h3>Elegimos Web o Server</h3><p>Elegimos la opción que mejor se adapta a tu negocio.</p></article>
      <article><span>03</span><h3>Configuramos FLUS</h3><p>Preparamos los accesos, la configuración y la carga de tus productos según lo acordado.</p></article>
      <article><span>04</span><h3>Empezás a operar</h3><p>Probamos el flujo real y te acompañamos en la puesta en marcha.</p></article>
    </div>
  </div>
</section>

<section class="resource-section resource-section--soft" data-reveal>
  <div class="container">
    <div class="resource-head">
      <div><span class="section-kicker">Recursos</span><h2>Aprendé antes de elegir un sistema.</h2></div>
      <p>Guías prácticas sobre caja, stock y gestión para resolver problemas reales del comercio y entender cuándo conviene automatizarlos.</p>
    </div>
    <div class="related-grid">
      <a class="related-card" href="<?= e(site_url('recursos/controlar-stock-comercio/')) ?>" data-track-event="click_resource" data-track-label="Home recurso stock"><span>Stock e inventario</span><strong>Cómo controlar el stock de un comercio</strong><p>Entradas, ventas, ajustes, inventarios y stock mínimo sin depender de una celda manual.</p></a>
      <a class="related-card" href="<?= e(site_url('recursos/como-hacer-cierre-de-caja/')) ?>" data-track-event="click_resource" data-track-label="Home recurso caja"><span>Caja y ventas</span><strong>Cómo hacer un cierre de caja correctamente</strong><p>Qué comparar, cómo declarar efectivo y cómo investigar una diferencia.</p></a>
      <a class="related-card" href="<?= e(site_url('recursos/que-sistema-necesita-un-kiosco/')) ?>" data-track-event="click_resource" data-track-label="Home recurso kiosco"><span>Guía por rubro</span><strong>Qué sistema necesita un kiosco</strong><p>Las funciones que realmente importan para vender rápido y mantener control.</p></a>
    </div>
    <div style="margin-top:22px"><a class="btn btn-secondary" href="<?= e(site_url('recursos/')) ?>">Ver todas las guías</a></div>
  </div>
</section>

<section class="flus26-section flus26-faq" data-reveal>
  <div class="container">
    <div class="flus26-sectionhead">
      <span class="section-kicker">Preguntas frecuentes</span>
      <h2>Lo importante antes de elegir.</h2>
    </div>
    <div class="flus26-faq-grid">
      <details open><summary>¿Cuál es la diferencia entre FLUS Web y FLUS Server?</summary><p>FLUS Web se usa por internet y nosotros nos encargamos de mantenerlo funcionando. FLUS Server se instala en los equipos de tu negocio y se prepara a medida.</p></details>
      <details><summary>¿FLUS Web funciona en teléfono y tablet?</summary><p>Sí. Podés usar las funciones principales desde tu computadora, tablet o celular Android o iPhone.</p></details>
      <details><summary>¿Puedo usar lector de código de barras?</summary><p>Sí. Podés usar un lector de códigos de barras. En FLUS Web también podés usar la cámara de un celular compatible.</p></details>
      <details><summary>¿La facturación electrónica está incluida?</summary><p>La facturación electrónica con ARCA se confirma y presupuesta antes de contratar. El abono no la incluye automáticamente.</p></details>
      <details><summary>¿Puedo sumar más cajas o sucursales?</summary><p>Sí. Elegí un plan de FLUS Web con las cajas y locales que necesitás. Si elegís FLUS Server, acordamos la cantidad de computadoras al preparar la instalación.</p></details>
      <details><summary>¿Puedo probar FLUS antes de contratar?</summary><p>Sí. Podés probar las pantallas de venta, caja, stock y productos para conocer el sistema antes de contratar.</p></details>
    </div>
  </div>
</section>

<section class="flus26-cta" data-reveal>
  <div class="container flus26-cta__inner">
    <div>
      <span>¿WEB O SERVER?</span>
      <h2>Probá FLUS y contanos cómo es tu negocio.</h2>
      <p>Probá cómo se usa el sistema o hablá con nosotros para elegir la opción que más te conviene.</p>
    </div>
    <div class="flus26-cta__actions">
      <a class="btn btn-primary" href="<?= e((string)($site['demo_url'] ?? site_url('contacto.php'))) ?>" target="_blank" rel="noopener" data-track-event="cta_primary_click" data-track-label="CTA final demo">Probar FLUS Web</a>
              <a class="btn btn-secondary" href="<?= e(whatsapp_url("Hola, quiero saber si me conviene FLUS Web o FLUS Server.")) ?>" target="_blank" rel="noopener" data-track-event="click_whatsapp" data-track-label="CTA final WhatsApp">Hablar por WhatsApp</a>
          </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
