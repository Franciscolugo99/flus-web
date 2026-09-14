<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Sistema de gestión con servidor propio | FLUS Server';
$pageDescription = 'FLUS Server es la edición para comercios y empresas que necesitan infraestructura propia, múltiples puestos, migraciones e implementación personalizada.';
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
    'operatingSystem' => 'Windows, Server, infraestructura del cliente',
    'description' => 'Edición de FLUS para instalaciones en infraestructura propia, múltiples puestos y proyectos con configuración personalizada.',
    'url' => page_url('flus-server.php'),
    'featureList' => 'Instalación local o en servidor, múltiples puestos, migración, gestión comercial, configuración e integraciones según proyecto',
]];
require __DIR__ . '/includes/header.php';
?>
<section class="growth-hero">
  <div class="container growth-hero__grid">
    <div>
      <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= e(site_url()) ?>">Inicio</a><span>/</span><span aria-current="page">FLUS Server</span></nav>
      <span class="growth-kicker">Server / local · proyecto personalizado</span>
      <h1>Sistema de gestión en infraestructura propia, adaptado a la operación.</h1>
      <p class="growth-hero__lead">FLUS Server está orientado a instalaciones que necesitan servidor propio, varios puestos de trabajo, migración de información o un alcance que debe definirse proyecto por proyecto.</p>
      <div class="growth-hero__actions"><a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>#formulario-contacto" data-track-event="click_contact" data-track-label="FLUS Server hero cotizar">Contar mi escenario</a><a class="btn btn-secondary" href="<?= e(site_url('flus-web.php')) ?>">Comparar con FLUS Web</a></div>
      <div class="growth-proof"><span>Infraestructura propia</span><span>Múltiples puestos</span><span>Implementación acompañada</span></div>
    </div>
    <div class="growth-showcase"><div class="growth-window"><div class="growth-window__bar"><i></i><i></i><i></i><b>FLUS Server · Gestión comercial</b></div><picture><source srcset="<?= e(asset_url('img/Dashboard.webp')) ?>" type="image/webp"><img src="<?= e(asset_url('img/Dashboard.png')) ?>" alt="Dashboard de gestión de FLUS Server" width="1608" height="978" fetchpriority="high"></picture></div><div class="growth-float"><strong>El precio se define por proyecto</strong><span>Puestos, infraestructura, migración, configuración e integraciones cambian el alcance.</span></div></div>
  </div>
</section>
<section class="growth-section">
  <div class="container"><div class="growth-head"><span class="section-kicker">Cuándo elegir Server</span><h2>Más control cuando la operación necesita algo más que una implementación estándar.</h2><p>No publicamos un precio genérico porque dos instalaciones Server pueden ser muy diferentes. Primero relevamos la operación y después proponemos alcance y valor.</p></div><div class="growth-grid"><article class="growth-card"><span class="growth-card__num">01</span><h3>Infraestructura propia</h3><p>Instalación local o en servidor del cliente según la arquitectura acordada.</p></article><article class="growth-card"><span class="growth-card__num">02</span><h3>Varios puestos</h3><p>Configuración de cajas y puestos de trabajo acorde a la operación real.</p></article><article class="growth-card"><span class="growth-card__num">03</span><h3>Migración</h3><p>Evaluación del origen de datos y planificación de la puesta en marcha cuando hay información previa.</p></article><article class="growth-card"><span class="growth-card__num">04</span><h3>Gestión comercial</h3><p>Ventas, stock, caja, compras, clientes y circuitos administrativos dentro del alcance acordado.</p></article><article class="growth-card"><span class="growth-card__num">05</span><h3>Integraciones</h3><p>Relevamiento de necesidades fiscales o de terceros antes de comprometer una integración.</p></article><article class="growth-card"><span class="growth-card__num">06</span><h3>Soporte del proyecto</h3><p>Puesta en marcha, capacitación y soporte adaptados al tamaño y criticidad de la instalación.</p></article></div></div>
</section>
<section class="growth-section growth-section--soft"><div class="container growth-split"><div class="growth-panel"><span class="section-kicker">Comercial</span><h3 style="font-size:34px;margin-top:14px">Cotización personalizada</h3><p>El valor se define luego de relevar puestos, infraestructura, migración, módulos e integraciones. Evitamos un precio publicado que no represente la instalación real.</p><div class="growth-hero__actions"><a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>#formulario-contacto" data-track-event="click_contact" data-track-label="FLUS Server propuesta">Solicitar cotización</a></div></div><div class="growth-panel"><h3>Información útil para cotizar</h3><ul class="growth-list"><li>Cantidad de locales, cajas y puestos.</li><li>Si ya existe servidor o infraestructura disponible.</li><li>Sistema actual y volumen aproximado de productos/datos.</li><li>Necesidad de facturación electrónica o integraciones.</li><li>Qué procesos necesitan resolver primero.</li></ul></div></div></section>
<section class="growth-section"><div class="container"><div class="growth-head"><span class="section-kicker">Elegir edición</span><h2>Server no reemplaza a Web: resuelve otra necesidad.</h2><p>Si tu prioridad es empezar rápido, trabajar desde navegador y no mantener infraestructura propia, probablemente FLUS Web sea mejor punto de entrada.</p></div><div class="growth-comparison"><table><thead><tr><th>Necesidad</th><th>FLUS Web</th><th>FLUS Server</th></tr></thead><tbody><tr><td>Infraestructura administrada</td><td>Sí</td><td>No / propia</td></tr><tr><td>Proyecto de implementación</td><td>Más estandarizado</td><td>Personalizado</td></tr><tr><td>Múltiples puestos y requerimientos específicos</td><td>Según alcance Web</td><td>Principal escenario</td></tr><tr><td>Precio público de entrada</td><td>Desde <?= e((string)($site['commercial']['web']['monthly_price'] ?? '$45.000')) ?>/mes</td><td>Cotización</td></tr></tbody></table></div></div></section>
<section class="growth-section"><div class="container"><div class="growth-cta"><div><h2>Contanos cómo está armada tu operación.</h2><p>Con unos pocos datos podemos decirte si necesitás Server o si FLUS Web te resuelve con una implementación más simple.</p></div><div class="growth-cta__actions"><a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>#formulario-contacto" data-track-event="click_contact">Solicitar cotización</a><a class="btn btn-secondary" href="<?= e(site_url('flus-web.php')) ?>">Ver FLUS Web</a></div></div></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
