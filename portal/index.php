<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/includes/bootstrap.php';
require_once __DIR__ . '/../admin/includes/client-portal.php';

require_portal_login();

$pdo = admin_db();
admin_cloud_sync_ensure_schema($pdo);

$portalUser = portal_current_user() ?? [];
$clientId = (int) ($portalUser['client_id'] ?? 0);
$clientName = (string) ($portalUser['client_name'] ?? 'Mi negocio');
$portalRole = portal_current_role();
$canViewSales = portal_role_can('view_sales', $portalRole);
$canViewFinancials = portal_role_can('view_financials', $portalRole);

$salesOverview = $canViewSales ? admin_cloud_sync_sales_overview($pdo, $clientId) : [];
$recentSales = $canViewSales ? admin_cloud_sync_recent_sales($pdo, 6, $clientId) : [];
$installations = portal_client_installations_summary($pdo, $clientId);
$license = portal_client_license_summary($pdo, $clientId);
$stockQuery = trim((string) ($_GET['stock_q'] ?? ''));
$stockState = trim((string) ($_GET['stock_estado'] ?? 'attention'));
$stockBranchId = max(0, (int) ($_GET['stock_sucursal'] ?? 0));
$stockStateLabels = [
    'attention' => 'Atencion',
    'sin_stock' => 'Sin stock',
    'bajo_minimo' => 'Bajo minimo',
    'ok' => 'Disponible',
    'all' => 'Todos',
];
if (!array_key_exists($stockState, $stockStateLabels)) {
    $stockState = 'attention';
}
$stockOverview = admin_cloud_sync_stock_overview($pdo, $clientId);
$stockBranches = admin_cloud_sync_stock_branches($pdo, $clientId);
$stockItems = admin_cloud_sync_stock_items($pdo, $clientId, [
    'q' => $stockQuery,
    'state' => $stockState,
    'branch_id' => $stockBranchId,
    'limit' => 24,
]);
$cloudStartedLabel = format_datetime($installations['first_seen_at'] ?? null, 'Pendiente de primera sincronizacion');
$lastSyncLabel = format_datetime($installations['last_seen_at'] ?? null, 'Sin sincronizacion');
$lastStockLabel = format_datetime($stockOverview['last_synced_at'] ?? null, 'Sin stock sincronizado');
$installationRows = is_array($installations['rows'] ?? null) ? $installations['rows'] : [];
$onlineCutoff = new DateTimeImmutable('-10 minutes', new DateTimeZone('UTC'));
$stockFilterBase = [
    'stock_q' => $stockQuery,
    'stock_sucursal' => $stockBranchId,
];
$stockResultContext = $stockStateLabels[$stockState] . ($stockQuery !== '' ? ' con busqueda "' . $stockQuery . '"' : '');
$sales24h = (int) ($salesOverview['sales_24h'] ?? 0);
$amount24h = (float) ($salesOverview['amount_24h'] ?? 0);
$avgTicket24h = (float) ($salesOverview['avg_ticket_24h'] ?? 0);
$stockTotal = (int) ($stockOverview['total'] ?? 0);
$stockWithoutUnits = (int) ($stockOverview['sin_stock'] ?? 0);
$stockLow = (int) ($stockOverview['bajo_minimo'] ?? 0);
$stockAttention = $stockWithoutUnits + $stockLow;
$installOnline = (int) ($installations['online'] ?? 0);
$installTotal = (int) ($installations['total'] ?? 0);
$installOffline = (int) ($installations['offline'] ?? 0);

