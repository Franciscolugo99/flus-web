<?php
if (!isset($pageTitle)) {
    $pageTitle = 'FLUS | Sistema de gestión comercial';
}
if (!isset($pageDescription)) {
    $pageDescription = 'FLUS es un sistema de gestión comercial para ventas, stock, clientes, caja y facturación.';
}
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
  <link rel="stylesheet" href="/flus-web/assets/css/styles.css">
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <a class="logo" href="/flus-web/">FLUS</a>

      <nav class="nav">
        <a href="/flus-web/" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Inicio</a>
        <a href="/flus-web/sistema-de-gestion.php" class="<?= $currentPage === 'sistema-de-gestion.php' ? 'active' : '' ?>">Sistema de gestión</a>
        <a href="/flus-web/sistema-pos.php" class="<?= $currentPage === 'sistema-pos.php' ? 'active' : '' ?>">Sistema POS</a>
        <a href="/flus-web/contacto.php" class="<?= $currentPage === 'contacto.php' ? 'active' : '' ?>">Contacto</a>
      </nav>
    </div>
  </header>

  <main>
