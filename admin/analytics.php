<?php
// ============================================================
// FLUS Admin — Analíticas & Crecimiento
// ============================================================
$page_title  = 'Analíticas';
$active_menu = 'analytics';

require_once __DIR__ . '/includes/layout-header.php';

// ---- Período seleccionado ----
$period = (int)($_GET['months'] ?? 12);
if (!in_array($period, [3, 6, 12])) $period = 12;

// ============================================================
// KPIs GLOBALES
// ============================================================
$kpi = [];

$r = $pdo->query("SELECT COUNT(*) FROM clients WHERE status = 'activo'");
$kpi['active_clients'] = (int)$r->fetchColumn();

$r = $pdo->query("SELECT COUNT(*) FROM clients WHERE DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')");
$kpi['new_this_month'] = (int)$r->fetchColumn();

$r = $pdo->query("SELECT COUNT(*) FROM clients WHERE DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH),'%Y-%m')");
$kpi['new_last_month'] = (int)$r->fetchColumn();

$r = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE paid_at >= DATE_FORMAT(CURDATE(),'%Y-%m-01')");
$kpi['mrr'] = (float)$r->fetchColumn();

$r = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE YEAR(paid_at) = YEAR(CURDATE())");
$kpi['arr_ytd'] = (float)$r->fetchColumn();

$r = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments");
$kpi['total_revenue'] = (float)$r->fetchColumn();

$r = $pdo->query("SELECT COUNT(*) FROM licenses WHERE status = 'activa'");
$kpi['active_licenses'] = (int)$r->fetchColumn();

$r = $pdo->query("SELECT COUNT(*) FROM licenses WHERE expires_at < CURDATE() AND status != 'suspendida'");
$kpi['expired_licenses'] = (int)$r->fetchColumn();

// LTV promedio (ingresos / clientes activos)
$kpi['avg_ltv'] = $kpi['active_clients'] > 0 ? $kpi['total_revenue'] / max($kpi['active_clients'], 1) : 0;

// Ticket promedio por pago
$r = $pdo->query("SELECT COALESCE(AVG(amount),0) FROM payments");
$kpi['avg_ticket'] = (float)$r->fetchColumn();

// Tasa retención estimada (clientes activos / total)
$r = $pdo->query("SELECT COUNT(*) FROM clients");
$total_clients = max(1, (int)$r->fetchColumn());
$kpi['retention_rate'] = round(($kpi['active_clients'] / $total_clients) * 100, 1);

// Crecimiento clientes mes a mes
$growth_pct = $kpi['new_last_month'] > 0
    ? round((($kpi['new_this_month'] - $kpi['new_last_month']) / $kpi['new_last_month']) * 100, 1)
    : 0;

// ============================================================
// INGRESOS POR MES (últimos N meses)
// ============================================================
$months_map = [];
for ($i = $period - 1; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $months_map[$m] = [
        'label'   => date('M Y', strtotime("-$i months")),
        'revenue' => 0,
        'count'   => 0,
        'clients' => 0,
    ];
}

