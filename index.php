<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Sistema de gestión para comercios | FLUS Web y Server';
$pageDescription = 'Caja, ventas, stock, compras y proveedores en una misma operación. Empezá con FLUS Web o implementá FLUS Server en infraestructura propia.';
$webPlan = $site['commercial']['web'] ?? [];

$faqItems = [
    ['question' => '¿Cuál es la diferencia entre FLUS Web y FLUS Server?', 'answer' => 'FLUS Web se usa desde PC, tablet o teléfono y FLUS se ocupa de la parte técnica. FLUS Server se instala en tu negocio o servidor propio y permite una configuración más personalizada.'],
    ['question' => '¿FLUS Web funciona en teléfono y tablet?', 'answer' => 'Sí. La interfaz está preparada para PC, tablet, Android y iPhone en los flujos principales.'],
    ['question' => '¿Puedo usar lector de código de barras?', 'answer' => 'Sí. FLUS contempla lector de códigos y, en la edición Web, también flujos de escaneo con cámara en dispositivos compatibles.'],
    ['question' => '¿La facturación ARCA está incluida?', 'answer' => 'Depende de la edición, perfil e implementación contratada. En proyectos Server puede incorporarse como parte del alcance; en Web se define según el perfil comercial.'],
    ['question' => '¿Puedo sumar más cajas o sucursales?', 'answer' => 'Sí. En FLUS Web la propuesta base parte de 1 local y 1 caja, y se puede ampliar. En Server se define la cantidad de puestos al cotizar la instalación.'],
    ['question' => '¿Puedo ver una demo antes de contratar?', 'answer' => 'Sí. La demo está pensada para mostrar una operación real: venta, caja, stock, productos y seguimiento desde las pantallas de FLUS.'],
];

$pageSchemas = [
    ['@context' => 'https://schema.org', '@type' => 'SoftwareApplication', 'name' => 'FLUS', 'applicationCategory' => 'BusinessApplication', 'operatingSystem' => 'Web, Windows, Server', 'description' => $pageDescription, 'url' => page_url(), 'offers' => [['@type' => 'Offer', 'name' => 'FLUS Web', 'priceCurrency' => 'ARS', 'price' => preg_replace('/\D+/', '', (string)($webPlan['monthly_price'] ?? '45000')), 'description' => 'Abono mensual base para 1 local y 1 caja.']]],
    ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(static function (array $item): array { return ['@type' => 'Question', 'name' => $item['question'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['answer']]]; }, $faqItems)],
];

require __DIR__ . '/includes/header.php';
?>

