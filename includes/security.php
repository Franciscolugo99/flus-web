<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/config/config.php';
require_once __DIR__ . '/../admin/includes/db.php';

function security_client_ip(): string
{
    $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : 'unknown';
}

function security_rate_limit_key(string $scope, string $identifier): string
{
    $security = admin_config('security', []);
    $salt = trim((string) ($security['rate_limit_salt'] ?? ''));
    if ($salt === '') {
        throw new RuntimeException('Falta configurar security.rate_limit_salt.');
    }

    return hash_hmac('sha256', $scope . '|' . $identifier, $salt);
}

function security_rate_limit(string $scope, string $identifier, int $limit, int $windowSeconds): array
{
    if ($limit < 1 || $windowSeconds < 1) {
        throw new InvalidArgumentException('Configuracion de rate limit invalida.');
    }

    $pdo = admin_db();
    $key = security_rate_limit_key($scope, $identifier);
    $windowStart = date('Y-m-d H:i:s', time() - $windowSeconds);

    $stmt = $pdo->prepare(
        'INSERT INTO security_rate_limits (rate_key, scope, hits, window_started_at, updated_at)
         VALUES (:rate_key, :scope, 1, NOW(), NOW())
         ON DUPLICATE KEY UPDATE
            hits = IF(window_started_at <= :window_start, 1, hits + 1),
            window_started_at = IF(window_started_at <= :window_start_reset, NOW(), window_started_at),
            updated_at = NOW()'
    );
    $stmt->execute([
        'rate_key' => $key,
        'scope' => $scope,
        'window_start' => $windowStart,
        'window_start_reset' => $windowStart,
    ]);

    $select = $pdo->prepare(
        'SELECT hits, UNIX_TIMESTAMP(window_started_at) AS window_started_at
         FROM security_rate_limits
         WHERE rate_key = :rate_key
         LIMIT 1'
    );
    $select->execute(['rate_key' => $key]);
    $row = $select->fetch() ?: ['hits' => $limit + 1, 'window_started_at' => time()];

    $hits = (int) $row['hits'];
    $startedAt = (int) $row['window_started_at'];
    $retryAfter = max(1, ($startedAt + $windowSeconds) - time());

    if (random_int(1, 100) === 1) {
        $pdo->exec('DELETE FROM security_rate_limits WHERE updated_at < DATE_SUB(NOW(), INTERVAL 2 DAY)');
    }

    return [
        'allowed' => $hits <= $limit,
        'remaining' => max(0, $limit - $hits),
        'retry_after' => $retryAfter,
    ];
}

function security_rate_limit_reset(string $scope, string $identifier): void
{
    $stmt = admin_db()->prepare('DELETE FROM security_rate_limits WHERE rate_key = :rate_key');
    $stmt->execute(['rate_key' => security_rate_limit_key($scope, $identifier)]);
}

function security_same_origin_request(): bool
{
    $expectedHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $expectedHost = preg_replace('/:\d+$/', '', $expectedHost) ?? $expectedHost;

    foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $header) {
        $value = trim((string) ($_SERVER[$header] ?? ''));
        if ($value === '') {
            continue;
        }

        $host = strtolower((string) parse_url($value, PHP_URL_HOST));
        if ($host === '' || !hash_equals($expectedHost, $host)) {
            return false;
        }
    }

    return true;
}
