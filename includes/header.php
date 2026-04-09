<?php
require_once __DIR__ . '/bootstrap.php';

if (!isset($pageTitle)) {
    $pageTitle = 'FLUS | Sistema de gestion comercial';
}

if (!isset($pageDescription)) {
    $pageDescription = 'FLUS es un sistema de gestion comercial para ventas, stock, clientes, caja y facturacion.';
}

$canonical = 'https://' . $site['domain'] . parse_url(site_url(basename($_SERVER['PHP_SELF'] ?? 'index.php')), PHP_URL_PATH);
if (is_active_page('index.php')) {
    $canonical = 'https://' . $site['domain'] . '/';
}

$ogImage = 'https://' . $site['domain'] . site_url('assets/img/logo1.png');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($pageDescription) ?>">
  <meta name="robots" content="index,follow">
  <meta name="theme-color" content="#081218">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <link rel="stylesheet" href="<?= e(asset_url('css/styles.css')) ?>">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= e($pageTitle) ?>">
  <meta property="og:description" content="<?= e($pageDescription) ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:site_name" content="<?= e($site['name']) ?>">
  <meta property="og:image" content="<?= e($ogImage) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($pageTitle) ?>">
  <meta name="twitter:description" content="<?= e($pageDescription) ?>">
  <meta name="twitter:image" content="<?= e($ogImage) ?>">
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "SoftwareApplication",
    "name": <?= json_encode($site['name']) ?>,
    "applicationCategory": "BusinessApplication",
    "operatingSystem": "Web",
    "description": <?= json_encode($pageDescription, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
    "url": <?= json_encode('https://' . $site['domain'] . '/', JSON_UNESCAPED_SLASHES) ?>
  }
  </script>
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <a class="logo" href="<?= e(site_url()) ?>" aria-label="Ir al inicio de FLUS">
        <span class="logo-mark" aria-hidden="true">
          <img src="<?= e(asset_url('img/logo1.png')) ?>" alt="" class="logo-mark-image">
        </span>
        <span class="logo-copy">
          <span class="logo-text">FLUS</span>
          <span class="logo-subtext">Ventas, stock, caja y facturaci&oacute;n conectadas</span>
        </span>
      </a>

      <nav class="nav" aria-label="Principal">
        <a href="<?= e(site_url()) ?>" class="<?= is_active_page('index.php') ? 'active' : '' ?>" <?= is_active_page('index.php') ? 'aria-current="page"' : '' ?>>Inicio</a>
        <a href="<?= e(site_url('sistema-de-gestion.php')) ?>" class="<?= is_active_page('sistema-de-gestion.php') ? 'active' : '' ?>" <?= is_active_page('sistema-de-gestion.php') ? 'aria-current="page"' : '' ?>>Sistema de gesti&oacute;n</a>
        <a href="<?= e(site_url('sistema-pos.php')) ?>" class="<?= is_active_page('sistema-pos.php') ? 'active' : '' ?>" <?= is_active_page('sistema-pos.php') ? 'aria-current="page"' : '' ?>>Sistema POS</a>
        <a href="<?= e(site_url('control-de-stock.php')) ?>" class="<?= is_active_page('control-de-stock.php') ? 'active' : '' ?>" <?= is_active_page('control-de-stock.php') ? 'aria-current="page"' : '' ?>>Stock</a>
        <a href="<?= e(site_url('facturacion.php')) ?>" class="<?= is_active_page('facturacion.php') ? 'active' : '' ?>" <?= is_active_page('facturacion.php') ? 'aria-current="page"' : '' ?>>Facturaci&oacute;n</a>
        <a href="<?= e(site_url('contacto.php')) ?>" class="nav-cta <?= is_active_page('contacto.php') ? 'active' : '' ?>" <?= is_active_page('contacto.php') ? 'aria-current="page"' : '' ?>>Solicitar demo</a>
      </nav>
    </div>
  </header>

  <main>
