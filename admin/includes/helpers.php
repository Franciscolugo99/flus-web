<?php
declare(strict_types=1);

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('admin_url')) {
    function admin_url(string $path = ''): string
    {
        $basePath = rtrim((string) admin_config('base_path', '/admin'), '/');
        if ($path === '') {
            return $basePath;
        }

        return $basePath . '/' . ltrim($path, '/');
    }
}

if (!function_exists('redirect_to')) {
    function redirect_to(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }
}

if (!function_exists('set_flash')) {
    function set_flash(string $type, string $message): void
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message,
        ];
    }
}

if (!function_exists('get_flash')) {
    function get_flash(): ?array
    {
        if (empty($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
            return null;
        }

        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);

        return $flash;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_input')) {
    function csrf_input(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(): void
    {
        $token = $_POST['_csrf'] ?? '';
        if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
            http_response_code(419);
            exit('Solicitud inválida. Token CSRF incorrecto.');
        }
    }
}

if (!function_exists('request_is_post')) {
    function request_is_post(): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }
}

if (!function_exists('old_input')) {
    function old_input(string $key, $default = ''): string
    {
        if (isset($_POST[$key])) {
            return trim((string) $_POST[$key]);
        }

        return (string) $default;
    }
}

if (!function_exists('normalize_nullable')) {
    function normalize_nullable(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}

if (!function_exists('normalize_email')) {
    function normalize_email(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return strtolower($value);
    }
}

if (!function_exists('is_valid_email_optional')) {
    function is_valid_email_optional(?string $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('admin_client_statuses')) {
    function admin_client_statuses(): array
    {
        return [
            'activo' => 'Activo',
            'demo' => 'Demo',
            'suspendido' => 'Suspendido',
            'inactivo' => 'Inactivo',
        ];
    }
}

if (!function_exists('admin_license_statuses')) {
    function admin_license_statuses(): array
    {
        return [
            'activa' => 'Activa',
            'por_vencer' => 'Por vencer',
            'vencida' => 'Vencida',
            'suspendida' => 'Suspendida',
            'demo' => 'Demo',
        ];
    }
}

if (!function_exists('admin_payment_methods')) {
    function admin_payment_methods(): array
    {
        return [
            'efectivo' => 'Efectivo',
            'transferencia' => 'Transferencia',
            'mercado_pago' => 'Mercado Pago',
            'otro' => 'Otro',
        ];
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $date, string $fallback = '—'): string
    {
        if (!$date) {
            return $fallback;
        }

        $time = strtotime($date);
        if ($time === false) {
            return $fallback;
        }

        return date('d/m/Y', $time);
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime(?string $date, string $fallback = '—'): string
    {
        if (!$date) {
            return $fallback;
        }

        $time = strtotime($date);
        if ($time === false) {
            return $fallback;
        }

        return date('d/m/Y H:i', $time);
    }
}

if (!function_exists('format_money')) {
    function format_money($amount): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }

        return '$' . number_format((float) $amount, 2, ',', '.');
    }
}

if (!function_exists('license_current_status')) {
    function license_current_status(string $storedStatus, ?string $expiresAt): string
    {
        if (in_array($storedStatus, ['suspendida', 'demo'], true)) {
            return $storedStatus;
        }

        if (!$expiresAt) {
            return $storedStatus;
        }

        $today = new DateTimeImmutable('today');
        $expiry = DateTimeImmutable::createFromFormat('Y-m-d', $expiresAt);
        if (!$expiry) {
            return $storedStatus;
        }

        if ($expiry < $today) {
            return 'vencida';
        }

        $limit = $today->modify('+15 days');
        if ($expiry <= $limit) {
            return 'por_vencer';
        }

        return 'activa';
    }
}

if (!function_exists('badge_class')) {
    function badge_class(string $status): string
    {
        $map = [
            'activo' => 'is-success',
            'activa' => 'is-success',
            'demo' => 'is-info',
            'por_vencer' => 'is-warning',
            'vencida' => 'is-danger',
            'suspendido' => 'is-muted',
            'suspendida' => 'is-muted',
            'inactivo' => 'is-muted',
            'activo_cliente' => 'is-success',
        ];

        return $map[$status] ?? 'is-muted';
    }
}

if (!function_exists('status_label')) {
    function status_label(string $status): string
    {
        $labels = admin_client_statuses() + admin_license_statuses() + admin_payment_methods() + [
            'activo' => 'Activo',
            'inactivo' => 'Inactivo',
        ];
        return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }
}

if (!function_exists('generate_license_key')) {
    function generate_license_key(PDO $pdo): string
    {
        do {
            $parts = [];
            for ($i = 0; $i < 3; $i++) {
                $parts[] = strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
            }
            $key = 'FLUS-' . implode('-', $parts);

            $stmt = $pdo->prepare('SELECT id FROM licenses WHERE license_key = :license_key LIMIT 1');
            $stmt->execute(['license_key' => $key]);
            $exists = $stmt->fetchColumn();
        } while ($exists);

        return $key;
    }
}

if (!function_exists('h')) {
    function h($value): string
    {
        return e($value);
    }
}

if (!function_exists('current_admin')) {
    function current_admin(): ?array
    {
        return $_SESSION['admin_user'] ?? null;
    }
}

if (!function_exists('admin_public_error')) {
    function admin_public_error(Throwable $e, string $message = 'No se pudo completar la operación. Revisá la configuración o intentá nuevamente.'): string
    {
        error_log('[FLUS Admin] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

        if (admin_config('env', 'production') === 'development') {
            return $message . ' Detalle técnico: ' . $e->getMessage();
        }

        return $message;
    }
}
