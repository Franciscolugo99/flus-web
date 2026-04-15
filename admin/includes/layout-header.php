<?php
declare(strict_types=1);
// ============================================================
// FLUS Admin - Layout Header v2.0
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

admin_start_session();
require_admin_login();

$page_title = $page_title ?? ($pageTitle ?? 'Panel Admin');
$active_menu = $active_menu ?? ($activeNav ?? '');

$__user = current_admin() ?? [];
$pdo    = admin_db();

// ---- Wrappers de compatibilidad para los archivos mejorados ----
if (!function_exists('fmt_date')) {
    function fmt_date(?string $d): string { return format_date($d); }
}
if (!function_exists('fmt_datetime')) {
    function fmt_datetime(?string $d): string { return format_datetime($d); }
}
if (!function_exists('fmt_money')) {
    function fmt_money($v): string { return format_money($v); }
}
if (!function_exists('csrf_field')) {
    function csrf_field(): string { return csrf_input(); }
}
if (!function_exists('csrf_check')) {
    function csrf_check(): void { verify_csrf(); }
}
if (!function_exists('flash')) {
    function flash(string $type, string $msg): void { set_flash($type, $msg); }
}
if (!function_exists('flash_html')) {
    function flash_html(): string {
        $f = get_flash();
        if (!$f) return '';
        $cls = match($f['type']) {
            'success' => 'alert-success',
            'error'   => 'alert-error',
            'warning' => 'alert-warning',
            default   => 'alert-info',
        };
        return '<div class="alert ' . $cls . '">' . e($f['message']) . '</div>';
    }
}
if (!function_exists('redirect_with_flash')) {
    function redirect_with_flash(string $url, string $type, string $msg): void {
        set_flash($type, $msg);
        redirect_to($url);
    }
}
if (!function_exists('license_status_badge')) {
    function license_status_badge(string $status): string {
        $labels = ['activa'=>'Activa','por_vencer'=>'Por vencer','vencida'=>'Vencida','suspendida'=>'Suspendida','demo'=>'Demo'];
        $classes = ['activa'=>'badge-green','por_vencer'=>'badge-yellow','vencida'=>'badge-red','suspendida'=>'badge-gray','demo'=>'badge-blue'];
        $lbl = $labels[$status] ?? ucfirst($status);
        $cls = $classes[$status] ?? 'badge-gray';
        return '<span class="badge ' . $cls . '">' . e($lbl) . '</span>';
    }
}
if (!function_exists('client_status_badge')) {
    function client_status_badge(string $status): string {
        $labels  = ['activo'=>'Activo','demo'=>'Demo','suspendido'=>'Suspendido','inactivo'=>'Inactivo'];
        $classes = ['activo'=>'badge-green','demo'=>'badge-blue','suspendido'=>'badge-yellow','inactivo'=>'badge-gray'];
        $lbl = $labels[$status] ?? ucfirst($status);
        $cls = $classes[$status] ?? 'badge-gray';
        return '<span class="badge ' . $cls . '">' . e($lbl) . '</span>';
    }
}
if (!function_exists('payment_method_label')) {
    function payment_method_label(string $m): string {
        return ['efectivo'=>'Efectivo','transferencia'=>'Transferencia','mercado_pago'=>'Mercado Pago','otro'=>'Otro'][$m] ?? e($m);
    }
}
if (!function_exists('plan_type_label')) {
    function plan_type_label(string $p): string {
        return ['mensual'=>'Mensual','anual'=>'Anual','demo'=>'Demo','otro'=>'Otro'][$p] ?? e($p);
    }
}
if (!function_exists('compute_license_status')) {
    function compute_license_status(string $expires_at, string $current_status): string {
        return license_current_status($current_status, $expires_at);
    }
}
if (!function_exists('paginate')) {
    function paginate(int $total, int $per_page, int $current_page): array {
        $total_pages  = max(1, (int)ceil($total / $per_page));
        $current_page = max(1, min($current_page, $total_pages));
        return [
            'total'        => $total,
            'per_page'     => $per_page,
            'current_page' => $current_page,
            'total_pages'  => $total_pages,
            'offset'       => ($current_page - 1) * $per_page,
        ];
    }
}

