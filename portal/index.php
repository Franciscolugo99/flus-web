<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/includes/bootstrap.php';
require_once __DIR__ . '/../admin/includes/client-portal.php';

$pdo = admin_db();
admin_cloud_sync_ensure_schema($pdo);
require_portal_login($pdo);

$portalUser = portal_current_user() ?? [];
$clientId = (int) ($portalUser['client_id'] ?? 0);
$clientName = (string) ($portalUser['client_name'] ?? 'Mi negocio');
$portalRole = portal_current_role();
$allowedBranchIds = portal_current_branch_scope();
$hasBranchRestriction = $allowedBranchIds !== null;
$canViewSales = portal_role_can('view_sales', $portalRole);
$canViewFinancials = portal_role_can('view_financials', $portalRole);

$periodKey = trim((string) ($_GET['periodo'] ?? 'today'));
$validPeriods = ['today', 'yesterday', '7d', '30d', 'custom'];
if (!in_array($periodKey, $validPeriods, true)) {
    $periodKey = 'today';
}
$localTimezone = new DateTimeZone((string) admin_config('timezone', 'America/Argentina/Mendoza'));
$utcTimezone = new DateTimeZone('UTC');
$todayLocal = new DateTimeImmutable('today', $localTimezone);
$fromLocal = $todayLocal;
$toLocal = $todayLocal->modify('+1 day');
$customFrom = trim((string) ($_GET['desde'] ?? ''));
$customTo = trim((string) ($_GET['hasta'] ?? ''));
$periodLabel = 'Hoy';
if ($periodKey === 'yesterday') {
    $fromLocal = $todayLocal->modify('-1 day');
    $toLocal = $todayLocal;
    $periodLabel = 'Ayer';
} elseif ($periodKey === '7d') {
    $fromLocal = $todayLocal->modify('-6 days');
    $periodLabel = 'Ultimos 7 dias';
} elseif ($periodKey === '30d') {
    $fromLocal = $todayLocal->modify('-29 days');
    $periodLabel = 'Ultimos 30 dias';
} elseif ($periodKey === 'custom') {
    $parsedFrom = DateTimeImmutable::createFromFormat('!Y-m-d', $customFrom, $localTimezone);
    $parsedTo = DateTimeImmutable::createFromFormat('!Y-m-d', $customTo, $localTimezone);
    $validCustomRange = $parsedFrom instanceof DateTimeImmutable
        && $parsedTo instanceof DateTimeImmutable
        && $parsedFrom->format('Y-m-d') === $customFrom
        && $parsedTo->format('Y-m-d') === $customTo
        && $parsedFrom <= $parsedTo
        && $parsedTo <= $todayLocal
        && $parsedFrom->diff($parsedTo)->days <= 366;
    if ($validCustomRange) {
        $fromLocal = $parsedFrom;
        $toLocal = $parsedTo->modify('+1 day');
        $periodLabel = $parsedFrom->format('d/m/Y') . ' al ' . $parsedTo->format('d/m/Y');
    } else {
        $periodKey = 'today';
        $customFrom = '';
        $customTo = '';
    }
}
$fromUtc = $fromLocal->setTimezone($utcTimezone)->format('Y-m-d H:i:s');
$toUtc = $toLocal->setTimezone($utcTimezone)->format('Y-m-d H:i:s');

$portalBranches = portal_client_branches_summary($pdo, $clientId);
if ($hasBranchRestriction) {
    $portalBranches = array_values(array_filter($portalBranches, static function (array $branch) use ($allowedBranchIds): bool {
        return in_array((int) ($branch['branch_id'] ?? 0), $allowedBranchIds, true);
    }));
}
$selectedBranchId = max(0, (int) ($_GET['sucursal'] ?? 0));
$selectedBranchName = 'Todo el negocio';
$validBranchIds = [];
foreach ($portalBranches as $portalBranch) {
    $portalBranchId = (int) ($portalBranch['branch_id'] ?? 0);
    if ($portalBranchId <= 0) {
        continue;
    }
    $validBranchIds[$portalBranchId] = (string) ($portalBranch['branch_name'] ?? 'Sucursal');
}
if ($selectedBranchId > 0 && !isset($validBranchIds[$selectedBranchId])) {
    $selectedBranchId = 0;
}
if ($selectedBranchId > 0) {
    $selectedBranchName = $validBranchIds[$selectedBranchId];
}
$branchFilterId = $selectedBranchId > 0 ? $selectedBranchId : null;
$salesOverview = $canViewSales
    ? admin_cloud_sync_sales_period_overview($pdo, $clientId, $fromUtc, $toUtc, $branchFilterId, $allowedBranchIds)
    : [];
