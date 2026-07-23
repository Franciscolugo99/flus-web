<?php
declare(strict_types=1);

$page_title = 'Sucursales cloud';
$active_menu = 'cloud-sync';
require_once __DIR__ . '/includes/layout-header.php';

$schemaReady = admin_cloud_sync_ensure_schema($pdo);
$installations = $schemaReady ? admin_cloud_sync_recent_installations($pdo, 50) : [];
$events = $schemaReady ? admin_cloud_sync_recent_events($pdo, 30) : [];

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
if ($schemaReady) {
    $branchesCount = (int) $pdo->query('SELECT COUNT(*) FROM client_branches')->fetchColumn();
    $eventsToday = (int) $pdo->query('SELECT COUNT(*) FROM cloud_sync_events WHERE DATE(received_at) = UTC_DATE()')->fetchColumn();
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
        <span class="ops-card-label">Eventos hoy</span>
        <strong><?= $eventsToday ?></strong>
        <span>Recibidos por la API</span>
      </div>
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
            <th>Version</th>
            <th>Ultimo contacto</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$installations): ?>
            <tr class="empty-row"><td colspan="7">Todavia no hay instalaciones sincronizadas.</td></tr>
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
                  <a href="<?= admin_url('client-view.php?id=' . (int) $row['client_id']) ?>" style="color:inherit">
                    <?= e($row['trade_name'] ?: $row['legal_name']) ?>
                  </a>
                </td>
                <td data-label="Sucursal"><?= e($row['branch_name'] ?: 'Sin sucursal') ?></td>
                <td data-label="Instalacion" class="td-mono td-mono--compact"><?= e($row['installation_uid']) ?></td>
                <td data-label="Licencia" class="td-mono td-mono--compact"><?= e($row['license_key']) ?></td>
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