<section class="flus26-hero">
  <div class="container flus26-hero__grid">
    <div class="flus26-hero__copy">
      <div class="flus26-pill"><span></span> FLUS · Gestión comercial</div>
      <h1>Vendé rápido.<br><em>El resto se actualiza solo.</em></h1>
      <p class="flus26-hero__lead">Una sola operación para vender, controlar stock y seguir el negocio donde estés.</p>
      <div class="flus26-hero__actions">
        <a class="btn btn-primary" href="<?= e((string)($site['demo_url'] ?? site_url('contacto.php') . '#formulario-contacto')) ?>" target="_blank" rel="noopener" data-track-event="cta_primary_click" data-track-label="Hero probar FLUS">Probar FLUS</a>
        <?php if (($site['whatsapp_number'] ?? '') !== ''): ?><a class="btn btn-secondary" href="<?= e(whatsapp_url('Hola, quiero conocer FLUS y coordinar una demo.')) ?>" target="_blank" rel="noopener" data-track-event="click_whatsapp" data-track-label="Hero consulta WhatsApp">Hablar por WhatsApp</a><?php endif; ?>
      </div>
      <div class="flus26-hero__trust" aria-label="Alcance de FLUS"><span>Desde cualquier dispositivo</span><span>Caja + stock + ventas</span><span>Implementación acompañada</span></div>
    </div>
    <div class="flus26-hero__visual" aria-label="FLUS funcionando en escritorio y teléfono">
      <div class="flus26-desktop-stage">
        <div class="flus26-product-window">
          <div class="flus26-windowbar"><span></span><span></span><span></span><b>FLUS · Caja y cobros</b></div>
          <picture><source srcset="<?= e(asset_url('img/flus-caja-pos.webp')) ?>" type="image/webp"><img src="<?= e(asset_url('img/flus-caja-pos.png')) ?>" alt="Pantalla real de caja POS de FLUS con ticket, medios de pago y total a cobrar" width="1608" height="978" fetchpriority="high" decoding="async"></picture>
        </div>
      </div>

      <div class="flus26-phone-wrap" aria-label="Demostración de FLUS Web en teléfono">
        <div class="flus26-phone-badge"><span class="flus26-version-dot"></span><b>FLUS Web</b><small>También en tu teléfono</small></div>
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
                <div class="flus26-phone__metricgrid"><div><small>Productos</small><b>528</b><span>Activos</span></div><div><small>Stock bajo</small><b>7</b><span>Revisar</span></div></div>
                <div class="flus26-phone__mini-title">Movimientos recientes</div>
                <div class="flus26-phone__stockrow"><span>Yerba 1 kg</span><b>24 u.</b></div>
                <div class="flus26-phone__stockrow"><span>Agua mineral</span><b>18 u.</b></div>
                <div class="flus26-phone__stockrow flus26-phone__stockrow--warn"><span>Jabón líquido</span><b>2 u.</b></div>
                <div class="flus26-phone__scan"><span>▦</span><div><b>Escanear código</b><small>Usar cámara del teléfono</small></div></div>
              </div>
              <div class="flus26-phone-panel flus26-phone-panel--dash">
                <div class="flus26-phone__sectiontop"><div><small>RESUMEN DE HOY</small><b>Resumen del día</b></div><span>↗</span></div>
                <div class="flus26-phone__salescard"><small>Ventas del día</small><b>$186.420</b><span>↑ 12,4% vs. ayer</span></div>
                <div class="flus26-phone__bars" aria-hidden="true"><i style="--h:38%"></i><i style="--h:58%"></i><i style="--h:46%"></i><i style="--h:72%"></i><i style="--h:64%"></i><i style="--h:88%"></i><i style="--h:76%"></i></div>
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

<section class="flus26-signalbar" aria-label="Resumen del producto"><div class="container flus26-signalbar__inner"><span><b>2</b> ediciones</span><span><b>1</b> plataforma de gestión</span><span><b>Compatible</b> con PC, tablet y teléfono</span><span><b>Demo</b> con operación real</span></div></section>