$recentSales = $canViewSales
    ? admin_cloud_sync_recent_sales($pdo, 6, $clientId, $branchFilterId, $fromUtc, $toUtc, $allowedBranchIds)
    : [];
$branchSalesData = $canViewSales
    ? admin_cloud_sync_branch_sales_comparison($pdo, $clientId, $fromUtc, $toUtc, $allowedBranchIds)
    : [];
$branchComparison = [];
foreach ($validBranchIds as $branchId => $branchName) {
    $branchComparison[] = $branchSalesData[$branchId] ?? [
        'branch_id' => $branchId,
        'branch_name' => $branchName,
        'sales' => 0,
        'amount' => 0.0,
        'avg_ticket' => 0.0,
    ];
}
usort($branchComparison, static function (array $a, array $b): int {
    return ((float) $b['amount'] <=> (float) $a['amount']) ?: ((int) $b['sales'] <=> (int) $a['sales']);
});
$comparisonMaxAmount = 0.0;
foreach ($branchComparison as $branchMetrics) {
    $comparisonMaxAmount = max($comparisonMaxAmount, (float) ($branchMetrics['amount'] ?? 0));
}
$installations = portal_client_installations_summary($pdo, $clientId, $branchFilterId, $allowedBranchIds);
$license = portal_client_license_summary($pdo, $clientId);
$stockQuery = trim((string) ($_GET['stock_q'] ?? ''));
$stockState = trim((string) ($_GET['stock_estado'] ?? 'attention'));
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
$stockOverview = admin_cloud_sync_stock_overview($pdo, $clientId, $branchFilterId, $allowedBranchIds);
$stockItems = admin_cloud_sync_stock_items($pdo, $clientId, [
    'q' => $stockQuery,
    'state' => $stockState,
    'branch_id' => $selectedBranchId,
    'branch_ids' => $allowedBranchIds,
    'limit' => 24,
]);
$cloudStartedLabel = format_datetime($installations['first_seen_at'] ?? null, 'Pendiente de primera sincronizacion');
$lastSyncLabel = format_datetime($installations['last_seen_at'] ?? null, 'Sin sincronizacion');
$lastStockLabel = format_datetime($stockOverview['last_synced_at'] ?? null, 'Sin stock sincronizado');
$stockFilterBase = [
    'sucursal' => $selectedBranchId,
    'periodo' => $periodKey,
    'desde' => $customFrom,
    'hasta' => $customTo,
    'stock_q' => $stockQuery,
];
$stockResultContext = $stockStateLabels[$stockState] . ($stockQuery !== '' ? ' con busqueda "' . $stockQuery . '"' : '');
$stockStateCounts = [
    'attention' => (int) ($stockOverview['sin_stock'] ?? 0) + (int) ($stockOverview['bajo_minimo'] ?? 0),
    'sin_stock' => (int) ($stockOverview['sin_stock'] ?? 0),
    'bajo_minimo' => (int) ($stockOverview['bajo_minimo'] ?? 0),
    'ok' => (int) ($stockOverview['ok'] ?? 0),
    'all' => (int) ($stockOverview['total'] ?? 0),
];
$salesCount = (int) ($salesOverview['sales'] ?? 0);
$amountPeriod = (float) ($salesOverview['amount'] ?? 0);
$stockTotal = (int) ($stockOverview['total'] ?? 0);
$stockWithoutUnits = (int) ($stockOverview['sin_stock'] ?? 0);
$stockLow = (int) ($stockOverview['bajo_minimo'] ?? 0);
$stockAttention = $stockWithoutUnits + $stockLow;
$installOnline = (int) ($installations['online'] ?? 0);
$installTotal = (int) ($installations['total'] ?? 0);
$installOffline = (int) ($installations['offline'] ?? 0);
$alertBranches = $portalBranches;
if ($selectedBranchId > 0) {
    $alertBranches = array_values(array_filter($portalBranches, static function (array $branch) use ($selectedBranchId): bool {
        return (int) ($branch['branch_id'] ?? 0) === $selectedBranchId;
    }));
}
$operationalAlerts = portal_operational_alerts($alertBranches, $installations, $stockOverview, $license, $todayLocal);
$criticalAlerts = count(array_filter($operationalAlerts, static fn (array $alert): bool => ($alert['severity'] ?? '') === 'critical'));
$warningAlerts = count(array_filter($operationalAlerts, static fn (array $alert): bool => ($alert['severity'] ?? '') === 'warning'));
$alertScopeQuery = [
    'sucursal' => $selectedBranchId,
    'periodo' => $periodKey,
];
if ($periodKey === 'custom') {
    $alertScopeQuery['desde'] = $customFrom;
    $alertScopeQuery['hasta'] = $customTo;
}

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
} elseif ($canViewSales && $salesCount > 0) {
    $portalHealthTitle = 'Ventas sincronizadas';
    $portalHealthText = $salesCount . ' venta' . ($salesCount === 1 ? '' : 's') . ' recibida' . ($salesCount === 1 ? '' : 's') . ' en el periodo seleccionado.';
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
    <div class="portal-topbar-identity">
      <a class="portal-brand portal-brand--compact" href="<?= e(portal_url('index.php')) ?>">
        <img src="<?= e(portal_public_asset_url('img/flus-mark.webp')) ?>" alt="" aria-hidden="true">
        <span>FLUS</span>
      </a>
      <span class="portal-topbar-client"><?= e($clientName) ?></span>
    </div>
    <div class="portal-topbar-actions">
      <a class="button button--ghost" href="<?= e(portal_url('logout.php')) ?>">Salir</a>
    </div>
  </header>
  <main class="portal-shell">
    <section class="portal-hero" data-portal-view="summary">
      <div class="portal-hero-copy">
        <span class="section-eyebrow">Panel del comercio</span>
        <h1><?= e($clientName) ?></h1>
        <p>Ventas, sucursales y stock sincronizados desde tus instalaciones FLUS.</p>
      </div>
      <div class="portal-hero-meta">
        <div class="portal-status-box">
        <span>Acceso</span>
        <strong><?= e(['owner' => 'Dueño', 'manager' => 'Encargado', 'viewer' => 'Consulta'][$portalRole] ?? 'Consulta') ?></strong>
        </div>
        <div class="portal-status-box">
        <span>Ultima sincronizacion</span>
        <strong><?= e($lastSyncLabel) ?></strong>
        </div>
      </div>
    </section>

    <nav id="portalNav" class="portal-nav" aria-label="Secciones del panel" style="--portal-nav-items: <?= $canViewSales ? 5 : 4 ?>">
      <a href="#resumen" data-view="summary" aria-current="page"><span aria-hidden="true">⌂</span><strong>Inicio</strong></a>
      <a href="#sucursales" data-view="branches"><span aria-hidden="true">⌖</span><strong>Sucursales</strong></a>
      <a href="#stock" data-view="stock"><span aria-hidden="true">▦</span><strong>Stock</strong></a>
      <a href="#alertas" data-view="alerts"><span aria-hidden="true">!</span><strong>Alertas</strong><?php if ($operationalAlerts): ?><em><?= count($operationalAlerts) ?></em><?php endif; ?></a>
      <?php if ($canViewSales): ?>
        <a href="#ventas" data-view="sales"><span aria-hidden="true">$</span><strong>Ventas</strong></a>
      <?php endif; ?>
    </nav>

    <form id="portalScopeForm" class="portal-scope-bar" method="get" action="<?= e(portal_url('index.php')) ?>">
      <div class="portal-scope-summary">
        <span>Vista actual</span>
        <strong><?= e($selectedBranchName) ?></strong>
        <small><?= e($periodLabel) ?><?= $hasBranchRestriction ? ' - acceso limitado' : '' ?></small>
      </div>
      <div class="portal-scope-control">
        <label>
          <span>Sucursal</span>
          <select id="portalBranchScope" name="sucursal" aria-label="Elegir sucursal">
            <option value="0"><?= $hasBranchRestriction ? 'Todas mis sucursales' : 'Todas las sucursales' ?></option>
            <?php foreach ($validBranchIds as $branchId => $branchName): ?>
              <option value="<?= $branchId ?>" <?= $selectedBranchId === $branchId ? 'selected' : '' ?>><?= e($branchName) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>
          <span>Periodo de ventas</span>
          <select id="portalPeriodScope" name="periodo" aria-label="Elegir periodo de ventas">
            <option value="today" <?= $periodKey === 'today' ? 'selected' : '' ?>>Hoy</option>
            <option value="yesterday" <?= $periodKey === 'yesterday' ? 'selected' : '' ?>>Ayer</option>
            <option value="7d" <?= $periodKey === '7d' ? 'selected' : '' ?>>Ultimos 7 dias</option>
            <option value="30d" <?= $periodKey === '30d' ? 'selected' : '' ?>>Ultimos 30 dias</option>
            <option value="custom" <?= $periodKey === 'custom' ? 'selected' : '' ?>>Elegir fechas</option>
          </select>
        </label>
        <label class="portal-custom-date" data-custom-date>
          <span>Desde</span>
          <input type="date" name="desde" value="<?= e($customFrom) ?>" max="<?= e($todayLocal->format('Y-m-d')) ?>">
        </label>
        <label class="portal-custom-date" data-custom-date>
          <span>Hasta</span>
          <input type="date" name="hasta" value="<?= e($customTo) ?>" max="<?= e($todayLocal->format('Y-m-d')) ?>">
        </label>
        <button class="button" type="submit">Aplicar</button>
      </div>
    </form>

    <section class="portal-sync-note" aria-label="Alcance de los datos cloud" data-portal-view="summary">
      <div>
        <span>Datos disponibles desde</span>
        <strong><?= e($cloudStartedLabel) ?></strong>
      </div>
      <p>Incluye lo recibido desde la activacion Cloud; no incorpora ventas anteriores de FLUS local.</p>
    </section>

    <section id="resumen" class="portal-overview <?= e($portalHealthClass) ?>" aria-label="Vista general del negocio" data-portal-view="summary">
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
            <span>Ventas - <?= e($periodLabel) ?></span>
            <strong><?= $salesCount ?></strong>
          </div>
          <?php if ($canViewFinancials): ?>
            <div>
              <span>Importe</span>
              <strong><?= e(format_money($amountPeriod)) ?></strong>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div>
            <span>Productos</span>
            <strong><?= $stockTotal ?></strong>
          </div>
          <div>
            <span>Sin stock</span>
            <strong><?= $stockWithoutUnits ?></strong>
          </div>
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

    <section id="alertas" class="portal-panel portal-alert-center" data-portal-view="alerts">
      <div class="section-header section-header--spaced">
        <div>
          <div class="section-title">Centro de alertas</div>
          <div class="section-meta"><?= e($selectedBranchName) ?>. Prioridades calculadas con la ultima informacion recibida.</div>
        </div>
        <div class="portal-alert-count" aria-label="<?= count($operationalAlerts) ?> alertas activas">
          <strong><?= count($operationalAlerts) ?></strong>
          <span>activas</span>
        </div>
      </div>

      <div class="portal-alert-summary" aria-label="Resumen de alertas">
        <div><span>Criticas</span><strong><?= $criticalAlerts ?></strong></div>
        <div><span>Atencion</span><strong><?= $warningAlerts ?></strong></div>
        <div><span>Estado</span><strong><?= $operationalAlerts ? 'Revisar' : 'Normal' ?></strong></div>
      </div>

      <?php if (!$operationalAlerts): ?>
        <div class="portal-alert-empty">
          <strong>Todo en orden</strong>
          <span>No hay alertas operativas para la sucursal seleccionada.</span>
        </div>
      <?php else: ?>
        <div class="portal-alert-list">
          <?php foreach ($operationalAlerts as $alert): ?>
            <?php
              $alertAction = (string) ($alert['action'] ?? 'summary');
              $alertQuery = $alertScopeQuery;
              $alertHash = '#resumen';
              if ($alertAction === 'branches') {
                  $alertHash = '#sucursales';
              } elseif ($alertAction === 'stock_empty') {
                  $alertQuery['stock_estado'] = 'sin_stock';
                  $alertHash = '#stock';
              } elseif ($alertAction === 'stock_low') {
                  $alertQuery['stock_estado'] = 'bajo_minimo';
                  $alertHash = '#stock';
              }
              $alertUrl = portal_url('index.php?' . http_build_query($alertQuery) . $alertHash);
              $severity = in_array((string) ($alert['severity'] ?? ''), ['critical', 'warning', 'info'], true)
                  ? (string) $alert['severity']
                  : 'info';
            ?>
            <article class="portal-alert-row portal-alert-row--<?= e($severity) ?>">
              <span class="portal-alert-marker" aria-hidden="true">!</span>
              <div class="portal-alert-copy">
                <strong><?= e((string) ($alert['title'] ?? 'Alerta')) ?></strong>
                <span><?= e((string) ($alert['message'] ?? '')) ?></span>
                <?php if (!empty($alert['meta'])): ?><small><?= e((string) $alert['meta']) ?></small><?php endif; ?>
              </div>
              <a class="button button--ghost button--compact" href="<?= e($alertUrl) ?>"><?= e((string) ($alert['actionLabel'] ?? 'Revisar')) ?></a>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="portal-grid">
      <?php if ($canViewSales): ?>
        <article class="portal-panel" data-portal-view="sales">
          <div class="section-header">
            <div>
              <div class="section-title">Medios de pago</div>
              <div class="section-meta"><?= e($selectedBranchName) ?> - <?= e($periodLabel) ?>.</div>
            </div>
          </div>

          <?php $periodPayments = $salesOverview['payments'] ?? []; ?>
          <?php if (!$periodPayments): ?>
            <div class="empty-panel">No hay ventas sincronizadas en este periodo.</div>
          <?php else: ?>
            <div class="cloud-payment-list">
              <?php foreach ($periodPayments as $paymentName => $paymentStats): ?>
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
        <article class="portal-panel" data-portal-view="summary">
          <div class="section-header">
            <div>
              <div class="section-title">Consulta operativa</div>
              <div class="section-meta">Este acceso puede revisar sucursales, estado de conexion y stock.</div>
            </div>
          </div>
          <div class="empty-panel">Los importes y ventas quedan reservados para accesos Dueño o Encargado.</div>
        </article>
      <?php endif; ?>

      <article class="portal-panel" data-portal-view="summary">
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

      <article class="portal-panel portal-panel--wide portal-preview" data-portal-view="summary">
        <div class="section-header">
          <div>
            <div class="section-title">Proximamente en FLUS</div>
            <div class="section-meta">Vista previa de funciones previstas. Todavia no ejecutan acciones ni modifican datos.</div>
          </div>
          <span class="portal-preview-label">Demostracion</span>
        </div>
        <div class="portal-preview-list">
          <div><strong>Notificaciones push</strong><span>Avisos importantes aunque el portal no este abierto.</span><small>En preparacion</small></div>
          <div><strong>Productos destacados</strong><span>Ranking de los productos mas vendidos por periodo y sucursal.</span><small>Proxima version</small></div>
          <div><strong>Metas del negocio</strong><span>Objetivos diarios y mensuales con avance visible para cada sucursal.</span><small>Vista previa</small></div>
          <div><strong>Sugerencias de reposicion</strong><span>Recomendaciones basadas en stock minimo y movimiento reciente.</span><small>En estudio</small></div>
        </div>
      </article>

      <article id="sucursales" class="portal-panel portal-panel--wide" data-portal-view="branches">
        <div class="section-header">
          <div>
            <div class="section-title">Sucursales e instalaciones</div>
            <div class="section-meta">PCs que estan enviando informacion al portal.</div>
          </div>
        </div>

        <?php if (!$portalBranches): ?>
          <div class="empty-panel">Todavia no hay sucursales configuradas.</div>
        <?php else: ?>
          <div class="portal-branch-list portal-contained-list">
            <?php foreach ($portalBranches as $branch): ?>
              <?php
                $branchInstallations = is_array($branch['installations'] ?? null) ? $branch['installations'] : [];
                $branchOnline = (int) ($branch['online'] ?? 0);
                $branchIsOnline = $branchOnline > 0;
                $branchStatus = !$branchInstallations
                    ? 'Sin instalacion'
                    : ($branchIsOnline ? $branchOnline . ' online' : 'Sin contacto');
              ?>
              <div class="portal-branch-row">
                <div class="portal-branch-row__identity">
                  <strong><?= e((string) ($branch['branch_name'] ?? 'Sin sucursal')) ?></strong>
                  <?php if (!$branchInstallations): ?>
                    <span>Lista para vincular una instalacion FLUS.</span>
                  <?php else: ?>
                    <?php foreach ($branchInstallations as $installation): ?>
                      <?php $deviceName = trim((string) ($installation['display_name'] ?: $installation['device_label'] ?: 'Instalacion FLUS')); ?>
                      <span><?= e($deviceName) ?><?= !empty($installation['app_version']) ? ' - v' . e((string) $installation['app_version']) : '' ?></span>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
                <div>
                  <span class="portal-presence <?= $branchIsOnline ? 'is-online' : 'is-offline' ?>"><?= e($branchStatus) ?></span>
                  <small><?= e(format_datetime($branch['last_seen_at'] ?? null, 'Sin sincronizacion')) ?></small>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </article>
    </section>

    <section id="stock" class="portal-panel portal-stock-workspace" data-portal-view="stock">
      <div class="section-header section-header--spaced">
        <div>
          <div class="section-title">Inventario</div>
          <div class="section-meta"><?= e($selectedBranchName) ?>. Actualizado <?= e($lastStockLabel) ?>.</div>
        </div>
        <div class="portal-stock-current">
          <strong><?= count($stockItems) ?></strong>
          <span>en pantalla</span>
        </div>
      </div>

      <nav class="portal-stock-tabs" aria-label="Estado del inventario">
        <?php foreach ($stockStateLabels as $stateKey => $stateLabel): ?>
          <?php
            $stateUrl = portal_url('index.php?' . http_build_query($stockFilterBase + ['stock_estado' => $stateKey]) . '#stock');
            $isCurrentState = $stockState === $stateKey;
          ?>
          <a href="<?= e($stateUrl) ?>" class="<?= $isCurrentState ? 'is-active' : '' ?>" aria-current="<?= $isCurrentState ? 'page' : 'false' ?>">
            <span><?= e($stateLabel) ?></span>
            <strong><?= (int) ($stockStateCounts[$stateKey] ?? 0) ?></strong>
          </a>
        <?php endforeach; ?>
      </nav>

      <form class="portal-stock-filters" method="get" action="<?= e(portal_url('index.php')) ?>#stock">
        <input type="hidden" name="sucursal" value="<?= $selectedBranchId ?>">
        <input type="hidden" name="periodo" value="<?= e($periodKey) ?>">
        <?php if ($periodKey === 'custom'): ?>
          <input type="hidden" name="desde" value="<?= e($customFrom) ?>">
          <input type="hidden" name="hasta" value="<?= e($customTo) ?>">
        <?php endif; ?>
        <input type="hidden" name="stock_estado" value="<?= e($stockState) ?>">
        <label for="portalStockSearch">Buscar en inventario</label>
        <div class="portal-stock-search">
          <input id="portalStockSearch" type="search" name="stock_q" value="<?= e($stockQuery) ?>" placeholder="Producto, codigo o categoria" autocomplete="off">
          <?php if ($stockQuery !== ''): ?>
            <a class="portal-stock-clear" href="<?= e(portal_url('index.php?' . http_build_query(array_merge($stockFilterBase, ['stock_q' => '', 'stock_estado' => $stockState])) . '#stock')) ?>">Limpiar</a>
          <?php endif; ?>
          <button class="button" type="submit">Buscar</button>
        </div>
      </form>

      <div class="portal-stock-result-note">
        <span><?= e($stockResultContext) ?></span>
        <small><?= count($stockItems) ?> de hasta 24 productos</small>
      </div>

      <?php if (!$stockItems): ?>
        <div class="portal-stock-empty">
          <strong>No encontramos productos</strong>
          <span>Proba otra busqueda o cambia el estado del inventario.</span>
        </div>
      <?php else: ?>
        <div class="portal-stock-list portal-contained-list">
          <?php foreach ($stockItems as $item): ?>
            <?php
              $stockView = portal_stock_item_view($item);
              $state = (string) $stockView['state'];
              $category = trim((string) ($item['categoria'] ?? ''));
              $code = trim((string) ($item['codigo'] ?? ''));
            ?>
            <article class="portal-stock-item portal-stock-item--<?= e($state) ?>">
              <div class="portal-stock-product">
                <div class="portal-stock-product-heading">
                  <span class="portal-stock-badge"><?= e((string) $stockView['state_label']) ?></span>
                  <small><?= e((string) ($item['branch_name'] ?? 'Sin sucursal')) ?></small>
                </div>
                <strong><?= e((string) $item['nombre']) ?></strong>
                <span><?= e($code !== '' ? $code : 'Sin codigo') ?><?= $category !== '' ? ' · ' . e($category) : '' ?></span>
              </div>
              <div class="portal-stock-level">
                <div>
                  <span>Disponible</span>
                  <strong><?= e((string) $stockView['stock_label']) ?></strong>
                </div>
                <div class="portal-stock-progress" role="progressbar" aria-label="Nivel respecto del minimo" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $stockView['progress'] ?>">
                  <span style="width: <?= (int) $stockView['progress'] ?>%"></span>
                </div>
                <small><?= e((string) $stockView['guidance']) ?></small>
                <?php if ((float) $stockView['stock_min'] > 0 || $canViewFinancials): ?>
                  <span class="portal-stock-minimum">
                    <?php if ((float) $stockView['stock_min'] > 0): ?>Minimo <?= e((string) $stockView['stock_min_label']) ?><?php endif; ?>
                    <?php if ($canViewFinancials): ?><?= (float) $stockView['stock_min'] > 0 ? ' · ' : '' ?>Precio <?= e(format_money($item['precio'] ?? 0)) ?><?php endif; ?>
                  </span>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <?php if ($canViewSales): ?>
      <section id="ventas" class="portal-panel" data-portal-view="sales">
        <div class="section-header">
          <div>
            <div class="section-title">Ultimas ventas recibidas</div>
            <div class="section-meta"><?= e($selectedBranchName) ?>. Listado de control para confirmar que la informacion llega desde caja.</div>
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

      <section class="portal-panel portal-branch-comparison" data-portal-view="sales">
        <div class="section-header">
          <div>
            <div class="section-title">Comparar sucursales</div>
            <div class="section-meta"><?= e($periodLabel) ?>. Importes calculados con las ventas sincronizadas.</div>
          </div>
        </div>
        <?php if (!$branchComparison): ?>
          <div class="empty-panel">Todavia no hay sucursales para comparar.</div>
        <?php else: ?>
          <div class="portal-comparison-list">
            <?php foreach ($branchComparison as $branchMetrics): ?>
              <?php
                $metricsBranchId = (int) ($branchMetrics['branch_id'] ?? 0);
                $barAmount = (float) ($branchMetrics['amount'] ?? 0);
                $barPercent = $comparisonMaxAmount > 0 && $barAmount > 0
                    ? max(2, (int) round(((float) ($branchMetrics['amount'] ?? 0) / $comparisonMaxAmount) * 100))
                    : 0;
              ?>
              <article class="portal-comparison-row <?= $selectedBranchId === $metricsBranchId ? 'is-selected' : '' ?>">
                <div class="portal-comparison-heading">
                  <strong><?= e((string) ($branchMetrics['branch_name'] ?? 'Sucursal')) ?></strong>
                  <span><?= (int) ($branchMetrics['sales'] ?? 0) ?> venta<?= (int) ($branchMetrics['sales'] ?? 0) === 1 ? '' : 's' ?></span>
                </div>
                <div class="portal-comparison-track" aria-hidden="true"><span style="width: <?= $barPercent ?>%"></span></div>
                <div class="portal-comparison-values">
                  <?php if ($canViewFinancials): ?>
                    <strong><?= e(format_money($branchMetrics['amount'] ?? 0)) ?></strong>
                    <small>Ticket prom. <?= e(format_money($branchMetrics['avg_ticket'] ?? 0)) ?></small>
                  <?php else: ?>
                    <small>Importes no disponibles para este acceso.</small>
                  <?php endif; ?>
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
      const mobileQuery = window.matchMedia('(max-width: 600px)');
      const nav = document.getElementById('portalNav');
      if (!nav) return;

      const links = Array.from(nav.querySelectorAll('[data-view]'));
      const availableViews = links.map(function(link) { return link.dataset.view; });
      const scopeForm = document.getElementById('portalScopeForm');
      const periodSelect = document.getElementById('portalPeriodScope');
      const customDateFields = Array.from(document.querySelectorAll('[data-custom-date]'));

      function updateCustomDates() {
        const showCustom = periodSelect && periodSelect.value === 'custom';
        customDateFields.forEach(function(field) {
          field.hidden = !showCustom;
          field.querySelectorAll('input').forEach(function(input) { input.disabled = !showCustom; });
        });
      }

      function viewFromLocation() {
        if (window.location.hash === '#sucursales') return 'branches';
        if (window.location.hash === '#alertas') return 'alerts';
        if (window.location.hash === '#stock') return 'stock';
        if (window.location.hash === '#ventas') return 'sales';
        return 'summary';
      }

      function activate(view, updateHistory) {
        if (!availableViews.includes(view)) view = 'summary';
        document.body.dataset.portalView = view;
        document.querySelectorAll('[data-portal-view]').forEach(function(panel) {
          panel.hidden = mobileQuery.matches && panel.dataset.portalView !== view;
        });
        links.forEach(function(link) {
          const active = link.dataset.view === view;
          link.classList.toggle('is-active', active);
          link.setAttribute('aria-current', active ? 'page' : 'false');
        });

        if (updateHistory) {
          const activeLink = links.find(function(link) { return link.dataset.view === view; });
          if (activeLink) history.replaceState(null, '', activeLink.getAttribute('href'));
        }
        if (mobileQuery.matches) window.scrollTo({ top: 0, behavior: 'auto' });
      }

      links.forEach(function(link) {
        link.addEventListener('click', function(event) {
          if (!mobileQuery.matches) return;
          event.preventDefault();
          activate(link.dataset.view || 'summary', true);
        });
      });

      if (scopeForm) {
        scopeForm.addEventListener('submit', function() {
          const view = document.body.dataset.portalView || viewFromLocation();
          const activeLink = links.find(function(link) { return link.dataset.view === view; });
          scopeForm.action = '<?= e(portal_url('index.php')) ?>' + (activeLink ? activeLink.getAttribute('href') : '#resumen');
        });
      }
      if (periodSelect) periodSelect.addEventListener('change', updateCustomDates);
      updateCustomDates();

      document.body.classList.add('portal-app-ready');
      activate(viewFromLocation(), false);
      window.addEventListener('hashchange', function() { activate(viewFromLocation(), false); });
      mobileQuery.addEventListener('change', function() { activate(document.body.dataset.portalView || 'summary', false); });
    })();
  </script>
</body>
</html>
