<?php
declare(strict_types=1);

$page_title = 'Sucursales cloud';
$active_menu = 'cloud-sync';
require_once __DIR__ . '/includes/layout-header.php';

$schemaReady = admin_cloud_sync_ensure_schema($pdo);
$installations = $schemaReady ? admin_cloud_sync_recent_installations($pdo, 50) : [];
$events = $schemaReady ? admin_cloud_sync_recent_events($pdo, 30) : [];
$salesOverview = $schemaReady ? admin_cloud_sync_sales_overview($pdo) : [];
$recentSales = $schemaReady ? admin_cloud_sync_recent_sales($pdo, 12) : [];

$utc = new DateTimeZone('UTC');
$onlineCutoff = new DateTimeImmutable('-10 minutes', $utc);
$online = 0;
$offline = 0;
foreach ($installations as $installation) {
    $lastSeen = !empty($installation['last_seen_at'])
        ? DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string) $installation['last_seen_at'], $utc)
        : false;
    if ($lastSeen && $lastSeen >= $onlineCutoff) {
        $online++;
    } else {
        $offline++;
    }
}

$branchesCount = 0;
$eventsToday = 0;
$cloudLicenses = 0;
$cloudNoContact = 0;
$localInstallations = 0;
if ($schemaReady) {
    $branchesCount = (int) $pdo->query('SELECT COUNT(*) FROM client_branches')->fetchColumn();
    $eventsToday = (int) $pdo->query('SELECT COUNT(*) FROM cloud_sync_events WHERE DATE(received_at) = UTC_DATE()')->fetchColumn();
    $cloudPlanWhere = "(LOWER(plan_type) LIKE '%cloud%' OR LOWER(plan_type) LIKE '%multi%' OR LOWER(plan_type) LIKE '%sucursal%' OR LOWER(plan_type) LIKE '%online%' OR LOWER(plan_type) LIKE '%web%')";
    $cloudLicenses = (int) $pdo->query("
        SELECT COUNT(*)
        FROM licenses
        WHERE status NOT IN ('vencida','suspendida')
          AND expires_at >= CURDATE()
          AND {$cloudPlanWhere}
    ")->fetchColumn();
    $cloudNoContact = (int) $pdo->query("
        SELECT COUNT(*)
        FROM licenses l
        LEFT JOIN (
            SELECT license_id, MAX(last_seen_at) AS last_seen_at
            FROM client_installations
            GROUP BY license_id
        ) i ON i.license_id = l.id
        WHERE l.status NOT IN ('vencida','suspendida')
          AND l.expires_at >= CURDATE()
          AND {$cloudPlanWhere}
          AND (i.last_seen_at IS NULL OR i.last_seen_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 10 MINUTE))
    ")->fetchColumn();
    $localInstallations = (int) $pdo->query("
        SELECT COUNT(*)
        FROM client_installations i
        INNER JOIN licenses l ON l.id = i.license_id
        WHERE NOT {$cloudPlanWhere}
    ")->fetchColumn();
}
?>

<section class="admin-license-panel">
  <div class="admin-license-head">
    <div>
      <span class="eyebrow">Control operativo</span>
      <h1>Sucursales cloud</h1>
      <p>Estado de instalaciones FLUS que reportan datos desde cada negocio.</p>
    </div>
  </div>

  <?php if (!$schemaReady): ?>
    <div class="alert alert-error">No se pudo preparar el esquema de sincronizacion cloud.</div>
  <?php else: ?>
    <div class="license-ops-grid">
      <div class="ops-card ops-card--info">
        <span class="ops-card-label">Instalaciones</span>
        <strong><?= count($installations) ?></strong>
        <span>PCs registradas por licencia</span>
      </div>
      <div class="ops-card">
        <span class="ops-card-label">Online</span>
        <strong><?= $online ?></strong>
        <span>Vistas en los ultimos 10 min</span>
      </div>
      <div class="ops-card <?= $offline > 0 ? 'ops-card--warn' : '' ?>">
        <span class="ops-card-label">Sin contacto</span>
        <strong><?= $offline ?></strong>
        <span>Revisar red o apagado</span>
      </div>
      <div class="ops-card">
        <span class="ops-card-label">Sucursales</span>
        <strong><?= $branchesCount ?></strong>
        <span>Registradas por cliente</span>
      </div>
      <div class="ops-card ops-card--info">
        <span class="ops-card-label">Licencias cloud</span>
        <strong><?= $cloudLicenses ?></strong>
        <span>Habilitadas para portal</span>
      </div>
      <div class="ops-card <?= $cloudNoContact > 0 ? 'ops-card--warn' : '' ?>">
        <span class="ops-card-label">Cloud sin contacto</span>
        <strong><?= $cloudNoContact ?></strong>
        <span>Planes cloud offline</span>
      </div>
      <div class="ops-card ops-card--muted">
        <span class="ops-card-label">Locales detectadas</span>
        <strong><?= $localInstallations ?></strong>
        <span>Instalaciones sin plan cloud</span>
      </div>
      <div class="ops-card ops-card--info">
        <span class="ops-card-label">Eventos hoy</span>
        <strong><?= $eventsToday ?></strong>
        <span>Recibidos por la API</span>
      </div>
    </div>

    <div class="section-header section-header--spaced">
      <div>
        <div class="section-title">Actividad comercial sincronizada</div>
        <div class="section-meta">Lectura resumida de ventas recibidas en las ultimas 24 hs desde las instalaciones conectadas.</div>
      </div>
    </div>

    <div class="cloud-commerce-grid">
      <div class="ops-card ops-card--info">
        <span class="ops-card-label">Ventas 24 hs</span>
        <strong><?= (int) ($salesOverview['sales_24h'] ?? 0) ?></strong>
        <span>Eventos de venta aceptados</span>
      </div>
      <div class="ops-card">
        <span class="ops-card-label">Importe 24 hs</span>
        <strong><?= e(format_money($salesOverview['amount_24h'] ?? 0)) ?></strong>
        <span>Total sincronizado</span>
      </div>
      <div class="ops-card">
        <span class="ops-card-label">Ticket promedio</span>
        <strong><?= e(format_money($salesOverview['avg_ticket_24h'] ?? 0)) ?></strong>
        <span>Sobre ventas recibidas</span>
      </div>
      <div class="ops-card ops-card--muted">
        <span class="ops-card-label">Items</span>
        <strong><?= (int) ($salesOverview['items_24h'] ?? 0) ?></strong>
        <span>Unidades o renglones reportados</span>
      </div>
    </div>

    <div class="cloud-sync-split">
      <section class="cloud-sync-panel">
        <div class="section-header">
          <div>
            <div class="section-title">Medios de pago 24 hs</div>
            <div class="section-meta">Importe recibido por medio informado por cada POS.</div>
          </div>
        </div>
        <?php $payments24h = $salesOverview['payments_24h'] ?? []; ?>
        <?php if (empty($payments24h)): ?>
          <div class="empty-panel">Todavia no hay ventas sincronizadas en las ultimas 24 hs.</div>
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
      </section>

      <section class="cloud-sync-panel">
        <div class="section-header">
          <div>
            <div class="section-title">Ultimas ventas recibidas</div>
            <div class="section-meta">Muestra rapida para confirmar que cada caja esta reportando.</div>
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
                $clientName = (string) ($sale['trade_name'] ?: $sale['legal_name']);
                $branchName = (string) ($sale['branch_name'] ?: 'Sin sucursal');
              ?>
              <article class="cloud-sale-item">
                <div>
                  <strong><?= e($clientName) ?></strong>
                  <span><?= e($branchName) ?> - venta <?= $saleId > 0 ? '#' . $saleId : 'sin numero' ?></span>
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
    </div>

    <div class="section-header">
      <div>
        <div class="section-title">Instalaciones recientes</div>
        <div class="section-meta">Cada fila pertenece a un cliente y licencia. No se mezclan datos entre negocios.</div>
      </div>
    </div>

    <div class="table-wrapper table-wrap--mobile-cards section-table">
      <table>
        <thead>
          <tr>
            <th>Cliente</th>
            <th>Sucursal</th>
            <th>Instalacion</th>
            <th>Licencia</th>
            <th>Plan</th>
            <th>Version</th>
            <th>Ultimo contacto</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$installations): ?>
            <tr class="empty-row"><td colspan="8">Todavia no hay instalaciones sincronizadas.</td></tr>
          <?php else: ?>
            <?php foreach ($installations as $row): ?>
              <?php
                $lastSeen = !empty($row['last_seen_at'])
                    ? DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string) $row['last_seen_at'], $utc)
                    : false;
                $isOnline = $lastSeen && $lastSeen >= $onlineCutoff;
              ?>
              <tr>
                <td data-label="Cliente">
                  <a href="<?= admin_url('client-view.php?id=' . (int) $row['client_id']) ?>" class="table-link">
                    <?= e($row['trade_name'] ?: $row['legal_name']) ?>
                  </a>
                </td>
                <td data-label="Sucursal"><?= e($row['branch_name'] ?: 'Sin sucursal') ?></td>
                <td data-label="Instalacion" class="td-mono td-mono--compact"><?= e($row['installation_uid']) ?></td>
                <td data-label="Licencia" class="td-mono td-mono--compact"><?= e($row['license_key']) ?></td>
                <td data-label="Plan">
                  <?php if (admin_license_plan_cloud_enabled($row)): ?>
                    <span class="badge badge-blue"><?= e(plan_type_label((string) $row['plan_type'])) ?></span>
                  <?php else: ?>
                    <span class="badge badge-gray">Local</span>
                  <?php endif; ?>
                </td>
                <td data-label="Version"><?= e($row['app_version'] ?: '-') ?></td>
                <td data-label="Ultimo contacto"><?= e(format_datetime($row['last_seen_at'] ?? null)) ?></td>
                <td data-label="Estado">
                  <span class="badge <?= $isOnline ? 'badge-green' : 'badge-yellow' ?>"><?= $isOnline ? 'Online' : 'Sin contacto' ?></span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="section-header">
      <div>
        <div class="section-title">Eventos recientes</div>
        <div class="section-meta">Resumen tecnico para auditar llegada de ventas, caja, stock y alertas.</div>
      </div>
    </div>

    <div class="table-wrapper table-wrap--mobile-cards">
      <table>
        <thead>
          <tr>
            <th>Recibido</th>
            <th>Cliente</th>
            <th>Sucursal</th>
            <th>Tipo</th>
            <th>Evento</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$events): ?>
            <tr class="empty-row"><td colspan="5">Sin eventos recibidos todavia.</td></tr>
          <?php else: ?>
            <?php foreach ($events as $event): ?>
              <tr>
                <td data-label="Recibido"><?= e(format_datetime($event['received_at'] ?? null)) ?></td>
                <td data-label="Cliente"><?= e($event['trade_name'] ?: $event['legal_name']) ?></td>
                <td data-label="Sucursal"><?= e($event['branch_name'] ?: 'Sin sucursal') ?></td>
                <td data-label="Tipo"><span class="badge badge-blue"><?= e($event['event_type']) ?></span></td>
                <td data-label="Evento" class="td-mono td-mono--compact"><?= e($event['event_uid']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/layout-footer.php'; ?>
