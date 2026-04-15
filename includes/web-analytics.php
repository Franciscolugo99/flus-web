<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/includes/db.php';
require_once __DIR__ . '/../admin/includes/auth.php';

if (!function_exists('web_analytics_allowed_events')) {
    function web_analytics_allowed_events(): array
    {
        return [
            'page_view',
            'click_whatsapp',
            'click_contact',
            'click_demo',
            'click_download',
        ];
    }
}

if (!function_exists('web_analytics_config')) {
    function web_analytics_config(string $key, $default = null)
    {
        $config = admin_config('analytics', []);
        return is_array($config) && array_key_exists($key, $config) ? $config[$key] : $default;
    }
}

if (!function_exists('web_analytics_remote_ip')) {
    function web_analytics_remote_ip(): string
    {
        return trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    }
}

if (!function_exists('web_analytics_is_local_request')) {
    function web_analytics_is_local_request(): bool
    {
        $ip = web_analytics_remote_ip();
        if (in_array($ip, ['127.0.0.1', '::1'], true)) {
            return true;
        }

        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
        $host = preg_replace('/:\d+$/', '', $host) ?? $host;

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || substr($host, -6) === '.local';
    }
}

if (!function_exists('web_analytics_is_excluded_ip')) {
    function web_analytics_is_excluded_ip(string $ip): bool
    {
        if ($ip === '') {
            return false;
        }

        $excludedIps = web_analytics_config('excluded_ips', []);
        if (!is_array($excludedIps)) {
            return false;
        }

        foreach ($excludedIps as $excludedIp) {
            if (trim((string) $excludedIp) === $ip) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('web_analytics_has_admin_session')) {
    function web_analytics_has_admin_session(): bool
    {
        if (!web_analytics_config('exclude_admin_sessions', true)) {
            return false;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            return admin_is_logged_in();
        }

        $sessionName = (string) admin_config('session_name', 'flus_admin_session');
        if (empty($_COOKIE[$sessionName])) {
            return false;
        }

        admin_start_session();
        return admin_is_logged_in();
    }
}

if (!function_exists('web_analytics_is_bot')) {
    function web_analytics_is_bot(string $userAgent): bool
    {
        if ($userAgent === '') {
            return false;
        }

        $needles = web_analytics_config('excluded_user_agents', []);
        if (!is_array($needles) || $needles === []) {
            $needles = ['bot', 'crawl', 'spider', 'slurp', 'wget', 'curl'];
        }

        foreach ($needles as $needle) {
            $needle = trim((string) $needle);
            if ($needle !== '' && stripos($userAgent, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('web_analytics_table_exists')) {
    function web_analytics_table_exists(PDO $pdo): bool
    {
        static $exists = null;

        if ($exists !== null) {
            return $exists;
        }

        $stmt = $pdo->query("SHOW TABLES LIKE 'web_events'");
        $exists = (bool) $stmt->fetchColumn();
        return $exists;
    }
}

if (!function_exists('web_analytics_limit_string')) {
    function web_analytics_limit_string($value, int $maxLength): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $maxLength);
        }

        return substr($value, 0, $maxLength);
    }
}

if (!function_exists('web_analytics_device_type')) {
    function web_analytics_device_type(string $userAgent): string
    {
        $ua = strtolower($userAgent);
        if ($ua === '') {
            return 'unknown';
        }

        if (preg_match('/ipad|tablet|playbook|silk|(android(?!.*mobile))/i', $userAgent)) {
            return 'tablet';
        }

        if (preg_match('/mobile|iphone|ipod|android|blackberry|iemobile|opera mini/i', $userAgent)) {
            return 'mobile';
        }

        return 'desktop';
    }
}

if (!function_exists('web_analytics_ip_hash')) {
    function web_analytics_ip_hash(): ?string
    {
        $ip = web_analytics_remote_ip();
        if ($ip === '') {
            return null;
        }

        $salt = 'flus-web-analytics-v1';
        return hash('sha256', $salt . '|' . $ip);
    }
}

if (!function_exists('web_analytics_should_ignore_request')) {
    function web_analytics_should_ignore_request(string $userAgent): bool
    {
        if ((string) admin_config('env', 'production') !== 'production') {
            return true;
        }

        if (web_analytics_config('exclude_local_requests', true) && web_analytics_is_local_request()) {
            return true;
        }

        if (web_analytics_is_excluded_ip(web_analytics_remote_ip())) {
            return true;
        }

        if (web_analytics_has_admin_session()) {
            return true;
        }

        return web_analytics_is_bot($userAgent);
    }
}

if (!function_exists('web_analytics_clean_extra')) {
    function web_analytics_clean_extra($extra): ?string
    {
        if (is_string($extra)) {
            $decoded = json_decode($extra, true);
            $extra = is_array($decoded) ? $decoded : ['value' => $extra];
        }

        if (!is_array($extra)) {
            return null;
        }

        $clean = [];
        $allowedKeys = ['href', 'label', 'form_id', 'form_name', 'action', 'download'];

        foreach ($allowedKeys as $key) {
            if (!array_key_exists($key, $extra)) {
                continue;
            }

            $value = $extra[$key];
            if (is_bool($value)) {
                $clean[$key] = $value;
                continue;
            }

            if (is_scalar($value)) {
                $value = web_analytics_limit_string($value, 255);
                if ($value !== null) {
                    $clean[$key] = $value;
                }
            }
        }

        if ($clean === []) {
            return null;
        }

        return json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null;
    }
}

if (!function_exists('web_analytics_normalize_page_path')) {
    function web_analytics_normalize_page_path(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '/';
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = $url;
        }

        $path = '/' . ltrim(str_replace('\\', '/', $path), '/');
        $path = preg_replace('#/+#', '/', $path) ?? $path;

        return $path;
    }
}

if (!function_exists('web_analytics_page_label')) {
    function web_analytics_page_label(?string $url): string
    {
        $path = web_analytics_normalize_page_path((string) $url);
        $trimmed = trim($path, '/');
        $file = basename($trimmed);

        if ($trimmed === '' || $file === 'index.php' || strpos($file, '.') === false) {
            return 'Inicio';
        }

        $labels = [
            'contacto.php' => 'Contacto',
            'sistema-de-gestion.php' => 'Sistema de gestion',
            'sistema-pos.php' => 'Sistema POS',
            'control-de-stock.php' => 'Control de stock',
            'facturacion.php' => 'Facturacion',
            '404.php' => 'Pagina 404',
        ];

        return $labels[$file] ?? ucfirst(str_replace(['-', '.php'], [' ', ''], $file));
    }
}

if (!function_exists('web_analytics_event_label')) {
    function web_analytics_event_label(string $eventType): string
    {
        $labels = [
            'page_view' => 'Vista de pagina',
            'click_whatsapp' => 'WhatsApp',
            'click_contact' => 'Contacto',
            'click_demo' => 'Demo',
            'click_download' => 'Descarga',
        ];

        return $labels[$eventType] ?? $eventType;
    }
}

if (!function_exists('web_analytics_referrer_label')) {
    function web_analytics_referrer_label(?string $referrer): string
    {
        $referrer = trim((string) $referrer);
        if ($referrer === '') {
            return 'Directo / sin referencia';
        }

        $host = parse_url($referrer, PHP_URL_HOST);
        return is_string($host) && $host !== ''
            ? (preg_replace('/^www\./', '', strtolower($host)) ?? strtolower($host))
            : (web_analytics_limit_string($referrer, 80) ?? 'Referencia externa');
    }
}

if (!function_exists('web_analytics_clean_referrer')) {
    function web_analytics_clean_referrer($referrer): ?string
    {
        $referrer = web_analytics_limit_string($referrer, 255);
        if ($referrer === null) {
            return null;
        }

        $referrerHost = parse_url($referrer, PHP_URL_HOST);
        $currentHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
        $currentHost = preg_replace('/:\d+$/', '', $currentHost) ?? $currentHost;

        if (is_string($referrerHost) && $referrerHost !== '') {
            $referrerHost = preg_replace('/^www\./', '', strtolower($referrerHost)) ?? strtolower($referrerHost);
            $currentHost = preg_replace('/^www\./', '', strtolower($currentHost)) ?? strtolower($currentHost);
            if ($currentHost !== '' && $referrerHost === $currentHost) {
                return null;
            }
        }

        return $referrer;
    }
}

if (!function_exists('web_analytics_normalize_payload')) {
    function web_analytics_normalize_payload(array $payload): ?array
    {
        $eventType = trim((string) ($payload['event_type'] ?? ''));
        if ($eventType === '' || !in_array($eventType, web_analytics_allowed_events(), true)) {
            return null;
        }

        $userAgent = web_analytics_limit_string($_SERVER['HTTP_USER_AGENT'] ?? '', 255) ?? '';
        if (web_analytics_should_ignore_request($userAgent)) {
            return null;
        }

        $extra = $payload['extra_json'] ?? $payload['extra'] ?? null;
        $extra = web_analytics_clean_extra($extra);

        return [
            'event_type' => $eventType,
            'page_url' => web_analytics_limit_string($payload['page_url'] ?? '', 255) ?? '/',
            'page_title' => web_analytics_limit_string($payload['page_title'] ?? '', 190),
            'referrer' => web_analytics_clean_referrer($payload['referrer'] ?? ''),
            'utm_source' => web_analytics_limit_string($payload['utm_source'] ?? '', 100),
            'utm_medium' => web_analytics_limit_string($payload['utm_medium'] ?? '', 100),
            'utm_campaign' => web_analytics_limit_string($payload['utm_campaign'] ?? '', 100),
            'session_id' => web_analytics_limit_string($payload['session_id'] ?? '', 100),
            'ip_hash' => web_analytics_ip_hash(),
            'user_agent' => $userAgent,
            'device_type' => web_analytics_device_type($userAgent),
            'extra_json' => $extra,
        ];
    }
}

if (!function_exists('web_analytics_store_event')) {
    function web_analytics_store_event(array $payload): bool
    {
        $data = web_analytics_normalize_payload($payload);
        if ($data === null) {
            return false;
        }

        try {
            $pdo = admin_db();
            if (!web_analytics_table_exists($pdo)) {
                return false;
            }

            $stmt = $pdo->prepare('INSERT INTO web_events (
                event_type,
                page_url,
                page_title,
                referrer,
                utm_source,
                utm_medium,
                utm_campaign,
                session_id,
                ip_hash,
                user_agent,
                device_type,
                extra_json
            ) VALUES (
                :event_type,
                :page_url,
                :page_title,
                :referrer,
                :utm_source,
                :utm_medium,
                :utm_campaign,
                :session_id,
                :ip_hash,
                :user_agent,
                :device_type,
                :extra_json
            )');

            return $stmt->execute($data);
        } catch (Throwable $e) {
            return false;
        }
    }
}
