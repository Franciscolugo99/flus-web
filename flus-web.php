<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Sistema de gestión web para comercios | FLUS Web';
$pageDescription = 'FLUS Web es un sistema de gestión web para comercios con caja POS, ventas, stock, compras y proveedores. Usalo desde PC, tablet, Android o iPhone.';
$pageStyles = ['css/seo-growth.css'];
$pageBreadcrumbs = [
    ['name' => 'Inicio', 'url' => page_url()],
    ['name' => 'FLUS Web', 'url' => page_url('flus-web.php')],
];
// Los planes de FLUS Web salen del registro comercial (includes/plans.php) y sus
// precios de configuracion. No se declaran precios que no esten definidos.
$inicioOffer = ['@type' => 'Offer', 'name' => 'FLUS Inicio', 'description' => 'Plan de entrada: 1 local, 1 caja, 1 usuario. Funciones centrales incluidas.', 'category' => 'Suscripción mensual'];
if (flus_plan_price('inicio') !== '') {
    $inicioOffer['price'] = preg_replace('/[^\d.,]/', '', flus_plan_price('inicio'));
    $inicioOffer['priceCurrency'] = 'ARS';
}
$pageSchemas = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'FLUS Web',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web, Android, iOS, Windows, macOS',
        'description' => 'Sistema de gestión web para comercios con caja POS, ventas, productos, stock, compras, proveedores, usuarios y terminales.',
        'url' => page_url('flus-web.php'),
        'offers' => [$inicioOffer],
        'featureList' => 'Caja POS, ventas, productos, stock, compras, proveedores, usuarios, terminales, lector y cámara para códigos de barras',
    ],
    [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => [
            ['@type'=>'Question','name'=>'¿Puedo usarlo desde iPhone o Android?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Sí. La interfaz Web está preparada para PC, tablet y teléfono mediante navegador o PWA.']],
            ['@type'=>'Question','name'=>'¿Puedo usar lector de códigos de barras?','acceptedAnswer'=>['@type'=>'Answer','text'=>'Sí. FLUS contempla operación con lector y funciones de cámara/código donde el módulo lo admite.']],
            ['@type'=>'Question','name'=>'¿La facturación electrónica está incluida?','acceptedAnswer'=>['@type'=>'Answer','text'=>'La integración fiscal/ARCA depende de la edición y de la implementación acordada. Se confirma antes de contratar para no prometer un alcance que no corresponda.']],
        ],
    ],
];
require __DIR__ . '/includes/header.php';
?>
<section class="growth-hero">
  <div class="container growth-hero__grid">
    <div>
      <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(site_url()) ?>">Inicio</a><span>/</span><span aria-current="page">FLUS Web</span></nav>
      <span class="growth-kicker">Web + PWA · recomendado para empezar</span>
      <h1>Sistema de gestión web para tu comercio, en la PC y también en el celular.</h1>
      <p class="growth-hero__lead">Vendé, controlá caja y stock, registrá compras y seguí la operación sin montar un servidor dentro del negocio. FLUS Web está pensado para una puesta en marcha ágil y una operación cotidiana simple.</p>
      <div class="growth-hero__actions">
        <a class="btn btn-primary" href="<?= e((string)($site['demo_url'] ?? site_url('contacto.php'))) ?>" target="_blank" rel="noopener" data-track-event="cta_primary_click" data-track-label="FLUS Web hero demo">Probar FLUS</a>
        <a class="btn btn-secondary" href="<?= e(site_url('contacto.php')) ?>#formulario-contacto" data-track-event="click_contact" data-track-label="FLUS Web hero consulta">Consultar implementación</a>
      </div>
      <div class="growth-proof"><span>PC + tablet + móvil</span><span>Sin servidor propio</span><span>Soporte y actualizaciones</span></div>
    </div>
    <div class="growth-showcase">
      <div class="growth-window"><div class="growth-window__bar"><i></i><i></i><i></i><b>FLUS Web · Caja POS</b></div><picture><source srcset="<?= e(asset_url('img/flus-caja-pos.webp')) ?>" type="image/webp"><img src="<?= e(asset_url('img/flus-caja-pos.png')) ?>" alt="Caja POS de FLUS Web" width="1608" height="978" fetchpriority="high" decoding="async"></picture></div>
      <div class="growth-float"><strong>Acceso desde navegador o PWA</strong><span>La misma operación preparada para computadora, tablet y teléfono.</span></div>
    </div>
  </div>
