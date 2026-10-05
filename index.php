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
$pageDescription = 'Sistema de gestión para comercios con caja POS, ventas, stock, compras y proveedores. Usá FLUS Web desde PC y móvil o implementá FLUS Server en infraestructura propia.';

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

    $price = flus_plan_price($planId);
    if ($price !== '') {
        $offer['price'] = preg_replace('/[^\d.,]/', '', $price);
        $offer['priceCurrency'] = 'ARS';
    }

    $planOffers[] = $offer;
}

// Preguntas del schema == preguntas visibles en la HOME (extraidas del markup).
$faqItems = [
    ['question' => "¿Cuál es la diferencia entre FLUS Web y FLUS Server?", 'answer' => "FLUS Web está pensado como servicio administrado y accesible desde navegador/PWA. FLUS Server se instala en la infraestructura del cliente y permite una implementación más personalizada."],
    ['question' => "¿FLUS Web funciona en teléfono y tablet?", 'answer' => "Sí. La interfaz está preparada para PC y uso móvil, incluyendo Android, iPhone y tablet en los flujos principales."],
    ['question' => "¿Puedo usar lector de código de barras?", 'answer' => "Sí. FLUS contempla lector de códigos y, en la edición Web, también flujos de escaneo con cámara en dispositivos compatibles."],
    ['question' => "¿La facturación ARCA está incluida?", 'answer' => "Depende de la edición, perfil e implementación contratada. En proyectos Server puede incorporarse como parte del alcance; en Web se define según el perfil comercial del cliente."],
    ['question' => "¿Puedo sumar más cajas o sucursales?", 'answer' => "Sí. En FLUS Web la propuesta base parte de 1 local y 1 caja, y se puede ampliar. En Server se define la cantidad de puestos al cotizar la instalación."],
    ['question' => "¿Puedo ver una demo antes de contratar?", 'answer' => "Sí. La demo está pensada para mostrar una operación real: venta, caja, stock, productos y seguimiento desde las pantallas de FLUS."],
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
        Caja, ventas, stock y compras en una misma operación. Empezá rápido con FLUS Web o elegí FLUS Server cuando necesitás infraestructura propia y una implementación más personalizada.
      </p>
      <div class="flus26-hero__actions">
        <a class="btn btn-primary" href="<?= e((string)($site['demo_url'] ?? site_url('contacto.php'))) ?>" target="_blank" rel="noopener" data-track-event="cta_primary_click" data-track-label="Hero probar FLUS Web">Probar FLUS Web</a>
        <a class="btn btn-secondary" href="#versiones">Ver versiones</a>
      </div>
      <div class="flus26-hero__trust" aria-label="Características principales">
        <span>PC + móvil + tablet</span>
        <span>Caja + stock + ventas</span>
        <span>Implementación acompañada</span>
      </div>
    </div>

    <div class="flus26-hero__visual" aria-label="FLUS funcionando en escritorio y teléfono">
      <div class="flus26-orbit flus26-orbit--one"></div>
      <div class="flus26-orbit flus26-orbit--two"></div>

      <div class="flus26-desktop-stage">
        <div class="flus26-product-window flus26-product-window--main">
          <div class="flus26-windowbar"><span></span><span></span><span></span><b>FLUS · Caja</b></div>
          <picture>
            <source srcset="<?= e(asset_url('img/flus-caja-pos.webp')) ?>" type="image/webp">
            <img src="<?= e(asset_url('img/flus-caja-pos.png')) ?>" alt="Pantalla real de caja POS de FLUS en computadora" width="1608" height="978" fetchpriority="high" decoding="async">
          </picture>
        </div>
        <div class="flus26-float-card flus26-float-card--server">
          <span class="flus26-server-icon">S</span>
          <div><b>FLUS Server</b><small>Infraestructura propia</small></div>
        </div>
      </div>

      <div class="flus26-phone-wrap" aria-label="Demostración animada de FLUS Web en teléfono">
        <div class="flus26-phone-badge"><span class="flus26-version-dot"></span><b>FLUS Web</b><small>Móvil + PWA</small></div>
        <div class="flus26-phone">
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
                <div class="flus26-phone__sectiontop"><div><small>RESUMEN DE HOY</small><b>Dashboard</b></div><span>↗</span></div>
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

      <div class="flus26-sync-chip"><span>↻</span><div><b>La misma operación</b><small>PC, tablet y teléfono</small></div></div>
    </div>
  </div>
</section>

<section class="flus26-signalbar" aria-label="Resumen del producto">
  <div class="container flus26-signalbar__inner">
    <span><b>2</b> ediciones</span>
    <span><b>1</b> plataforma de gestión</span>
    <span><b>Responsive</b> PC, tablet y teléfono</span>
    <span><b>Demo</b> con operación real</span>
  </div>
</section>

<section class="flus26-section flus26-versions" id="versiones" data-reveal>
  <div class="container">
    <div class="flus26-sectionhead">
      <span class="section-kicker">Versiones</span>
      <h2>¿Qué FLUS le conviene a tu negocio?</h2>
      <p>No son “planes” del mismo producto. Son dos formas distintas de implementar FLUS según cómo trabaja tu comercio.</p>
    </div>

    <div class="flus26-edition-grid">
      <article class="flus26-edition flus26-edition--web" id="flus-web">
        <div class="flus26-edition__top">
          <span class="flus26-edition__tag">Recomendado para empezar</span>
          <span class="flus26-edition__code">WEB / PWA</span>
        </div>
        <h3>FLUS Web</h3>
        <p class="flus26-edition__lead">Tu sistema listo para usar desde navegador, PC, tablet, Android o iPhone, sin montar un servidor dentro del negocio.</p>
        <div class="flus26-edition__price">
          <?php if (flus_plan_price('inicio') !== ''): ?>
            <span>Desde</span>
            <strong><?= e(flus_plan_price('inicio')) ?></strong>
            <small>ARS / mes</small>
          <?php else: ?>
            <span>Plan de entrada</span>
            <strong class="is-placeholder"><?= e(flus_plan_price_text('inicio')) ?></strong>
            <small>Precios en definición</small>
          <?php endif; ?>
        </div>
        <ul class="flus26-checks">
          <li>1 local + 1 caja en la propuesta base</li>
          <li>Hosting e infraestructura administrada</li>
          <li>Actualizaciones y soporte incluidos</li>
          <li>Caja, ventas, productos, stock, compras y proveedores</li>
          <li>Usuarios, roles, terminales y precios</li>
          <li>Lector y cámara para códigos de barras</li>
        </ul>
        <div class="flus26-edition__foot">
          <span>Puesta en marcha según configuración · hosting, soporte y actualizaciones incluidos.</span>
          <a href="<?= e(site_url('flus-web.php')) ?>" class="flus26-arrowlink" data-track-event="click_contact" data-track-label="Conocer FLUS Web">Conocer FLUS Web <span>→</span></a>
        </div>
      </article>

      <article class="flus26-edition flus26-edition--server" id="flus-server">
        <div class="flus26-edition__top">
          <span class="flus26-edition__tag">Más control y personalización</span>
          <span class="flus26-edition__code">SERVER / LOCAL</span>
        </div>
        <h3>FLUS Server</h3>
        <p class="flus26-edition__lead">Instalación en infraestructura propia para operaciones que necesitan una implementación más completa, control local y adaptación por proyecto.</p>
        <div class="flus26-edition__price flus26-edition__price--quote">
          <span>Propuesta según tu operación</span>
          <strong>Cotización personalizada</strong>
          <small>Sin precio genérico: se define por puestos, infraestructura y alcance.</small>
        </div>
        <ul class="flus26-checks">
          <li>Instalación local o en servidor del cliente</li>
          <li>Configuración según cantidad de puestos</li>
          <li>Migración y puesta en marcha según proyecto</li>
          <li>Gestión comercial completa y módulos avanzados</li>
          <li>Integraciones fiscales/ARCA según implementación</li>
          <li>Soporte y actualizaciones adaptados al proyecto</li>
        </ul>
        <div class="flus26-edition__foot">
          <span>El valor final depende de puestos, infraestructura e integraciones.</span>
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
      <p>Marcá lo que más se parece a tu negocio. Es una orientación rápida; después ajustamos la implementación con vos.</p>
    </div>

    <div class="flus26-finder__grid" data-flus-finder>
      <div class="flus26-finder__questions">
        <fieldset class="flus26-choicegroup" data-finder-group="access">
          <legend>1. ¿Cómo querés trabajar?</legend>
          <div class="flus26-choicegroup__options">
            <button type="button" class="is-selected" data-finder-choice="web">Desde PC, tablet y teléfono</button>
            <button type="button" data-finder-choice="server">En mi red / servidor propio</button>
          </div>
        </fieldset>
        <fieldset class="flus26-choicegroup" data-finder-group="scale">
          <legend>2. ¿Qué tan compleja es la operación?</legend>
          <div class="flus26-choicegroup__options">
            <button type="button" class="is-selected" data-finder-choice="web">Quiero empezar simple y rápido</button>
            <button type="button" data-finder-choice="server">Tengo varios puestos o requisitos propios</button>
          </div>
        </fieldset>
        <fieldset class="flus26-choicegroup" data-finder-group="custom">
          <legend>3. ¿Cuánta personalización necesitás?</legend>
          <div class="flus26-choicegroup__options">
            <button type="button" class="is-selected" data-finder-choice="web">Configuración estándar del comercio</button>
            <button type="button" data-finder-choice="server">Migraciones, integraciones o adaptación avanzada</button>
          </div>
        </fieldset>
      </div>

      <aside class="flus26-finder__result is-web" data-finder-result aria-live="polite">
        <span class="flus26-finder__eyebrow">Nuestra recomendación</span>
        <div class="flus26-finder__resulthead"><span class="flus26-finder__resulticon">W</span><div><small>PARA TU ESCENARIO</small><h3 data-finder-title>FLUS Web</h3></div></div>
        <p data-finder-copy>Por lo que marcaste, te conviene empezar con la versión Web: rápida de implementar, administrada por FLUS y lista para usar desde distintos dispositivos.</p>
        <ul data-finder-benefits>
          <li>Acceso desde navegador y PWA</li>
          <li>Infraestructura administrada</li>
          <li>Desde el plan de entrada, con las funciones centrales incluidas</li>
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
      <p>FLUS no es solo una pantalla de caja. La venta alimenta stock, historial, compras, usuarios y control operativo.</p>
    </div>

    <div class="flus26-feature-grid">
              <article class="flus26-feature">
          <span class="flus26-feature__number">01</span>
          <h3>Caja POS</h3>
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
          <h3>Usuarios y terminales</h3>
          <p>Roles, permisos y operación por caja o puesto de trabajo.</p>
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
        <h2>Conocé FLUS<br>por dentro.</h2>
      </div>
      <p>Explorá las pantallas que vas a usar todos los días. Sin maquetas: caja, indicadores, stock y ventas reales del sistema.</p>
    </div>

    <div class="flus26-tour">
      <div class="flus26-tour__nav" role="tablist" aria-label="Pantallas de FLUS">
        <button class="flus26-tour__tab is-active" type="button" role="tab" aria-selected="true" aria-controls="flus-tour-caja" data-tour-target="caja">
          <span class="flus26-tour__num">01</span>
          <span class="flus26-tour__tabcopy"><strong>Caja POS</strong><small>Venta, ticket y cobro en una sola vista.</small></span>
          <span class="flus26-tour__arrow" aria-hidden="true">→</span>
        </button>
        <button class="flus26-tour__tab" type="button" role="tab" aria-selected="false" aria-controls="flus-tour-dashboard" data-tour-target="dashboard">
          <span class="flus26-tour__num">02</span>
          <span class="flus26-tour__tabcopy"><strong>Dashboard</strong><small>Indicadores claros para leer el negocio.</small></span>
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
          <strong data-tour-window-title>FLUS · Caja POS</strong>
          <span><i></i> Producto real</span>
        </div>

        <div class="flus26-tour__viewport">
          <figure class="flus26-tour__panel is-active" id="flus-tour-caja" role="tabpanel" data-tour-panel="caja" data-tour-title="FLUS · Caja POS" data-tour-kicker="OPERACIÓN DIARIA" data-tour-caption="Vendé, elegí el medio de pago y cerrá la operación sin cambiar de pantalla.">
            <picture>
              <source srcset="<?= e(asset_url('img/flus-caja-pos.webp')) ?>" type="image/webp">
              <img src="<?= e(asset_url('img/flus-caja-pos.png')) ?>" alt="Pantalla de Caja POS de FLUS" width="1608" height="978" loading="lazy">
            </picture>
          </figure>
          <figure class="flus26-tour__panel" id="flus-tour-dashboard" role="tabpanel" hidden data-tour-panel="dashboard" data-tour-title="FLUS · Dashboard" data-tour-kicker="CONTROL DEL NEGOCIO" data-tour-caption="Ventas, productos y medios de pago resumidos para entender qué está pasando.">
            <picture>
              <source srcset="<?= e(asset_url('img/Dashboard.webp')) ?>" type="image/webp">
              <img src="<?= e(asset_url('img/Dashboard.png')) ?>" alt="Dashboard de FLUS" width="1536" height="1024" loading="lazy">
            </picture>
          </figure>
          <figure class="flus26-tour__panel" id="flus-tour-stock" role="tabpanel" hidden data-tour-panel="stock" data-tour-title="FLUS · Stock" data-tour-kicker="INVENTARIO" data-tour-caption="Detectá faltantes y revisá existencias con filtros y alertas en una misma vista.">
            <picture>
              <source srcset="<?= e(asset_url('img/Stock.webp')) ?>" type="image/webp">
              <img src="<?= e(asset_url('img/Stock.png')) ?>" alt="Control de stock de FLUS" width="1536" height="1024" loading="lazy">
            </picture>
          </figure>
          <figure class="flus26-tour__panel" id="flus-tour-ventas" role="tabpanel" hidden data-tour-panel="ventas" data-tour-title="FLUS · Ventas" data-tour-kicker="SEGUIMIENTO" data-tour-caption="Consultá operaciones anteriores y mantené trazabilidad de las ventas del comercio.">
            <picture>
              <source srcset="<?= e(asset_url('img/ventas.webp')) ?>" type="image/webp">
              <img src="<?= e(asset_url('img/ventas.png')) ?>" alt="Historial de ventas de FLUS" width="1536" height="1024" loading="lazy">
            </picture>
          </figure>
        </div>

        <div class="flus26-tour__caption">
          <div><span data-tour-kicker-output>OPERACIÓN DIARIA</span><strong data-tour-caption-output>Vendé, elegí el medio de pago y cerrá la operación sin cambiar de pantalla.</strong></div>
          <span class="flus26-tour__hint">Elegí un módulo para explorarlo</span>
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
      <p>La diferencia principal no es la caja: es dónde vive el sistema, cómo se administra y cuánto necesita personalizarse.</p>
    </div>

    <div class="flus26-tablewrap" role="region" aria-label="Comparación entre FLUS Web y FLUS Server" tabindex="0">
      <table class="flus26-table">
        <thead>
          <tr><th>Característica</th><th>FLUS Web</th><th>FLUS Server</th></tr>
        </thead>
        <tbody>
          <tr><th>Acceso</th><td>Navegador + PWA</td><td>Red / infraestructura propia</td></tr>
          <tr><th>Dispositivos</th><td>PC, tablet, Android, iPhone</td><td>Puestos definidos en la instalación</td></tr>
          <tr><th>Infraestructura</th><td>Administrada por FLUS</td><td>Del cliente</td></tr>
          <tr><th>Actualizaciones</th><td>Incluidas en el abono</td><td>Según soporte/proyecto</td></tr>
          <tr><th>Implementación</th><td>Rápida y estandarizada por perfil</td><td>Más personalizada</td></tr>
          <tr><th>Integraciones fiscales</th><td>Según edición/perfil contratado</td><td>Según implementación requerida</td></tr>
          <tr><th>Ideal para</th><td>Comercios que quieren empezar rápido</td><td>Operaciones con requisitos propios</td></tr>
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
      <p>Todos los planes son FLUS Web: la misma plataforma con distinta capacidad. Las funciones centrales están desde el primer plan; la diferencia está en cuántos locales, cajas y usuarios necesitás.</p>
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
          <?php if ($hasPrice && $plan['price_from']): ?><small>Desde</small><?php endif; ?>
          <strong class="<?= $hasPrice ? '' : 'is-placeholder' ?>"><?= e($priceText) ?></strong>
          <?php if ($hasPrice): ?><span>ARS / mes</span><?php endif; ?>
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
                'cajas' => 'Cajas / puestos',
                'usuarios' => 'Usuarios',
                'productos' => 'Productos',
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
              <th scope="row">Funciones centrales</th>
              <?php foreach (flus_web_plans() as $plan): ?>
                <td>Incluidas</td>
              <?php endforeach; ?>
            </tr>
            <tr>
              <th scope="row">Facturación / ARCA</th>
              <?php foreach (flus_web_plans() as $plan): ?>
                <td>Según implementación</td>
              <?php endforeach; ?>
            </tr>
          </tbody>
        </table>
      </div>
      <p class="flus26-compare__foot">
        Todas las capacidades se pueden ampliar. Si necesitás un usuario o una caja más,
        evaluamos el complemento antes de cambiar de plan.
      </p>
    </div>

    <div class="flus26-server-note">
      <h3>¿Necesitás una implementación propia?</h3>
      <p><b>FLUS Server</b> es otra modalidad, no un plan más: instalación en tu infraestructura, varios puestos, migraciones, integraciones y escenarios especiales.</p>
      <a class="btn btn-secondary" href="<?= e(site_url('flus-server.php')) ?>" data-track-event="pricing_plan_click" data-track-plan="server" data-track-label="Plan FLUS Server">Consultar FLUS Server</a>
    </div>
  </div>
</section>

<section class="flus26-section flus26-onboarding" data-reveal>
  <div class="container">
    <div class="flus26-sectionhead">
      <span class="section-kicker">Puesta en marcha</span>
      <h2>De la consulta a la primera venta.</h2>
      <p>No te entregamos un sistema vacío y te dejamos solo. La implementación se arma alrededor de cómo trabaja tu comercio.</p>
    </div>
    <div class="flus26-onboarding__grid">
      <article><span>01</span><h3>Nos contás tu operación</h3><p>Cajas, productos, medios de pago, stock, usuarios y qué necesitás resolver.</p></article>
      <article><span>02</span><h3>Elegimos Web o Server</h3><p>Te recomendamos la versión que tenga sentido hoy, sin sobredimensionar el proyecto.</p></article>
      <article><span>03</span><h3>Configuramos FLUS</h3><p>Preparamos la base, parámetros, accesos y carga o migración inicial según alcance.</p></article>
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
      <details open><summary>¿Cuál es la diferencia entre FLUS Web y FLUS Server?</summary><p>FLUS Web está pensado como servicio administrado y accesible desde navegador/PWA. FLUS Server se instala en la infraestructura del cliente y permite una implementación más personalizada.</p></details>
      <details><summary>¿FLUS Web funciona en teléfono y tablet?</summary><p>Sí. La interfaz está preparada para PC y uso móvil, incluyendo Android, iPhone y tablet en los flujos principales.</p></details>
      <details><summary>¿Puedo usar lector de código de barras?</summary><p>Sí. FLUS contempla lector de códigos y, en la edición Web, también flujos de escaneo con cámara en dispositivos compatibles.</p></details>
      <details><summary>¿La facturación ARCA está incluida?</summary><p>Depende de la edición, perfil e implementación contratada. En proyectos Server puede incorporarse como parte del alcance; en Web se define según el perfil comercial del cliente.</p></details>
      <details><summary>¿Puedo sumar más cajas o sucursales?</summary><p>Sí. En FLUS Web la propuesta base parte de 1 local y 1 caja, y se puede ampliar. En Server se define la cantidad de puestos al cotizar la instalación.</p></details>
      <details><summary>¿Puedo ver una demo antes de contratar?</summary><p>Sí. La demo está pensada para mostrar una operación real: venta, caja, stock, productos y seguimiento desde las pantallas de FLUS.</p></details>
    </div>
  </div>
</section>

<section class="flus26-cta" data-reveal>
  <div class="container flus26-cta__inner">
    <div>
      <span>¿WEB O SERVER?</span>
      <h2>Probá FLUS primero. Después elegimos la implementación.</h2>
      <p>Podés entrar a la demo de FLUS Web o contarnos cómo trabajás para que te recomendemos la edición adecuada.</p>
    </div>
    <div class="flus26-cta__actions">
      <a class="btn btn-primary" href="<?= e((string)($site['demo_url'] ?? site_url('contacto.php'))) ?>" target="_blank" rel="noopener" data-track-event="cta_primary_click" data-track-label="CTA final demo">Abrir demo de FLUS Web</a>
              <a class="btn btn-secondary" href="<?= e(whatsapp_url("Hola, quiero saber si me conviene FLUS Web o FLUS Server.")) ?>" target="_blank" rel="noopener" data-track-event="click_whatsapp" data-track-label="CTA final WhatsApp">Hablar por WhatsApp</a>
          </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
