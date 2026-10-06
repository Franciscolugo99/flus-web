<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Sistema de gestión web para comercios | FLUS Web';
$pageDescription = 'FLUS Web es un sistema de gestión web para comercios con caja de ventas, ventas, stock, compras y proveedores. Usalo desde PC, tablet, Android o iPhone.';
$pageStyles = ['css/seo-growth.css'];
$pageBreadcrumbs = [
    ['name' => 'Inicio', 'url' => page_url()],
    ['name' => 'FLUS Web', 'url' => page_url('flus-web.php')],
];
// Los planes de FLUS Web salen del registro comercial (includes/plans.php) y sus
// precios de configuracion. No se declaran precios que no esten definidos.
$inicioOffer = ['@type' => 'Offer', 'name' => 'FLUS Inicio', 'description' => 'Plan de entrada: 1 local, 1 caja, 1 usuario. Caja, ventas y stock incluidas.', 'category' => 'Suscripción mensual'];
if (flus_plan_price_value('inicio') !== '') {
    // Valor numerico para el schema, no el texto con formato de moneda.
    $inicioOffer['price'] = flus_plan_price_value('inicio');
    $inicioOffer['priceCurrency'] = 'ARS';
}
$pageSchemas = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'FLUS Web',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web, Android, iOS, Windows, macOS',
        'description' => 'Sistema de gestión web para comercios con caja de ventas, ventas, productos, stock, compras, proveedores, accesos y cajas.',
        'url' => page_url('flus-web.php'),
        'offers' => [$inicioOffer],
        'featureList' => 'Caja de ventas, ventas, productos, stock, compras, proveedores, usuarios, cajas, lector y cámara para códigos de barras',
    ],
    [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => [
            ['@type'=>'Question','name'=>'¿Puedo usarlo desde iPhone o Android?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Sí. Podés usar FLUS Web desde la computadora, una tablet o tu celular Android o iPhone.']],
            ['@type'=>'Question','name'=>'¿Puedo usar lector de códigos de barras?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Sí. Podés usar un lector de códigos de barras y, en las funciones compatibles, la cámara del celular.']],
            ['@type'=>'Question','name'=>'¿La facturación electrónica está incluida?','acceptedAnswer'=>['@type'=>'Answer','text'=>'La facturación electrónica con ARCA se confirma y presupuesta antes de contratar. El abono no la incluye automáticamente.']],
        ],
    ],
];
require __DIR__ . '/includes/header.php';
?>
<section class="growth-hero">
  <div class="container growth-hero__grid">
    <div>
      <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(site_url()) ?>">Inicio</a><span>/</span><span aria-current="page">FLUS Web</span></nav>
      <span class="growth-kicker">Por internet · recomendado para empezar</span>
      <h1>Sistema de gestión web para tu comercio, en la PC y también en el celular.</h1>
      <p class="growth-hero__lead">Vendé, revisá el stock y registrá tus compras desde tu computadora o celular. Usás el sistema por internet y nosotros nos encargamos de mantenerlo funcionando.</p>
      <div class="growth-hero__actions">
        <a class="btn btn-primary" href="<?= e((string)($site['demo_url'] ?? site_url('contacto.php'))) ?>" target="_blank" rel="noopener" data-track-event="cta_primary_click" data-track-label="FLUS Web hero demo">Probar FLUS</a>
        <a class="btn btn-secondary" href="<?= e(site_url('contacto.php')) ?>#formulario-contacto" data-track-event="click_contact" data-track-label="FLUS Web hero consulta">Consultar cómo empezar</a>
      </div>
      <div class="growth-proof"><span>PC + tablet + móvil</span><span>Nos encargamos del sistema</span><span>Soporte y actualizaciones</span></div>
    </div>
    <div class="growth-showcase">
      <div class="growth-window"><div class="growth-window__bar"><i></i><i></i><i></i><b>FLUS Web · Caja de ventas</b></div><picture><source srcset="<?= e(asset_url('img/flus-caja-pos.webp')) ?>" type="image/webp"><img src="<?= e(asset_url('img/flus-caja-pos.png')) ?>" alt="Caja de ventas de FLUS Web" width="1608" height="978" fetchpriority="high" decoding="async"></picture></div>
      <div class="growth-float"><strong>Entrá desde tu computadora o celular</strong><span>La misma operación preparada para computadora, tablet y teléfono.</span></div>
    </div>
  </div>
</section>
<section class="growth-section">
  <div class="container">
    <div class="growth-head"><span class="section-kicker">Operación diaria</span><h2>Lo necesario para dejar de depender de planillas y pantallas sueltas.</h2><p>FLUS Web conecta las tareas principales del comercio para que una venta, un ingreso de mercadería o un movimiento de stock formen parte del mismo circuito.</p></div>
    <div class="growth-grid">
      <article class="growth-card"><span class="growth-card__num">01</span><h3>Caja de ventas y ventas</h3><p>Productos, ticket, cantidades, medios de pago, apertura, movimientos y cierre de caja.</p></article>
      <article class="growth-card"><span class="growth-card__num">02</span><h3>Productos y stock</h3><p>Existencias, movimientos, alertas, precios e inventario con búsqueda y códigos de barras.</p></article>
      <article class="growth-card"><span class="growth-card__num">03</span><h3>Compras y proveedores</h3><p>Ingresá mercadería y conectá reposición, costos y disponibilidad con la operación.</p></article>
      <article class="growth-card"><span class="growth-card__num">04</span><h3>Accesos y cajas</h3><p>Organizá la operación por usuarios, permisos y cajas o puestos de trabajo.</p></article>
      <article class="growth-card"><span class="growth-card__num">05</span><h3>Resumen de ventas</h3><p>Consultá ventas, actividad y movimientos para entender mejor lo que pasó en el negocio.</p></article>
      <article class="growth-card"><span class="growth-card__num">06</span><h3>Códigos de barras</h3><p>Trabajá con lector y, donde corresponda, cámara del teléfono para agilizar búsquedas y carga.</p></article>
    </div>
  </div>
