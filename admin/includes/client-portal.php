<?php
declare(strict_types=1);

if (!function_exists('portal_url')) {
    function portal_url(string $path = ''): string
    {
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/portal/index.php'));
        $basePath = rtrim(dirname($scriptName), '/');
        if ($basePath === '' || $basePath === '.') {
            $basePath = '/portal';
        }

        if ($path === '') {
            return $basePath;
        }

        return $basePath . '/' . ltrim($path, '/');
    }
}

if (!function_exists('portal_public_asset_url')) {
    function portal_public_asset_url(string $path): string
    {
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/portal/index.php'));
        $basePath = rtrim(dirname($scriptName), '/');
        $publicBase = preg_replace('#/portal$#', '', $basePath) ?: '';

        return $publicBase . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('portal_admin_asset_url')) {
    function portal_admin_asset_url(string $path): string
    {
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/portal/index.php'));
        $basePath = rtrim(dirname($scriptName), '/');
        $publicBase = preg_replace('#/portal$#', '', $basePath) ?: '';

        return $publicBase . '/admin/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('portal_current_user')) {
    function portal_current_user(): ?array
    {
        return $_SESSION['client_portal_user'] ?? null;
    }
}

if (!function_exists('portal_current_role')) {
    function portal_current_role(): string
    {
        $user = portal_current_user();
        $role = is_array($user) ? (string) ($user['role'] ?? 'viewer') : 'viewer';

        return in_array($role, ['owner', 'manager', 'viewer'], true) ? $role : 'viewer';
    }
}

if (!function_exists('portal_role_can')) {
    function portal_role_can(string $capability, ?string $role = null): bool
    {
        $role = $role ?? portal_current_role();
        $capabilities = [
            'owner' => ['view_sales', 'view_financials', 'view_stock', 'view_operations'],
            'manager' => ['view_sales', 'view_financials', 'view_stock', 'view_operations'],
            'viewer' => ['view_stock', 'view_operations'],
        ];

        return in_array($capability, $capabilities[$role] ?? $capabilities['viewer'], true);
    }
}

if (!function_exists('portal_is_logged_in')) {
    function portal_is_logged_in(): bool
    {
        $user = portal_current_user();
        return is_array($user) && !empty($user['id']) && !empty($user['client_id']);
    }
}

if (!function_exists('portal_login_user')) {
    function portal_login_user(array $user, array $membership): void
    {
        session_regenerate_id(true);
        $_SESSION['client_portal_user'] = [
            'id' => (int) $user['id'],
            'email' => (string) $user['email'],
            'full_name' => (string) ($user['full_name'] ?? ''),
            'client_id' => (int) $membership['client_id'],
            'client_name' => (string) ($membership['trade_name'] ?: $membership['legal_name']),
            'role' => (string) ($membership['role'] ?? 'owner'),
        ];
    }
}

if (!function_exists('portal_logout_user')) {
    function portal_logout_user(): void
    {
        unset($_SESSION['client_portal_user']);
        session_regenerate_id(true);
    }
}

if (!function_exists('require_portal_login')) {
    function require_portal_login(): void
    {
        admin_start_session();
        if (!portal_is_logged_in()) {
            set_flash('error', 'Inicia sesion para ver tu negocio.');
            redirect_to(portal_url('login.php'));
        }
    }
}

if (!function_exists('portal_find_user_membership')) {
    function portal_find_user_membership(PDO $pdo, string $email): ?array
    {
        $stmt = $pdo->prepare("
            SELECT
                u.id,
                u.email,
                u.full_name,
                u.password_hash,
                u.is_active AS user_active,
                m.client_id,
                m.role,
                m.is_active AS membership_active,
                c.legal_name,
                c.trade_name,
                c.status AS client_status
            FROM client_portal_users u
            INNER JOIN client_portal_memberships m ON m.user_id = u.id
            INNER JOIN clients c ON c.id = m.client_id
            WHERE u.email = :email
              AND u.is_active = 1
              AND m.is_active = 1
            ORDER BY m.id ASC
            LIMIT 1
        ");
        $stmt->execute(['email' => strtolower(trim($email))]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }
}

if (!function_exists('portal_authenticate')) {
    function portal_authenticate(PDO $pdo, string $email, string $password): ?array
    {
        $row = portal_find_user_membership($pdo, $email);
        if (!$row) {
            return null;
        }

        if (!(int) $row['user_active'] || !(int) $row['membership_active']) {
            return null;
        }

        if (!password_verify($password, (string) $row['password_hash'])) {
            return null;
        }

        return [
            'user' => [
                'id' => (int) $row['id'],
                'email' => (string) $row['email'],
                'full_name' => (string) ($row['full_name'] ?? ''),
            ],
            'membership' => [
                'client_id' => (int) $row['client_id'],
                'role' => (string) $row['role'],
                'legal_name' => (string) $row['legal_name'],
                'trade_name' => (string) ($row['trade_name'] ?? ''),
                'client_status' => (string) $row['client_status'],
            ],
        ];
    }
}

if (!function_exists('portal_client_installations_summary')) {
    function portal_client_installations_summary(PDO $pdo, int $clientId): array
    {
        if (!admin_cloud_sync_ensure_schema($pdo)) {
            return ['total' => 0, 'online' => 0, 'offline' => 0, 'last_seen_at' => null, 'rows' => []];
        }

        $stmt = $pdo->prepare("
            SELECT
                i.display_name,
                i.device_label,
                i.app_version,
                i.last_seen_at,
                i.created_at,
                b.name AS branch_name
            FROM client_installations i
            LEFT JOIN client_branches b ON b.id = i.branch_id
            WHERE i.client_id = :client_id
            ORDER BY i.last_seen_at DESC, i.id DESC
            LIMIT 20
        ");
        $stmt->execute(['client_id' => $clientId]);
        $rows = $stmt->fetchAll();

        $utc = new DateTimeZone('UTC');
        $onlineCutoff = new DateTimeImmutable('-10 minutes', $utc);
        $online = 0;
        $offline = 0;
        $lastSeenAt = null;
        $firstSeenAt = null;

        foreach ($rows as $row) {
            if ($lastSeenAt === null && !empty($row['last_seen_at'])) {
                $lastSeenAt = (string) $row['last_seen_at'];
            }
            if (!empty($row['created_at']) && ($firstSeenAt === null || (string) $row['created_at'] < $firstSeenAt)) {
                $firstSeenAt = (string) $row['created_at'];
            }

            $lastSeen = !empty($row['last_seen_at'])
                ? DateTimeImmutable::createFromFormat('Y-m-d H:i:s', (string) $row['last_seen_at'], $utc)
                : false;

            if ($lastSeen && $lastSeen >= $onlineCutoff) {
                $online++;
            } else {
                $offline++;
            }
        }

        return [
            'total' => count($rows),
            'online' => $online,
            'offline' => $offline,
            'last_seen_at' => $lastSeenAt,
            'first_seen_at' => $firstSeenAt,
            'rows' => $rows,
        ];
    }
}

if (!function_exists('portal_client_license_summary')) {
    function portal_client_license_summary(PDO $pdo, int $clientId): ?array
    {
        $stmt = $pdo->prepare("
            SELECT license_key, status, plan_type, expires_at
            FROM licenses
            WHERE client_id = :client_id
            ORDER BY expires_at DESC, id DESC
            LIMIT 1
        ");
        $stmt->execute(['client_id' => $clientId]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $row['effective_status'] = license_current_status((string) $row['status'], (string) $row['expires_at']);
        return $row;
    }
}