</section>
<section class="growth-section">
  <div class="container">
    <div class="growth-head"><span class="section-kicker">Operación diaria</span><h2>Lo necesario para dejar de depender de planillas y pantallas sueltas.</h2><p>FLUS Web conecta las tareas principales del comercio para que una venta, un ingreso de mercadería o un movimiento de stock formen parte del mismo circuito.</p></div>
    <div class="growth-grid">
      <article class="growth-card"><span class="growth-card__num">01</span><h3>Caja POS y ventas</h3><p>Productos, ticket, cantidades, medios de pago, apertura, movimientos y cierre de caja.</p></article>
      <article class="growth-card"><span class="growth-card__num">02</span><h3>Productos y stock</h3><p>Existencias, movimientos, alertas, precios e inventario con búsqueda y códigos de barras.</p></article>
      <article class="growth-card"><span class="growth-card__num">03</span><h3>Compras y proveedores</h3><p>Ingresá mercadería y conectá reposición, costos y disponibilidad con la operación.</p></article>
      <article class="growth-card"><span class="growth-card__num">04</span><h3>Usuarios y terminales</h3><p>Organizá la operación por usuarios, permisos y cajas o puestos de trabajo.</p></article>
      <article class="growth-card"><span class="growth-card__num">05</span><h3>Dashboard e historial</h3><p>Consultá ventas, actividad y movimientos para entender mejor lo que pasó en el negocio.</p></article>
      <article class="growth-card"><span class="growth-card__num">06</span><h3>Códigos de barras</h3><p>Trabajá con lector y, donde corresponda, cámara del teléfono para agilizar búsquedas y carga.</p></article>
    </div>
  </div>
</section>
<section class="growth-section growth-section--soft">
  <div class="container growth-split">
    <div class="growth-panel"><span class="section-kicker">Planes</span><div class="growth-price"><strong<?= flus_plan_price('inicio') === '' ? ' class="is-placeholder"' : '' ?>><?= e(flus_plan_price_text('inicio')) ?></strong><?php if (flus_plan_price('inicio') !== ''): ?><span>ARS / mes</span><?php endif; ?></div><p class="growth-note">El plan de entrada es <b>FLUS Inicio</b>: 1 local, 1 caja y 1 usuario, con las funciones centrales incluidas. Desde ahí sumás capacidad cuando la operación lo pide. <a href="<?= e(site_url()) ?>#precios">Ver todos los planes →</a></p><div class="growth-hero__actions"><a class="btn btn-primary" href="<?= e((string)($site['demo_url'] ?? site_url('contacto.php'))) ?>" target="_blank" rel="noopener" data-track-event="cta_primary_click" data-track-label="FLUS Web precio demo">Probar FLUS</a></div></div>
    <div class="growth-panel"><h3>¿Para quién encaja mejor?</h3><ul class="growth-list"><li>Comercios que hoy trabajan con Excel, cuadernos o un sistema viejo.</li><li>Negocios que quieren empezar sin instalar un servidor local.</li><li>Operaciones que necesitan consultar o trabajar también desde móvil.</li><li>Comercios con una o varias cajas que buscan una base simple para crecer.</li></ul></div>
  </div>
</section>
<section class="growth-section">
  <div class="container"><div class="growth-head"><span class="section-kicker">Web o Server</span><h2>Si necesitás infraestructura propia, hay otra edición.</h2><p>FLUS Web prioriza rapidez de puesta en marcha y acceso desde cualquier dispositivo. FLUS Server apunta a proyectos que requieren instalación propia y mayor adaptación.</p></div><div class="growth-comparison" role="region" aria-label="Comparación entre FLUS Web y FLUS Server" tabindex="0"><table><thead><tr><th scope="col">Necesidad</th><th scope="col">FLUS Web</th><th scope="col">FLUS Server</th></tr></thead><tbody><tr><td>Acceso por navegador / PWA</td><td>Sí</td><td>Según proyecto</td></tr><tr><td>Servidor propio en el negocio</td><td>No necesario</td><td>Sí / infraestructura del cliente</td></tr><tr><td>Puesta en marcha rápida</td><td>Prioridad</td><td>Proyecto personalizado</td></tr><tr><td>Adaptaciones e integraciones</td><td>Alcance Web</td><td>Según requerimiento</td></tr></tbody></table></div><div class="growth-hero__actions" style="margin-top:22px"><a class="btn btn-secondary" href="<?= e(site_url('flus-server.php')) ?>">Conocer FLUS Server</a></div></div>
</section>
<section class="growth-section growth-section--soft">
  <div class="container"><div class="growth-head"><span class="section-kicker">Preguntas frecuentes</span><h2>Antes de probar FLUS Web.</h2></div><div class="growth-faq"><details><summary>¿Puedo usarlo desde iPhone o Android?</summary><p>Sí. La interfaz Web está preparada para PC, tablet y teléfono mediante navegador o PWA.</p></details><details><summary>¿Puedo usar lector de códigos de barras?</summary><p>Sí. FLUS contempla operación con lector y funciones de cámara/código donde el módulo lo admite.</p></details><details><summary>¿La facturación electrónica está incluida?</summary><p>La integración fiscal/ARCA depende de la edición y de la implementación acordada. Se confirma antes de contratar para no prometer un alcance que no corresponda.</p></details></div></div>
</section>
<section class="growth-section"><div class="container"><div class="growth-cta"><div><h2>Probalo antes de decidir.</h2><p>Entrá a la demo o contanos cómo trabaja tu comercio y te decimos si FLUS Web es la edición adecuada.</p></div><div class="growth-cta__actions"><a class="btn btn-primary" href="<?= e((string)($site['demo_url'] ?? site_url('contacto.php'))) ?>" target="_blank" rel="noopener" data-track-event="cta_primary_click" data-track-label="FLUS Web CTA demo">Probar FLUS</a><a class="btn btn-secondary" href="<?= e(site_url('contacto.php')) ?>#formulario-contacto" data-track-event="click_contact">Consultar mi caso</a></div></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