</section>
<section class="growth-section growth-section--soft">
  <div class="container growth-split">
    <div class="growth-panel"><span class="section-kicker">Planes</span><div class="growth-price"><strong<?= flus_plan_price('inicio') === '' ? ' class="is-placeholder"' : '' ?>><?= e(flus_plan_price_text('inicio')) ?></strong><?php if (flus_plan_price('inicio') !== ''): ?><span>por mes</span><?php endif; ?></div><p class="growth-note">El plan de entrada es <b>FLUS Inicio</b>: 1 local, 1 caja y 1 usuario, con las funciones de caja, ventas y stock incluidas. Desde ahí sumás capacidad cuando la operación lo pide. <a href="<?= e(site_url()) ?>#precios">Ver todos los planes →</a></p><div class="growth-hero__actions"><a class="btn btn-primary" href="<?= e((string)($site['demo_url'] ?? site_url('contacto.php'))) ?>" target="_blank" rel="noopener" data-track-event="cta_primary_click" data-track-label="FLUS Web precio demo">Probar FLUS</a></div></div>
    <div class="growth-panel"><h3>¿Para quién encaja mejor?</h3><ul class="growth-list"><li>Comercios que hoy trabajan con Excel, cuadernos o un sistema viejo.</li><li>Negocios que quieren empezar sin mantener equipos dedicados al sistema.</li><li>Operaciones que necesitan consultar o trabajar también desde móvil.</li><li>Comercios con una o varias cajas que buscan una base simple para crecer.</li></ul></div>
  </div>
</section>
<section class="growth-section">
  <div class="container"><div class="growth-head"><span class="section-kicker">Web o Server</span><h2>También podés instalar FLUS en tu negocio.</h2><p>Con FLUS Web entrás por internet desde tus dispositivos. Si necesitás instalar el sistema en los equipos de tu negocio o adaptarlo a tu forma de trabajar, consultanos por FLUS Server.</p></div><div class="growth-comparison" role="region" aria-label="Comparación entre FLUS Web y FLUS Server" tabindex="0"><table><thead><tr><th scope="col">Necesidad</th><th scope="col">FLUS Web</th><th scope="col">FLUS Server</th></tr></thead><tbody><tr><td>Usarlo desde computadora o celular</td><td>Sí</td><td>Según proyecto</td></tr><tr><td>Equipos dedicados al sistema</td><td>No necesario</td><td>En los equipos de tu negocio</td></tr><tr><td>Puesta en marcha rápida</td><td>Prioridad</td><td>Proyecto personalizado</td></tr><tr><td>Adaptaciones a tu negocio</td><td>Se confirma antes de contratar</td><td>Acordadas con vos</td></tr></tbody></table></div><div class="growth-hero__actions" style="margin-top:22px"><a class="btn btn-secondary" href="<?= e(site_url('flus-server.php')) ?>">Conocer FLUS Server</a></div></div>
</section>
<section class="growth-section growth-section--soft">
  <div class="container"><div class="growth-head"><span class="section-kicker">Preguntas frecuentes</span><h2>Antes de probar FLUS Web.</h2></div><div class="growth-faq"><details><summary>¿Puedo usarlo desde iPhone o Android?</summary><p>Sí. Podés usar FLUS Web desde la computadora, una tablet o tu celular Android o iPhone.</p></details><details><summary>¿Puedo usar lector de códigos de barras?</summary><p>Sí. Podés usar un lector de códigos de barras y, en las funciones compatibles, la cámara del celular.</p></details><details><summary>¿La facturación electrónica está incluida?</summary><p>La facturación electrónica con ARCA se confirma y presupuesta antes de contratar. El abono no la incluye automáticamente.</p></details></div></div>
</section>
<section class="growth-section"><div class="container"><div class="growth-cta"><div><h2>Probalo antes de decidir.</h2><p>Probá el sistema o contanos cómo trabaja tu comercio y te ayudamos a elegir.</p></div><div class="growth-cta__actions"><a class="btn btn-primary" href="<?= e((string)($site['demo_url'] ?? site_url('contacto.php'))) ?>" target="_blank" rel="noopener" data-track-event="cta_primary_click" data-track-label="FLUS Web CTA demo">Probar FLUS</a><a class="btn btn-secondary" href="<?= e(site_url('contacto.php')) ?>#formulario-contacto" data-track-event="click_contact">Contar cómo es mi negocio</a></div></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
