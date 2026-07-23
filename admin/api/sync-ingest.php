<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/license-cloud.php';
require_once __DIR__ . '/../includes/cloud-sync.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function cloud_sync_json_response(int $statusCode, array $body): void
{
    http_response_code($statusCode);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    cloud_sync_json_response(405, ['ok' => false, 'error' => 'METHOD_NOT_ALLOWED']);
}

$licenseConfig = admin_config('license', []);
$expectedToken = trim((string) ($licenseConfig['cloud_api_token'] ?? ''));
if ($expectedToken === '') {
    cloud_sync_json_response(503, ['ok' => false, 'error' => 'CLOUD_TOKEN_NOT_CONFIGURED']);
}

$authorization = trim((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
$headerToken = trim((string) ($_SERVER['HTTP_X_FLUS_CLOUD_TOKEN'] ?? ''));
$bearerToken = '';
if (preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches) === 1) {
    $bearerToken = trim((string) $matches[1]);
}

if (!hash_equals($expectedToken, $bearerToken) && !hash_equals($expectedToken, $headerToken)) {
    cloud_sync_json_response(401, ['ok' => false, 'error' => 'UNAUTHORIZED']);
}

$rawBody = file_get_contents('php://input');
if ($rawBody === false || strlen($rawBody) > 262144) {
    cloud_sync_json_response(400, ['ok' => false, 'error' => 'BAD_REQUEST']);
}

$request = json_decode($rawBody, true);
if (!is_array($request)) {
    cloud_sync_json_response(400, ['ok' => false, 'error' => 'JSON_INVALID']);
}

$licenseKey = strtoupper(trim((string) ($request['license_key'] ?? '')));
if (preg_match('/^FLUS-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $licenseKey) !== 1) {
    cloud_sync_json_response(400, ['ok' => false, 'error' => 'LICENSE_KEY_INVALID']);
}

$installationUid = admin_cloud_sync_normalize_uid((string) ($request['installation_id'] ?? $request['installation_uid'] ?? ''));
if ($installationUid === '') {
    cloud_sync_json_response(400, ['ok' => false, 'error' => 'INSTALLATION_ID_INVALID']);
}

$events = $request['events'] ?? [];
if (!is_array($events)) {
    cloud_sync_json_response(400, ['ok' => false, 'error' => 'EVENTS_INVALID']);
}

try {
    $pdo = admin_db();
    if (!admin_cloud_sync_ensure_schema($pdo)) {
        cloud_sync_json_response(500, ['ok' => false, 'error' => 'SCHEMA_UNAVAILABLE']);
    }

    $license = admin_cloud_sync_find_license($pdo, $licenseKey);
    if (!$license) {
        cloud_sync_json_response(404, ['ok' => false, 'error' => 'LICENSE_NOT_FOUND']);
    }

    if (!admin_cloud_sync_license_accepts_events($license)) {
        cloud_sync_json_response(403, ['ok' => false, 'error' => 'LICENSE_NOT_ACTIVE']);
    }

    $pdo->beginTransaction();
    $branchId = null;
    if (isset($request['branch']) && is_array($request['branch'])) {
        $branchId = admin_cloud_sync_upsert_branch($pdo, (int) $license['client_id'], $request['branch']);
    }

    $installationId = admin_cloud_sync_upsert_installation($pdo, $license, $installationUid, $branchId, $request);
    $result = admin_cloud_sync_store_events($pdo, $license, $installationId, $branchId, $events);
    $pdo->commit();

    cloud_sync_json_response(200, [
        'ok' => true,
        'accepted' => $result['accepted'],
        'duplicates' => $result['duplicates'],
        'rejected' => $result['rejected'],
        'next_push_after_sec' => 60,
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[FLUS Admin] cloud sync ingest: ' . $e->getMessage());
    cloud_sync_json_response(500, ['ok' => false, 'error' => 'SERVER_ERROR']);
}
