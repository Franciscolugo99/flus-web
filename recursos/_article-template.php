<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/content-registry.php';
$article = flus_resource_article($articleSlug ?? '');
if ($article === null) {
    http_response_code(404);
    require __DIR__ . '/../404.php';
    exit;
}
$pageTitle = $article['title'] . ' | FLUS';
$pageDescription = $article['description'];
$pageCanonical = page_url('recursos/' . $article['slug'] . '/');
$pageBodyClass = 'page-resource-article';
$pageStyles = ['css/content-seo.css'];
$pageBreadcrumbs = [
    ['name' => 'Inicio', 'url' => page_url()],
    ['name' => 'Recursos', 'url' => page_url('recursos/')],
    ['name' => $article['short_title'], 'url' => $pageCanonical],
];
$pageSchemas = [[
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $article['title'],
    'description' => $article['description'],
    'image' => page_url($article['image']),
    'datePublished' => $article['published'],
    'dateModified' => $article['updated'],
    'author' => ['@type' => 'Organization', 'name' => 'FLUS', 'url' => page_url()],
    'publisher' => ['@type' => 'Organization', 'name' => 'FLUS', 'logo' => ['@type' => 'ImageObject', 'url' => page_url('assets/img/logo1.png')]],
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $pageCanonical],
]];
$allArticles = flus_resource_articles();
require __DIR__ . '/../includes/header.php';
?>
<div class="container seo-breadcrumbs" aria-label="Migas de pan"><a href="<?= e(site_url()) ?>">Inicio</a><span>›</span><a href="<?= e(site_url('recursos/')) ?>">Recursos</a><span>›</span><?= e($article['category']) ?></div>
<section class="article-hero">
  <div class="container article-hero__inner">
    <span class="article-eyebrow"><?= e($article['category']) ?></span>
    <h1><?= e($article['title']) ?></h1>
    <p class="article-deck"><?= e($article['description']) ?></p>
    <div class="article-meta"><span>Equipo FLUS</span><span>Actualizado <?= e(date('d/m/Y', strtotime($article['updated']))) ?></span><span><?= e($article['reading_time']) ?> de lectura</span></div>
  </div>
</section>
<div class="article-cover"><div class="article-cover__frame"><div class="article-cover__bar"><i></i><i></i><i></i><b>FLUS · <?= e($article['category']) ?></b></div><img src="<?= e(site_url($article['image'])) ?>" alt="<?= e($article['image_alt']) ?>" width="1608" height="978"></div></div>
<div class="article-layout">
  <article class="article-body">
    <?php require __DIR__ . '/_content/' . $article['slug'] . '.php'; ?>
  </article>
  <aside class="article-side"><div class="article-side__sticky">
    <div class="article-side__box"><strong>En esta guía</strong><?php foreach (($articleToc ?? []) as $anchor => $label): ?><a href="#<?= e($anchor) ?>"><?= e($label) ?></a><?php endforeach; ?></div>
    <div class="article-side__box"><strong>Verlo en FLUS</strong><p style="margin:0 0 10px;color:#766c7e;font-size:14px;line-height:1.5">Probá la operación real desde el navegador.</p><a class="btn btn-primary" href="<?= e((string)$site['demo_url']) ?>" target="_blank" rel="noopener" data-track-event="cta_primary_click" data-track-label="Artículo <?= e($article['slug']) ?>Probar FLUS</a></div>
  </div></aside>
</div>
<section class="resource-section resource-section--soft"><div class="container"><div class="resource-head"><h2>Seguí aprendiendo.</h2><p>Estas guías están conectadas para que puedas ordenar el comercio por etapas.</p></div><div class="related-grid">
<?php $shown=0; foreach ($allArticles as $slug => $related): if ($slug === $article['slug'] || $shown>=3) continue; $shown++; ?>
<a class="related-card" href="<?= e(site_url('recursos/'.$slug.'/')) ?>"><span><?= e($related['category']) ?></span><strong><?= e($related['short_title']) ?></strong><p><?= e($related['description']) ?></p></a>
<?php endforeach; ?>
</div></div></section>
<script>window.addEventListener('load',function(){if(window.FLUSTrack){window.FLUSTrack.track('view_resource_article',{slug:<?= json_encode($article['slug']) ?>,category:<?= json_encode($article['category']) ?>});}if(typeof gtag==='function'){gtag('event','view_resource_article',{content_slug:<?= json_encode($article['slug']) ?>,content_category:<?= json_encode($article['category']) ?>});}});</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
