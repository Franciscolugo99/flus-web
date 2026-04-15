<?php
declare(strict_types=1);
$page_title  = 'Dashboard';
$active_menu = 'dashboard';
require_once __DIR__ . '/includes/layout-header.php';

$total_clients     = (int)$pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn();
$active_clients    = (int)$pdo->query("SELECT COUNT(*) FROM clients WHERE status = 'activo'")->fetchColumn();
$new_clients_month = (int)$pdo->query("SELECT COUNT(*) FROM clients WHERE DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')")->fetchColumn();
$active_licenses   = (int)$pdo->query("SELECT COUNT(*) FROM licenses WHERE status = 'activa'")->fetchColumn();
$expiring_7d       = (int)$pdo->query("SELECT COUNT(*) FROM licenses WHERE expires_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status NOT IN ('vencida','suspendida')")->fetchColumn();
$expiring_soon     = (int)$pdo->query("SELECT COUNT(*) FROM licenses WHERE expires_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY) AND status NOT IN ('vencida','suspendida')")->fetchColumn();
$expired_count     = (int)$pdo->query("SELECT COUNT(*) FROM licenses WHERE expires_at < CURDATE() AND status != 'suspendida'")->fetchColumn();
$month_revenue     = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE paid_at >= DATE_FORMAT(CURDATE(),'%Y-%m-01')")->fetchColumn();
$prev_revenue      = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE paid_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH),'%Y-%m-01') AND paid_at < DATE_FORMAT(CURDATE(),'%Y-%m-01')")->fetchColumn();
$total_revenue     = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments")->fetchColumn();
$year_revenue      = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE YEAR(paid_at) = YEAR(CURDATE())")->fetchColumn();
$revenue_trend     = $prev_revenue > 0 ? (($month_revenue - $prev_revenue) / $prev_revenue) * 100 : 0;

$chart_months = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $chart_months[$m] = ['label' => date('M Y', strtotime("-$i months")), 'revenue' => 0.0, 'clients' => 0];
}
foreach ($pdo->query("SELECT DATE_FORMAT(paid_at,'%Y-%m') m, COALESCE(SUM(amount),0) t FROM payments WHERE paid_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY m")->fetchAll() as $r) {
    if (isset($chart_months[$r['m']])) $chart_months[$r['m']]['revenue'] = (float)$r['t'];
}
foreach ($pdo->query("SELECT DATE_FORMAT(created_at,'%Y-%m') m, COUNT(*) t FROM clients WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY m")->fetchAll() as $r) {
    if (isset($chart_months[$r['m']])) $chart_months[$r['m']]['clients'] = (int)$r['t'];
}
$chart_labels  = array_values(array_column($chart_months, 'label'));
$chart_revenue = array_values(array_column($chart_months, 'revenue'));
$chart_clients = array_values(array_column($chart_months, 'clients'));

$recent_payments = $pdo->query("SELECT p.*, c.legal_name, c.trade_name FROM payments p JOIN clients c ON c.id = p.client_id ORDER BY p.paid_at DESC, p.id DESC LIMIT 8")->fetchAll();
$upcoming        = $pdo->query("SELECT l.*, c.legal_name, c.trade_name, DATEDIFF(l.expires_at, CURDATE()) AS days_left FROM licenses l JOIN clients c ON c.id = l.client_id WHERE l.expires_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY) AND l.status NOT IN ('vencida','suspendida') ORDER BY l.expires_at ASC LIMIT 8")->fetchAll();
$expired_recent  = $pdo->query("SELECT l.*, c.legal_name, c.trade_name FROM licenses l JOIN clients c ON c.id = l.client_id WHERE l.expires_at < CURDATE() AND l.status NOT IN ('suspendida') ORDER BY l.expires_at DESC LIMIT 5")->fetchAll();
?>
<div class="cards-grid">
  <div class="stat-card accent">
    <div class="stat-label">Clientes activos</div>
    <div class="stat-value"><?= $active_clients ?></div>
    <div class="stat-sub">de <?= $total_clients ?> totales</div>
    <?php if ($new_clients_month > 0): ?><span class="stat-trend up">+<?= $new_clients_month ?> este mes</span><?php endif; ?>
  </div>
  <div class="stat-card info">
    <div class="stat-label">Licencias activas</div>
    <div class="stat-value"><?= $active_licenses ?></div>
    <div class="stat-sub">en producción</div>
  </div>
  <div class="stat-card <?= $expiring_7d > 0 ? 'danger' : 'warn' ?>">
    <div class="stat-label">Por vencer (15 días)</div>
    <div class="stat-value"><?= $expiring_soon ?></div>
    <div class="stat-sub">
      <?php if ($expiring_7d > 0): ?><span style="color:var(--red)">⚠ <?= $expiring_7d ?> en 7 días</span>
      <?php else: ?><a href="<?= admin_url('expirations.php') ?>">Ver detalle →</a><?php endif; ?>
    </div>
  </div>
  <div class="stat-card <?= $expired_count > 0 ? 'danger' : '' ?>">
    <div class="stat-label">Vencidas</div>
    <div class="stat-value"><?= $expired_count ?></div>
    <div class="stat-sub"><a href="<?= admin_url('expirations.php') ?>">Gestionar →</a></div>
  </div>
  <div class="stat-card accent">
    <div class="stat-label">Ingresos del mes</div>
    <div class="stat-value" style="font-size:1.3rem"><?= format_money($month_revenue) ?></div>
    <?php $td=$revenue_trend>0?'up':($revenue_trend<0?'down':'flat'); $ti=$revenue_trend>0?'↑':($revenue_trend<0?'↓':'→'); $ts=$revenue_trend>=0?'+':''; ?>
    <span class="stat-trend <?= $td ?>"><?= $ti ?> <?= $ts.round(abs($revenue_trend),1) ?>% vs mes ant.</span>
  </div>
  <div class="stat-card">
    <div class="stat-label">Ingresos <?= date('Y') ?></div>
    <div class="stat-value" style="font-size:1.25rem"><?= format_money($year_revenue) ?></div>
    <div class="stat-sub">Histórico: <?= format_money($total_revenue) ?></div>
  </div>
