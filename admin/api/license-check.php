<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function cloud_license_json_response(int $statusCode, array $body): void
{
    http_response_code($statusCode);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    cloud_license_json_response(405, ['ok' => false, 'error' => 'METHOD_NOT_ALLOWED']);
}

$licenseConfig = admin_config('license', []);
$expectedToken = trim((string) ($licenseConfig['cloud_api_token'] ?? ''));
if ($expectedToken !== '') {
    $authorization = trim((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
    $headerToken = trim((string) ($_SERVER['HTTP_X_FLUS_CLOUD_TOKEN'] ?? ''));
    $bearerToken = '';
    if (preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches) === 1) {
        $bearerToken = trim((string) $matches[1]);
    }

    if (!hash_equals($expectedToken, $bearerToken) && !hash_equals($expectedToken, $headerToken)) {
        cloud_license_json_response(401, ['ok' => false, 'error' => 'UNAUTHORIZED']);
    }
}

$rawBody = file_get_contents('php://input');
if ($rawBody === false || strlen($rawBody) > 16384) {
    cloud_license_json_response(400, ['ok' => false, 'error' => 'BAD_REQUEST']);
}

$request = json_decode($rawBody, true);
if (!is_array($request)) {
    cloud_license_json_response(400, ['ok' => false, 'error' => 'JSON_INVALID']);
}

$licenseKey = strtoupper(trim((string) ($request['license_key'] ?? '')));
if (preg_match('/^FLUS-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $licenseKey) !== 1) {
    cloud_license_json_response(400, ['ok' => false, 'error' => 'LICENSE_KEY_INVALID']);
}

try {
    $pdo = admin_db();
    $stmt = $pdo->prepare('
        SELECT
            l.*,
            c.legal_name,
            c.trade_name,
            c.email,
            c.tax_id
        FROM licenses l
        INNER JOIN clients c ON c.id = l.client_id
        WHERE l.license_key = :license_key
        LIMIT 1
    ');
    $stmt->execute(['license_key' => $licenseKey]);
    $license = $stmt->fetch();

    if (!$license) {
        $license = [
            'license_key' => $licenseKey,
            'status' => 'suspendida',
            'plan_type' => '',
            'expires_at' => date('Y-m-d', strtotime('-1 day')),
        ];
        $payload = admin_build_cloud_license_payload($license, $request);
        $payload['status'] = 'revoked';
        $payload['message'] = 'Licencia no registrada en el panel FLUS.';

        cloud_license_json_response(200, admin_build_signed_cloud_license_document($payload));
    }

    cloud_license_json_response(
        200,
        admin_build_signed_cloud_license_document(admin_build_cloud_license_payload($license, $request))
    );
} catch (Throwable $e) {
    error_log('[FLUS Admin] cloud license check: ' . $e->getMessage());
    cloud_license_json_response(500, ['ok' => false, 'error' => 'SERVER_ERROR']);
}
