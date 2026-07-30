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

if (!function_exists('portal_current_branch_ids')) {
    function portal_current_branch_ids(): array
    {
        $user = portal_current_user();
        $branchIds = is_array($user) && is_array($user['branch_ids'] ?? null) ? $user['branch_ids'] : [];
        return array_values(array_unique(array_filter(array_map('intval', $branchIds), static fn (int $id): bool => $id > 0)));
    }
}

if (!function_exists('portal_current_branch_scope')) {
    function portal_current_branch_scope(): ?array
    {
        $user = portal_current_user();
        if (!is_array($user) || !($user['branch_restricted'] ?? false)) {
            return null;
        }
        return portal_current_branch_ids();
    }
}

if (!function_exists('portal_membership_branch_ids')) {
    function portal_membership_branch_ids(PDO $pdo, int $membershipId, int $clientId, string $role, string $branchScope = 'all'): ?array
    {
        if ($membershipId <= 0 || $clientId <= 0 || $role === 'owner' || $branchScope !== 'selected') {
            return null;
        }
        $stmt = $pdo->prepare("
            SELECT mb.branch_id
            FROM client_portal_membership_branches mb
            INNER JOIN client_branches b ON b.id = mb.branch_id
            WHERE mb.membership_id = :membership_id
              AND b.client_id = :client_id
              AND b.status = 'active'
            ORDER BY b.name ASC, b.id ASC
        ");
        $stmt->execute(['membership_id' => $membershipId, 'client_id' => $clientId]);
        return array_values(array_unique(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN))));
    }
}

