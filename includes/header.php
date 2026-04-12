<?php
require_once __DIR__ . '/bootstrap.php';

if (!isset($pageTitle)) {
    $pageTitle = 'FLUS | Sistema de gestión comercial';
}

if (!isset($pageDescription)) {
    $pageDescription = 'FLUS es un sistema de gestión comercial para ventas, stock, caja, clientes y facturación.';
}

if (!isset($pageSchemas)) {
    $pageSchemas = [];
}

$currentFile = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$canonical = is_active_page('index.php')
    ? page_url()
    : page_url($currentFile);

$ogImage = page_url('assets/img/flus-caja-pos.png');
$stylesPath = __DIR__ . '/../assets/css/styles.css';
$stylesHref = asset_url('css/styles.css');
if (is_file($stylesPath)) {
    $stylesHref .= '?v=' . filemtime($stylesPath);
}

$baseSchemas = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $site['name'],
        'url' => page_url(),
        'logo' => page_url('assets/img/logo1.png'),
        'email' => $site['contact_email'],
        'telephone' => $site['contact_phone'],
    ],
    [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $site['name'],
        'url' => page_url(),
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => page_url() . '?s={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ],
];

if (isset($pageBreadcrumbs) && is_array($pageBreadcrumbs) && $pageBreadcrumbs !== []) {
    $baseSchemas[] = breadcrumb_schema($pageBreadcrumbs);
}

$schemas = array_merge($baseSchemas, $pageSchemas);
$bodyClass = 'page-' . preg_replace('/[^a-z0-9\-]+/i', '-', pathinfo($currentFile, PATHINFO_FILENAME));
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($pageDescription) ?>">
  <meta name="robots" content="index,follow">
  <meta name="theme-color" content="#0b141a">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <link rel="icon" type="image/png" sizes="96x96" href="<?= e(asset_url('img/favicon.png')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,700;0,9..40,800;1,9..40,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e($stylesHref) ?>">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= e($pageTitle) ?>">
  <meta property="og:description" content="<?= e($pageDescription) ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:site_name" content="<?= e($site['name']) ?>">
  <meta property="og:image" content="<?= e($ogImage) ?>">
  <meta property="og:image:width" content="1608">
  <meta property="og:image:height" content="978">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($pageTitle) ?>">
  <meta name="twitter:description" content="<?= e($pageDescription) ?>">
  <meta name="twitter:image" content="<?= e($ogImage) ?>">
<?php if (is_active_page('index.php')): ?>
  <link rel="preload" as="image" href="<?= e(asset_url('img/flus-caja-pos.png')) ?>" fetchpriority="high">
<?php endif; ?>
<?php foreach ($schemas as $schema): ?>
  <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?></script>
<?php endforeach; ?>
</head>
<body class="<?= e($bodyClass) ?>">
  <header class="site-header" id="site-header">
    <div class="container header-inner">
      <a class="brand" href="<?= e(site_url()) ?>" aria-label="Ir al inicio de FLUS">
        <span class="brand-mark">
          <img src="<?= e(asset_url('img/flus-mark.png')) ?>" alt="" width="36" height="40">
        </span>
        <span class="brand-copy">
          <span class="brand-name">FLUS</span>
        </span>
      </a>

      <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="Abrir menú">
        <span></span>
        <span></span>
        <span></span>
      </button>

      <div class="nav-panel" id="site-nav">
        <nav class="nav" aria-label="Principal">
          <a href="<?= e(site_url()) ?>" class="<?= is_active_page('index.php') ? 'active' : '' ?>" <?= is_active_page('index.php') ? 'aria-current="page"' : '' ?>>Inicio</a>
          <a href="<?= e(site_url('sistema-de-gestion.php')) ?>" class="<?= is_active_page('sistema-de-gestion.php') ? 'active' : '' ?>" <?= is_active_page('sistema-de-gestion.php') ? 'aria-current="page"' : '' ?>>Sistema</a>
          <a href="<?= e(site_url('sistema-pos.php')) ?>" class="<?= is_active_page('sistema-pos.php') ? 'active' : '' ?>" <?= is_active_page('sistema-pos.php') ? 'aria-current="page"' : '' ?>>POS</a>
          <a href="<?= e(site_url('control-de-stock.php')) ?>" class="<?= is_active_page('control-de-stock.php') ? 'active' : '' ?>" <?= is_active_page('control-de-stock.php') ? 'aria-current="page"' : '' ?>>Stock</a>
          <a href="<?= e(site_url('facturacion.php')) ?>" class="<?= is_active_page('facturacion.php') ? 'active' : '' ?>" <?= is_active_page('facturacion.php') ? 'aria-current="page"' : '' ?>>Facturación</a>
          <a href="<?= e(site_url('contacto.php')) ?>" class="<?= is_active_page('contacto.php') ? 'active' : '' ?>" <?= is_active_page('contacto.php') ? 'aria-current="page"' : '' ?>>Contacto</a>
          <a href="<?= e(site_url('contacto.php')) ?>#formulario-contacto" class="nav-cta <?= is_active_page('contacto.php') ? 'active' : '' ?>" <?= is_active_page('contacto.php') ? 'aria-current="page"' : '' ?>>Demo</a>
        </nav>
      </div>
    </div>
  </header>

  <main>
