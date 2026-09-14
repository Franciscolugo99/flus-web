<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_admin_login();

$pageTitle = 'Instancias FLUS Web';
$activeNav = 'web-instances';
$error = null;
$instances = [];
$status = trim((string) ($_GET['status'] ?? ''));
$clientId = (int) ($_GET['client_id'] ?? 0);
$search = trim((string) ($_GET['q'] ?? ''));

try {
    $pdo = admin_db();
    $where = [];
    $params = [];
    if ($status !== '' && array_key_exists($status, admin_web_instance_statuses())) {
        $where[] = 'wi.status = :status';
        $params['status'] = $status;
    }
    if ($clientId > 0) {
        $where[] = 'wi.client_id = :client_id';
        $params['client_id'] = $clientId;
    }
    if ($search !== '') {
        $where[] = '(wi.slug LIKE :search OR wi.primary_domain LIKE :search OR wi.db_name LIKE :search OR c.legal_name LIKE :search OR c.trade_name LIKE :search)';
        $params['search'] = '%' . $search . '%';
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $stmt = $pdo->prepare("
        SELECT wi.*, c.legal_name, c.trade_name, l.license_key
        FROM web_instances wi
        INNER JOIN clients c ON c.id = wi.client_id
        LEFT JOIN licenses l ON l.id = wi.license_id
        {$whereSql}
        ORDER BY wi.updated_at DESC, wi.id DESC
    ");
    $stmt->execute($params);
    $instances = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = admin_public_error($e, 'No se pudieron cargar las instancias web. Ejecuta primero la migracion web_instances.sql.');
}

require __DIR__ . '/includes/layout-header.php';
?>

<?php if ($error): ?>
  <div class="alert alert-error"><?= e($error) ?></div>
<?php endif; ?>

<div class="toolbar">
  <form method="get" class="toolbar-filters">
    <?php if ($clientId > 0): ?>
      <input type="hidden" name="client_id" value="<?= $clientId ?>">
    <?php endif; ?>
    <input type="search" name="q" value="<?= e($search) ?>" placeholder="Cliente, dominio o base" aria-label="Buscar instancias">
    <select name="status" aria-label="Filtrar por estado">
      <option value="">Todos los estados</option>
      <?php foreach (admin_web_instance_statuses() as $value => $label): ?>
        <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="button button--ghost">Filtrar</button>
    <?php if ($search !== '' || $status !== ''): ?>
      <a class="button button--ghost" href="<?= e(admin_url('web-instances.php' . ($clientId > 0 ? '?client_id=' . $clientId : ''))) ?>">Limpiar</a>
    <?php endif; ?>
  </form>
  <a class="button" href="<?= e(admin_url('web-instance-edit.php' . ($clientId > 0 ? '?client_id=' . $clientId : ''))) ?>">Nueva instancia</a>
</div>

<div class="table-wrap table-wrap--mobile-cards">
  <table>
    <thead>
      <tr>
        <th>Instancia</th>
        <th>Cliente</th>
        <th>Estado</th>
        <th>Preparación</th>
        <th>Versión</th>
        <th>Base</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$instances): ?>
        <tr><td colspan="7" class="empty-state">Todavia no hay instancias FLUS Web registradas.</td></tr>
      <?php else: ?>
        <?php foreach ($instances as $instance): ?>
          <?php $clientName = (string) ($instance['trade_name'] ?: $instance['legal_name']); ?>
          <tr>
            <td data-label="Instancia">
              <strong><?= e($instance['slug']) ?></strong><br>
              <a href="https://<?= e($instance['primary_domain']) ?>" target="_blank" rel="noopener noreferrer"><?= e($instance['primary_domain']) ?></a>
            </td>
            <td data-label="Cliente"><a href="<?= e(admin_url('client-view.php?id=' . (int) $instance['client_id'])) ?>"><?= e($clientName) ?></a></td>
            <td data-label="Estado"><?= admin_web_instance_badge((string) $instance['status']) ?></td>
            <td data-label="Preparación"><?= e(admin_web_instance_provisioning_statuses()[$instance['provisioning_status']] ?? $instance['provisioning_status']) ?></td>
            <td data-label="Versión"><?= e((string) ($instance['app_version'] ?: 'Sin informar')) ?></td>
            <td data-label="Base"><span class="td-mono"><?= e($instance['db_host'] . '/' . $instance['db_name']) ?></span></td>
            <td data-label="Acciones"><a class="button button--ghost" href="<?= e(admin_url('web-instance-edit.php?id=' . (int) $instance['id'])) ?>">Administrar</a></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
