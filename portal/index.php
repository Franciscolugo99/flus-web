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
$canPreviewStockCount = portal_role_can('preview_stock_count', $portalRole);
$canPreviewPriceChange = $canViewFinancials && portal_role_can('preview_price_change', $portalRole);
$canChangePrice = $canViewFinancials && portal_role_can('change_price', $portalRole);

$periodKey = trim((string) ($_GET['periodo'] ?? 'today'));
$validPeriods = ['today', 'yesterday', '7d', '30d', 'all', 'custom'];
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
} elseif ($periodKey === 'all') {
    $fromLocal = new DateTimeImmutable('2000-01-01 00:00:00', $localTimezone);
    $periodLabel = 'Todo lo sincronizado';
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
$nowLocal = new DateTimeImmutable('now', $localTimezone);
$effectiveToLocal = $toLocal < $nowLocal ? $toLocal : $nowLocal;
$periodDays = max(1, (int)$fromLocal->diff($toLocal)->days);
$previousFromUtc = $fromLocal->modify('-' . $periodDays . ' days')->setTimezone($utcTimezone)->format('Y-m-d H:i:s');
$previousToUtc = $effectiveToLocal->modify('-' . $periodDays . ' days')->setTimezone($utcTimezone)->format('Y-m-d H:i:s');

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
$previousSalesOverview = $canViewSales && $periodKey !== 'all'
    ? admin_cloud_sync_sales_period_overview($pdo, $clientId, $previousFromUtc, $previousToUtc, $branchFilterId, $allowedBranchIds)
    : [];
$salesQuery = mb_substr(trim((string) ($_GET['venta_q'] ?? '')), 0, 80);
$salesPayment = mb_substr(strtoupper(trim((string) ($_GET['venta_medio'] ?? ''))), 0, 40);
$salesCashier = mb_substr(trim((string) ($_GET['venta_cajero'] ?? '')), 0, 80);
$salesPage = max(1, (int) ($_GET['venta_pagina'] ?? 1));
$salesFilters = [
    'branch_id' => $selectedBranchId,
    'branch_ids' => $allowedBranchIds,
    'from_utc' => $fromUtc,
    'to_utc' => $toUtc,
    'q' => $salesQuery,
    'payment' => $salesPayment,
    'cashier' => $salesCashier,
];
$salesList = $canViewSales
    ? admin_cloud_sync_sales_list($pdo, $clientId, $salesFilters + ['page' => $salesPage, 'per_page' => 15])
    : ['items' => [], 'total' => 0, 'page' => 1, 'per_page' => 15, 'pages' => 0];
$salesRows = is_array($salesList['items'] ?? null) ? $salesList['items'] : [];
$filteredSalesOverview = $canViewSales
    ? admin_cloud_sync_sales_filtered_overview($pdo, $clientId, $salesFilters)
    : ['sales' => 0, 'amount' => 0.0, 'avg_ticket' => 0.0, 'items' => 0];
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
$cashSessions = admin_cloud_sync_cash_sessions($pdo, $clientId, $branchFilterId, $allowedBranchIds);
$openCashSessions = array_values(array_filter($cashSessions, static fn (array $session): bool => ($session['status'] ?? '') === 'open'));
$lastClosedCashSession = null;
foreach ($cashSessions as $cashSession) {
    if (($cashSession['status'] ?? '') === 'closed') {
        $lastClosedCashSession = $cashSession;
        break;
    }
}
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
$cloudStartedLabel = format_utc_datetime($installations['first_seen_at'] ?? null, 'Pendiente de primera sincronizacion');
$lastSyncLabel = format_utc_datetime($installations['last_seen_at'] ?? null, 'Sin sincronizacion');
$lastStockLabel = format_utc_datetime($stockOverview['last_synced_at'] ?? null, 'Sin stock sincronizado');
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
$averageTicket = (float) ($salesOverview['avg_ticket'] ?? 0);
$itemsPeriod = (int) ($salesOverview['items'] ?? 0);
$previousSalesCount = (int) ($previousSalesOverview['sales'] ?? 0);
$previousAmount = (float) ($previousSalesOverview['amount'] ?? 0);
$previousAverageTicket = (float) ($previousSalesOverview['avg_ticket'] ?? 0);
$pulseTrend = static function (float $current, float $previous): array {
    if (abs($previous) < 0.0001) {
        return abs($current) < 0.0001
            ? ['class' => 'is-neutral', 'label' => 'Sin cambios']
            : ['class' => 'is-new', 'label' => 'Sin movimiento previo'];
    }
    $percentage = (($current - $previous) / abs($previous)) * 100;
    if (abs($percentage) < 0.5) {
        return ['class' => 'is-neutral', 'label' => 'Sin cambios'];
    }
    return [
        'class' => $percentage > 0 ? 'is-up' : 'is-down',
        'label' => ($percentage > 0 ? '+' : '') . number_format($percentage, 0, ',', '.') . '% vs anterior',
    ];
};
$amountTrend = $pulseTrend($amountPeriod, $previousAmount);
$salesTrend = $pulseTrend((float)$salesCount, (float)$previousSalesCount);
$ticketTrend = $pulseTrend($averageTicket, $previousAverageTicket);
if ($periodKey === 'all') {
    $amountTrend = $salesTrend = $ticketTrend = ['class' => 'is-neutral', 'label' => 'Periodo completo'];
}
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
  <meta name="theme-color" content="#111520">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="FLUS">
  <title><?= e($clientName) ?> - FLUS</title>
  <link rel="icon" type="image/png" href="<?= e(portal_public_asset_url('img/favicon.png')) ?>">
  <link rel="apple-touch-icon" href="<?= e(portal_url('assets/icons/flus-192.png')) ?>">
  <link rel="manifest" href="<?= e(portal_url('manifest.webmanifest')) ?>">
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
      <button type="button" class="button button--ghost portal-install-action" data-pwa-install hidden>Instalar</button>
      <a class="button button--ghost" href="<?= e(portal_url('logout.php')) ?>">Salir</a>
    </div>
  </header>
  <p class="portal-install-help portal-install-help--topbar" data-pwa-install-help hidden></p>
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
            <option value="all" <?= $periodKey === 'all' ? 'selected' : '' ?>>Todo lo sincronizado</option>
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

    <?php if ($canViewSales): ?>
      <section class="portal-panel portal-pulse" data-portal-view="summary" aria-label="Pulso del negocio">
        <div class="section-header">
          <div>
            <div class="section-title">Pulso del negocio</div>
            <div class="section-meta"><?= e($selectedBranchName) ?>, <?= e(strtolower($periodLabel)) ?> comparado con el periodo anterior.</div>
          </div>
        </div>
        <div class="portal-pulse-grid">
          <div class="portal-pulse-metric">
            <span>Facturacion</span>
            <?php if ($canViewFinancials): ?>
              <strong><?= e(format_money($amountPeriod)) ?></strong>
              <small class="<?= e($amountTrend['class']) ?>"><?= e($amountTrend['label']) ?></small>
            <?php else: ?>
              <strong>Restringido</strong>
              <small class="is-neutral">Solo Dueño o Encargado</small>
            <?php endif; ?>
          </div>
          <div class="portal-pulse-metric">
            <span>Ventas</span>
            <strong><?= $salesCount ?></strong>
            <small class="<?= e($salesTrend['class']) ?>"><?= e($salesTrend['label']) ?></small>
          </div>
          <div class="portal-pulse-metric">
            <span>Ticket promedio</span>
            <?php if ($canViewFinancials): ?>
              <strong><?= e(format_money($averageTicket)) ?></strong>
              <small class="<?= e($ticketTrend['class']) ?>"><?= e($ticketTrend['label']) ?></small>
            <?php else: ?>
              <strong>Restringido</strong>
              <small class="is-neutral">Importe no disponible</small>
            <?php endif; ?>
          </div>
          <div class="portal-pulse-metric">
            <span>Productos vendidos</span>
            <strong><?= $itemsPeriod ?></strong>
            <small class="is-neutral">En <?= $salesCount ?> venta<?= $salesCount === 1 ? '' : 's' ?></small>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <section class="portal-panel portal-cash-live" data-portal-view="summary" aria-label="Estado de cajas">
      <div class="section-header section-header--spaced">
        <div>
          <div class="section-title">Cajas ahora</div>
          <div class="section-meta"><?= e($selectedBranchName) ?>. Aperturas y cierres informados por FLUS.</div>
        </div>
        <span class="portal-cash-count <?= $openCashSessions ? 'is-open' : '' ?>"><?= count($openCashSessions) ?> abierta<?= count($openCashSessions) === 1 ? '' : 's' ?></span>
      </div>

      <?php if (!$cashSessions): ?>
        <div class="portal-cash-empty">
          <strong>Aun no hay estados de caja sincronizados</strong>
          <span>Las ventas siguen visibles. Esta seccion se activara cuando FLUS envie la proxima apertura o cierre.</span>
        </div>
      <?php else: ?>
        <div class="portal-cash-list">
          <?php foreach ($openCashSessions as $cashSession): ?>
            <?php $cashSummary = is_array($cashSession['summary'] ?? null) ? $cashSession['summary'] : []; ?>
            <article class="portal-cash-row is-open">
              <div>
                <span class="portal-presence is-online">Abierta</span>
                <strong><?= e((string)(($cashSummary['terminal_name'] ?? '') ?: $cashSession['installation_name'])) ?></strong>
                <small><?= e((string)$cashSession['branch_name']) ?></small>
              </div>
              <div>
                <strong><?= e($canViewSales ? (string)(($cashSummary['cashier_name'] ?? '') ?: 'Cajero sin informar') : 'En operacion') ?></strong>
                <small>Desde <?= e(format_utc_datetime($cashSummary['fecha_apertura'] ?? $cashSession['occurred_at'])) ?></small>
              </div>
            </article>
          <?php endforeach; ?>

          <?php if ($lastClosedCashSession): ?>
            <?php $closedSummary = is_array($lastClosedCashSession['summary'] ?? null) ? $lastClosedCashSession['summary'] : []; ?>
            <article class="portal-cash-row">
              <div>
                <span class="portal-presence is-offline">Ultimo cierre</span>
                <strong><?= e((string)(($closedSummary['terminal_name'] ?? '') ?: $lastClosedCashSession['installation_name'])) ?></strong>
                <small><?= e((string)$lastClosedCashSession['branch_name']) ?></small>
              </div>
              <div>
                <?php if ($canViewFinancials): ?>
                  <strong>Diferencia <?= e(format_money($closedSummary['diferencia'] ?? 0)) ?></strong>
                <?php endif; ?>
                <small><?= e(format_utc_datetime($closedSummary['fecha_cierre'] ?? $lastClosedCashSession['occurred_at'])) ?></small>
              </div>
            </article>
          <?php endif; ?>
        </div>
      <?php endif; ?>
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
                  <small><?= e(format_utc_datetime($branch['last_seen_at'] ?? null, 'Sin sincronizacion')) ?></small>
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

      <form id="portalStockForm" class="portal-stock-filters" method="get" action="<?= e(portal_url('index.php')) ?>#stock" data-stock-scanner data-zxing-src="<?= e(portal_url('assets/vendor/zxing/zxing-browser.min.js')) ?>">
        <input type="hidden" name="sucursal" value="<?= $selectedBranchId ?>">
        <input type="hidden" name="periodo" value="<?= e($periodKey) ?>">
        <?php if ($periodKey === 'custom'): ?>
          <input type="hidden" name="desde" value="<?= e($customFrom) ?>">
          <input type="hidden" name="hasta" value="<?= e($customTo) ?>">
        <?php endif; ?>
        <input id="portalStockState" type="hidden" name="stock_estado" value="<?= e($stockState) ?>">
        <label for="portalStockSearch">Buscar en inventario</label>
        <div class="portal-stock-search">
          <input id="portalStockSearch" type="search" name="stock_q" value="<?= e($stockQuery) ?>" placeholder="Producto, codigo o categoria" autocomplete="off">
          <?php if ($stockQuery !== ''): ?>
            <a class="portal-stock-clear" href="<?= e(portal_url('index.php?' . http_build_query(array_merge($stockFilterBase, ['stock_q' => '', 'stock_estado' => $stockState])) . '#stock')) ?>">Limpiar</a>
          <?php endif; ?>
          <button class="button" type="submit">Buscar</button>
          <button class="button button--ghost portal-stock-scan-open" type="button" data-stock-scan-open>Escanear codigo</button>
        </div>
        <section class="portal-stock-scanner" data-stock-scanner-panel hidden aria-labelledby="portalStockScannerTitle">
          <div class="portal-stock-scanner-head">
            <div>
              <strong id="portalStockScannerTitle">Escanear producto</strong>
              <span>Apunta la camara al codigo de barras.</span>
            </div>
            <button class="portal-stock-scanner-close" type="button" data-stock-scan-close aria-label="Cerrar camara">Cerrar</button>
          </div>
          <div class="portal-stock-camera">
            <video data-stock-scanner-video playsinline muted aria-label="Vista de la camara"></video>
            <span class="portal-stock-camera-frame" aria-hidden="true"></span>
          </div>
          <div class="portal-stock-scanner-footer">
            <p data-stock-scanner-status role="status" aria-live="polite">Preparando camara...</p>
            <button class="button button--ghost button--compact" type="button" data-stock-scan-torch hidden>Encender luz</button>
          </div>
        </section>
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
              $brand = trim((string) ($item['marca'] ?? ''));
              $unit = trim((string) ($item['unidad_venta'] ?? ''));
              $isWeighable = (int) ($item['es_pesable'] ?? 0) === 1;
              $stockUpdatedAt = $item['synced_at'] ?? $item['updated_at'] ?? null;
            ?>
            <article class="portal-stock-item portal-stock-item--<?= e($state) ?>"<?= $code !== '' ? ' data-product-code="' . e($code) . '"' : '' ?>>
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
              <?php if ($canPreviewStockCount || $canPreviewPriceChange): ?>
                <div class="portal-stock-actions">
                  <?php if ($canPreviewStockCount): ?>
                    <button
                      class="button button--ghost portal-stock-count-open"
                      type="button"
                      data-stock-count-open
                      data-stock-count-name="<?= e((string) $item['nombre']) ?>"
                      data-stock-count-code="<?= e($code) ?>"
                      data-stock-count-branch="<?= e((string) ($item['branch_name'] ?? 'Sin sucursal')) ?>"
                      data-stock-count-current="<?= e((string) $stockView['stock']) ?>"
                      data-stock-count-current-label="<?= e((string) $stockView['stock_label']) ?>"
                      data-stock-count-unit="<?= e($unit !== '' ? strtolower($unit) : 'unidad') ?>"
                      data-stock-count-step="<?= $isWeighable ? '0.001' : '1' ?>"
                    >
                      <span>Contar stock</span>
                      <small>Demostracion</small>
                    </button>
                  <?php endif; ?>
                  <?php if ($canPreviewPriceChange): ?>
                    <button
                      class="button button--ghost portal-price-change-open"
                      type="button"
                      data-price-change-open
                      data-price-change-name="<?= e((string) $item['nombre']) ?>"
                      data-price-change-code="<?= e($code) ?>"
                      data-price-change-branch="<?= e((string) ($item['branch_name'] ?? 'Sin sucursal')) ?>"
                      data-price-change-current="<?= e((string) (float) ($item['precio'] ?? 0)) ?>"
                      data-price-change-current-label="<?= e(format_money($item['precio'] ?? 0)) ?>"
                      data-price-change-stock-id="<?= (int) ($item['id'] ?? 0) ?>"
                    >
                      <span>Cambiar precio</span>
                      <small>Sucursal</small>
                    </button>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              <details class="portal-stock-detail-disclosure">
                <summary>Ver ficha del producto</summary>
                <dl class="portal-stock-detail-grid">
                  <div><dt>Codigo</dt><dd><?= e($code !== '' ? $code : 'Sin codigo') ?></dd></div>
                  <div><dt>Sucursal</dt><dd><?= e((string) ($item['branch_name'] ?? 'Sin sucursal')) ?></dd></div>
                  <div><dt>Categoria</dt><dd><?= e($category !== '' ? $category : 'Sin categoria') ?></dd></div>
                  <div><dt>Marca</dt><dd><?= e($brand !== '' ? $brand : 'Sin marca') ?></dd></div>
                  <div><dt>Stock actual</dt><dd><?= e((string) $stockView['stock_label']) ?></dd></div>
                  <div><dt>Stock minimo</dt><dd><?= (float) $stockView['stock_min'] > 0 ? e((string) $stockView['stock_min_label']) : 'No configurado' ?></dd></div>
                  <div><dt>Venta</dt><dd><?= e($isWeighable ? 'Producto pesable' : ($unit !== '' ? 'Por ' . strtolower($unit) : 'Por unidad')) ?></dd></div>
                  <?php if ($canViewFinancials): ?><div><dt>Precio</dt><dd><?= e(format_money($item['precio'] ?? 0)) ?></dd></div><?php endif; ?>
                  <div><dt>Actualizado</dt><dd><?= e(format_utc_datetime($stockUpdatedAt, 'Sin sincronizacion')) ?></dd></div>
                </dl>
              </details>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <?php if ($canViewSales): ?>
      <section id="ventas" class="portal-panel portal-sales-workspace" data-portal-view="sales">
        <div class="section-header section-header--spaced">
          <div>
            <div class="section-title">Ventas</div>
            <div class="section-meta"><?= e($selectedBranchName) ?>, <?= e(strtolower($periodLabel)) ?>.</div>
          </div>
          <strong class="portal-sales-count"><?= (int) ($salesList['total'] ?? 0) ?> venta<?= (int) ($salesList['total'] ?? 0) === 1 ? '' : 's' ?></strong>
        </div>

        <form class="portal-sales-filters" method="get" action="<?= e(portal_url('index.php')) ?>#ventas">
          <input type="hidden" name="sucursal" value="<?= $selectedBranchId ?>">
          <input type="hidden" name="periodo" value="<?= e($periodKey) ?>">
          <?php if ($periodKey === 'custom'): ?>
            <input type="hidden" name="desde" value="<?= e($customFrom) ?>">
            <input type="hidden" name="hasta" value="<?= e($customTo) ?>">
          <?php endif; ?>
          <label class="portal-sales-search">
            <span>Buscar venta o producto</span>
            <input type="search" name="venta_q" value="<?= e($salesQuery) ?>" placeholder="Numero, producto o codigo" autocomplete="off">
          </label>
          <label>
            <span>Medio de pago</span>
            <select name="venta_medio">
              <option value="">Todos</option>
              <?php foreach (array_keys((array) ($salesOverview['payments'] ?? [])) as $paymentOption): ?>
                <option value="<?= e((string) $paymentOption) ?>" <?= $salesPayment === (string) $paymentOption ? 'selected' : '' ?>><?= e(ucwords(strtolower(str_replace('_', ' ', (string) $paymentOption)))) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>
            <span>Cajero</span>
            <input type="search" name="venta_cajero" value="<?= e($salesCashier) ?>" placeholder="Nombre o identificador" autocomplete="off">
          </label>
          <button class="button" type="submit">Buscar</button>
          <?php if ($salesQuery !== '' || $salesPayment !== '' || $salesCashier !== ''): ?>
            <a class="button button--ghost" href="<?= e(portal_url('index.php?' . http_build_query([
              'sucursal' => $selectedBranchId,
              'periodo' => $periodKey,
              'desde' => $customFrom,
              'hasta' => $customTo,
            ]))) ?>#ventas">Limpiar</a>
          <?php endif; ?>
          <a class="button button--ghost portal-sales-export" href="<?= e(portal_url('ventas-exportar.php?' . http_build_query([
            'sucursal' => $selectedBranchId,
            'periodo' => $periodKey,
            'desde' => $customFrom,
            'hasta' => $customTo,
            'venta_q' => $salesQuery,
            'venta_medio' => $salesPayment,
            'venta_cajero' => $salesCashier,
          ]))) ?>">Exportar CSV</a>
        </form>

        <div class="portal-sales-totals" aria-label="Totales de ventas filtradas">
          <span><small>Operaciones</small><strong><?= (int) ($filteredSalesOverview['sales'] ?? 0) ?></strong></span>
          <span><small>Productos</small><strong><?= (int) ($filteredSalesOverview['items'] ?? 0) ?></strong></span>
          <?php if ($canViewFinancials): ?>
            <span><small>Total neto</small><strong><?= e(format_money($filteredSalesOverview['amount'] ?? 0)) ?></strong></span>
            <span><small>Ticket promedio</small><strong><?= e(format_money($filteredSalesOverview['avg_ticket'] ?? 0)) ?></strong></span>
          <?php endif; ?>
        </div>

        <?php if (!$salesRows): ?>
          <div class="empty-panel">
            <?= $salesQuery !== '' || $salesPayment !== '' || $salesCashier !== '' ? 'No encontramos ventas con esos filtros.' : 'Sin ventas sincronizadas en este periodo.' ?>
          </div>
        <?php else: ?>
          <div class="portal-sales-list">
            <?php foreach ($salesRows as $sale): ?>
              <?php
                $summary = is_array($sale['summary'] ?? null) ? $sale['summary'] : [];
                $payload = is_array($sale['payload'] ?? null) ? $sale['payload'] : [];
                $saleId = (int) ($summary['venta_id'] ?? 0);
                $saleGross = (float) ($summary['total'] ?? 0);
                $saleAnnulled = (float) ($sale['annulled_amount'] ?? 0);
                $saleTotal = (float) ($sale['net_amount'] ?? $saleGross);
                $saleStatus = strtoupper(trim((string) ($sale['sale_status'] ?? $summary['estado'] ?? 'EMITIDA')));
                $salePayment = strtoupper(trim((string) ($summary['medio_pago'] ?? 'SIN_DATO')));
                $saleItems = (int) ($summary['items_count'] ?? 0);
                $branchName = (string) ($sale['branch_name'] ?: 'Sin sucursal');
                $saleProductRows = is_array($payload['items'] ?? null) ? $payload['items'] : [];
                $salePaymentLabel = ucwords(strtolower(str_replace('_', ' ', $salePayment)));
                $saleCashier = trim((string) ($summary['cajero_nombre'] ?? $payload['cajero_nombre'] ?? ''));
                if ($saleCashier === '' && (int) ($summary['user_id'] ?? $payload['user_id'] ?? 0) > 0) {
                  $saleCashier = 'Cajero #' . (int) ($summary['user_id'] ?? $payload['user_id']);
                }
              ?>
              <details class="portal-sale-row">
                <summary>
                  <span class="portal-sale-identity">
                    <small><?= e($branchName) ?></small>
                    <strong><?= $saleId > 0 ? 'Venta #' . $saleId : 'Venta sincronizada' ?><?= $saleStatus !== 'EMITIDA' ? ' · ' . e(ucwords(strtolower(str_replace('_', ' ', $saleStatus)))) : '' ?></strong>
                    <time><?= e(format_utc_datetime($sale['occurred_at'] ?? null)) ?></time>
                  </span>
                  <span class="portal-sale-summary">
                    <?php if ($canViewFinancials): ?><strong><?= e(format_money($saleTotal)) ?></strong><?php endif; ?>
                    <small><?= e($salePaymentLabel) ?>, <?= $saleItems ?> producto<?= $saleItems === 1 ? '' : 's' ?><?= $saleCashier !== '' ? ', ' . e($saleCashier) : '' ?></small>
                  </span>
                  <span class="portal-sale-chevron" aria-hidden="true">+</span>
                </summary>
                <div class="portal-sale-detail">
                  <?php if ($saleAnnulled > 0 && $canViewFinancials): ?>
                    <p>Importe original <?= e(format_money($saleGross)) ?>, anulado <?= e(format_money($saleAnnulled)) ?>, neto vigente <?= e(format_money($saleTotal)) ?>.</p>
                  <?php endif; ?>
                  <?php if (!$saleProductRows): ?>
                    <p>El detalle de productos no estaba disponible cuando se sincronizo esta venta.</p>
                  <?php else: ?>
                    <div class="portal-sale-items">
                      <?php foreach ($saleProductRows as $saleProduct): ?>
                        <?php
                          if (!is_array($saleProduct)) { continue; }
                          $productName = trim((string) ($saleProduct['nombre'] ?? ''));
                          $productCode = trim((string) ($saleProduct['codigo'] ?? ''));
                          $productQuantity = (float) ($saleProduct['cantidad'] ?? 0);
                        ?>
                        <div class="portal-sale-product">
                          <span>
                            <strong><?= e($productName !== '' ? $productName : 'Producto sin nombre') ?></strong>
                            <small><?= e($productCode !== '' ? $productCode : 'Sin codigo') ?></small>
                          </span>
                          <span>
                            <strong><?= e(number_format($productQuantity, abs($productQuantity - round($productQuantity)) < 0.0001 ? 0 : 3, ',', '.')) ?> u.</strong>
                            <?php if ($canViewFinancials): ?><small><?= e(format_money($saleProduct['subtotal'] ?? 0)) ?></small><?php endif; ?>
                          </span>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </div>
              </details>
            <?php endforeach; ?>
          </div>

          <?php if ((int) ($salesList['pages'] ?? 0) > 1): ?>
            <nav class="portal-sales-pagination" aria-label="Paginas de ventas">
              <?php
                $currentSalesPage = (int) ($salesList['page'] ?? 1);
                $salesPages = (int) ($salesList['pages'] ?? 0);
                $salesPageQuery = [
                  'sucursal' => $selectedBranchId,
                  'periodo' => $periodKey,
                  'desde' => $customFrom,
                  'hasta' => $customTo,
                  'venta_q' => $salesQuery,
                  'venta_medio' => $salesPayment,
                  'venta_cajero' => $salesCashier,
                ];
              ?>
              <?php if ($currentSalesPage > 1): ?>
                <?php $salesPageQuery['venta_pagina'] = $currentSalesPage - 1; ?>
                <a class="button button--ghost" href="<?= e(portal_url('index.php?' . http_build_query($salesPageQuery))) ?>#ventas">Anterior</a>
              <?php endif; ?>
              <span>Pagina <?= $currentSalesPage ?> de <?= $salesPages ?></span>
              <?php if ($currentSalesPage < $salesPages): ?>
                <?php $salesPageQuery['venta_pagina'] = $currentSalesPage + 1; ?>
                <a class="button button--ghost" href="<?= e(portal_url('index.php?' . http_build_query($salesPageQuery))) ?>#ventas">Siguiente</a>
              <?php endif; ?>
            </nav>
          <?php endif; ?>
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
  <?php if ($canPreviewStockCount): ?>
    <div class="portal-stock-count-sheet" data-stock-count-sheet hidden>
      <button class="portal-stock-count-backdrop" type="button" data-stock-count-close aria-label="Cerrar conteo"></button>
      <section class="portal-stock-count-panel" role="dialog" aria-modal="true" aria-labelledby="portalStockCountTitle">
        <header class="portal-stock-count-head">
          <div>
            <span class="portal-stock-count-demo">Demostracion</span>
            <h2 id="portalStockCountTitle">Conteo de stock</h2>
          </div>
          <button class="portal-stock-count-close" type="button" data-stock-count-close aria-label="Cerrar conteo">Cerrar</button>
        </header>
        <div class="portal-stock-count-product">
          <strong data-stock-count-product>Producto</strong>
          <span><b data-stock-count-branch>Sucursal</b><i aria-hidden="true">&middot;</i><span data-stock-count-code>Sin codigo</span></span>
        </div>
        <form class="portal-stock-count-form" data-stock-count-form novalidate>
          <div class="portal-stock-count-current">
            <span>Stock informado</span>
            <strong data-stock-count-current>0 unidades</strong>
          </div>
          <label for="portalStockCountQuantity">
            <span>Cantidad contada</span>
            <input id="portalStockCountQuantity" data-stock-count-quantity type="number" min="0" inputmode="decimal" autocomplete="off" required>
          </label>
          <label for="portalStockCountReason">
            <span>Motivo</span>
            <select id="portalStockCountReason" data-stock-count-reason required>
              <option value="physical_count">Conteo fisico</option>
              <option value="breakage">Rotura o merma</option>
              <option value="expiration">Vencimiento</option>
              <option value="correction">Correccion de carga</option>
            </select>
          </label>
          <div class="portal-stock-count-difference" data-stock-count-difference aria-live="polite">
            <span>Diferencia</span>
            <strong>Ingresá la cantidad contada</strong>
          </div>
          <p class="portal-stock-count-note">Esta vista calcula el ajuste, pero todavia no modifica FLUS ni envia datos a la sucursal.</p>
          <button class="button button--block" type="submit">Simular ajuste</button>
          <div class="portal-stock-count-result" data-stock-count-result role="status" hidden></div>
        </form>
      </section>
    </div>
  <?php endif; ?>
  <?php if ($canPreviewPriceChange): ?>
    <div class="portal-stock-count-sheet portal-price-change-sheet" data-price-change-sheet hidden>
      <button class="portal-stock-count-backdrop" type="button" data-price-change-close aria-label="Cerrar cambio de precio"></button>
      <section class="portal-stock-count-panel" role="dialog" aria-modal="true" aria-labelledby="portalPriceChangeTitle">
        <header class="portal-stock-count-head">
          <div>
            <span class="portal-stock-count-demo">Cambio remoto</span>
            <h2 id="portalPriceChangeTitle">Cambiar precio</h2>
          </div>
          <button class="portal-stock-count-close" type="button" data-price-change-close aria-label="Cerrar cambio de precio">Cerrar</button>
        </header>
        <div class="portal-stock-count-product">
          <strong data-price-change-product>Producto</strong>
          <span><b data-price-change-branch>Sucursal</b><i aria-hidden="true">&middot;</i><span data-price-change-code>Sin codigo</span></span>
        </div>
        <form class="portal-stock-count-form portal-price-change-form" data-price-change-form data-price-command-url="<?= e(portal_url('price-command.php')) ?>" data-price-command-enabled="<?= $canChangePrice ? '1' : '0' ?>" novalidate>
          <input type="hidden" data-price-change-csrf value="<?= e(csrf_token()) ?>">
          <input type="hidden" data-price-change-stock-id value="">
          <input type="hidden" data-price-change-request-uid value="">
          <div class="portal-stock-count-current">
            <span>Precio actual</span>
            <strong data-price-change-current>$ 0,00</strong>
          </div>
          <label for="portalPriceChangeValue">
            <span>Nuevo precio</span>
            <input id="portalPriceChangeValue" data-price-change-value type="number" min="0.01" step="0.01" inputmode="decimal" autocomplete="off" required>
          </label>
          <div class="portal-price-presets" role="group" aria-label="Ajustes rapidos de precio">
            <button type="button" data-price-change-percent="-5">-5%</button>
            <button type="button" data-price-change-percent="5">+5%</button>
            <button type="button" data-price-change-percent="10">+10%</button>
            <button type="button" data-price-change-percent="15">+15%</button>
          </div>
          <label for="portalPriceChangeReason">
            <span>Motivo</span>
            <select id="portalPriceChangeReason" data-price-change-reason required>
              <option value="supplier_cost">Cambio de costo</option>
              <option value="price_list">Nueva lista</option>
              <option value="margin">Ajuste de margen</option>
              <option value="promotion">Promocion</option>
              <option value="correction">Correccion de carga</option>
            </select>
          </label>
          <div class="portal-stock-count-difference portal-price-change-difference" data-price-change-difference aria-live="polite">
            <span>Variacion</span>
            <strong>Ingresa el nuevo precio</strong>
          </div>
          <p class="portal-stock-count-note"><?= $canChangePrice ? 'La orden se enviara solamente a esta sucursal. FLUS validara nuevamente el producto y el precio antes de aplicarla.' : 'Esta vista previsualiza el cambio, pero todavia no modifica FLUS ni envia precios a la sucursal.' ?></p>
          <button class="button button--block" type="submit"><?= $canChangePrice ? 'Enviar cambio a la sucursal' : 'Simular cambio' ?></button>
          <div class="portal-stock-count-result" data-price-change-result role="status" hidden></div>
        </form>
      </section>
    </div>
  <?php endif; ?>
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
  <script src="<?= e(portal_url('assets/js/stock-scanner.js?v=' . (string) @filemtime(__DIR__ . '/assets/js/stock-scanner.js'))) ?>" defer></script>
  <?php if ($canPreviewStockCount): ?><script src="<?= e(portal_url('assets/js/stock-count-demo.js?v=' . (string) @filemtime(__DIR__ . '/assets/js/stock-count-demo.js'))) ?>" defer></script><?php endif; ?>
  <?php if ($canPreviewPriceChange): ?><script src="<?= e(portal_url('assets/js/price-change-demo.js?v=' . (string) @filemtime(__DIR__ . '/assets/js/price-change-demo.js'))) ?>" defer></script><?php endif; ?>
  <script src="<?= e(portal_url('assets/js/pwa.js')) ?>" defer></script>
</body>
</html>
