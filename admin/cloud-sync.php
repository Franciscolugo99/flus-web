<?php
declare(strict_types=1);

$page_title = 'Sucursales cloud';
$active_menu = 'cloud-sync';
require_once __DIR__ . '/includes/layout-header.php';

$schemaReady = admin_cloud_sync_ensure_schema($pdo);
$selectedClientId = max(0, (int) ($_GET['client_id'] ?? 0));
$clientFilter = $selectedClientId > 0 ? $selectedClientId : null;
$selectedClient = null;
$cloudClients = $schemaReady ? admin_cloud_sync_clients_overview($pdo, 80) : [];
$clientBranches = [];
if ($schemaReady && $selectedClientId > 0) {
    $clientStmt = $pdo->prepare('SELECT id, legal_name, trade_name FROM clients WHERE id = ? LIMIT 1');
    $clientStmt->execute([$selectedClientId]);
    $selectedClient = $clientStmt->fetch() ?: null;
    if ($selectedClient) {
        $clientBranches = admin_cloud_sync_client_branches($pdo, $selectedClientId);
    } else {
        $selectedClientId = 0;
        $clientFilter = null;
    }
}
$allowedClientViews = ['operacion', 'ventas', 'tecnico'];
$clientView = $selectedClient ? trim((string) ($_GET['view'] ?? 'operacion')) : '';
if ($selectedClient && !in_array($clientView, $allowedClientViews, true)) {
    $clientView = 'operacion';
}
$clientViewUrl = static function (string $view) use ($selectedClientId): string {
    return admin_url('cloud-sync.php?client_id=' . $selectedClientId . '&view=' . urlencode($view));
};
$installations = $schemaReady ? admin_cloud_sync_recent_installations($pdo, 50, $clientFilter) : [];
$events = $schemaReady ? admin_cloud_sync_recent_events($pdo, 30, $clientFilter) : [];
$salesOverview = $schemaReady ? admin_cloud_sync_sales_overview($pdo, $clientFilter) : [];
$recentSales = $schemaReady ? admin_cloud_sync_recent_sales($pdo, 12, $clientFilter) : [];

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
    if ($selectedClientId > 0) {
        $branchesCount = count($clientBranches);
        $eventsTodayStmt = $pdo->prepare('SELECT COUNT(*) FROM cloud_sync_events WHERE DATE(received_at) = UTC_DATE() AND client_id = ?');
        $eventsTodayStmt->execute([$selectedClientId]);
        $eventsToday = (int) $eventsTodayStmt->fetchColumn();
    } else {
        $branchesCount = (int) $pdo->query('SELECT COUNT(*) FROM client_branches')->fetchColumn();
        $eventsToday = (int) $pdo->query('SELECT COUNT(*) FROM cloud_sync_events WHERE DATE(received_at) = UTC_DATE()')->fetchColumn();
    }
    $cloudPlanWhere = "(LOWER(plan_type) LIKE '%cloud%' OR LOWER(plan_type) LIKE '%multi%' OR LOWER(plan_type) LIKE '%sucursal%' OR LOWER(plan_type) LIKE '%online%' OR LOWER(plan_type) LIKE '%web%')";
    $clientLicenseWhere = $selectedClientId > 0 ? ' AND client_id = ' . $selectedClientId : '';
    $cloudLicenses = (int) $pdo->query("
        SELECT COUNT(*)
        FROM licenses
        WHERE status NOT IN ('vencida','suspendida')
          AND expires_at >= CURDATE()
          {$clientLicenseWhere}
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
          {$clientLicenseWhere}
          AND {$cloudPlanWhere}
          AND (i.last_seen_at IS NULL OR i.last_seen_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 10 MINUTE))
    ")->fetchColumn();
    $localInstallations = (int) $pdo->query("
        SELECT COUNT(*)
        FROM client_installations i
        INNER JOIN licenses l ON l.id = i.license_id
        WHERE NOT {$cloudPlanWhere}
        " . ($selectedClientId > 0 ? " AND i.client_id = {$selectedClientId}" : '') . "
    ")->fetchColumn();
}
?>

