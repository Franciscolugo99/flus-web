<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/print-queue.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

if (!function_exists('print_agent_json_response')) {
    function print_agent_json_response(int $statusCode, array $body): void
    {
        http_response_code($statusCode);
        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    print_agent_json_response(405, ['ok' => false, 'error' => 'METHOD_NOT_ALLOWED']);
}

$rawBody = file_get_contents('php://input');
if ($rawBody === false || strlen($rawBody) > 65536) {
    print_agent_json_response(400, ['ok' => false, 'error' => 'BAD_REQUEST']);
}
$printAgentRequest = trim($rawBody) === '' ? [] : json_decode($rawBody, true);
if (!is_array($printAgentRequest)) {
    print_agent_json_response(400, ['ok' => false, 'error' => 'JSON_INVALID']);
}

$agentUid = admin_print_normalize_uid((string) ($_SERVER['HTTP_X_FLUS_AGENT_ID'] ?? ''), 120);
$authorization = trim((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
$agentToken = '';
if (preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches) === 1) {
    $agentToken = trim((string) $matches[1]);
}
if ($agentUid === '' || $agentToken === '') {
    print_agent_json_response(401, ['ok' => false, 'error' => 'AGENT_AUTH_REQUIRED']);
}

try {
    $printAgentPdo = admin_db();
    if (!admin_print_queue_ensure_schema($printAgentPdo)) {
        print_agent_json_response(503, ['ok' => false, 'error' => 'PRINT_SCHEMA_UNAVAILABLE']);
    }
    $printAgent = admin_print_auth_agent($printAgentPdo, $agentUid, $agentToken);
    if (!is_array($printAgent)) {
        print_agent_json_response(401, ['ok' => false, 'error' => 'AGENT_UNAUTHORIZED']);
    }
} catch (Throwable $e) {
    error_log('[FLUS Print] agent auth: ' . $e->getMessage());
    print_agent_json_response(500, ['ok' => false, 'error' => 'SERVER_ERROR']);
}
