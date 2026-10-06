<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Sistema de gestión con servidor propio | FLUS Server';
$pageDescription = 'FLUS Server se instala en los equipos de tu negocio y se prepara según cómo trabajás. Consultanos por una propuesta a medida.';
$pageStyles = ['css/seo-growth.css'];
$pageBreadcrumbs = [
    ['name' => 'Inicio', 'url' => page_url()],
    ['name' => 'FLUS Server', 'url' => page_url('flus-server.php')],
];
$pageSchemas = [[
    '@context' => 'https://schema.org',
    '@type' => 'SoftwareApplication',
    'name' => 'FLUS Server',
    'applicationCategory' => 'BusinessApplication',
    'operatingSystem' => 'Windows y equipos del negocio',
    'description' => 'FLUS instalado en los equipos del negocio, con varias cajas y una configuración a medida.',
    'url' => page_url('flus-server.php'),
    'featureList' => 'Instalación en los equipos del negocio, varias cajas, traspaso de datos, ventas y stock según lo acordado',
]];
require __DIR__ . '/includes/header.php';
?>
<section class="growth-hero">
  <div class="container growth-hero__grid">
    <div>
      <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(site_url()) ?>">Inicio</a><span>/</span><span aria-current="page">FLUS Server</span></nav>
      <span class="growth-kicker">En los equipos de tu negocio · a medida</span>
      <h1>FLUS instalado en tu negocio y adaptado a tu forma de trabajar.</h1>
      <p class="growth-hero__lead">Si necesitás usar FLUS en los equipos de tu negocio, preparar varias cajas o pasar datos de otro sistema, revisamos tu caso y armamos una propuesta con vos.</p>
      <div class="growth-hero__actions"><a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>#formulario-contacto" data-track-event="click_contact" data-track-label="FLUS Server hero cotizar">Contar cómo es mi negocio</a><a class="btn btn-secondary" href="<?= e(site_url('flus-web.php')) ?>">Comparar con FLUS Web</a></div>
      <div class="growth-proof"><span>Equipos de tu negocio</span><span>Varias computadoras</span><span>Te ayudamos a empezar</span></div>
    </div>
    <div class="growth-showcase"><div class="growth-window"><div class="growth-window__bar"><i></i><i></i><i></i><b>FLUS Server · Gestión comercial</b></div><picture><source srcset="<?= e(asset_url('img/Dashboard.webp')) ?>" type="image/webp"><img src="<?= e(asset_url('img/Dashboard.png')) ?>" alt="Resumen de ventas de FLUS Server" width="1536" height="1024" fetchpriority="high" decoding="async"></picture></div><div class="growth-float"><strong>El precio se define por proyecto</strong><span>Depende de las cajas, los equipos y las adaptaciones que necesites.</span></div></div>
  </div>
</section>
<section class="growth-section">
  <div class="container"><div class="growth-head"><span class="section-kicker">Cuándo elegir Server</span><h2>Una opción para negocios que necesitan una forma de trabajo a medida.</h2><p>Contanos cómo trabajás, cuántas cajas tenés y qué necesitás resolver. Con esa información preparamos la instalación y el presupuesto.</p></div><div class="growth-grid"><article class="growth-card"><span class="growth-card__num">01</span><h3>Equipos de tu negocio</h3><p>Instalamos el sistema en los equipos acordados para tu negocio.</p></article><article class="growth-card"><span class="growth-card__num">02</span><h3>Varias cajas</h3><p>Preparamos las cajas y computadoras que necesitás para trabajar.</p></article><article class="growth-card"><span class="growth-card__num">03</span><h3>Pasar tus datos</h3><p>Revisamos qué información podés traer de tu sistema actual y acordamos cómo cargarla.</p></article><article class="growth-card"><span class="growth-card__num">04</span><h3>Gestión comercial</h3><p>Ventas, stock, caja, compras y clientes según las funciones acordadas para tu negocio.</p></article><article class="growth-card"><span class="growth-card__num">05</span><h3>Conectar otras herramientas</h3><p>Si necesitás facturación electrónica u otras herramientas, confirmamos qué se puede conectar antes de contratar.</p></article><article class="growth-card"><span class="growth-card__num">06</span><h3>Soporte del proyecto</h3><p>Te ayudamos a empezar, capacitamos a tu equipo y acordamos el soporte que necesitás.</p></article></div></div>
</section>
<section class="growth-section growth-section--soft"><div class="container growth-split"><div class="growth-panel"><span class="section-kicker">Comercial</span><h2 style="font-size:34px;margin-top:14px">Presupuesto a medida</h2><p>El presupuesto depende de cuántas cajas tenés, los equipos disponibles y las adaptaciones que necesitás. Lo acordamos antes de empezar.</p><div class="growth-hero__actions"><a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>#formulario-contacto" data-track-event="click_contact" data-track-label="FLUS Server propuesta">Solicitar cotización</a></div></div><div class="growth-panel"><h3>Información útil para cotizar</h3><ul class="growth-list"><li>Cantidad de locales, cajas y computadoras.</li><li>Qué computadoras y equipos tenés disponibles.</li><li>Qué sistema usás hoy y cuántos productos manejás.</li><li>Si necesitás facturación electrónica o conectar otras herramientas.</li><li>Qué procesos necesitan resolver primero.</li></ul></div></div></section>
<section class="growth-section"><div class="container"><div class="growth-head"><span class="section-kicker">Elegir cómo usar FLUS</span><h2>Elegí la forma de usar FLUS que te resulte más cómoda.</h2><p>Si preferís entrar por internet y que nosotros mantengamos el sistema, podés empezar con FLUS Web.</p></div><div class="growth-comparison" role="region" aria-label="Comparación entre FLUS Server y FLUS Web" tabindex="0"><table><thead><tr><th scope="col">Necesidad</th><th scope="col">FLUS Web</th><th scope="col">FLUS Server</th></tr></thead><tbody><tr><td>Nos encargamos de mantenerlo funcionando</td><td>Sí</td><td>Se acuerda para tu instalación</td></tr><tr><td>Cómo empezás</td><td>Con ayuda de FLUS</td><td>Personalizado</td></tr><tr><td>Varias cajas y una forma de trabajo a medida</td><td>Se confirma con vos</td><td>Se prepara a medida</td></tr><tr><td>Precio público de entrada</td><td><?= e(flus_plan_price_text('inicio')) ?><?php if (flus_plan_price('inicio') !== ''): ?> /mes<?php endif; ?></td><td>Cotización</td></tr></tbody></table></div></div></section>
<section class="growth-section"><div class="container"><div class="growth-cta"><div><h2>Contanos cómo es tu negocio.</h2><p>Con unos pocos datos te ayudamos a elegir entre usar FLUS por internet o instalarlo en tu negocio.</p></div><div class="growth-cta__actions"><a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>#formulario-contacto" data-track-event="click_contact">Solicitar cotización</a><a class="btn btn-secondary" href="<?= e(site_url('flus-web.php')) ?>">Ver FLUS Web</a></div></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