if (!function_exists('portal_role_can')) {
    function portal_role_can(string $capability, ?string $role = null): bool
    {
        $role = $role ?? portal_current_role();
        $capabilities = [
            'owner' => ['view_sales', 'view_financials', 'view_stock', 'view_operations', 'preview_stock_count', 'preview_price_change', 'change_price'],
            'manager' => ['view_sales', 'view_financials', 'view_stock', 'view_operations', 'preview_stock_count', 'preview_price_change', 'change_price'],
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
            'branch_restricted' => is_array($membership['branch_ids'] ?? null),
            'branch_ids' => is_array($membership['branch_ids'] ?? null) ? $membership['branch_ids'] : [],
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
    function require_portal_login(PDO $pdo): void
    {
        admin_start_session();
        if (!portal_is_logged_in() || !portal_refresh_current_session($pdo)) {
            portal_logout_user();
            set_flash('error', 'Inicia sesion para ver tu negocio.');
            redirect_to(portal_url('login.php'));
        }
    }
}

if (!function_exists('portal_refresh_current_session')) {
    function portal_refresh_current_session(PDO $pdo): bool
    {
        $current = portal_current_user();
        $userId = is_array($current) ? (int) ($current['id'] ?? 0) : 0;
        $currentClientId = is_array($current) ? (int) ($current['client_id'] ?? 0) : 0;
        if ($userId <= 0) {
            return false;
        }

        $stmt = $pdo->prepare("
            SELECT
                u.id,
                u.email,
                u.full_name,
                m.id AS membership_id,
                m.client_id,
                m.role,
                m.branch_scope,
                c.legal_name,
                c.trade_name
            FROM client_portal_users u
            INNER JOIN client_portal_memberships m ON m.user_id = u.id
            INNER JOIN clients c ON c.id = m.client_id
            WHERE u.id = :user_id
              AND u.is_active = 1
              AND m.is_active = 1
              AND c.status = 'activo'
            ORDER BY CASE WHEN m.client_id = :current_client_id THEN 0 ELSE 1 END, m.id ASC
            LIMIT 1
        ");
        $stmt->execute([
            'user_id' => $userId,
            'current_client_id' => $currentClientId,
        ]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return false;
        }

        $role = (string) ($row['role'] ?? 'viewer');
        $_SESSION['client_portal_user'] = [
            'id' => (int) $row['id'],
            'email' => (string) $row['email'],
            'full_name' => (string) ($row['full_name'] ?? ''),
            'client_id' => (int) $row['client_id'],
            'client_name' => (string) ($row['trade_name'] ?: $row['legal_name']),
            'role' => $role,
            'branch_restricted' => $role !== 'owner' && (string) ($row['branch_scope'] ?? 'all') === 'selected',
            'branch_ids' => portal_membership_branch_ids($pdo, (int) $row['membership_id'], (int) $row['client_id'], $role, (string) ($row['branch_scope'] ?? 'all')) ?? [],
        ];

        return true;
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
                m.id AS membership_id,
                m.client_id,
                m.role,
                m.branch_scope,
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
              AND c.status = 'activo'
            ORDER BY m.id ASC
            LIMIT 1
        ");
        $stmt->execute(['email' => strtolower(trim($email))]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }
}

if (!function_exists('portal_client_branches_summary')) {
    function portal_client_branches_summary(PDO $pdo, int $clientId): array
    {
        if ($clientId <= 0 || !admin_cloud_sync_ensure_schema($pdo)) {
            return [];
        }

        $stmt = $pdo->prepare("
            SELECT
                b.id AS branch_id,
                b.name AS branch_name,
                b.code AS branch_code,
                i.id AS installation_id,
                i.display_name,
                i.device_label,
                i.app_version,
                i.last_seen_at
            FROM client_branches b
            LEFT JOIN client_installations i
              ON i.client_id = b.client_id
             AND i.branch_id = b.id
            WHERE b.client_id = :client_id
              AND b.status = 'active'
            UNION ALL
            SELECT
                0 AS branch_id,
                'Sin sucursal' AS branch_name,
                '' AS branch_code,
                i.id AS installation_id,
                i.display_name,
                i.device_label,
                i.app_version,
                i.last_seen_at
            FROM client_installations i
            WHERE i.client_id = :unassigned_client_id
              AND i.branch_id IS NULL
            ORDER BY branch_name ASC, last_seen_at DESC
        ");
        $stmt->execute([
            'client_id' => $clientId,
            'unassigned_client_id' => $clientId,
        ]);

        $utc = new DateTimeZone('UTC');
        $onlineCutoff = new DateTimeImmutable('-10 minutes', $utc);
        $branches = [];
        foreach ($stmt->fetchAll() as $row) {
            $branchId = (int) ($row['branch_id'] ?? 0);
            $key = $branchId > 0 ? 'branch-' . $branchId : 'unassigned';
            if (!isset($branches[$key])) {
                $branches[$key] = [
                    'branch_id' => $branchId,
                    'branch_name' => (string) ($row['branch_name'] ?? 'Sin sucursal'),
                    'branch_code' => (string) ($row['branch_code'] ?? ''),
                    'installations' => [],
                    'online' => 0,
                    'offline' => 0,
                    'last_seen_at' => null,
                ];
            }

            if (empty($row['installation_id'])) {
                continue;
            }

            $lastSeenRaw = (string) ($row['last_seen_at'] ?? '');
            $lastSeen = $lastSeenRaw !== ''
                ? DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $lastSeenRaw, $utc)
                : false;
            $isOnline = $lastSeen && $lastSeen >= $onlineCutoff;
            $branches[$key]['installations'][] = [
                'installation_id' => (int) $row['installation_id'],
                'display_name' => (string) ($row['display_name'] ?? ''),
                'device_label' => (string) ($row['device_label'] ?? ''),
                'app_version' => (string) ($row['app_version'] ?? ''),
                'last_seen_at' => $lastSeenRaw !== '' ? $lastSeenRaw : null,
                'is_online' => (bool) $isOnline,
            ];
            $branches[$key][$isOnline ? 'online' : 'offline']++;
            if ($lastSeenRaw !== '' && ($branches[$key]['last_seen_at'] === null || $lastSeenRaw > $branches[$key]['last_seen_at'])) {
                $branches[$key]['last_seen_at'] = $lastSeenRaw;
            }
        }

        return array_values($branches);
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
                'membership_id' => (int) $row['membership_id'],
                'client_id' => (int) $row['client_id'],
                'role' => (string) $row['role'],
                'branch_ids' => portal_membership_branch_ids($pdo, (int) $row['membership_id'], (int) $row['client_id'], (string) $row['role'], (string) ($row['branch_scope'] ?? 'all')),
                'legal_name' => (string) $row['legal_name'],
                'trade_name' => (string) ($row['trade_name'] ?? ''),
                'client_status' => (string) $row['client_status'],
            ],
        ];
    }
}

if (!function_exists('portal_client_installations_summary')) {
    function portal_client_installations_summary(PDO $pdo, int $clientId, ?int $branchId = null, ?array $allowedBranchIds = null): array
    {
        if (!admin_cloud_sync_ensure_schema($pdo)) {
            return ['total' => 0, 'online' => 0, 'offline' => 0, 'last_seen_at' => null, 'rows' => []];
        }

        $where = 'WHERE i.client_id = :client_id';
        $params = ['client_id' => $clientId];
        if ($branchId !== null && $branchId > 0) {
            if ($allowedBranchIds !== null && !in_array($branchId, array_map('intval', $allowedBranchIds), true)) {
                $where .= ' AND 1 = 0';
            } else {
                $where .= ' AND i.branch_id = :branch_id';
                $params['branch_id'] = $branchId;
            }
        } elseif ($allowedBranchIds !== null) {
            $branchPlaceholders = [];
            foreach (array_values(array_unique(array_map('intval', $allowedBranchIds))) as $index => $allowedBranchId) {
                if ($allowedBranchId <= 0) continue;
                $key = 'installation_allowed_branch_' . $index;
                $branchPlaceholders[] = ':' . $key;
                $params[$key] = $allowedBranchId;
            }
            $where .= $branchPlaceholders ? ' AND i.branch_id IN (' . implode(',', $branchPlaceholders) . ')' : ' AND 1 = 0';
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
            {$where}
            ORDER BY i.last_seen_at DESC, i.id DESC
            LIMIT 20
        ");
        $stmt->execute($params);
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
            $rowCreatedAt = (string) ($row['created_at'] ?? '');
            $rowFirstSeen = ($rowCreatedAt !== '' && $rowCreatedAt !== '0000-00-00 00:00:00')
                ? $rowCreatedAt
                : (string) ($row['last_seen_at'] ?? '');
            if ($rowFirstSeen !== '' && ($firstSeenAt === null || $rowFirstSeen < $firstSeenAt)) {
                $firstSeenAt = $rowFirstSeen;
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

if (!function_exists('portal_operational_alerts')) {
    function portal_operational_alerts(
        array $branches,
        array $installations,
        array $stockOverview,
        ?array $license,
        DateTimeImmutable $today
    ): array {
        $alerts = [];
        $addAlert = static function (
            string $id,
            string $severity,
            string $title,
            string $message,
            string $action,
            string $actionLabel,
            ?string $meta = null
        ) use (&$alerts): void {
            $alerts[] = compact('id', 'severity', 'title', 'message', 'action', 'actionLabel', 'meta');
        };

        $installationTotal = max(0, (int) ($installations['total'] ?? 0));
        $installationOffline = max(0, (int) ($installations['offline'] ?? 0));
        if ($installationTotal === 0) {
            $addAlert(
                'installations-missing',
                'critical',
                'Sin instalaciones conectadas',
                'Todavia no hay una PC FLUS enviando informacion para este alcance.',
                'branches',
                'Ver sucursales'
            );
        } elseif ($installationOffline > 0) {
            $offlineNames = [];
            foreach ($branches as $branch) {
                $branchInstallations = is_array($branch['installations'] ?? null) ? $branch['installations'] : [];
                if ($branchInstallations && (int) ($branch['online'] ?? 0) === 0) {
                    $offlineNames[] = (string) ($branch['branch_name'] ?? 'Sucursal');
                }
            }
            $offlineLabel = $offlineNames ? implode(', ', array_slice($offlineNames, 0, 3)) : null;
            $addAlert(
                'installations-offline',
                'critical',
                'Sucursal sin contacto',
                $installationOffline . ' instalacion' . ($installationOffline === 1 ? '' : 'es') . ' no reporta en los ultimos 10 minutos.',
                'branches',
                'Revisar conexion',
                $offlineLabel
            );
        }

        if ($installationTotal > 0) {
            $unlinkedNames = [];
            foreach ($branches as $branch) {
                if (empty($branch['installations'])) {
                    $unlinkedNames[] = (string) ($branch['branch_name'] ?? 'Sucursal');
                }
            }
            if ($unlinkedNames) {
                $addAlert(
                    'branches-unlinked',
                    'warning',
                    'Sucursal pendiente de vinculacion',
                    count($unlinkedNames) . ' sucursal' . (count($unlinkedNames) === 1 ? '' : 'es') . ' todavia no tiene una instalacion FLUS asignada.',
                    'branches',
                    'Ver sucursales',
                    implode(', ', array_slice($unlinkedNames, 0, 3))
                );
            }
        }

        $withoutStock = max(0, (int) ($stockOverview['sin_stock'] ?? 0));
        if ($withoutStock > 0) {
            $addAlert(
                'stock-empty',
                'critical',
                'Productos sin stock',
                $withoutStock . ' producto' . ($withoutStock === 1 ? '' : 's') . ' necesita reposicion o revision.',
                'stock_empty',
                'Ver faltantes'
            );
        }

        $lowStock = max(0, (int) ($stockOverview['bajo_minimo'] ?? 0));
        if ($lowStock > 0) {
            $addAlert(
                'stock-low',
                'warning',
                'Stock bajo minimo',
                $lowStock . ' producto' . ($lowStock === 1 ? '' : 's') . ' esta por debajo del minimo configurado.',
                'stock_low',
                'Revisar reposicion'
            );
        }

        if ($license === null) {
            $addAlert(
                'license-missing',
                'critical',
                'Licencia no disponible',
                'No se encontro una licencia asociada al comercio.',
                'summary',
                'Ver estado'
            );
        } else {
            $effectiveStatus = (string) ($license['effective_status'] ?? '');
            if ($effectiveStatus !== 'activa') {
                $addAlert(
                    'license-status',
                    'critical',
                    'Licencia ' . strtolower(status_label($effectiveStatus)),
                    'El estado actual de la licencia requiere revision administrativa.',
                    'summary',
                    'Ver estado',
                    format_date((string) ($license['expires_at'] ?? ''))
                );
            } else {
                $expiresAt = DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($license['expires_at'] ?? ''), $today->getTimezone());
                if ($expiresAt instanceof DateTimeImmutable) {
                    $daysLeft = (int) $today->diff($expiresAt)->format('%r%a');
                    if ($daysLeft >= 0 && $daysLeft <= 15) {
                        $addAlert(
                            'license-expiring',
                            'warning',
                            'Licencia proxima a vencer',
                            $daysLeft === 0 ? 'La licencia vence hoy.' : 'La licencia vence en ' . $daysLeft . ' dias.',
                            'summary',
                            'Ver vencimiento',
                            format_date((string) $license['expires_at'])
                        );
                    }
                }
            }
        }

        $weights = ['critical' => 0, 'warning' => 1, 'info' => 2];
        usort($alerts, static function (array $left, array $right) use ($weights): int {
            return (($weights[$left['severity']] ?? 9) <=> ($weights[$right['severity']] ?? 9))
                ?: strcmp((string) $left['title'], (string) $right['title']);
        });

        return $alerts;
    }
}

if (!function_exists('portal_format_stock_quantity')) {
    function portal_format_stock_quantity(float $quantity, string $unit = ''): string
    {
        $isWhole = abs($quantity - round($quantity)) < 0.0005;
        $number = $isWhole
            ? number_format($quantity, 0, ',', '.')
            : rtrim(rtrim(number_format($quantity, 3, ',', '.'), '0'), ',');

        $unitKey = strtoupper(trim($unit));
        if ($unitKey === '') {
            return $number;
        }

        $isSingular = abs(abs($quantity) - 1.0) < 0.0005;
        $unitLabel = match ($unitKey) {
            'UNIDAD', 'UNID', 'U' => $isSingular ? 'unidad' : 'unidades',
            'CAJA' => $isSingular ? 'caja' : 'cajas',
            'PAQUETE' => $isSingular ? 'paquete' : 'paquetes',
            'LITRO', 'LITROS' => $isSingular ? 'litro' : 'litros',
            'METRO', 'METROS' => $isSingular ? 'metro' : 'metros',
            'KG', 'KILO', 'KILOGRAMO', 'KILOGRAMOS' => 'kg',
            'G', 'GRAMO', 'GRAMOS' => 'g',
            'L', 'ML' => strtolower($unitKey),
            default => strtolower(trim($unit)),
        };

        return $number . ' ' . $unitLabel;
    }
}

if (!function_exists('portal_stock_item_view')) {
    function portal_stock_item_view(array $item): array
    {
        $stock = (float) ($item['stock'] ?? 0);
        $stockMin = max(0.0, (float) ($item['stock_minimo'] ?? 0));
        $reportedState = trim((string) ($item['estado_stock'] ?? 'ok'));
        $unit = trim((string) ($item['unidad_venta'] ?? ''));

        if ($stock <= 0 || $reportedState === 'sin_stock') {
            $state = 'sin_stock';
            $stateLabel = 'Sin stock';
        } elseif ($reportedState === 'bajo_minimo' || ($stockMin > 0 && $stock <= $stockMin)) {
            $state = 'bajo_minimo';
            $stateLabel = 'Bajo minimo';
        } else {
            $state = 'ok';
            $stateLabel = 'Disponible';
        }

        $shortage = max(0.0, $stockMin - max(0.0, $stock));
        $progress = $stockMin > 0
            ? (int) round(min(100, max(0, ($stock / $stockMin) * 100)))
            : ($stock > 0 ? 100 : 0);

        if ($state === 'sin_stock') {
            $guidance = $stockMin > 0
                ? 'Reponer al menos ' . portal_format_stock_quantity($stockMin, $unit)
                : 'No quedan unidades disponibles';
        } elseif ($state === 'bajo_minimo') {
            $guidance = $shortage > 0
                ? 'Faltan ' . portal_format_stock_quantity($shortage, $unit) . ' para el minimo'
                : 'Revisar el minimo configurado';
        } elseif ($stockMin > 0) {
            $guidance = portal_format_stock_quantity(max(0.0, $stock - $stockMin), $unit) . ' sobre el minimo';
        } else {
            $guidance = 'Sin minimo configurado';
        }

        return [
            'state' => $state,
            'state_label' => $stateLabel,
            'stock' => $stock,
            'stock_min' => $stockMin,
            'stock_label' => portal_format_stock_quantity($stock, $unit),
            'stock_min_label' => portal_format_stock_quantity($stockMin, $unit),
            'guidance' => $guidance,
            'progress' => $progress,
        ];
    }
}
