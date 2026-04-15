<?php
declare(strict_types=1);
// ============================================================
// FLUS Admin — Ver detalle de cliente v2.0
// ============================================================
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php';
admin_start_session();
require_admin_login();
$pdo = admin_db();

// Compat helpers (se redefinen en layout-header, aqui para el bloque pre-header)
if (!function_exists('redirect_with_flash')) {
    function redirect_with_flash(string $url, string $type, string $msg): void {
        set_flash($type, $msg); redirect_to($url);
    }
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect_with_flash(admin_url('clients.php'), 'error', 'Cliente no encontrado.');

$stmt = $pdo->prepare('SELECT * FROM clients WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$client = $stmt->fetch();
if (!$client) redirect_with_flash(admin_url('clients.php'), 'error', 'Cliente no encontrado.');

// Licencias del cliente
$licenses_stmt = $pdo->prepare('SELECT * FROM licenses WHERE client_id = ? ORDER BY expires_at DESC');
$licenses_stmt->execute([$id]);
$licenses = $licenses_stmt->fetchAll();

// Todos los pagos
$payments_stmt = $pdo->prepare('
    SELECT p.*, l.license_key FROM payments p
    LEFT JOIN licenses l ON l.id = p.license_id
    WHERE p.client_id = ?
    ORDER BY p.paid_at DESC, p.id DESC
');
$payments_stmt->execute([$id]);
$payments = $payments_stmt->fetchAll();

// Métricas financieras
$r = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE client_id = ?');
$r->execute([$id]); $total_paid = (float)$r->fetchColumn();

$r = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE client_id = ? AND paid_at >= DATE_FORMAT(CURDATE(),"%Y-%m-01")');
$r->execute([$id]); $paid_this_month = (float)$r->fetchColumn();

$r = $pdo->prepare('SELECT COUNT(*) FROM payments WHERE client_id = ?');
$r->execute([$id]); $total_payments = (int)$r->fetchColumn();

$r = $pdo->prepare('SELECT MAX(paid_at) FROM payments WHERE client_id = ?');
$r->execute([$id]); $last_payment_date = $r->fetchColumn();

$avg_payment = $total_payments > 0 ? $total_paid / $total_payments : 0;

// Licencia activa
$active_license = null;
foreach ($licenses as $lic) {
    if ($lic['status'] === 'activa') { $active_license = $lic; break; }
}
if (!$active_license && !empty($licenses)) $active_license = $licenses[0];

// Mini chart — ingresos últimos 6 meses
$mini_months = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $mini_months[$m] = ['label' => date('M', strtotime("-$i months")), 'total' => 0];
}
$mini_rows = $pdo->prepare("
    SELECT DATE_FORMAT(paid_at,'%Y-%m') AS m, COALESCE(SUM(amount),0) AS total
    FROM payments WHERE client_id = ? AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(paid_at,'%Y-%m')
");
$mini_rows->execute([$id]);
foreach ($mini_rows->fetchAll() as $row) {
    if (isset($mini_months[$row['m']])) $mini_months[$row['m']]['total'] = (float)$row['total'];
}

// Acción: eliminar
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['delete_client'])) {
    csrf_check();
    $has_rel = $pdo->prepare('SELECT COUNT(*) FROM licenses WHERE client_id = ?');
    $has_rel->execute([$id]);
    if ((int)$has_rel->fetchColumn() > 0) {
        flash('error', 'No se puede eliminar un cliente con licencias asociadas.');
    } else {
        $pdo->prepare('DELETE FROM clients WHERE id = ?')->execute([$id]);
        redirect_with_flash(admin_url('clients.php'), 'success', 'Cliente eliminado.');
    }
}

$page_title  = 'Cliente: ' . ($client['trade_name'] ?: $client['legal_name']);
$active_menu = 'clients';
require_once __DIR__ . '/includes/layout-header.php';
?>

<!-- Barra de acciones -->
<div style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap;align-items:center">
  <a href="<?= admin_url('clients.php') ?>" class="btn btn-secondary btn-sm">← Clientes</a>
  <a href="<?= admin_url('client-edit.php?id=' . $id) ?>" class="btn btn-primary btn-sm">✎ Editar</a>
  <a href="<?= admin_url('license-edit.php?client_id=' . $id) ?>" class="btn btn-secondary btn-sm">+ Licencia</a>
  <a href="<?= admin_url('payment-edit.php?client_id=' . $id) ?>" class="btn btn-secondary btn-sm">+ Pago</a>
  <form method="POST" action="" style="margin-left:auto">
    <?= csrf_field() ?>
    <button type="submit" name="delete_client" value="1" class="btn btn-ghost btn-sm"
            data-confirm="¿Eliminar este cliente? Esta acción no se puede deshacer.">🗑 Eliminar</button>
  </form>
</div>

<!-- Grid: datos + finanzas -->
<div style="display:grid;grid-template-columns:1.2fr 1fr;gap:16px;margin-bottom:20px" class="client-view-grid">

  <!-- Datos del cliente -->
  <div class="detail-card" style="margin-bottom:0">
    <div class="detail-card-header">
      Datos del cliente
      <?= client_status_badge($client['status']) ?>
      <?php if ($active_license): ?>
        <?= license_status_badge($active_license['status']) ?>
      <?php endif; ?>
    </div>
    <div class="detail-grid">
      <div class="detail-field">
        <div class="detail-field-label">Razón social</div>
        <div class="detail-field-value"><?= e($client['legal_name']) ?></div>
      </div>
      <div class="detail-field">
        <div class="detail-field-label">Nombre comercial</div>
        <div class="detail-field-value"><?= e($client['trade_name'] ?: '—') ?></div>
      </div>
      <div class="detail-field">
        <div class="detail-field-label">Email</div>
        <div class="detail-field-value"><a href="mailto:<?= e($client['email']) ?>"><?= e($client['email']) ?></a></div>
      </div>
      <div class="detail-field">
        <div class="detail-field-label">Teléfono</div>
        <div class="detail-field-value">
          <?php if ($client['phone']): ?>
            <a href="tel:<?= e($client['phone']) ?>"><?= e($client['phone']) ?></a>
          <?php else: ?>—<?php endif; ?>
        </div>
      </div>
      <div class="detail-field">
        <div class="detail-field-label">CUIT / DNI</div>
        <div class="detail-field-value"><?= e($client['tax_id'] ?: '—') ?></div>
      </div>
      <div class="detail-field">
        <div class="detail-field-label">Rubro</div>
        <div class="detail-field-value"><?= e($client['business_type'] ?: '—') ?></div>
      </div>
      <div class="detail-field">
        <div class="detail-field-label">Dirección</div>
        <div class="detail-field-value"><?= e($client['address'] ?: '—') ?></div>
      </div>
      <div class="detail-field">
        <div class="detail-field-label">Alta en sistema</div>
        <div class="detail-field-value"><?= format_datetime($client['created_at']) ?></div>
      </div>
      <?php if ($client['internal_notes']): ?>
      <div class="detail-field" style="grid-column:1/-1;border-right:none">
        <div class="detail-field-label">Notas internas</div>
        <div class="detail-field-value" style="white-space:pre-line"><?= e($client['internal_notes']) ?></div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Resumen financiero -->
  <div class="detail-card" style="margin-bottom:0">
    <div class="detail-card-header">💰 Resumen financiero</div>

    <div class="revenue-summary">
      <div class="revenue-summary-item">
        <div class="revenue-summary-label">Total pagado</div>
        <div class="revenue-summary-value"><?= format_money($total_paid) ?></div>
        <div class="revenue-summary-sub"><?= $total_payments ?> pago<?= $total_payments !== 1 ? 's' : '' ?></div>
      </div>
      <div class="revenue-summary-item">
        <div class="revenue-summary-label">Este mes</div>
        <div class="revenue-summary-value"><?= format_money($paid_this_month) ?></div>
        <div class="revenue-summary-sub"><?= date('M Y') ?></div>
      </div>
      <div class="revenue-summary-item">
        <div class="revenue-summary-label">Ticket promedio</div>
        <div class="revenue-summary-value"><?= format_money($avg_payment) ?></div>
        <div class="revenue-summary-sub">por pago</div>
      </div>
    </div>

    <div style="padding:14px 16px 10px">
      <div style="font-size:.7rem;color:var(--text-muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:.06em;font-weight:700">
        Ingresos últimos 6 meses
      </div>
      <div style="height:75px">
        <canvas id="clientMiniChart"></canvas>
      </div>
    </div>

    <?php if ($last_payment_date): ?>
    <div class="detail-field" style="border-right:none">
      <div class="detail-field-label">Último pago</div>
      <div class="detail-field-value"><?= format_date($last_payment_date) ?></div>
    </div>
    <?php endif; ?>

    <?php if ($active_license): ?>
    <div class="detail-field" style="border-right:none">
      <div class="detail-field-label">Licencia vigente</div>
      <div class="detail-field-value">
        <span class="td-mono"><?= e($active_license['license_key']) ?></span>
        <button class="btn btn-secondary btn-xs" style="margin-left:5px" data-copy="<?= e($active_license['license_key']) ?>">Copiar</button>
        <br><span style="font-size:.75rem;color:var(--text-muted)">
          <?= plan_type_label($active_license['plan_type']) ?> · vence: <?= format_date($active_license['expires_at']) ?>
        </span>
      </div>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- Licencias -->
<div class="section-header">
  <div class="section-title">🔑 Licencias</div>
  <a href="<?= admin_url('license-edit.php?client_id=' . $id) ?>" class="btn btn-secondary btn-sm">+ Agregar</a>
</div>

<div class="table-wrapper" style="margin-bottom:20px">
  <table>
    <thead>
      <tr>
        <th>Clave</th><th>Plan</th><th>Estado</th><th>Inicio</th><th>Vencimiento</th><th>Puestos</th><th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($licenses)): ?>
        <tr class="empty-row"><td colspan="7">Sin licencias.</td></tr>
      <?php else: ?>
        <?php foreach ($licenses as $lic): ?>
          <tr>
            <td>
              <span class="td-mono"><?= e($lic['license_key']) ?></span>
              <button class="btn btn-secondary btn-xs" style="margin-left:4px" data-copy="<?= e($lic['license_key']) ?>">Copiar</button>
            </td>
            <td><?= plan_type_label($lic['plan_type']) ?></td>
            <td><?= license_status_badge($lic['status']) ?></td>
            <td><?= format_date($lic['starts_at']) ?></td>
            <td <?= strtotime($lic['expires_at']) < time() ? 'style="color:var(--red)"' : '' ?>><?= format_date($lic['expires_at']) ?></td>
            <td><?= $lic['seats'] ?? '—' ?></td>
            <td><a href="<?= admin_url('license-edit.php?id=' . $lic['id']) ?>" class="btn btn-secondary btn-xs">Editar</a></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Historial de pagos -->
