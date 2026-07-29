<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';

$endpoint = (string) file_get_contents(__DIR__ . '/../api/sync-ingest.php');
$failures = [];

if (strpos($endpoint, 'admin_cloud_sync_upsert_installation($pdo, $license, $installationUid, $branchId, $request);') === false) {
    $failures[] = 'El preflight no actualiza la instalacion.';
}
if (strpos($endpoint, "'preflight' => true") === false) {
    $failures[] = 'El contrato de preflight no esta presente.';
}
if (format_utc_datetime('2026-07-29 03:46:00') !== '29/07/2026 00:46') {
    $failures[] = 'La fecha UTC no se convierte a la zona local configurada.';
}

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, '[FAIL] ' . $failure . PHP_EOL);
    }
    exit(1);
}

fwrite(STDOUT, "OK: heartbeat cloud y zona horaria.\n");
