<?php
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Sistema POS para comercios con caja y stock integrado | FLUS';
$pageDescription = 'FLUS funciona como sistema POS para comercios que necesitan vender, cobrar, revisar caja y operar con stock dentro de una misma lógica comercial.';
$pageBreadcrumbs = [
    ['name' => 'Inicio', 'url' => page_url()],
    ['name' => 'Sistema POS', 'url' => page_url('sistema-pos.php')],
];
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <div class="container page-hero-grid">
    <div>
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="<?= e(site_url()) ?>">Inicio</a>
        <span>/</span>
        <span aria-current="page">Sistema POS</span>
      </nav>
      <span class="eyebrow">Sistema POS</span>
      <h1>Un sistema POS para vender y cobrar con más control, no solo más rápido</h1>
      <p>
        FLUS ayuda a trabajar el mostrador con agilidad, pero también con mejor contexto sobre caja,
        medios de pago, stock y continuidad comercial.
      </p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Solicitar demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('sistema-de-gestion.php')) ?>">Ver sistema completo</a>
      </div>
    </div>

    <aside class="hero-panel">
      <h2>Qué debería aportar un POS serio</h2>
      <ul class="check-list">
        <li>Más agilidad en caja sin perder control.</li>
        <li>Claridad sobre medios de pago y movimientos.</li>
        <li>Relación directa con stock y seguimiento.</li>
        <li>Trazabilidad sobre cada operación.</li>
      </ul>
    </aside>
  </div>
</section>

<section class="section">
  <div class="container">
    <span class="section-kicker">Uso real en mostrador</span>
    <h2>El problema aparece cuando el POS resuelve el cobro y deja todo lo demás afuera</h2>
    <div class="feature-grid">
      <article class="feature-card">
        <h3>Cobro sin contexto</h3>
        <p>La venta se completa, pero después cuesta seguir stock, cliente o comprobante.</p>
      </article>
      <article class="feature-card">
        <h3>Caja reactiva</h3>
        <p>Si el control diario llega tarde, los desvíos se entienden cuando el problema ya pasó.</p>
      </article>
      <article class="feature-card">
        <h3>Medios de pago poco claros</h3>
        <p>Efectivo, transferencias y otros cobros necesitan una base más prolija para revisar.</p>
      </article>
      <article class="feature-card">
        <h3>Venta desconectada del stock</h3>
        <p>Cuando el mostrador no conversa bien con disponibilidad, el negocio trabaja con fricción.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section-dark">
  <div class="container split-grid">
    <div class="surface-card">
      <h3>Qué valor aporta FLUS como POS</h3>
      <ul class="plain-list">
        <li>Venta más ágil y mejor registrada.</li>
        <li>Caja diaria con más orden y visibilidad.</li>
        <li>Vínculo directo con stock y clientes.</li>
        <li>Mejor continuidad entre cobro y facturación.</li>
      </ul>
    </div>

    <div class="surface-card">
      <h3>Qué conviene mirar después del cobro</h3>
      <p>
        Ahí se ve si el POS sirve de verdad: cuando la operación sigue bien cerrada y no obliga a reconstruir información.
      </p>
      <ul class="link-list">
        <li><a href="<?= e(site_url('control-de-stock.php')) ?>">Ver cómo trabaja el stock</a></li>
        <li><a href="<?= e(site_url('facturacion.php')) ?>">Ver la facturación integrada</a></li>
      </ul>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta-box">
      <h2>Si buscás un software para ventas, conviene mirar también lo que pasa después de cobrar</h2>
      <p>
        FLUS apunta a que el mostrador no quede desconectado del resto de la gestión comercial.
      </p>
      <div class="inline-actions">
        <a class="btn btn-primary" href="<?= e(site_url('contacto.php')) ?>">Pedir una demo</a>
        <a class="btn btn-secondary" href="<?= e(site_url('control-de-stock.php')) ?>">Ver control de stock</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
