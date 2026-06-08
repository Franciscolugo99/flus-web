<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/web-analytics.php';
require_once __DIR__ . '/includes/security.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (!security_same_origin_request()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'origin_not_allowed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > 8192) {
    http_response_code(413);
    echo json_encode(['ok' => false, 'error' => 'payload_too_large'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $rateLimit = security_rate_limit('web_analytics_ip', security_client_ip(), 120, 60);
    if (!$rateLimit['allowed']) {
        http_response_code(429);
        header('Retry-After: ' . $rateLimit['retry_after']);
        echo json_encode(['ok' => false, 'error' => 'rate_limited'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
} catch (Throwable $e) {
    error_log('[FLUS Web] analytics rate limit: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'temporarily_unavailable'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$rawBody = file_get_contents('php://input');
if (!is_string($rawBody) || trim($rawBody) === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'empty_body'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (strlen($rawBody) > 8192) {
    http_response_code(413);
    echo json_encode(['ok' => false, 'error' => 'payload_too_large'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$stored = web_analytics_store_event($payload);
http_response_code($stored ? 204 : 202);
if (!$stored) {
    echo json_encode(['ok' => false], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
