<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/includes/bootstrap.php';
require_once __DIR__ . '/../admin/includes/client-portal.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

$respond = static function (int $status, array $body): void {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

if (!request_is_post()) {
    header('Allow: POST');
    $respond(405, ['ok' => false, 'error' => 'METHOD_NOT_ALLOWED']);
}

admin_start_session();
$pdo = admin_db();
if (!portal_is_logged_in() || !portal_refresh_current_session($pdo)) {
    $respond(401, ['ok' => false, 'error' => 'AUTH_REQUIRED']);
}
$csrf = (string) ($_POST['_csrf'] ?? '');
if ($csrf === '' || !hash_equals(csrf_token(), $csrf)) {
    $respond(419, ['ok' => false, 'error' => 'CSRF_INVALID']);
}
if (!portal_role_can('change_price')) {
    $respond(403, ['ok' => false, 'error' => 'PERMISSION_DENIED']);
}
if (!admin_cloud_sync_ensure_schema($pdo)) {
    $respond(503, ['ok' => false, 'error' => 'SCHEMA_UNAVAILABLE']);
}

$portalUser = portal_current_user() ?? [];
$clientId = (int) ($portalUser['client_id'] ?? 0);
$portalUserId = (int) ($portalUser['id'] ?? 0);
$allowedBranchIds = portal_current_branch_scope();
$action = trim((string) ($_POST['action'] ?? 'create'));

try {
    if ($action === 'status') {
        $command = admin_cloud_command_find_for_portal(
            $pdo,
            $clientId,
            (string) ($_POST['command_uid'] ?? ''),
            $allowedBranchIds
        );
        if (!is_array($command)) {
            $respond(404, ['ok' => false, 'error' => 'COMMAND_NOT_FOUND']);
        }
        $respond(200, ['ok' => true, 'command' => admin_cloud_command_public_row($command)]);
    }
    if ($action !== 'create') {
        $respond(400, ['ok' => false, 'error' => 'UNKNOWN_ACTION']);
    }

    $result = admin_cloud_command_create_price(
        $pdo,
        $clientId,
        $portalUserId,
        (int) ($_POST['stock_item_id'] ?? 0),
        (float) ($_POST['new_price'] ?? 0),
        trim((string) ($_POST['reason'] ?? '')),
        (string) ($_POST['request_uid'] ?? ''),
        $allowedBranchIds
    );
    $respond(200, ['ok' => true, 'duplicate' => !empty($result['duplicate']), 'command' => $result['command']]);
} catch (InvalidArgumentException $e) {
    $respond(422, ['ok' => false, 'error' => 'VALIDATION_ERROR', 'message' => $e->getMessage()]);
} catch (RuntimeException $e) {
    $respond(403, ['ok' => false, 'error' => 'OPERATION_NOT_ALLOWED', 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('[FLUS Portal] price command: ' . $e->getMessage());
    $respond(500, ['ok' => false, 'error' => 'SERVER_ERROR', 'message' => 'No se pudo crear la orden de precio.']);
}