// ---- Alertas para badge de notificaciones ----
$__expiring_soon = (int)$pdo->query("
    SELECT COUNT(*) FROM licenses
    WHERE expires_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
      AND status NOT IN ('vencida','suspendida')
")->fetchColumn();

$__expired = (int)$pdo->query("
    SELECT COUNT(*) FROM licenses
    WHERE expires_at < CURDATE()
      AND status NOT IN ('suspendida')
")->fetchColumn();

$__total_alerts = $__expiring_soon + $__expired;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title ?? 'Panel Admin') ?> — FLUS Admin</title>
  <link rel="stylesheet" href="<?= admin_url('assets/css/admin.css') ?>">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

<div class="layout">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <span class="brand-logo">
        <img src="../assets/img/flus-mark.webp" alt="" aria-hidden="true">
      </span>
      <div>
        <span class="brand-name">FLUS <em>Admin</em></span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <a href="<?= admin_url('index.php') ?>"
         class="nav-item <?= ($active_menu ?? '') === 'dashboard' ? 'active' : '' ?>">
        <span class="nav-icon">⊞</span> Dashboard
      </a>

      <div class="nav-group-label">Gestión</div>

      <a href="<?= admin_url('clients.php') ?>"
         class="nav-item <?= ($active_menu ?? '') === 'clients' ? 'active' : '' ?>">
        <span class="nav-icon">◈</span> Clientes
      </a>
      <a href="<?= admin_url('licenses.php') ?>"
         class="nav-item <?= ($active_menu ?? '') === 'licenses' ? 'active' : '' ?>">
        <span class="nav-icon">◉</span> Licencias
      </a>
      <a href="<?= admin_url('payments.php') ?>"
         class="nav-item <?= ($active_menu ?? '') === 'payments' ? 'active' : '' ?>">
        <span class="nav-icon">◎</span> Pagos
      </a>

      <div class="nav-group-label">Reportes & Alertas</div>

      <a href="<?= admin_url('expirations.php') ?>"
         class="nav-item <?= ($active_menu ?? '') === 'expirations' ? 'active' : '' ?>">
        <span class="nav-icon">◷</span> Vencimientos
        <?php if ($__total_alerts > 0): ?>
          <span class="nav-badge <?= ($__expiring_soon > 0 && $__expired === 0) ? 'yellow' : '' ?>">
            <?= $__total_alerts ?>
          </span>
        <?php endif; ?>
      </a>
      <a href="<?= admin_url('analytics.php') ?>"
         class="nav-item <?= ($active_menu ?? '') === 'analytics' ? 'active' : '' ?>">
        <span class="nav-icon">◱</span> Analíticas
      </a>

      <div class="nav-group-label">Recursos</div>

      <a href="<?= admin_url('downloads.php') ?>"
         class="nav-item <?= ($active_menu ?? '') === 'downloads' ? 'active' : '' ?>">
        <span class="nav-icon">↓</span> Descargas
      </a>
    </nav>

    <div class="sidebar-footer">
      <div class="sidebar-user">
        <?php $__initials = strtoupper(substr($__user['full_name'] ?? $__user['username'] ?? 'A', 0, 1)); ?>
        <span class="user-avatar"><?= $__initials ?></span>
        <div class="user-info">
          <span class="user-name"><?= e($__user['full_name'] ?? $__user['username'] ?? '') ?></span>
        </div>
      </div>
      <a href="<?= admin_url('logout.php') ?>" class="btn-logout" title="Cerrar sesión">⏻ Salir</a>
    </div>
  </aside>

  <!-- Main content -->
  <main class="main-content">
    <div class="topbar">
      <h1 class="page-title"><?= e($page_title ?? 'Panel') ?></h1>

      <div class="topbar-actions">
        <span class="topbar-date" id="topbar-date"></span>

        <?php if ($__total_alerts > 0): ?>
        <a href="<?= admin_url('expirations.php') ?>" class="topbar-notif"
           title="<?= $__total_alerts ?> alerta<?= $__total_alerts !== 1 ? 's' : '' ?> de vencimiento">
          🔔
          <span class="topbar-notif-count"><?= $__total_alerts ?></span>
        </a>
        <?php else: ?>
        <a href="<?= admin_url('expirations.php') ?>" class="topbar-notif" title="Sin alertas activas">
          🔔
        </a>
        <?php endif; ?>
      </div>
    </div>

    <div class="content-body">
      <?= flash_html() ?>