<section class="flus26-section flus26-product-tour" id="producto" data-reveal data-product-tour>
  <div class="container">
    <span id="funciones" class="sr-only">Funciones de FLUS</span>
    <div class="flus26-sectionhead flus26-sectionhead--split flus26-product-tour__head"><div><span class="section-kicker">Producto real</span><h2>Conocé FLUS<br>por dentro.</h2></div><p>Elegí una pantalla y mirá el flujo completo: vender, controlar y seguir el negocio.</p></div>
    <div class="flus26-tour">
      <div class="flus26-tour__nav" role="tablist" aria-label="Pantallas de FLUS">
        <button class="flus26-tour__tab is-active" type="button" role="tab" aria-selected="true" aria-controls="flus-tour-caja" data-tour-target="caja"><span class="flus26-tour__num">01</span><span class="flus26-tour__tabcopy"><strong>Caja y cobros</strong><small>Venta, ticket y cobro en una sola vista.</small></span><span class="flus26-tour__arrow" aria-hidden="true">→</span></button>
        <button class="flus26-tour__tab" type="button" role="tab" aria-selected="false" aria-controls="flus-tour-dashboard" data-tour-target="dashboard"><span class="flus26-tour__num">02</span><span class="flus26-tour__tabcopy"><strong>Resumen del negocio</strong><small>Indicadores claros para leer el negocio.</small></span><span class="flus26-tour__arrow" aria-hidden="true">→</span></button>
        <button class="flus26-tour__tab" type="button" role="tab" aria-selected="false" aria-controls="flus-tour-stock" data-tour-target="stock"><span class="flus26-tour__num">03</span><span class="flus26-tour__tabcopy"><strong>Stock</strong><small>Disponibilidad, filtros y alertas.</small></span><span class="flus26-tour__arrow" aria-hidden="true">→</span></button>
        <button class="flus26-tour__tab" type="button" role="tab" aria-selected="false" aria-controls="flus-tour-ventas" data-tour-target="ventas"><span class="flus26-tour__num">04</span><span class="flus26-tour__tabcopy"><strong>Ventas</strong><small>Historial y seguimiento de cada operación.</small></span><span class="flus26-tour__arrow" aria-hidden="true">→</span></button>
      </div>
      <div class="flus26-tour__stage">
        <div class="flus26-tour__windowbar"><div class="flus26-tour__dots" aria-hidden="true"><i></i><i></i><i></i></div><strong data-tour-window-title>FLUS · Caja y cobros</strong><span><i></i> Producto real</span></div>
        <div class="flus26-tour__viewport">
          <figure class="flus26-tour__panel is-active" id="flus-tour-caja" role="tabpanel" data-tour-panel="caja" data-tour-title="FLUS · Caja y cobros" data-tour-kicker="OPERACIÓN DIARIA" data-tour-caption="Vendé, elegí el medio de pago y cerrá la operación sin cambiar de pantalla."><picture><source srcset="<?= e(asset_url('img/flus-caja-pos.webp')) ?>" type="image/webp"><img src="<?= e(asset_url('img/flus-caja-pos.png')) ?>" alt="Pantalla de Caja POS de FLUS" width="1608" height="978" loading="lazy"></picture></figure>
          <figure class="flus26-tour__panel" id="flus-tour-dashboard" role="tabpanel" hidden data-tour-panel="dashboard" data-tour-title="FLUS · Resumen del negocio" data-tour-kicker="CONTROL DEL NEGOCIO" data-tour-caption="Ventas, productos y medios de pago resumidos para entender qué está pasando."><picture><source srcset="<?= e(asset_url('img/Dashboard.webp')) ?>" type="image/webp"><img src="<?= e(asset_url('img/Dashboard.png')) ?>" alt="Resumen del negocio en FLUS" width="1536" height="1024" loading="lazy"></picture></figure>
          <figure class="flus26-tour__panel" id="flus-tour-stock" role="tabpanel" hidden data-tour-panel="stock" data-tour-title="FLUS · Stock" data-tour-kicker="INVENTARIO" data-tour-caption="Detectá faltantes y revisá existencias con filtros y alertas en una misma vista."><picture><source srcset="<?= e(asset_url('img/Stock.webp')) ?>" type="image/webp"><img src="<?= e(asset_url('img/Stock.png')) ?>" alt="Control de stock de FLUS" width="1536" height="1024" loading="lazy"></picture></figure>
          <figure class="flus26-tour__panel" id="flus-tour-ventas" role="tabpanel" hidden data-tour-panel="ventas" data-tour-title="FLUS · Ventas" data-tour-kicker="SEGUIMIENTO" data-tour-caption="Consultá operaciones anteriores y mantené trazabilidad de las ventas del comercio."><picture><source srcset="<?= e(asset_url('img/ventas.webp')) ?>" type="image/webp"><img src="<?= e(asset_url('img/ventas.png')) ?>" alt="Historial de ventas de FLUS" width="1536" height="1024" loading="lazy"></picture></figure>
        </div>
        <div class="flus26-tour__caption"><div><span data-tour-kicker-output>OPERACIÓN DIARIA</span><strong data-tour-caption-output>Vendé, elegí el medio de pago y cerrá la operación sin cambiar de pantalla.</strong></div><span class="flus26-tour__hint">Elegí un módulo para explorarlo</span></div>
      </div>
    </div>
  </div>
</section>

