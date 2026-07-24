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

$salesOverview = admin_cloud_sync_sales_overview($pdo, $clientId);
$recentSales = admin_cloud_sync_recent_sales($pdo, 8, $clientId);
$installations = portal_client_installations_summary($pdo, $clientId);
$license = portal_client_license_summary($pdo, $clientId);
$lastSyncLabel = format_datetime($installations['last_seen_at'] ?? null, 'Sin sincronizacion');
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
    <a class="button button--ghost" href="<?= e(portal_url('logout.php')) ?>">Salir</a>
  </header>

  <main class="portal-shell">
    <section class="portal-hero">
      <div>
        <span class="section-eyebrow">Panel del comercio</span>
        <h1><?= e($clientName) ?></h1>
        <p>Resumen online de actividad recibida desde tus instalaciones FLUS.</p>
      </div>
      <div class="portal-status-box">
        <span>Ultima sincronizacion</span>
        <strong><?= e($lastSyncLabel) ?></strong>
      </div>
    </section>

    <section class="portal-kpi-grid" aria-label="Resumen de las ultimas 24 horas">
      <article class="portal-kpi">
        <span>Ventas 24 hs</span>
        <strong><?= (int) ($salesOverview['sales_24h'] ?? 0) ?></strong>
        <small>Comprobantes recibidos</small>
      </article>
      <article class="portal-kpi">
        <span>Importe 24 hs</span>
        <strong><?= e(format_money($salesOverview['amount_24h'] ?? 0)) ?></strong>
        <small>Total sincronizado</small>
      </article>
      <article class="portal-kpi">
        <span>Ticket promedio</span>
        <strong><?= e(format_money($salesOverview['avg_ticket_24h'] ?? 0)) ?></strong>
        <small>Sobre ventas recibidas</small>
      </article>
      <article class="portal-kpi">
        <span>Instalaciones</span>
        <strong><?= (int) ($installations['online'] ?? 0) ?>/<?= (int) ($installations['total'] ?? 0) ?></strong>
        <small>Online ahora</small>
      </article>
    </section>

    <section class="portal-grid">
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
                <strong><?= e(format_money($paymentStats['amount'] ?? 0)) ?></strong>
                <small><?= (int) ($paymentStats['count'] ?? 0) ?> ventas</small>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </article>

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
    </section>

    <section class="portal-panel">
      <div class="section-header">
        <div>
          <div class="section-title">Ultimas ventas recibidas</div>
          <div class="section-meta">Listado de control para confirmar que la informacion llega desde caja.</div>
        </div>
      </div>

      <?php if (!$recentSales): ?>
        <div class="empty-panel">Sin ventas recibidas todavia.</div>
      <?php else: ?>
        <div class="cloud-sales-list">
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
                <strong><?= e(format_money($saleTotal)) ?></strong>
                <span><?= e($salePayment) ?> - <?= $saleItems ?> items</span>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