$portalHealthClass = 'is-ok';
$portalHealthTitle = 'Operacion normal';
$portalHealthText = 'No hay alertas criticas en los datos sincronizados.';
$portalNextAction = 'Revisar stock';
$portalNextText = 'Usa los filtros para ver faltantes y productos bajo minimo.';
if ($installTotal === 0) {
    $portalHealthClass = 'is-warn';
    $portalHealthTitle = 'Sin instalaciones';
    $portalHealthText = 'Todavia no hay una PC FLUS enviando datos a este portal.';
    $portalNextAction = 'Conectar instalacion';
    $portalNextText = 'Configura Cloud en FLUS local para empezar a ver datos.';
} elseif ($installOffline > 0) {
    $portalHealthClass = 'is-warn';
    $portalHealthTitle = 'Sucursal sin contacto';
    $portalHealthText = $installOffline . ' instalacion' . ($installOffline === 1 ? '' : 'es') . ' no reporta en los ultimos minutos.';
    $portalNextAction = 'Ver sucursales';
    $portalNextText = 'Confirma si la PC esta encendida, con internet y FLUS abierto.';
} elseif ($stockAttention > 0) {
    $portalHealthClass = 'is-warn';
    $portalHealthTitle = 'Stock con atencion';
    $portalHealthText = $stockAttention . ' producto' . ($stockAttention === 1 ? '' : 's') . ' requiere reposicion o revision.';
    $portalNextAction = 'Resolver faltantes';
    $portalNextText = 'Prioriza productos sin stock y bajo minimo.';
} elseif ($canViewSales && $sales24h > 0) {
    $portalHealthTitle = 'Ventas sincronizadas';
    $portalHealthText = $sales24h . ' venta' . ($sales24h === 1 ? '' : 's') . ' recibida' . ($sales24h === 1 ? '' : 's') . ' en las ultimas 24 hs.';
    $portalNextAction = 'Control rapido';
    $portalNextText = 'Mira medios de pago y ultimas ventas para confirmar la operacion.';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title><?= e($clientName) ?> - FLUS</title>
  <link rel="icon" type="image/png" href="<?= e(portal_public_asset_url('img/favicon.png')) ?>">
  <link rel="stylesheet" href="<?= e(portal_admin_asset_url('css/admin.css?v=' . (is_file(__DIR__ . '/../admin/assets/css/admin.css') ? filemtime(__DIR__ . '/../admin/assets/css/admin.css') : time()))) ?>">
</head>
<body class="portal-page">
  <header class="portal-topbar">
    <a class="portal-brand portal-brand--compact" href="<?= e(portal_url('index.php')) ?>">
      <img src="<?= e(portal_public_asset_url('img/flus-mark.webp')) ?>" alt="" aria-hidden="true">
      <span>FLUS</span>
    </a>
    <div class="portal-topbar-actions">
      <button class="portal-menu-button" type="button" aria-expanded="false" aria-controls="portalNav">
        <span></span>
        <span></span>
        <span></span>
        <strong>Menu</strong>
      </button>
      <a class="button button--ghost" href="<?= e(portal_url('logout.php')) ?>">Salir</a>
    </div>
  </header>
  <div class="portal-nav-backdrop" hidden></div>

  <main class="portal-shell">
    <section class="portal-hero">
      <div>
        <span class="section-eyebrow">Panel del comercio</span>
        <h1><?= e($clientName) ?></h1>
        <p>Ventas, sucursales y stock sincronizados desde tus instalaciones FLUS.</p>
      </div>
      <div class="portal-status-box">
        <span>Acceso</span>
        <strong><?= e(['owner' => 'Dueño', 'manager' => 'Encargado', 'viewer' => 'Consulta'][$portalRole] ?? 'Consulta') ?></strong>
      </div>
      <div class="portal-status-box">
        <span>Ultima sincronizacion</span>
        <strong><?= e($lastSyncLabel) ?></strong>
      </div>
    </section>

    <nav id="portalNav" class="portal-nav" aria-label="Secciones del panel">
      <button class="portal-nav-close" type="button">Cerrar</button>
      <a href="#resumen">Resumen</a>
      <a href="#sucursales">Sucursales</a>
      <a href="#stock">Stock</a>
      <?php if ($canViewSales): ?>
        <a href="#ventas">Ventas</a>
      <?php endif; ?>
    </nav>

    <section class="portal-sync-note" aria-label="Alcance de los datos cloud">
      <div>
        <span>Datos disponibles desde</span>
        <strong><?= e($cloudStartedLabel) ?></strong>
      </div>
      <p>Este portal muestra la informacion recibida desde que Cloud esta activo en cada instalacion. Las ventas anteriores de FLUS local no se importan automaticamente.</p>
    </section>

    <section id="resumen" class="portal-overview <?= e($portalHealthClass) ?>" aria-label="Vista general del negocio">
      <div class="portal-overview-main">
        <span>Vista general</span>
        <h2><?= e($portalHealthTitle) ?></h2>
        <p><?= e($portalHealthText) ?></p>
      </div>
      <div class="portal-overview-action">
        <span>Ahora conviene</span>
        <strong><?= e($portalNextAction) ?></strong>
        <small><?= e($portalNextText) ?></small>
      </div>
      <div class="portal-overview-strip">
        <?php if ($canViewSales): ?>
          <div>
            <span>Ventas 24 hs</span>
            <strong><?= $sales24h ?></strong>
          </div>
          <?php if ($canViewFinancials): ?>
            <div>
              <span>Importe</span>
              <strong><?= e(format_money($amount24h)) ?></strong>
            </div>
          <?php endif; ?>
        <?php endif; ?>
        <div>
          <span>Stock atencion</span>
          <strong><?= $stockAttention ?></strong>
        </div>
        <div>
          <span>Online</span>
          <strong><?= $installOnline ?>/<?= $installTotal ?></strong>
        </div>
      </div>
    </section>

    <section class="portal-kpi-grid" aria-label="Resumen de las ultimas 24 horas">
      <?php if ($canViewSales): ?>
        <article class="portal-kpi">
          <span>Ventas 24 hs</span>
          <strong><?= $sales24h ?></strong>
          <small>Comprobantes recibidos</small>
        </article>
        <?php if ($canViewFinancials): ?>
          <article class="portal-kpi">
            <span>Importe 24 hs</span>
            <strong><?= e(format_money($amount24h)) ?></strong>
            <small>Total sincronizado</small>
          </article>
          <article class="portal-kpi">
            <span>Ticket promedio</span>
            <strong><?= e(format_money($avgTicket24h)) ?></strong>
            <small>Sobre ventas recibidas</small>
          </article>
        <?php endif; ?>
      <?php else: ?>
        <article class="portal-kpi">
          <span>Productos</span>
          <strong><?= $stockTotal ?></strong>
          <small>Stock sincronizado</small>
        </article>
        <article class="portal-kpi">
          <span>Sin stock</span>
          <strong><?= $stockWithoutUnits ?></strong>
          <small>Requiere reposicion</small>
        </article>
        <article class="portal-kpi">
          <span>Bajo minimo</span>
          <strong><?= $stockLow ?></strong>
          <small>Atencion operativa</small>
        </article>
      <?php endif; ?>
      <article class="portal-kpi">
        <span>Instalaciones</span>
        <strong><?= $installOnline ?>/<?= $installTotal ?></strong>
        <small>Online ahora</small>
      </article>
    </section>

    <section class="portal-grid">
      <?php if ($canViewSales): ?>
        <article class="portal-panel">
          <div class="section-header">
            <div>
              <div class="section-title">Medios de pago</div>
              <div class="section-meta">Ventas recibidas durante las ultimas 24 hs.</div>
            </div>
          </div>

          <?php $payments24h = $salesOverview['payments_24h'] ?? []; ?>
          <?php if (!$payments24h): ?>
            <div class="empty-panel">Todavia no hay ventas sincronizadas hoy.</div>
          <?php else: ?>
            <div class="cloud-payment-list">
              <?php foreach ($payments24h as $paymentName => $paymentStats): ?>
                <div class="cloud-payment-row">
                  <span><?= e((string) $paymentName) ?></span>
                  <?php if ($canViewFinancials): ?>
                    <strong><?= e(format_money($paymentStats['amount'] ?? 0)) ?></strong>
                  <?php endif; ?>
                  <small><?= (int) ($paymentStats['count'] ?? 0) ?> ventas</small>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </article>
      <?php else: ?>
        <article class="portal-panel">
          <div class="section-header">
            <div>
              <div class="section-title">Consulta operativa</div>
              <div class="section-meta">Este acceso puede revisar sucursales, estado de conexion y stock.</div>
            </div>
          </div>
          <div class="empty-panel">Los importes y ventas quedan reservados para accesos Dueño o Encargado.</div>
        </article>
      <?php endif; ?>

      <article class="portal-panel">
        <div class="section-header">
          <div>
            <div class="section-title">Estado operativo</div>
            <div class="section-meta">Licencia e instalaciones conectadas.</div>
          </div>
        </div>

        <div class="portal-status-list">
          <div>
            <span>Licencia</span>
            <strong><?= $license ? e(status_label((string) $license['effective_status'])) : 'Sin licencia' ?></strong>
          </div>
          <div>
            <span>Vencimiento</span>
            <strong><?= $license ? e(format_date((string) $license['expires_at'])) : '-' ?></strong>
          </div>
          <div>
            <span>Sin contacto</span>
            <strong><?= (int) ($installations['offline'] ?? 0) ?></strong>
          </div>
        </div>
      </article>

      <article id="sucursales" class="portal-panel portal-panel--wide">
        <div class="section-header">
          <div>
            <div class="section-title">Sucursales e instalaciones</div>
            <div class="section-meta">PCs que estan enviando informacion al portal.</div>
          </div>
        </div>

        <?php if (!$installationRows): ?>
          <div class="empty-panel">Todavia no hay instalaciones sincronizadas.</div>
        <?php else: ?>
          <div class="portal-branch-list portal-contained-list">
            <?php foreach ($installationRows as $installation): ?>
              <?php
                $lastSeenRaw = (string) ($installation['last_seen_at'] ?? '');
                $lastSeen = $lastSeenRaw !== ''
                    ? DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $lastSeenRaw, new DateTimeZone('UTC'))
                    : false;
                $isOnline = $lastSeen && $lastSeen >= $onlineCutoff;
                $branchName = trim((string) ($installation['branch_name'] ?? ''));
                $deviceName = trim((string) ($installation['display_name'] ?: $installation['device_label'] ?: 'Instalacion FLUS'));
              ?>
              <div class="portal-branch-row">
                <div>
                  <strong><?= e($branchName !== '' ? $branchName : 'Sin sucursal') ?></strong>
                  <span><?= e($deviceName) ?><?= !empty($installation['app_version']) ? ' - v' . e((string) $installation['app_version']) : '' ?></span>
                </div>
                <div>
                  <span class="portal-presence <?= $isOnline ? 'is-online' : 'is-offline' ?>"><?= $isOnline ? 'Online' : 'Sin contacto' ?></span>
                  <small><?= e(format_datetime($installation['last_seen_at'] ?? null, 'Sin registro')) ?></small>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </article>
    </section>

    <section id="stock" class="portal-panel">
      <div class="section-header section-header--spaced">
        <div>
          <div class="section-title">Stock por sucursal</div>
          <div class="section-meta">Solo lectura. Ultima actualizacion: <?= e($lastStockLabel) ?>.</div>
        </div>
        <div class="portal-stock-current">
          <span><?= e($stockStateLabels[$stockState]) ?></span>
          <strong><?= count($stockItems) ?></strong>
        </div>
      </div>

      <div class="portal-stock-summary" aria-label="Resumen de stock">
        <div>
          <span>Productos</span>
          <strong><?= (int) ($stockOverview['total'] ?? 0) ?></strong>
        </div>
        <div>
          <span>Sin stock</span>
          <strong><?= (int) ($stockOverview['sin_stock'] ?? 0) ?></strong>
        </div>
        <div>
          <span>Bajo minimo</span>
          <strong><?= (int) ($stockOverview['bajo_minimo'] ?? 0) ?></strong>
        </div>
      </div>

      <nav class="portal-stock-tabs" aria-label="Filtros rapidos de stock">
        <?php foreach ($stockStateLabels as $stateKey => $stateLabel): ?>
          <?php
            $stateUrl = portal_url('index.php?' . http_build_query($stockFilterBase + ['stock_estado' => $stateKey]) . '#stock');
            $isCurrentState = $stockState === $stateKey;
          ?>
          <a href="<?= e($stateUrl) ?>" class="<?= $isCurrentState ? 'is-active' : '' ?>" aria-current="<?= $isCurrentState ? 'page' : 'false' ?>">
            <?= e($stateLabel) ?>
          </a>
        <?php endforeach; ?>
      </nav>

      <form class="portal-stock-filters" method="get">
        <label>
          <span>Buscar</span>
          <input type="search" name="stock_q" value="<?= e($stockQuery) ?>" placeholder="Producto, codigo o categoria">
        </label>
        <label>
          <span>Sucursal</span>
          <select name="stock_sucursal">
            <option value="0">Todas</option>
            <?php foreach ($stockBranches as $branch): ?>
              <?php $branchId = (int) ($branch['branch_id'] ?? 0); ?>
              <?php if ($branchId > 0): ?>
                <option value="<?= $branchId ?>" <?= $stockBranchId === $branchId ? 'selected' : '' ?>><?= e((string) $branch['branch_name']) ?></option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </label>
        <label>
          <span>Estado</span>
          <select name="stock_estado">
            <option value="attention" <?= $stockState === 'attention' ? 'selected' : '' ?>>Requiere atencion</option>
            <option value="sin_stock" <?= $stockState === 'sin_stock' ? 'selected' : '' ?>>Sin stock</option>
            <option value="bajo_minimo" <?= $stockState === 'bajo_minimo' ? 'selected' : '' ?>>Bajo minimo</option>
            <option value="ok" <?= $stockState === 'ok' ? 'selected' : '' ?>>Stock disponible</option>
            <option value="all" <?= $stockState === 'all' ? 'selected' : '' ?>>Todos</option>
          </select>
        </label>
        <button class="button" type="submit">Filtrar</button>
      </form>

      <div class="portal-stock-result-note">
        <span><?= count($stockItems) ?> producto<?= count($stockItems) === 1 ? '' : 's' ?> mostrado<?= count($stockItems) === 1 ? '' : 's' ?></span>
        <small><?= e($stockResultContext) ?>. Maximo 24 por vista para mantener la consulta rapida.</small>
      </div>

      <?php if (!$stockItems): ?>
        <div class="empty-panel">Todavia no hay stock sincronizado con esos filtros.</div>
      <?php else: ?>
        <div class="portal-stock-list portal-contained-list">
          <?php foreach ($stockItems as $item): ?>
            <?php
              $state = (string) ($item['estado_stock'] ?? 'ok');
              $stateLabel = $state === 'sin_stock' ? 'Sin stock' : ($state === 'bajo_minimo' ? 'Bajo minimo' : 'Disponible');
              $stock = (float) ($item['stock'] ?? 0);
              $stockMin = (float) ($item['stock_minimo'] ?? 0);
              $unit = trim((string) ($item['unidad_venta'] ?? ''));
              $stockLabel = number_format($stock, 3, ',', '.');
              $stockMinLabel = number_format($stockMin, 3, ',', '.');
            ?>
            <article class="portal-stock-item portal-stock-item--<?= e($state) ?>">
              <div>
                <strong><?= e((string) $item['nombre']) ?></strong>
                <span><?= e((string) ($item['codigo'] ?: 'Sin codigo')) ?></span>
                <small><?= e((string) ($item['branch_name'] ?? 'Sin sucursal')) ?></small>
              </div>
              <div>
                <span class="portal-stock-badge"><?= e($stateLabel) ?></span>
                <strong><?= e($stockLabel) ?><?= $unit !== '' ? ' ' . e($unit) : '' ?></strong>
                <small>Min. <?= e($stockMinLabel) ?><?= $canViewFinancials ? ' - ' . e(format_money($item['precio'] ?? 0)) : '' ?></small>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <?php if ($canViewSales): ?>
      <section id="ventas" class="portal-panel">
        <div class="section-header">
          <div>
            <div class="section-title">Ultimas ventas recibidas</div>
            <div class="section-meta">Listado de control para confirmar que la informacion llega desde caja.</div>
          </div>
        </div>

        <?php if (!$recentSales): ?>
          <div class="empty-panel">Sin ventas recibidas todavia.</div>
        <?php else: ?>
          <div class="cloud-sales-list portal-contained-list">
            <?php foreach ($recentSales as $sale): ?>
              <?php
                $summary = is_array($sale['summary'] ?? null) ? $sale['summary'] : [];
                $saleId = (int) ($summary['venta_id'] ?? 0);
                $saleTotal = (float) ($summary['total'] ?? 0);
                $salePayment = strtoupper(trim((string) ($summary['medio_pago'] ?? 'SIN_DATO')));
                $saleItems = (int) ($summary['items_count'] ?? 0);
                $branchName = (string) ($sale['branch_name'] ?: 'Sin sucursal');
              ?>
              <article class="cloud-sale-item">
                <div>
                  <strong><?= e($branchName) ?> - <?= $saleId > 0 ? 'venta #' . $saleId : 'venta sin numero' ?></strong>
                  <span><?= e(format_datetime($sale['received_at'] ?? null)) ?></span>
                </div>
                <div>
                  <?php if ($canViewFinancials): ?>
                    <strong><?= e(format_money($saleTotal)) ?></strong>
                  <?php endif; ?>
                  <span><?= e($salePayment) ?> - <?= $saleItems ?> items</span>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>
    <?php endif; ?>
  </main>
  <script>
    (function() {
      const button = document.querySelector('.portal-menu-button');
      const nav = document.getElementById('portalNav');
      const close = document.querySelector('.portal-nav-close');
      const backdrop = document.querySelector('.portal-nav-backdrop');
      if (!button || !nav || !backdrop) return;

      function setOpen(open) {
        document.body.classList.toggle('portal-nav-open', open);
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        backdrop.hidden = !open;
      }

      button.addEventListener('click', function() {
        setOpen(!document.body.classList.contains('portal-nav-open'));
      });
      if (close) close.addEventListener('click', function() { setOpen(false); });
      backdrop.addEventListener('click', function() { setOpen(false); });
      nav.querySelectorAll('a').forEach(function(link) {
        link.addEventListener('click', function() { setOpen(false); });
      });
      document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') setOpen(false);
      });
    })();
  </script>
</body>
</html>