<section class="flus26-section flus26-versions" id="versiones" data-reveal><div class="container"><div class="flus26-sectionhead"><span class="section-kicker">Versiones</span><h2>Dos formas de usar FLUS.</h2><p>Elegí dónde querés trabajar y cuánto necesitás personalizar.</p></div><div class="flus26-edition-grid">
  <article class="flus26-edition flus26-edition--web" id="flus-web"><div class="flus26-edition__top"><span class="flus26-edition__tag">Recomendado para empezar</span><span class="flus26-edition__code">WEB / ONLINE</span></div><h3>FLUS Web</h3><p class="flus26-edition__lead">Para trabajar desde PC, tablet o teléfono, sin instalar un servidor en el negocio.</p><div class="flus26-edition__summary"><span>Acceso</span><strong>PC, tablet y teléfono</strong></div><ul class="flus26-checks"><li>FLUS se ocupa de la parte técnica</li><li>Puesta en marcha rápida</li><li>Actualizaciones y soporte incluidos</li></ul><div class="flus26-edition__foot"><span>Ideal para empezar con una operación simple y conectada.</span><a href="<?= e(site_url('flus-web.php')) ?>" class="flus26-arrowlink" data-track-event="click_contact" data-track-label="Conocer FLUS Web">Conocer FLUS Web <span>→</span></a></div></article>
  <article class="flus26-edition flus26-edition--server" id="flus-server"><div class="flus26-edition__top"><span class="flus26-edition__tag">Más control y personalización</span><span class="flus26-edition__code">INSTALACIÓN PROPIA</span></div><h3>FLUS Server</h3><p class="flus26-edition__lead">Para negocios con varias cajas o una instalación propia.</p><div class="flus26-edition__summary"><span>Instalación</span><strong>En tu negocio o servidor</strong></div><ul class="flus26-checks"><li>Instalación en tu red o servidor</li><li>Se adapta a tus cajas y puestos</li><li>Pasamos tus datos y conectamos otros sistemas</li></ul><div class="flus26-edition__foot"><span>Ideal cuando el escenario necesita un proyecto propio.</span><a href="<?= e(site_url('flus-server.php')) ?>" class="flus26-arrowlink" data-track-event="click_contact" data-track-label="Conocer FLUS Server">Conocer FLUS Server <span>→</span></a></div></article>
</div></div></section>

<section class="flus26-section flus26-finder" id="que-flus-necesito" data-reveal><div class="container"><div class="flus26-sectionhead flus26-sectionhead--split"><div><span class="section-kicker">Orientación rápida</span><h2>Elegí por tu forma de trabajar.</h2></div><p>Tres respuestas para saber qué edición conviene mirar primero.</p></div><div class="flus26-finder__grid" data-flus-finder><div class="flus26-finder__questions"><fieldset class="flus26-choicegroup" data-finder-group="access"><legend>1. ¿Desde dónde necesitás trabajar?</legend><div class="flus26-choicegroup__options"><button type="button" class="is-selected" data-finder-choice="web">PC, tablet o teléfono</button><button type="button" data-finder-choice="server">Quiero instalarlo en mi negocio</button></div></fieldset><fieldset class="flus26-choicegroup" data-finder-group="scale"><legend>2. ¿Cómo es tu operación?</legend><div class="flus26-choicegroup__options"><button type="button" class="is-selected" data-finder-choice="web">Quiero empezar simple y rápido</button><button type="button" data-finder-choice="server">Tengo varias cajas o necesidades especiales</button></div></fieldset><fieldset class="flus26-choicegroup" data-finder-group="custom"><legend>3. ¿Necesitás conectar otros sistemas?</legend><div class="flus26-choicegroup__options"><button type="button" class="is-selected" data-finder-choice="web">No, con lo habitual me alcanza</button><button type="button" data-finder-choice="server">Sí, tengo datos o sistemas para conectar</button></div></fieldset></div><aside class="flus26-finder__result is-web" data-finder-result aria-live="polite"><span class="flus26-finder__eyebrow">Nuestra recomendación</span><div class="flus26-finder__resulthead"><span class="flus26-finder__resulticon">W</span><div><small>PARA TU ESCENARIO</small><h3 data-finder-title>FLUS Web</h3></div></div><p data-finder-copy>Por lo que marcaste, podés comenzar con la versión Web, rápida de implementar y administrada por FLUS.</p><ul data-finder-benefits><li>Funciona en PC, tablet y teléfono</li><li>FLUS se ocupa de la parte técnica</li><li>Entrada mensual simple</li></ul><div class="flus26-finder__actions"><a class="btn btn-primary" data-finder-primary href="<?= e((string)($site['demo_url'] ?? site_url('contacto.php') . '#formulario-contacto')) ?>" target="_blank" rel="noopener" data-track-event="cta_primary_click" data-track-label="Quiz probar FLUS">Probar FLUS</a><a class="flus26-finder__secondary" data-finder-secondary href="<?= e(whatsapp_url('Hola, quiero conocer FLUS y coordinar una demo.')) ?>" target="_blank" rel="noopener" data-track-event="click_whatsapp" data-track-label="Quiz consulta WhatsApp">Consultar por WhatsApp</a></div></aside></div></div></section>