<div class="section-header">
  <div class="section-title">📋 Historial de pagos</div>
  <div style="display:flex;align-items:center;gap:8px">
    <span style="font-size:.78rem;color:var(--text-muted)"><?= $total_payments ?> registros · total: <?= format_money($total_paid) ?></span>
    <a href="<?= admin_url('payment-edit.php?client_id=' . $id) ?>" class="btn btn-secondary btn-sm">+ Cargar pago</a>
  </div>
</div>

<div class="table-wrapper">
  <table>
    <thead>
      <tr>
        <th>Fecha</th><th>Período</th><th>Monto</th><th>Método</th><th>Licencia</th><th>Referencia</th><th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($payments)): ?>
        <tr class="empty-row"><td colspan="7">Sin pagos registrados.</td></tr>
      <?php else: ?>
        <?php foreach ($payments as $pay): ?>
          <tr>
            <td><?= format_date($pay['paid_at']) ?></td>
            <td class="td-secondary">
              <?= ($pay['period_from'] && $pay['period_to'])
                  ? format_date($pay['period_from']) . ' – ' . format_date($pay['period_to'])
                  : '—' ?>
            </td>
            <td><strong><?= format_money($pay['amount']) ?></strong></td>
            <td><?= payment_method_label($pay['method']) ?></td>
            <td class="td-mono" style="font-size:.76rem"><?= e($pay['license_key'] ?? '—') ?></td>
            <td class="td-secondary"><?= e($pay['reference'] ?? '—') ?></td>
            <td><a href="<?= admin_url('payment-edit.php?id=' . $pay['id']) ?>" class="btn btn-secondary btn-xs">Editar</a></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
(function() {
  const ctx = document.getElementById('clientMiniChart');
  if (!ctx || typeof Chart === 'undefined') return;
  const labels = <?= json_encode(array_column($mini_months, 'label')) ?>;
  const data   = <?= json_encode(array_column($mini_months, 'total')) ?>;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{ data, backgroundColor: 'rgba(0,200,150,.2)', borderColor: '#00c896', borderWidth: 1.5, borderRadius: 3 }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#1e2438', borderColor: '#252d45', borderWidth: 1,
          titleColor: '#e2e6f0', bodyColor: '#8993ad', padding: 8,
          callbacks: { label: c => ' $' + Number(c.parsed.y).toLocaleString('es-AR') }
        }
      },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#4e586d', font:{size:10} } },
        y: { grid: { color: 'rgba(37,45,69,.3)' }, ticks: { color: '#4e586d', font:{size:10}, callback: v => '$'+Number(v).toLocaleString('es-AR') } }
      }
    }
  });
})();
</script>

<style>
@media (max-width: 900px) { .client-view-grid { grid-template-columns: 1fr !important; } }
</style>

<?php require_once __DIR__ . '/includes/layout-footer.php'; ?>
