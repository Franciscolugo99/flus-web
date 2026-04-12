<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Control de stock para comercios y pymes | FLUS';
$pageDescription = 'FLUS ayuda a controlar stock, productos, movimientos y disponibilidad dentro del flujo comercial del negocio, con menos incertidumbre y más trazabilidad.';
$pageBreadcrumbs = [
    ['name' => 'Inicio', 'url' => page_url()],
    ['name' => 'Control de stock', 'url' => page_url('control-de-stock.php')],
];
$pageSchemas = [
    [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'FLUS — Control de stock',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web',
        'description' => 'Control de stock para comercios y pymes: inventario en tiempo real, movimientos trazables y disponibilidad integrada al punto de venta.',
        'url' => page_url('control-de-stock.php'),
        'featureList' => 'Control de inventario, Movimientos de stock, Alertas de bajo stock, Trazabilidad, Integración con ventas',
    ],
];
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container page-hero-grid">
    <div>
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="<?= e(site_url()) ?>">Inicio</a>
        <span>/</span>
        <span aria-current="page">Control de stock</span>
      </nav>
      <span class="eyebrow">Inventario operativo</span>
      <h1>Más claridad sobre disponibilidad y movimientos para vender con menos dudas</h1>
      <p>
        FLUS ayuda a leer mejor lo que pasa con los productos dentro de la operación diaria:
        qué se vendió, qué se movió, qué queda disponible y dónde conviene revisar.
      </p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Solicitar demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-pos.php')) ?>">Ver sistema POS</a>
      </div>
    </div>

    <aside class="hero-panel">
      <h2>Qué debería aportar un buen control de stock</h2>
      <ul class="check-list">
        <li>Disponibilidad más confiable para vender.</li>
        <li>Movimientos con mejor contexto operativo.</li>
        <li>Menos incertidumbre al reponer.</li>
        <li>Más trazabilidad para revisar lo que pasó.</li>
      </ul>
    </aside>
  </div>
</section>

<section class="section">
  <div class="container">
    <span class="section-kicker">Problema habitual</span>
    <h2>Cuando el stock no es confiable, el negocio vende con inseguridad</h2>
    <div class="feature-grid">
      <article class="feature-card">
        <h3>Disponibilidad dudosa</h3>
        <p>La operación trabaja con preguntas básicas que deberían resolverse rápido.</p>
      </article>
      <article class="feature-card">
        <h3>Movimientos difíciles de seguir</h3>
        <p>Sin buen contexto, cuesta reconstruir qué pasó con un producto o por qué cambió el stock.</p>
      </article>
      <article class="feature-card">
        <h3>Reposición sin criterio suficiente</h3>
        <p>Si el dato no es confiable, comprar y ordenar stock se vuelve más reactivo.</p>
      </article>
      <article class="feature-card">
        <h3>Venta desconectada de la disponibilidad</h3>
        <p>El mostrador pierde seguridad cuando no conversa bien con la realidad del depósito o exhibición.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-dark">
  <div class="container split-grid">
    <div class="surface-card">
      <h3>Cómo suma FLUS</h3>
      <ul class="plain-list">
        <li>Más visibilidad sobre productos y movimientos.</li>
        <li>Mejor lectura de la disponibilidad real.</li>
        <li>Relación más clara entre venta y stock.</li>
        <li>Una base más ordenada para reponer y revisar.</li>
      </ul>
    </div>

    <div class="surface-card">
      <h3>Qué conviene revisar en una demo</h3>
      <p>
        El stock vale más cuando se lo mira dentro del circuito comercial y no como una lista suelta de productos.
      </p>
      <ul class="link-list">
        <li><a href="<?= e(site_url('sistema-pos.php')) ?>">Ver relación con el sistema POS</a></li>
        <li><a href="<?= e(site_url('facturacion.php')) ?>">Ver relación con facturación</a></li>
      </ul>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta-box">
      <h2>Si hoy el stock deja dudas, hay margen claro para trabajar con más criterio</h2>
      <p>
        FLUS busca que la disponibilidad y los movimientos formen parte de una operación más conectada y más revisable.
      </p>
      <div class="inline-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Pedir una demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-de-gestion.php')) ?>">Ver sistema completo</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