<section class="admin-license-panel">
  <div class="admin-license-head">
    <div>
      <span class="eyebrow">Control operativo</span>
      <h1>Sucursales cloud</h1>
      <p><?= $selectedClient ? 'Vista filtrada por cliente, sucursales e instalaciones.' : 'Clientes, sucursales e instalaciones FLUS que reportan datos desde cada negocio.' ?></p>
    </div>
  </div>

  <?php if (!$schemaReady): ?>
    <div class="alert alert-error">No se pudo preparar el esquema de sincronizacion cloud.</div>
  <?php else: ?>
    <?php if ($selectedClient): ?>
      <div class="cloud-focus-bar">
        <div>
          <span class="eyebrow">Cliente seleccionado</span>
          <strong><?= e($selectedClient['trade_name'] ?: $selectedClient['legal_name']) ?></strong>
          <span>Los datos de ventas, stock, eventos e instalaciones estan filtrados por este cliente.</span>
        </div>
        <div class="cloud-focus-actions">
          <a href="<?= admin_url('client-view.php?id=' . $selectedClientId) ?>" class="button button--ghost">Ficha del cliente</a>
          <a href="<?= admin_url('cloud-sync.php') ?>" class="button button--ghost">Ver todos</a>
        </div>
      </div>

      <nav class="cloud-client-tabs" aria-label="Datos cloud del cliente">
        <a href="<?= e($clientViewUrl('operacion')) ?>" class="<?= $clientView === 'operacion' ? 'is-active' : '' ?>">Operacion</a>
        <a href="<?= e($clientViewUrl('ventas')) ?>" class="<?= $clientView === 'ventas' ? 'is-active' : '' ?>">Ventas</a>
        <a href="<?= e($clientViewUrl('tecnico')) ?>" class="<?= $clientView === 'tecnico' ? 'is-active' : '' ?>">Tecnico</a>
      </nav>
    <?php endif; ?>

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
      <?php if ($selectedClient): ?>
        <div class="ops-card ops-card--info">
          <span class="ops-card-label">Eventos hoy</span>
          <strong><?= $eventsToday ?></strong>
          <span>Recibidos por la API</span>
        </div>
      <?php endif; ?>
    </div>

    <?php if (!$selectedClient): ?>
      <div class="section-header section-header--spaced">
        <div>
          <div class="section-title">Clientes cloud</div>
          <div class="section-meta">Estado de plan, sucursales e instalaciones. Los datos comerciales se ven al entrar al cliente.</div>
        </div>
      </div>

      <div class="cloud-client-list">
        <?php if (!$cloudClients): ?>
          <div class="empty-panel">Todavia no hay clientes con cloud, sucursales o instalaciones sincronizadas.</div>
        <?php else: ?>
          <?php foreach ($cloudClients as $cloudClient): ?>
            <?php
              $clientName = (string) ($cloudClient['trade_name'] ?: $cloudClient['legal_name']);
              $planTypes = trim((string) ($cloudClient['cloud_plan_types'] ?? ''));
              $planLabels = [];
              foreach (array_filter(array_map('trim', explode(',', $planTypes))) as $planType) {
                  $planLabels[] = plan_type_label($planType);
              }
              $installationsCount = (int) ($cloudClient['installations_count'] ?? 0);
              $onlineCount = (int) ($cloudClient['online_count'] ?? 0);
            ?>
            <article class="cloud-client-row">
              <div class="cloud-client-main">
                <strong><?= e($clientName) ?></strong>
                <span><?= $planLabels ? e(implode(', ', array_unique($planLabels))) : 'Sin plan cloud activo' ?></span>
              </div>
              <div class="cloud-client-metrics">
                <span><strong><?= (int) ($cloudClient['active_branches_count'] ?? 0) ?></strong>Sucursales</span>
                <span><strong><?= $installationsCount ?></strong>Instalaciones</span>
                <span><strong><?= $onlineCount ?></strong>Online</span>
              </div>
              <div class="cloud-client-actions">
                <span>Ultimo contacto: <?= e(format_datetime($cloudClient['last_seen_at'] ?? null)) ?></span>
                <a href="<?= admin_url('cloud-sync.php?client_id=' . (int) $cloudClient['client_id']) ?>" class="button button--compact">Ver datos</a>
                <a href="<?= admin_url('client-view.php?id=' . (int) $cloudClient['client_id']) ?>" class="button button--ghost button--compact">Cliente</a>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    <?php elseif ($clientView === 'operacion'): ?>
      <div class="section-header section-header--spaced">
        <div>
          <div class="section-title">Sucursales del cliente</div>
          <div class="section-meta">Puntos activos reportados por las instalaciones FLUS de este comercio.</div>
        </div>
      </div>

      <div class="cloud-branch-list">
        <?php if (!$clientBranches): ?>
          <div class="empty-panel">Este cliente todavia no tiene sucursales sincronizadas.</div>
        <?php else: ?>
          <?php foreach ($clientBranches as $branch): ?>
            <?php $branchOnline = (int) ($branch['online_count'] ?? 0); ?>
            <article class="cloud-branch-row">
              <div>
                <strong><?= e((string) ($branch['branch_name'] ?: 'Sin sucursal')) ?></strong>
                <span><?= e((string) ($branch['branch_code'] ?: 'sin codigo')) ?></span>
              </div>
              <div class="cloud-branch-metrics">
                <span><strong><?= (int) ($branch['installations_count'] ?? 0) ?></strong>Instalaciones</span>
                <span><strong><?= $branchOnline ?></strong>Online</span>
                <span><strong><?= (int) ($branch['stock_items'] ?? 0) ?></strong>Productos stock</span>
                <span>Ultimo contacto: <?= e(format_datetime($branch['last_seen_at'] ?? null)) ?></span>
              </div>
              <span class="badge <?= $branchOnline > 0 ? 'badge-green' : 'badge-yellow' ?>"><?= $branchOnline > 0 ? 'Operativa' : 'Sin contacto' ?></span>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($selectedClient && $clientView === 'ventas'): ?>
      <div class="section-header section-header--spaced">
        <div>
          <div class="section-title">Actividad comercial sincronizada</div>
          <div class="section-meta">Lectura resumida de ventas recibidas en las ultimas 24 hs para este cliente.</div>
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
              <div class="section-meta">Importe recibido por medio informado por el POS.</div>
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
                  $branchName = (string) ($sale['branch_name'] ?: 'Sin sucursal');
                ?>
                <article class="cloud-sale-item">
                  <div>
                    <strong><?= e($branchName) ?></strong>
                    <span>Venta <?= $saleId > 0 ? '#' . $saleId : 'sin numero' ?></span>
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
    <?php endif; ?>

    <?php if ($selectedClient && $clientView === 'operacion'): ?>
      <div class="section-header">
        <div>
          <div class="section-title">Instalaciones recientes</div>
          <div class="section-meta">PCs del cliente que reportaron contra FLUS Web.</div>
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
    <?php endif; ?>

    <?php if ($selectedClient && $clientView === 'tecnico'): ?>
      <div class="section-header">
        <div>
          <div class="section-title">Eventos recientes</div>
          <div class="section-meta">Resumen tecnico para auditar llegada de ventas, caja, stock y alertas de este cliente.</div>
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
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/layout-footer.php'; ?>