</div>

<?php if (!empty($expired_recent)): ?>
<div class="alert alert-error" style="margin-bottom:18px;display:flex;align-items:center;gap:12px">
  <div style="flex:1">
    <strong>⚠ <?= count($expired_recent) ?> licencia<?= count($expired_recent)!==1?'s':'' ?> vencida<?= count($expired_recent)!==1?'s':'' ?>:</strong>
    <?= implode(', ', array_map(fn($l)=>e($l['trade_name']?:$l['legal_name']), array_slice($expired_recent,0,3))) ?>
    <?= count($expired_recent)>3?' y más...':'' ?>
  </div>
  <a href="<?= admin_url('expirations.php') ?>" class="btn btn-danger btn-sm" style="flex-shrink:0">Gestionar</a>
</div>
<?php endif; ?>

<div class="chart-panel">
  <div class="chart-panel-header">
    <div>
      <div class="chart-panel-title">📈 Evolución de ingresos — últimos 6 meses</div>
      <div class="chart-panel-subtitle">Barras = ingresos · Línea = nuevos clientes</div>
    </div>
    <a href="<?= admin_url('analytics.php') ?>" class="btn btn-secondary btn-sm">Analíticas completas →</a>
  </div>
  <div class="chart-panel-body">
    <div class="chart-container" style="height:210px"><canvas id="dashChart"></canvas></div>
  </div>
  <div class="chart-legend">
    <div class="chart-legend-item"><span class="chart-legend-dot" style="background:#00c896"></span>Ingresos</div>
    <div class="chart-legend-item"><span class="chart-legend-dot" style="background:#3b82f6"></span>Clientes nuevos</div>
  </div>
</div>

<div class="dash-grid">
  <div class="dash-panel">
    <div class="dash-panel-header">💰 Últimos pagos <a href="<?= admin_url('payments.php') ?>" style="margin-left:auto;font-weight:400;font-size:.76rem">Ver todos →</a></div>
    <div class="dash-panel-body">
      <?php if (empty($recent_payments)): ?>
        <div class="empty-panel">Sin pagos registrados.</div>
      <?php else: foreach($recent_payments as $pay): ?>
        <div class="dash-row">
          <span class="dash-row-label"><?= e($pay['trade_name']?:$pay['legal_name']) ?></span>
          <span class="dash-row-amount"><?= format_money($pay['amount']) ?></span>
          <span class="dash-row-meta"><?= format_date($pay['paid_at']) ?></span>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
  <div class="dash-panel">
    <div class="dash-panel-header">⏰ Próximos vencimientos <a href="<?= admin_url('expirations.php') ?>" style="margin-left:auto;font-weight:400;font-size:.76rem">Ver todos →</a></div>
    <div class="dash-panel-body">
      <?php if (empty($upcoming)): ?>
        <div class="empty-panel">Sin vencimientos próximos. 🎉</div>
      <?php else: foreach($upcoming as $lic): $days=(int)$lic['days_left']; ?>
        <div class="dash-row">
          <span class="dash-row-label"><a href="<?= admin_url('client-view.php?id='.$lic['client_id']) ?>" style="color:inherit"><?= e($lic['trade_name']?:$lic['legal_name']) ?></a></span>
          <span class="badge <?= $days<=3?'badge-red':($days<=7?'badge-yellow':'badge-blue') ?>" style="font-size:.63rem"><?= $days ?>d</span>
          <span class="dash-row-meta"><?= format_date($lic['expires_at']) ?></span>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<script>
(function(){
  var ctx=document.getElementById('dashChart');
  if(!ctx||typeof Chart==='undefined') return;
  new Chart(ctx,{type:'bar',data:{labels:<?= json_encode($chart_labels) ?>,datasets:[
    {label:'Ingresos',data:<?= json_encode($chart_revenue) ?>,backgroundColor:'rgba(0,200,150,.18)',borderColor:'#00c896',borderWidth:2,borderRadius:5,yAxisID:'y'},
    {label:'Clientes',data:<?= json_encode($chart_clients) ?>,type:'line',borderColor:'#3b82f6',backgroundColor:'rgba(59,130,246,.07)',borderWidth:2,pointBackgroundColor:'#3b82f6',pointRadius:4,tension:0.35,fill:true,yAxisID:'y1'}
  ]},options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},
    plugins:{legend:{display:false},tooltip:{backgroundColor:'#1e2438',borderColor:'#252d45',borderWidth:1,titleColor:'#e2e6f0',bodyColor:'#8993ad',padding:10,
      callbacks:{label:function(c){return c.dataset.label==='Ingresos'?' $'+Number(c.parsed.y).toLocaleString('es-AR',{minimumFractionDigits:2}):'  '+c.parsed.y+' clientes';}}}},
    scales:{x:{grid:{color:'rgba(37,45,69,.4)'},ticks:{color:'#4e586d',font:{size:11}}},
      y:{position:'left',grid:{color:'rgba(37,45,69,.4)'},ticks:{color:'#4e586d',font:{size:11},callback:function(v){return '$'+Number(v).toLocaleString('es-AR');}}},
      y1:{position:'right',grid:{display:false},ticks:{color:'#4e586d',font:{size:11},stepSize:1}}}}});
})();
</script>
<?php require_once __DIR__ . '/includes/layout-footer.php'; ?>
