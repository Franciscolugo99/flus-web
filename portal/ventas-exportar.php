<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/includes/bootstrap.php';
require_once __DIR__ . '/../admin/includes/client-portal.php';

$pdo = admin_db();
admin_cloud_sync_ensure_schema($pdo);
require_portal_login($pdo);

$portalUser = portal_current_user();
$portalRole = portal_current_role();
if (!is_array($portalUser) || !portal_role_can('view_sales', $portalRole)) {
    http_response_code(403);
    exit('No tenes permiso para exportar ventas.');
}

$clientId = (int) ($portalUser['client_id'] ?? 0);
$allowedBranchIds = portal_current_branch_scope();
$selectedBranchId = max(0, (int) ($_GET['sucursal'] ?? 0));
$periodKey = trim((string) ($_GET['periodo'] ?? 'today'));
$validPeriods = ['today', 'yesterday', '7d', '30d', 'all', 'custom'];
if (!in_array($periodKey, $validPeriods, true)) {
    $periodKey = 'today';
}

$localTimezone = new DateTimeZone('America/Argentina/Buenos_Aires');
$utcTimezone = new DateTimeZone('UTC');
$todayLocal = new DateTimeImmutable('today', $localTimezone);
$fromLocal = $todayLocal;
$toLocal = $todayLocal->modify('+1 day');
if ($periodKey === 'yesterday') {
    $fromLocal = $todayLocal->modify('-1 day');
    $toLocal = $todayLocal;
} elseif ($periodKey === '7d') {
    $fromLocal = $todayLocal->modify('-6 days');
} elseif ($periodKey === '30d') {
    $fromLocal = $todayLocal->modify('-29 days');
} elseif ($periodKey === 'all') {
    $fromLocal = new DateTimeImmutable('2000-01-01 00:00:00', $localTimezone);
} elseif ($periodKey === 'custom') {
    $customFrom = DateTimeImmutable::createFromFormat('!Y-m-d', trim((string) ($_GET['desde'] ?? '')), $localTimezone);
    $customTo = DateTimeImmutable::createFromFormat('!Y-m-d', trim((string) ($_GET['hasta'] ?? '')), $localTimezone);
    if ($customFrom instanceof DateTimeImmutable && $customTo instanceof DateTimeImmutable && $customFrom <= $customTo) {
        $fromLocal = $customFrom;
        $toLocal = $customTo->modify('+1 day');
    } else {
        $periodKey = 'today';
    }
}

$rows = admin_cloud_sync_sales_export_rows($pdo, $clientId, [
    'branch_id' => $selectedBranchId,
    'branch_ids' => $allowedBranchIds,
    'from_utc' => $fromLocal->setTimezone($utcTimezone)->format('Y-m-d H:i:s'),
    'to_utc' => $toLocal->setTimezone($utcTimezone)->format('Y-m-d H:i:s'),
    'q' => mb_substr(trim((string) ($_GET['venta_q'] ?? '')), 0, 80),
    'payment' => mb_substr(strtoupper(trim((string) ($_GET['venta_medio'] ?? ''))), 0, 40),
    'cashier' => mb_substr(trim((string) ($_GET['venta_cajero'] ?? '')), 0, 80),
]);

$fileName = 'ventas-flus-' . date('Ymd-His') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('X-Content-Type-Options: nosniff');

$output = fopen('php://output', 'wb');
if ($output === false) {
    http_response_code(500);
    exit;
}
$safeCsvCell = static function ($value): string {
    $cell = (string) $value;
    if ($cell !== '' && preg_match('/^[=+\-@\t\r]/', $cell) === 1) {
        return "'" . $cell;
    }
    return $cell;
};
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, ['Fecha', 'Sucursal', 'Venta', 'Estado', 'Cajero', 'Medio de pago', 'Productos', 'Importe original', 'Anulado', 'Neto vigente', 'Codigos', 'Detalle'], ';');

foreach ($rows as $row) {
    $summary = is_array($row['summary'] ?? null) ? $row['summary'] : [];
    $payload = is_array($row['payload'] ?? null) ? $row['payload'] : [];
    $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
    $cashier = trim((string) ($summary['cajero_nombre'] ?? $payload['cajero_nombre'] ?? ''));
    if ($cashier === '' && (int) ($summary['user_id'] ?? $payload['user_id'] ?? 0) > 0) {
        $cashier = 'Cajero #' . (int) ($summary['user_id'] ?? $payload['user_id']);
    }
    $codes = [];
    $details = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $code = trim((string) ($item['codigo'] ?? ''));
        $name = trim((string) ($item['nombre'] ?? 'Producto'));
        $quantity = (float) ($item['cantidad'] ?? 0);
        if ($code !== '') {
            $codes[] = $code;
        }
        $details[] = $name . ' x ' . number_format($quantity, abs($quantity - round($quantity)) < 0.0001 ? 0 : 3, ',', '.');
    }

    fputcsv($output, [
        $safeCsvCell(format_utc_datetime($row['occurred_at'] ?? null)),
        $safeCsvCell($row['branch_name'] ?: 'Sin sucursal'),
        $safeCsvCell($summary['venta_id'] ?? ''),
        $safeCsvCell($row['sale_status'] ?? $summary['estado'] ?? 'EMITIDA'),
        $safeCsvCell($cashier),
        $safeCsvCell($summary['medio_pago'] ?? ''),
        (string) ($summary['items_count'] ?? count($items)),
        number_format((float) ($summary['total'] ?? 0), 2, ',', ''),
        number_format((float) ($row['annulled_amount'] ?? 0), 2, ',', ''),
        number_format((float) ($row['net_amount'] ?? $summary['total'] ?? 0), 2, ',', ''),
        $safeCsvCell(implode(', ', $codes)),
        $safeCsvCell(implode(' | ', $details)),
    ], ';');
}

fclose($output);
exit;