<section class="flus26-social" data-reveal aria-labelledby="social-title"><div class="container flus26-social__inner"><div><span class="section-kicker">Casos y referencias</span><h2 id="social-title">Conocé cómo lo usan otros comercios.</h2></div><p>Estamos reuniendo historias reales para publicarlas acá. Mientras tanto, podemos compartirte referencias en una conversación.</p><a class="flus26-arrowlink" href="<?= e(site_url('contacto.php')) ?>" data-track-event="click_contact" data-track-label="Consultar referencias">Consultar referencias <span>→</span></a></div></section>

<section class="flus26-section flus26-compare" id="comparar" data-reveal><div class="container"><div class="flus26-sectionhead"><span class="section-kicker">En una mirada</span><h2>La diferencia está acá.</h2><p>Una edición prioriza velocidad de puesta en marcha; la otra, control sobre la instalación.</p></div><div class="flus26-tablewrap" role="region" aria-label="Comparación entre FLUS Web y FLUS Server" tabindex="0"><table class="flus26-table"><thead><tr><th scope="col">Decisión</th><th scope="col">FLUS Web</th><th scope="col">FLUS Server</th></tr></thead><tbody><tr><th scope="row">Dónde trabajás</th><td>PC, tablet o teléfono</td><td>Instalado en tu negocio</td></tr><tr><th scope="row">Quién se ocupa de la parte técnica</th><td>FLUS</td><td>Tu equipo</td></tr><tr><th scope="row">Cómo empezás</th><td>Rápido</td><td>A medida</td></tr><tr><th scope="row">Cuándo elegirlo</th><td>Querés operar ya</td><td>Necesitás adaptar el escenario</td></tr></tbody></table></div></div></section>

<section class="flus26-section flus26-pricing" id="precios" data-reveal><div class="container"><div class="flus26-sectionhead flus26-sectionhead--dark"><span class="section-kicker">Precios</span><h2>Empezá con una base clara.</h2><p>Web tiene una entrada mensual definida. Server se cotiza según el alcance.</p></div><div class="flus26-price-grid"><article class="flus26-price-card flus26-price-card--primary"><div class="flus26-price-card__top"><span class="flus26-price-card__label">FLUS Web</span><span class="flus26-price-card__badge">Para empezar</span></div><div class="flus26-price-card__amount"><small>desde</small><strong><?= e((string)($webPlan['monthly_price'] ?? '$45.000')) ?></strong><span>ARS / mes</span></div><p>Base para <b><?= e((string)($webPlan['base_scope'] ?? '1 local + 1 caja')) ?></b>, con acceso, soporte y actualizaciones incluidos.</p><a class="btn btn-primary" href="<?= e((string)($site['demo_url'] ?? site_url('contacto.php') . '#formulario-contacto')) ?>" target="_blank" rel="noopener" data-track-event="cta_primary_click" data-track-label="Precios probar FLUS">Probar FLUS</a></article><article class="flus26-price-card flus26-price-card--server"><div class="flus26-price-card__top"><span class="flus26-price-card__label">FLUS Server</span><span class="flus26-price-card__badge">A medida</span></div><div class="flus26-price-card__quote"><small>VALOR</small><strong>Cotización personalizada</strong><span>Se define por puestos, instalación y alcance.</span></div><p>Para negocios con instalación propia, varias cajas o necesidades especiales.</p><?php if (($site['whatsapp_number'] ?? '') !== ''): ?><a class="btn btn-secondary" href="<?= e(whatsapp_url('Hola, quiero cotizar FLUS Server.')) ?>" target="_blank" rel="noopener" data-track-event="click_whatsapp" data-track-label="Precios cotizar Server">Hablar por WhatsApp</a><?php endif; ?></article></div><p class="flus26-price-note">Te ayudamos a elegir la edición adecuada antes de configurar nada.</p></div></section>

