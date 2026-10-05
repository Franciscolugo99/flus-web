<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/config/config.php';
require_once __DIR__ . '/../admin/includes/db.php';

/**
 * Detecta si la IP que nos conecta es un proxy/CDN confiable.
 * Solo en ese caso tiene sentido creer en CF-Connecting-IP / X-Forwarded-For;
 * de lo contrario un atacante podria falsificar la cabecera y saltarse el
 * rate limit cambiando de "IP" en cada intento.
 */
function security_is_trusted_proxy(string $ip): bool
{
    if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
        return false;
    }

    // Rangos de Cloudflare (IPv4 + IPv6).
    $ranges = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
        '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
        '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32',
        '2405:b500::/32', '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
    ];

    foreach ($ranges as $range) {
        if (security_ip_in_range($ip, $range)) {
            return true;
        }
    }

    return false;
}

/** Comprueba si $ip cae dentro de $range en notacion CIDR. */
function security_ip_in_range(string $ip, string $range): bool
{
    if (strpos($range, '/') === false) {
        return $ip === $range;
    }

    [$subnet, $bits] = explode('/', $range, 2);
    $bits = (int) $bits;

    $ipBin = @inet_pton($ip);
    $subnetBin = @inet_pton($subnet);
    if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
        return false;
    }

    $bytes = intdiv($bits, 8);
    $remainder = $bits % 8;

    if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
        return false;
    }

    if ($remainder === 0) {
        return true;
    }

    $mask = 0xff << (8 - $remainder) & 0xff;
    return (ord($ipBin[$bytes]) & $mask) === (ord($subnetBin[$bytes]) & $mask);
}

/**
 * IP real del visitante para rate limiting.
 * Usa cabeceras de proxy SOLO si quien conecta es un proxy confiable.
 */
function security_client_ip(): string
{
    $remote = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    $remote = filter_var($remote, FILTER_VALIDATE_IP) !== false ? $remote : '';

    if ($remote !== '' && security_is_trusted_proxy($remote)) {
        $candidates = [
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate === '') {
                continue;
            }
            if (strpos($candidate, ',') !== false) {
                $candidate = trim(explode(',', $candidate)[0]);
            }
            if (filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
                return $candidate;
            }
        }
    }

    return $remote !== '' ? $remote : 'unknown';
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

/* ──────────────────────────────────────────────────────────────
 * CSRF para formularios publicos.
 *
 * Patron "double submit": se genera un token aleatorio, se guarda en
 * $_SESSION y ademas en una cookie del mismo sitio. El formulario lo envia
 * en un campo oculto y el servidor exige que el campo y la cookie coincidan.
 * Un tercero no puede leer la cookie (SameSite=Lax + no accesible por JS),
 * por lo que no puede fabricar un POST valido.
 * ────────────────────────────────────────────────────────────── */

const SECURITY_CSRF_COOKIE = 'flus_csrf';
const SECURITY_CSRF_FIELD = '_csrf';
const SECURITY_CSRF_TTL = 7200; // 2 horas

function security_secure_context(): bool
{
    return (($_SERVER['HTTPS'] ?? '') === 'on')
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

/**
 * Devuelve el token CSRF vigente, generandolo si hace falta.
 * No inicia sesion en cada visita: la cookie es la fuente de verdad.
 */
function security_csrf_token(): string
{
    static $token = null;
    if ($token !== null) {
        return $token;
    }

    $existing = (string) ($_COOKIE[SECURITY_CSRF_COOKIE] ?? '');
    if ($existing !== '' && preg_match('/^[a-f0-9]{64}$/', $existing) === 1) {
        return $token = $existing;
    }

    $token = bin2hex(random_bytes(32));

    if (!headers_sent()) {
        setcookie(SECURITY_CSRF_COOKIE, $token, [
            'expires' => time() + SECURITY_CSRF_TTL,
            'path' => '/',
            'secure' => security_secure_context(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    $_COOKIE[SECURITY_CSRF_COOKIE] = $token;

    return $token;
}

/** Campo oculto listo para insertar dentro del <form>. */
function security_csrf_input(): string
{
    return '<input type="hidden" name="' . SECURITY_CSRF_FIELD . '" value="'
        . htmlspecialchars(security_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Valida el POST. Exige token en el cuerpo Y cookie coincidente.
 * La cookie es HttpOnly, asi que el token se compara solo en el servidor.
 */
function security_csrf_validate(): bool
{
    $posted = trim((string) ($_POST[SECURITY_CSRF_FIELD] ?? ''));
    $cookie = trim((string) ($_COOKIE[SECURITY_CSRF_COOKIE] ?? ''));

    if ($posted === '' || $cookie === '') {
        return false;
    }

    // Verificacion de formato antes de comparar.
    if (preg_match('/^[a-f0-9]{64}$/', $posted) !== 1
        || preg_match('/^[a-f0-9]{64}$/', $cookie) !== 1) {
        return false;
    }

    return hash_equals($cookie, $posted);
}

