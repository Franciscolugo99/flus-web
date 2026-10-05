<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/content-registry.php';
$pageTitle = 'Guías para comercios: caja, stock y gestión | FLUS';
$pageDescription = 'Recursos prácticos para ordenar caja, ventas, stock e inventario. Guías de FLUS basadas en problemas reales de comercios.';
$pageCanonical = page_url('recursos/');
$pageBodyClass = 'page-recursos';
$pageStyles = ['css/seo-growth.css', 'css/content-seo.css'];
$pageBreadcrumbs = [
    ['name' => 'Inicio', 'url' => page_url()],
    ['name' => 'Recursos', 'url' => $pageCanonical],
];
$articles = flus_resource_articles();
require __DIR__ . '/../includes/header.php';
?>
<section class="resource-hero">
  <div class="container resource-hero__grid">
    <div>
      <span class="resource-eyebrow">Centro de Recursos FLUS</span>
      <h1>Guías prácticas para administrar mejor un comercio.</h1>
      <p>Caja, stock, ventas e inventario explicados sin vueltas. Contenido pensado para resolver problemas reales antes de elegir una herramienta.</p>
    </div>
    <aside class="resource-hero__note">
      <strong>Primero el problema. Después el software.</strong>
      <p>Estas guías están hechas para que puedas aplicar mejoras incluso si todavía trabajás con planillas. Cuando una tarea ya no escala, te mostramos cómo puede automatizarla FLUS.</p>
    </aside>
  </div>
</section>

<section class="resource-section" aria-labelledby="guias-destacadas">
  <div class="container">
    <div class="resource-head">
      <h2 id="guias-destacadas">Empezá por estas guías.</h2>
      <p>Cuatro temas que aparecen todos los días en comercios: controlar stock, cerrar caja, saber cuándo dejar Excel y elegir un sistema para kioscos.</p>
    </div>
    <div class="resource-grid">
      <?php foreach ($articles as $slug => $article): ?>
        <article class="resource-card">
          <a class="resource-card__media" href="<?= e(site_url('recursos/' . $slug . '/')) ?>" aria-label="Leer <?= e($article['short_title']) ?>">
            <img src="<?= e(site_url($article['image'])) ?>" alt="<?= e($article['image_alt']) ?>" loading="lazy" width="804" height="489">
          </a>
          <div class="resource-card__body">
            <div class="resource-card__meta"><span class="resource-card__category"><?= e($article['category']) ?></span><span>·</span><span><?= e($article['reading_time']) ?></span></div>
            <h2><a href="<?= e(site_url('recursos/' . $slug . '/')) ?>"><?= e($article['short_title']) ?></a></h2>
            <p><?= e($article['description']) ?></p>
            <a class="resource-card__link" href="<?= e(site_url('recursos/' . $slug . '/')) ?>" data-track-event="click_resource" data-track-label="<?= e($slug) ?>">Leer guía</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="resource-section resource-section--soft">
  <div class="container">
    <div class="resource-head"><h2>Un tema, un problema concreto.</h2><p>La biblioteca va a crecer por grupos para que cada artículo refuerce a los demás y a las páginas comerciales relacionadas.</p></div>
    <div class="resource-categories">
      <div class="resource-category"><span>Caja y ventas</span><strong>Operación diaria</strong><p>Apertura, cobro, medios de pago, arqueos y cierres.</p></div>
      <div class="resource-category"><span>Stock e inventario</span><strong>Control real</strong><p>Entradas, salidas, ajustes, mínimos y conteos.</p></div>
      <div class="resource-category"><span>Gestión</span><strong>Menos planillas</strong><p>Procesos, indicadores y decisiones para el dueño.</p></div>
      <div class="resource-category"><span>Por rubro</span><strong>Casos específicos</strong><p>Kioscos, almacenes, dietéticas, ferreterías y más.</p></div>
    </div>
  </div>
</section>

<section class="resource-section">
  <div class="container">
    <div class="growth-cta">
      <div><h2>¿Querés ver estos procesos funcionando?</h2><p>FLUS Web reúne caja, ventas, productos y stock en una misma operación y podés probarlo desde el navegador.</p></div>
      <div class="growth-cta__actions"><a class="btn btn-primary" href="<?= e((string)$site['demo_url']) ?>" target="_blank" rel="noopener" data-track-event="cta_primary_click" data-track-label="Recursos CTA demo">Probar FLUS</a><a class="btn btn-secondary" href="<?= e(site_url('contacto.php')) ?>#formulario-contacto" data-track-event="click_contact" data-track-label="Recursos CTA contacto">Contarnos tu caso</a></div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
