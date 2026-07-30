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

if (!function_exists('cloud_command_json_response')) {
    function cloud_command_json_response(int $statusCode, array $body): void
    {
        http_response_code($statusCode);
        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    cloud_command_json_response(405, ['ok' => false, 'error' => 'METHOD_NOT_ALLOWED']);
}

$licenseConfig = admin_config('license', []);
$expectedToken = trim((string) ($licenseConfig['cloud_api_token'] ?? ''));
if ($expectedToken === '') {
    cloud_command_json_response(503, ['ok' => false, 'error' => 'CLOUD_TOKEN_NOT_CONFIGURED']);
}

$authorization = trim((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
$headerToken = trim((string) ($_SERVER['HTTP_X_FLUS_CLOUD_TOKEN'] ?? ''));
$bearerToken = '';
if (preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches) === 1) {
    $bearerToken = trim((string) $matches[1]);
}
if (!hash_equals($expectedToken, $bearerToken) && !hash_equals($expectedToken, $headerToken)) {
    cloud_command_json_response(401, ['ok' => false, 'error' => 'UNAUTHORIZED']);
}

$rawBody = file_get_contents('php://input');
if ($rawBody === false || strlen($rawBody) > 65536) {
    cloud_command_json_response(400, ['ok' => false, 'error' => 'BAD_REQUEST']);
}
$cloudCommandRequest = json_decode($rawBody, true);
if (!is_array($cloudCommandRequest)) {
    cloud_command_json_response(400, ['ok' => false, 'error' => 'JSON_INVALID']);
}

$licenseKey = strtoupper(trim((string) ($cloudCommandRequest['license_key'] ?? '')));
$installationUid = admin_cloud_sync_normalize_uid((string) ($cloudCommandRequest['installation_id'] ?? ''), 120);
if (preg_match('/^FLUS-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $licenseKey) !== 1) {
    cloud_command_json_response(400, ['ok' => false, 'error' => 'LICENSE_KEY_INVALID']);
}
if ($installationUid === '') {
    cloud_command_json_response(400, ['ok' => false, 'error' => 'INSTALLATION_ID_INVALID']);
}

try {
    $cloudCommandPdo = admin_db();
    if (!admin_cloud_sync_ensure_schema($cloudCommandPdo)) {
        cloud_command_json_response(500, ['ok' => false, 'error' => 'SCHEMA_UNAVAILABLE']);
    }
    $cloudCommandLicense = admin_cloud_sync_find_license($cloudCommandPdo, $licenseKey);
    if (!$cloudCommandLicense) {
        cloud_command_json_response(404, ['ok' => false, 'error' => 'LICENSE_NOT_FOUND']);
    }
    if (!admin_cloud_sync_license_accepts_events($cloudCommandLicense)) {
        $reason = admin_cloud_sync_license_reject_reason($cloudCommandLicense);
        cloud_command_json_response(403, ['ok' => false, 'error' => $reason !== '' ? $reason : 'LICENSE_NOT_ACTIVE']);
    }

    $installationStmt = $cloudCommandPdo->prepare('SELECT * FROM client_installations WHERE client_id = :client_id AND license_id = :license_id AND installation_uid = :installation_uid LIMIT 1');
    $installationStmt->execute([
        'client_id' => (int) $cloudCommandLicense['client_id'],
        'license_id' => (int) $cloudCommandLicense['id'],
        'installation_uid' => $installationUid,
    ]);
    $cloudCommandInstallation = $installationStmt->fetch();
    if (!is_array($cloudCommandInstallation)) {
        cloud_command_json_response(404, ['ok' => false, 'error' => 'INSTALLATION_NOT_FOUND']);
    }
} catch (Throwable $e) {
    error_log('[FLUS Admin] cloud command authentication: ' . $e->getMessage());
    cloud_command_json_response(500, ['ok' => false, 'error' => 'SERVER_ERROR']);
}