$rev_rows = $pdo->query("
    SELECT DATE_FORMAT(paid_at,'%Y-%m') AS m,
           COALESCE(SUM(amount),0) AS total,
           COUNT(*) AS cnt
    FROM payments
    WHERE paid_at >= DATE_SUB(CURDATE(), INTERVAL {$period} MONTH)
    GROUP BY DATE_FORMAT(paid_at,'%Y-%m')
")->fetchAll();
foreach ($rev_rows as $row) {
    if (isset($months_map[$row['m']])) {
        $months_map[$row['m']]['revenue'] = (float)$row['total'];
        $months_map[$row['m']]['count']   = (int)$row['cnt'];
    }
}

$cli_rows = $pdo->query("
    SELECT DATE_FORMAT(created_at,'%Y-%m') AS m, COUNT(*) AS total
    FROM clients
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL {$period} MONTH)
    GROUP BY DATE_FORMAT(created_at,'%Y-%m')
")->fetchAll();
foreach ($cli_rows as $row) {
    if (isset($months_map[$row['m']])) $months_map[$row['m']]['clients'] = (int)$row['total'];
}

$chart_labels   = array_column($months_map, 'label');
$chart_revenue  = array_column($months_map, 'revenue');
$chart_payments = array_column($months_map, 'count');
$chart_clients  = array_column($months_map, 'clients');

// ============================================================
// DISTRIBUCIÓN DE LICENCIAS (donut)
// ============================================================
$lic_dist = $pdo->query("
    SELECT status, COUNT(*) AS cnt FROM licenses GROUP BY status
")->fetchAll();
$lic_labels = [];
$lic_data   = [];
$lic_colors = [
    'activa'     => '#00c896',
    'por_vencer' => '#f59e0b',
    'vencida'    => '#ef4444',
    'suspendida' => '#4e586d',
    'demo'       => '#3b82f6',
];
$lic_label_map = [
    'activa' => 'Activa', 'por_vencer' => 'Por vencer',
    'vencida' => 'Vencida', 'suspendida' => 'Suspendida', 'demo' => 'Demo'
];
foreach ($lic_dist as $row) {
    $lic_labels[] = $lic_label_map[$row['status']] ?? $row['status'];
    $lic_data[]   = (int)$row['cnt'];
}
$lic_bg_colors = array_map(fn($l) => $lic_colors[$l] ?? '#888', array_column($lic_dist, 'status'));

// ============================================================
// DISTRIBUCIÓN POR MÉTODO DE PAGO
// ============================================================
$pay_dist = $pdo->query("
    SELECT method, COUNT(*) AS cnt, COALESCE(SUM(amount),0) AS total
    FROM payments GROUP BY method ORDER BY total DESC
")->fetchAll();
$pay_labels = [];
$pay_data   = [];
$pay_label_map = ['efectivo'=>'Efectivo','transferencia'=>'Transferencia','mercado_pago'=>'Mercado Pago','otro'=>'Otro'];
$pay_colors = ['#00c896','#3b82f6','#f59e0b','#8b5cf6'];
foreach ($pay_dist as $i => $row) {
    $pay_labels[] = $pay_label_map[$row['method']] ?? $row['method'];
    $pay_data[]   = (float)$row['total'];
}

// ============================================================
// INGRESOS POR PLAN
// ============================================================
$plan_dist = $pdo->query("
    SELECT l.plan_type, COALESCE(SUM(p.amount),0) AS total, COUNT(DISTINCT l.id) AS lics
    FROM licenses l
    LEFT JOIN payments p ON p.license_id = l.id
    GROUP BY l.plan_type ORDER BY total DESC
")->fetchAll();
$plan_labels = [];
$plan_data   = [];
$plan_label_map = ['mensual'=>'Mensual','anual'=>'Anual','demo'=>'Demo','otro'=>'Otro'];
$plan_colors = ['#00c896','#3b82f6','#f59e0b','#8b5cf6'];
foreach ($plan_dist as $row) {
    $plan_labels[] = $plan_label_map[$row['plan_type']] ?? $row['plan_type'];
    $plan_data[]   = (float)$row['total'];
}

// ============================================================
// TOP 10 CLIENTES POR INGRESOS
// ============================================================
$top_clients = $pdo->query("
    SELECT c.id, COALESCE(c.trade_name, c.legal_name) AS name,
           COALESCE(SUM(p.amount), 0) AS total_paid,
           COUNT(DISTINCT p.id) AS pay_count,
           MAX(p.paid_at) AS last_payment
    FROM clients c
    LEFT JOIN payments p ON p.client_id = c.id
    GROUP BY c.id, name
    ORDER BY total_paid DESC
    LIMIT 10
")->fetchAll();
$max_revenue = !empty($top_clients) ? max(array_column($top_clients, 'total_paid')) : 1;

// ============================================================
// ACUMULADO (running total) para curva de ingresos
// ============================================================
$running = [];
$sum = 0;
foreach ($chart_revenue as $v) {
    $sum += $v;
    $running[] = $sum;
}
?>

<!-- Selector de período -->
<div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;flex-wrap:wrap">
  <span style="font-size:.82rem;color:var(--text-muted)">Período:</span>
  <?php foreach ([3=>'3 meses', 6=>'6 meses', 12=>'12 meses'] as $m => $label): ?>
    <a href="?months=<?= $m ?>"
       class="btn btn-sm <?= $period === $m ? 'btn-primary' : 'btn-secondary' ?>"><?= $label ?></a>
  <?php endforeach; ?>
  <span style="margin-left:auto;font-size:.78rem;color:var(--text-muted)">
    Datos al <?= date('d/m/Y') ?>
  </span>
</div>

<!-- ============================================================
     KPI ROW
     ============================================================ -->
<div class="cards-grid" style="margin-bottom:20px">
  <div class="stat-card accent">
    <div class="stat-label">MRR (este mes)</div>
    <div class="stat-value" style="font-size:1.3rem"><?= format_money($kpi['mrr']) ?></div>
    <div class="stat-sub">Ingreso mensual recurrente</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">ARR <?= date('Y') ?></div>
    <div class="stat-value" style="font-size:1.3rem"><?= format_money($kpi['arr_ytd']) ?></div>
    <div class="stat-sub">Ingresos acumulados del año</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Total histórico</div>
    <div class="stat-value" style="font-size:1.2rem"><?= format_money($kpi['total_revenue']) ?></div>
    <div class="stat-sub">Todos los tiempos</div>
  </div>
  <div class="stat-card accent">
    <div class="stat-label">Clientes activos</div>
    <div class="stat-value"><?= $kpi['active_clients'] ?></div>
    <?php $gd = $growth_pct > 0 ? 'up' : ($growth_pct < 0 ? 'down' : 'flat'); ?>
    <span class="stat-trend <?= $gd ?>"><?= $growth_pct >= 0 ? '+' : '' ?><?= $growth_pct ?>% vs mes ant.</span>
  </div>
  <div class="stat-card">
    <div class="stat-label">Ticket promedio</div>
    <div class="stat-value" style="font-size:1.25rem"><?= format_money($kpi['avg_ticket']) ?></div>
    <div class="stat-sub">Por pago registrado</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Retención estimada</div>
    <div class="stat-value"><?= $kpi['retention_rate'] ?>%</div>
    <div class="stat-sub"><?= $kpi['active_clients'] ?> activos / <?= $total_clients ?> totales</div>
  </div>
</div>

<!-- ============================================================
     GRÁFICO PRINCIPAL: Ingresos mensuales
     ============================================================ -->
<div class="chart-panel" style="margin-bottom:18px">
  <div class="chart-panel-header">
    <div>
      <div class="chart-panel-title">📊 Ingresos mensuales</div>
      <div class="chart-panel-subtitle">Últimos <?= $period ?> meses · barras = mensual · línea = acumulado</div>
    </div>
  </div>
  <div class="chart-panel-body">
    <div class="chart-container" style="height:240px">
      <canvas id="revenueMonthlyChart"></canvas>
    </div>
  </div>
  <div class="chart-legend">
    <div class="chart-legend-item"><span class="chart-legend-dot" style="background:#00c896"></span>Ingresos del mes</div>
    <div class="chart-legend-item"><span class="chart-legend-dot" style="background:rgba(0,200,150,.4)"></span>Acumulado</div>
  </div>
</div>

<!-- ============================================================
     DOS COLUMNAS: Clientes nuevos + Distribución licencias
     ============================================================ -->
<div class="analytics-grid" style="margin-bottom:18px">

  <!-- Clientes nuevos por mes -->
  <div class="chart-panel" style="margin-bottom:0">
    <div class="chart-panel-header">
      <div class="chart-panel-title">👥 Clientes nuevos por mes</div>
    </div>
    <div class="chart-panel-body">
      <div class="chart-container" style="height:200px">
        <canvas id="newClientsChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Distribución de licencias -->
  <div class="chart-panel" style="margin-bottom:0">
    <div class="chart-panel-header">
      <div class="chart-panel-title">🥧 Estado de licencias</div>
    </div>
    <div class="chart-panel-body" style="display:flex;align-items:center;gap:20px">
      <div class="chart-container" style="height:200px;flex:1">
        <canvas id="licStatusChart"></canvas>
      </div>
      <div style="flex-shrink:0">
        <?php foreach ($lic_dist as $i => $row): ?>
          <div style="display:flex;align-items:center;gap:7px;margin-bottom:7px;font-size:.78rem;color:var(--text-secondary)">
            <span style="width:10px;height:10px;border-radius:50%;background:<?= $lic_bg_colors[$i] ?>;flex-shrink:0"></span>
            <?= $lic_label_map[$row['status']] ?? $row['status'] ?>
            <strong style="color:var(--text-primary);margin-left:auto;padding-left:8px"><?= $row['cnt'] ?></strong>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>

<!-- ============================================================
     DOS COLUMNAS: Métodos de pago + Ingresos por plan
     ============================================================ -->
<div class="analytics-grid" style="margin-bottom:18px">

  <div class="chart-panel" style="margin-bottom:0">
    <div class="chart-panel-header">
      <div class="chart-panel-title">💳 Ingresos por método de pago</div>
    </div>
    <div class="chart-panel-body" style="display:flex;align-items:center;gap:20px">
      <div class="chart-container" style="height:190px;flex:1">
        <canvas id="payMethodChart"></canvas>
      </div>
      <div style="flex-shrink:0">
        <?php foreach ($pay_dist as $i => $row): ?>
          <div style="display:flex;align-items:center;gap:7px;margin-bottom:7px;font-size:.78rem;color:var(--text-secondary)">
            <span style="width:10px;height:10px;border-radius:50%;background:<?= $pay_colors[$i] ?? '#888' ?>;flex-shrink:0"></span>
            <?= $pay_label_map[$row['method']] ?? $row['method'] ?>
            <strong style="color:var(--accent);margin-left:auto;padding-left:8px"><?= format_money($row['total']) ?></strong>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="chart-panel" style="margin-bottom:0">
    <div class="chart-panel-header">
      <div class="chart-panel-title">📦 Ingresos por tipo de plan</div>
    </div>
    <div class="chart-panel-body" style="display:flex;align-items:center;gap:20px">
      <div class="chart-container" style="height:190px;flex:1">
        <canvas id="planTypeChart"></canvas>
      </div>
      <div style="flex-shrink:0">
        <?php foreach ($plan_dist as $i => $row): ?>
          <div style="display:flex;align-items:center;gap:7px;margin-bottom:7px;font-size:.78rem;color:var(--text-secondary)">
            <span style="width:10px;height:10px;border-radius:50%;background:<?= $plan_colors[$i] ?? '#888' ?>;flex-shrink:0"></span>
            <?= $plan_label_map[$row['plan_type']] ?? $row['plan_type'] ?>
            <strong style="color:var(--accent);margin-left:auto;padding-left:8px"><?= format_money($row['total']) ?></strong>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>

<!-- ============================================================
     TOP CLIENTES
     ============================================================ -->
<div class="chart-panel">
  <div class="chart-panel-header">
    <div class="chart-panel-title">🏆 Top clientes por ingresos generados</div>
  </div>
  <div style="padding:4px 0">
    <?php if (empty($top_clients)): ?>
      <div class="empty-panel">Sin datos de pagos aún.</div>
    <?php else: ?>
      <?php foreach ($top_clients as $i => $c): ?>
        <?php
        $rank_class = match($i) { 0 => 'rank-1', 1 => 'rank-2', 2 => 'rank-3', default => 'rank-other' };
        $bar_pct = $max_revenue > 0 ? round(($c['total_paid'] / $max_revenue) * 100) : 0;
        ?>
        <div class="top-client-row">
          <span class="top-client-rank <?= $rank_class ?>"><?= $i + 1 ?></span>
          <a href="<?= admin_url('client-view.php?id=' . $c['id']) ?>" class="top-client-name" style="color:inherit">
            <?= e($c['name']) ?>
          </a>
          <div class="top-client-bar-wrap">
            <div class="top-client-bar-bg">
              <div class="top-client-bar-fill" style="width:<?= $bar_pct ?>%"></div>
            </div>
          </div>
          <span class="top-client-amount"><?= format_money($c['total_paid']) ?></span>
          <span style="font-size:.74rem;color:var(--text-muted);flex-shrink:0;margin-left:8px">
            <?= $c['pay_count'] ?> pago<?= $c['pay_count'] !== 1 ? 's' : '' ?>
            <?php if ($c['last_payment']): ?>
              · último: <?= format_date($c['last_payment']) ?>
            <?php endif; ?>
          </span>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- ============================================================
     CHART.JS SCRIPTS
     ============================================================ -->
<script>
(function() {
  if (typeof Chart === 'undefined') return;

  const ACCENT  = '#00c896';
  const BLUE    = '#3b82f6';
  const YELLOW  = '#f59e0b';
  const RED     = '#ef4444';
  const PURPLE  = '#8b5cf6';
  const MUTED   = '#4e586d';
  const SURFACE = '#1e2438';

  const tooltipDefaults = {
    backgroundColor: '#181d2e',
    borderColor: '#252d45',
    borderWidth: 1,
    titleColor: '#e2e6f0',
    bodyColor: '#8993ad',
    padding: 10,
  };

  const scaleDefaults = (yCallback) => ({
    x: { grid: { color: 'rgba(37,45,69,.4)' }, ticks: { color: MUTED, font:{size:11} } },
    y: { grid: { color: 'rgba(37,45,69,.4)' }, ticks: { color: MUTED, font:{size:11}, callback: yCallback } }
  });

  // ---- Gráfico ingresos mensuales ----
  const labels  = <?= json_encode(array_values($chart_labels)) ?>;
  const revData = <?= json_encode(array_values($chart_revenue)) ?>;
  const cumData = <?= json_encode(array_values($running)) ?>;
  const cliData = <?= json_encode(array_values($chart_clients)) ?>;
  const payData = <?= json_encode(array_values($chart_payments)) ?>;

  const fmtARS = v => '$' + Number(v).toLocaleString('es-AR', {minimumFractionDigits:0});

  // 1. Ingresos mensuales + acumulado
  new Chart(document.getElementById('revenueMonthlyChart'), {
    type: 'bar',
    data: {
      labels,
      datasets: [
        {
          label: 'Mensual',
          data: revData,
          backgroundColor: 'rgba(0,200,150,.2)',
          borderColor: ACCENT,
          borderWidth: 2,
          borderRadius: 5,
          yAxisID: 'y',
        },
        {
          label: 'Acumulado',
          data: cumData,
          type: 'line',
          borderColor: 'rgba(0,200,150,.5)',
          backgroundColor: 'transparent',
          borderWidth: 2,
          borderDash: [4, 3],
          pointRadius: 3,
          pointBackgroundColor: ACCENT,
          tension: 0.3,
          yAxisID: 'y',
        }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { display: false },
        tooltip: { ...tooltipDefaults, callbacks: { label: c => ' ' + fmtARS(c.parsed.y) } }
      },
      scales: {
        x: { grid: { color: 'rgba(37,45,69,.4)' }, ticks: { color: MUTED, font:{size:11} } },
        y: { grid: { color: 'rgba(37,45,69,.4)' }, ticks: { color: MUTED, font:{size:11}, callback: fmtARS } }
      }
    }
  });

  // 2. Clientes nuevos
  new Chart(document.getElementById('newClientsChart'), {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Nuevos clientes',
        data: cliData,
        backgroundColor: 'rgba(59,130,246,.22)',
        borderColor: BLUE,
        borderWidth: 2,
        borderRadius: 5,
      }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { ...tooltipDefaults, callbacks: { label: c => ' ' + c.parsed.y + ' clientes' } }
      },
      scales: {
        x: { grid: { color: 'rgba(37,45,69,.4)' }, ticks: { color: MUTED, font:{size:11} } },
        y: { grid: { color: 'rgba(37,45,69,.4)' }, ticks: { color: MUTED, font:{size:11}, stepSize: 1 } }
      }
    }
  });

  // 3. Estado de licencias (donut)
  const licLabels = <?= json_encode(array_values($lic_labels)) ?>;
  const licData   = <?= json_encode(array_values($lic_data)) ?>;
  const licColors = <?= json_encode(array_values($lic_bg_colors)) ?>;

  new Chart(document.getElementById('licStatusChart'), {
    type: 'doughnut',
    data: {
      labels: licLabels,
      datasets: [{ data: licData, backgroundColor: licColors, borderWidth: 2, borderColor: '#111520', hoverOffset: 6 }]
    },
    options: {
      responsive: true, maintainAspectRatio: false, cutout: '65%',
      plugins: {
        legend: { display: false },
        tooltip: { ...tooltipDefaults, callbacks: { label: c => ' ' + c.label + ': ' + c.parsed } }
      }
    }
  });

  // 4. Métodos de pago (doughnut)
  const payLabels = <?= json_encode(array_values($pay_labels)) ?>;
  const payRevData = <?= json_encode(array_values($pay_data)) ?>;
  const payColors = [ACCENT, BLUE, YELLOW, PURPLE];

  new Chart(document.getElementById('payMethodChart'), {
    type: 'doughnut',
    data: {
      labels: payLabels,
      datasets: [{ data: payRevData, backgroundColor: payColors, borderWidth: 2, borderColor: '#111520', hoverOffset: 6 }]
    },
    options: {
      responsive: true, maintainAspectRatio: false, cutout: '60%',
      plugins: {
        legend: { display: false },
        tooltip: { ...tooltipDefaults, callbacks: { label: c => ' ' + c.label + ': ' + fmtARS(c.parsed) } }
      }
    }
  });

  // 5. Planes (doughnut)
  const planLabels = <?= json_encode(array_values($plan_labels)) ?>;
  const planRevData = <?= json_encode(array_values($plan_data)) ?>;

  new Chart(document.getElementById('planTypeChart'), {
    type: 'doughnut',
    data: {
      labels: planLabels,
      datasets: [{ data: planRevData, backgroundColor: [ACCENT, BLUE, YELLOW, PURPLE], borderWidth: 2, borderColor: '#111520', hoverOffset: 6 }]
    },
    options: {
      responsive: true, maintainAspectRatio: false, cutout: '60%',
      plugins: {
        legend: { display: false },
        tooltip: { ...tooltipDefaults, callbacks: { label: c => ' ' + c.label + ': ' + fmtARS(c.parsed) } }
      }
    }
  });
})();
</script>

<?php require_once __DIR__ . '/includes/layout-footer.php'; ?>
