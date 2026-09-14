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
$canonical = isset($pageCanonical) && is_string($pageCanonical) && $pageCanonical !== ''
    ? $pageCanonical
    : (is_active_page('index.php') ? page_url() : page_url($currentFile));
$pageRobots = isset($pageRobots) && is_string($pageRobots) && $pageRobots !== '' ? $pageRobots : 'index,follow';

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
    ],
];

if (isset($pageBreadcrumbs) && is_array($pageBreadcrumbs) && $pageBreadcrumbs !== []) {
    $baseSchemas[] = breadcrumb_schema($pageBreadcrumbs);
}

$schemas = array_merge($baseSchemas, $pageSchemas);
$bodyClass = isset($pageBodyClass) && is_string($pageBodyClass) && $pageBodyClass !== ''
    ? $pageBodyClass
    : 'page-' . preg_replace('/[^a-z0-9\-]+/i', '-', pathinfo($currentFile, PATHINFO_FILENAME));
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($pageDescription) ?>">
  <meta name="robots" content="<?= e($pageRobots) ?>">
  <meta name="theme-color" content="#0b0715">
<?php if (trim((string)($site['google_site_verification'] ?? '')) !== ''): ?>
  <meta name="google-site-verification" content="<?= e((string)$site['google_site_verification']) ?>">
<?php endif; ?>
  <link rel="canonical" href="<?= e($canonical) ?>">
  <link rel="icon" type="image/png" href="<?= e(site_url('favicon.png')) ?>" sizes="96x96">
  <link rel="icon" type="image/png" sizes="96x96" href="<?= e(site_url('favicon-96x96.png')) ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= e(site_url('apple-touch-icon.png')) ?>">
  <link rel="manifest" href="<?= e(site_url('site.webmanifest')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,600;12..96,700;12..96,800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e($stylesHref) ?>">
<?php if (is_active_page('index.php')):
  $homeStylesPath = __DIR__ . '/../assets/css/home-2026.css';
  $homeStylesHref = asset_url('css/home-2026.css');
  if (is_file($homeStylesPath)) { $homeStylesHref .= '?v=' . filemtime($homeStylesPath); }
?>
  <link rel="stylesheet" href="<?= e($homeStylesHref) ?>">
<?php endif; ?>
<?php if (isset($pageStyles) && is_array($pageStyles)): ?>
<?php foreach ($pageStyles as $pageStyle):
  $pageStyle = ltrim((string)$pageStyle, '/');
  $pageStylePath = __DIR__ . '/../assets/' . $pageStyle;
  if (!is_file($pageStylePath)) { continue; }
  $pageStyleHref = asset_url($pageStyle) . '?v=' . filemtime($pageStylePath);
?>
  <link rel="stylesheet" href="<?= e($pageStyleHref) ?>">
<?php endforeach; ?>
<?php endif; ?>
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
  <link rel="preload" as="image" href="<?= e(asset_url('img/flus-caja-pos.webp')) ?>" fetchpriority="high">
<?php endif; ?>
<?php foreach ($schemas as $schema): ?>
  <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?></script>
<?php endforeach; ?>
<?php if (preg_match('/^G-[A-Z0-9]+$/', trim((string)($site['ga4_measurement_id'] ?? '')))): ?>
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e((string)$site['ga4_measurement_id']) ?>"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', <?= json_encode((string)$site['ga4_measurement_id']) ?>, {anonymize_ip: true});
  </script>
<?php endif; ?>
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
          <?php
          $solutionPages = ['flus-web.php', 'flus-server.php', 'sistema-de-gestion.php', 'sistema-pos.php', 'control-de-stock.php', 'facturacion.php'];
          $solutionActive = false;
          foreach ($solutionPages as $sp) {
              if (is_active_page($sp)) { $solutionActive = true; break; }
          }
          ?>
          <div class="nav-dropdown" data-nav-dropdown>
            <button class="nav-dropdown__trigger <?= (is_active_page('flus-web.php') || is_active_page('flus-server.php')) ? 'active' : '' ?>" type="button" aria-expanded="false" aria-haspopup="true">
              Versiones
              <svg class="nav-dropdown__arrow" viewBox="0 0 12 12" aria-hidden="true"><path d="M3 4.5 6 7.5 9 4.5"/></svg>
            </button>
            <div class="nav-dropdown__menu">
              <a href="<?= e(site_url('flus-web.php')) ?>" class="<?= is_active_page('flus-web.php') ? 'active' : '' ?>">
                <strong>FLUS Web</strong>
                <span>Navegador, PWA, PC, tablet y teléfono</span>
              </a>
              <a href="<?= e(site_url('flus-server.php')) ?>" class="<?= is_active_page('flus-server.php') ? 'active' : '' ?>">
                <strong>FLUS Server</strong>
                <span>Infraestructura propia y proyecto a medida</span>
              </a>
            </div>
          </div>
          <div class="nav-dropdown" data-nav-dropdown>
            <button class="nav-dropdown__trigger <?= $solutionActive ? 'active' : '' ?>" type="button" aria-expanded="false" aria-haspopup="true">
              Funciones
              <svg class="nav-dropdown__arrow" viewBox="0 0 12 12" aria-hidden="true"><path d="M3 4.5 6 7.5 9 4.5"/></svg>
            </button>
            <div class="nav-dropdown__menu">
              <a href="<?= e(site_url('sistema-de-gestion.php')) ?>" class="<?= is_active_page('sistema-de-gestion.php') ? 'active' : '' ?>">
                <strong>Sistema de gestión</strong>
                <span>Ventas, caja, stock, clientes y facturación</span>
              </a>
              <a href="<?= e(site_url('sistema-pos.php')) ?>" class="<?= is_active_page('sistema-pos.php') ? 'active' : '' ?>">
                <strong>Sistema POS</strong>
                <span>Punto de venta ágil con control de caja</span>
              </a>
              <a href="<?= e(site_url('control-de-stock.php')) ?>" class="<?= is_active_page('control-de-stock.php') ? 'active' : '' ?>">
                <strong>Control de stock</strong>
                <span>Inventario, movimientos y disponibilidad</span>
              </a>
              <a href="<?= e(site_url('facturacion.php')) ?>" class="<?= is_active_page('facturacion.php') ? 'active' : '' ?>">
                <strong>Facturación</strong>
                <span>Comprobantes integrados al circuito comercial</span>
              </a>
            </div>
          </div>
          <a href="<?= e(site_url('recursos/')) ?>" class="<?= is_active_section('recursos') ? 'active' : '' ?>" <?= is_active_section('recursos') ? 'aria-current="page"' : '' ?>>Recursos</a>
          <a href="<?= e(site_url()) ?>#precios">Precios</a>
          <a href="<?= e(site_url('contacto.php')) ?>" class="<?= is_active_page('contacto.php') ? 'active' : '' ?>" <?= is_active_page('contacto.php') ? 'aria-current="page"' : '' ?> data-track-event="click_contact" data-track-label="Header contacto">Contacto</a>
          <a href="<?= e((string)($site['demo_url'] ?? site_url('contacto.php') . '#formulario-contacto')) ?>" class="nav-cta" target="_blank" rel="noopener" data-track-event="click_demo" data-track-label="Header demo">Ver demo</a>
        </nav>
      </div>
    </div>
  </header>

  <main>
