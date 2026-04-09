<?php
require_once __DIR__ . '/bootstrap.php';

if (!isset($pageTitle)) {
    $pageTitle = 'FLUS | Sistema de gestión comercial';
}

if (!isset($pageDescription)) {
    $pageDescription = 'FLUS es un sistema de gestión comercial para ventas, stock, clientes, caja y facturación.';
}

if (!isset($pageHeading)) {
    $pageHeading = $pageTitle;
}

$canonical = 'https://' . $site['domain'] . parse_url(site_url(basename($_SERVER['PHP_SELF'] ?? 'index.php')), PHP_URL_PATH);
if (is_active_page('index.php')) {
    $canonical = 'https://' . $site['domain'] . '/';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($pageDescription) ?>">
  <link rel="canonical" href="<?= e($canonical) ?>">
  <link rel="stylesheet" href="<?= e(asset_url('css/styles.css')) ?>">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= e($pageTitle) ?>">
  <meta property="og:description" content="<?= e($pageDescription) ?>">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:site_name" content="<?= e($site['name']) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($pageTitle) ?>">
  <meta name="twitter:description" content="<?= e($pageDescription) ?>">
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
      <a class="logo" href="<?= e(site_url()) ?>">
        <img
          src="<?= e(asset_url('img/logo1.png')) ?>"
          alt="FLUS"
          class="logo-mark"
        >
        <span class="logo-text">FLUS</span>
      </a>

      <nav class="nav" aria-label="Principal">
        <a href="<?= e(site_url()) ?>" class="<?= is_active_page('index.php') ? 'active' : '' ?>">Inicio</a>
        <a href="<?= e(site_url('sistema-de-gestion.php')) ?>" class="<?= is_active_page('sistema-de-gestion.php') ? 'active' : '' ?>">Sistema de gestión</a>
        <a href="<?= e(site_url('sistema-pos.php')) ?>" class="<?= is_active_page('sistema-pos.php') ? 'active' : '' ?>">Sistema POS</a>
        <a href="<?= e(site_url('control-de-stock.php')) ?>" class="<?= is_active_page('control-de-stock.php') ? 'active' : '' ?>">Stock</a>
        <a href="<?= e(site_url('facturacion.php')) ?>" class="<?= is_active_page('facturacion.php') ? 'active' : '' ?>">Facturación</a>
        <a href="<?= e(site_url('contacto.php')) ?>" class="<?= is_active_page('contacto.php') ? 'active' : '' ?>">Contacto</a>
      </nav>
    </div>
  </header>

  <main>