<section class="flus26-section flus26-onboarding" data-reveal><div class="container"><div class="flus26-sectionhead"><span class="section-kicker">Puesta en marcha</span><h2>De la consulta a la primera venta.</h2></div><div class="flus26-onboarding__grid"><article><span>01</span><h3>Nos contás cómo trabajás</h3><p>Vemos cajas, productos, medios de pago y qué necesitás resolver.</p></article><article><span>02</span><h3>Elegimos cómo usarlo</h3><p>Vemos si te conviene trabajar online o con una instalación propia.</p></article><article><span>03</span><h3>Dejamos todo listo</h3><p>Preparamos la base, los accesos y la carga inicial.</p></article><article><span>04</span><h3>Empezás a operar</h3><p>Probamos el flujo real y te acompañamos.</p></article></div></div></section>

<section class="resource-section resource-section--soft" id="recursos" data-reveal><div class="container"><div class="resource-head"><div><span class="section-kicker">Recursos</span><h2>Aprendé antes de elegir un sistema.</h2></div><p>Guías prácticas sobre caja, stock y gestión para resolver problemas reales del comercio.</p></div><div class="related-grid"><a class="related-card related-card--feature" href="<?= e(site_url('recursos/controlar-stock-comercio/')) ?>" data-track-event="click_resource" data-track-label="Home recurso stock"><span class="related-card__eyebrow"><b>01</b><em>Stock e inventario</em></span><strong>Cómo controlar el stock de un comercio</strong><p>Entradas, ventas, ajustes e inventario sin depender de una celda manual.</p><span class="related-card__read">Leer guía <b aria-hidden="true">↗</b></span></a><a class="related-card related-card--light" href="<?= e(site_url('recursos/como-hacer-cierre-de-caja/')) ?>" data-track-event="click_resource" data-track-label="Home recurso caja"><span class="related-card__eyebrow"><b>02</b><em>Caja y ventas</em></span><strong>Cómo hacer un cierre de caja correctamente</strong><p>Qué comparar y cómo investigar una diferencia.</p><span class="related-card__read">Leer guía <b aria-hidden="true">↗</b></span></a><a class="related-card related-card--light" href="<?= e(site_url('recursos/que-sistema-necesita-un-kiosco/')) ?>" data-track-event="click_resource" data-track-label="Home recurso kiosco"><span class="related-card__eyebrow"><b>03</b><em>Guía por rubro</em></span><strong>Qué sistema necesita un kiosco</strong><p>Las funciones que importan para vender rápido y mantener control.</p><span class="related-card__read">Leer guía <b aria-hidden="true">↗</b></span></a></div><div class="resource-section__actions"><a class="btn btn-secondary" href="<?= e(site_url('recursos/')) ?>">Ver todas las guías</a></div></div></section>

<section class="flus26-section flus26-faq" data-reveal><div class="container"><div class="flus26-sectionhead"><span class="section-kicker">Preguntas frecuentes</span><h2>Lo importante antes de elegir.</h2></div><div class="flus26-faq-grid"><?php foreach ($faqItems as $index => $item): ?><details <?= $index === 0 ? 'open' : '' ?>><summary><?= e($item['question']) ?></summary><p><?= e($item['answer']) ?></p></details><?php endforeach; ?></div></div></section>

<section class="flus26-cta" data-reveal><div class="container flus26-cta__inner"><div><span>SIGUIENTE PASO</span><h2>¿Querés verlo en acción?</h2><p>Entrá a la demo o contanos cómo trabajás y te orientamos.</p></div><div class="flus26-cta__actions"><a class="btn btn-primary" href="<?= e((string)($site['demo_url'] ?? site_url('contacto.php') . '#formulario-contacto')) ?>" target="_blank" rel="noopener" data-track-event="cta_primary_click" data-track-label="CTA final probar FLUS">Probar FLUS</a><?php if (($site['whatsapp_number'] ?? '') !== ''): ?><a class="btn btn-secondary" href="<?= e(whatsapp_url('Hola, quiero saber si me conviene FLUS Web o FLUS Server.')) ?>" target="_blank" rel="noopener" data-track-event="click_whatsapp" data-track-label="CTA final consulta WhatsApp">Hablar por WhatsApp</a><?php endif; ?></div></div></section>

<?php require __DIR__ . '/includes/footer.php'; ?>
