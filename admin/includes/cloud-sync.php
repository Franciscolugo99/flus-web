<?php
declare(strict_types=1);

if (!function_exists('admin_cloud_sync_ensure_schema')) {
    function admin_cloud_sync_ensure_schema(PDO $pdo): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        $requiredTables = [
            'client_portal_users',
            'client_portal_memberships',
            'client_portal_membership_branches',
            'client_branches',
            'client_installations',
            'cloud_sync_events',
            'cloud_sync_stock_items',
            'cloud_commands',
        ];

        try {
            $placeholders = implode(',', array_fill(0, count($requiredTables), '?'));
            $stmt = $pdo->prepare("
                SELECT table_name
                FROM information_schema.TABLES
                WHERE table_schema = DATABASE()
                  AND table_name IN ({$placeholders})
            ");
            $stmt->execute($requiredTables);
            $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (count(array_unique($existing)) === count($requiredTables)) {
                $columnStmt = $pdo->query("
                    SELECT COUNT(*)
                    FROM information_schema.COLUMNS
                    WHERE table_schema = DATABASE()
                      AND table_name = 'client_portal_memberships'
                      AND column_name = 'branch_scope'
                ");
                if ((int) $columnStmt->fetchColumn() === 1) {
                    $ready = true;
                    return true;
                }
            }
        } catch (Throwable $e) {
            error_log('[FLUS Admin] cloud sync schema check: ' . $e->getMessage());
        }

        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS client_portal_users (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(190) NOT NULL,
                    full_name VARCHAR(150) DEFAULT NULL,
                    password_hash VARCHAR(255) NOT NULL,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    last_login_at DATETIME DEFAULT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_client_portal_users_email (email),
                    KEY idx_client_portal_users_active (is_active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS client_portal_memberships (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL,
                    client_id INT UNSIGNED NOT NULL,
                    role VARCHAR(30) NOT NULL DEFAULT 'owner',
                    branch_scope VARCHAR(20) NOT NULL DEFAULT 'all',
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_client_portal_membership (user_id, client_id),
                    KEY idx_client_portal_memberships_client_id (client_id),
                    KEY idx_client_portal_memberships_active (is_active),
                    CONSTRAINT fk_client_portal_memberships_user FOREIGN KEY (user_id) REFERENCES client_portal_users(id) ON DELETE RESTRICT ON UPDATE CASCADE,
                    CONSTRAINT fk_client_portal_memberships_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS client_branches (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    client_id INT UNSIGNED NOT NULL,
                    name VARCHAR(150) NOT NULL,
                    code VARCHAR(60) NOT NULL,
                    address VARCHAR(255) DEFAULT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'active',
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_client_branches_code (client_id, code),
                    KEY idx_client_branches_client_id (client_id),
                    KEY idx_client_branches_status (status),
                    CONSTRAINT fk_client_branches_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $columnStmt = $pdo->query("
                SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE table_schema = DATABASE()
                  AND table_name = 'client_portal_memberships'
                  AND column_name = 'branch_scope'
            ");
            if ((int) $columnStmt->fetchColumn() === 0) {
                $pdo->exec("ALTER TABLE client_portal_memberships ADD COLUMN branch_scope VARCHAR(20) NOT NULL DEFAULT 'all' AFTER role");
            }

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS client_portal_membership_branches (
                    membership_id INT UNSIGNED NOT NULL,
                    branch_id INT UNSIGNED NOT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (membership_id, branch_id),
                    KEY idx_portal_membership_branches_branch (branch_id),
                    CONSTRAINT fk_portal_membership_branches_membership FOREIGN KEY (membership_id) REFERENCES client_portal_memberships(id) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT fk_portal_membership_branches_branch FOREIGN KEY (branch_id) REFERENCES client_branches(id) ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS client_installations (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    client_id INT UNSIGNED NOT NULL,
                    branch_id INT UNSIGNED DEFAULT NULL,
                    license_id INT UNSIGNED NOT NULL,
                    installation_uid VARCHAR(120) NOT NULL,
                    display_name VARCHAR(150) DEFAULT NULL,
                    app_version VARCHAR(40) DEFAULT NULL,
                    device_label VARCHAR(150) DEFAULT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'online',
                    last_seen_at DATETIME DEFAULT NULL,
                    last_payload_at DATETIME DEFAULT NULL,
                    last_ip_hash CHAR(64) DEFAULT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_client_installations_uid (client_id, installation_uid),
                    KEY idx_client_installations_client_id (client_id),
                    KEY idx_client_installations_branch_id (branch_id),
                    KEY idx_client_installations_license_id (license_id),
                    KEY idx_client_installations_last_seen (last_seen_at),
                    CONSTRAINT fk_client_installations_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT ON UPDATE CASCADE,
                    CONSTRAINT fk_client_installations_branch FOREIGN KEY (branch_id) REFERENCES client_branches(id) ON DELETE SET NULL ON UPDATE CASCADE,
                    CONSTRAINT fk_client_installations_license FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE RESTRICT ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS cloud_sync_events (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    client_id INT UNSIGNED NOT NULL,
                    branch_id INT UNSIGNED DEFAULT NULL,
                    installation_id BIGINT UNSIGNED NOT NULL,
                    license_id INT UNSIGNED NOT NULL,
                    event_uid VARCHAR(120) NOT NULL,
                    event_type VARCHAR(60) NOT NULL,
                    occurred_at DATETIME NOT NULL,
                    received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    payload_json LONGTEXT DEFAULT NULL,
                    summary_json LONGTEXT DEFAULT NULL,
                    UNIQUE KEY uq_cloud_sync_events_installation_event (installation_id, event_uid),
                    KEY idx_cloud_sync_events_client_date (client_id, occurred_at),
                    KEY idx_cloud_sync_events_branch_date (branch_id, occurred_at),
                    KEY idx_cloud_sync_events_type (event_type),
                    KEY idx_cloud_sync_events_license_id (license_id),
                    CONSTRAINT fk_cloud_sync_events_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT ON UPDATE CASCADE,
                    CONSTRAINT fk_cloud_sync_events_branch FOREIGN KEY (branch_id) REFERENCES client_branches(id) ON DELETE SET NULL ON UPDATE CASCADE,
                    CONSTRAINT fk_cloud_sync_events_installation FOREIGN KEY (installation_id) REFERENCES client_installations(id) ON DELETE RESTRICT ON UPDATE CASCADE,
                    CONSTRAINT fk_cloud_sync_events_license FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE RESTRICT ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS cloud_sync_stock_items (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    client_id INT UNSIGNED NOT NULL,
                    branch_id INT UNSIGNED DEFAULT NULL,
                    installation_id BIGINT UNSIGNED NOT NULL,
                    license_id INT UNSIGNED NOT NULL,
                    product_uid VARCHAR(120) NOT NULL,
                    local_product_id INT UNSIGNED DEFAULT NULL,
                    codigo VARCHAR(80) DEFAULT NULL,
                    nombre VARCHAR(190) NOT NULL,
                    categoria VARCHAR(120) DEFAULT NULL,
                    marca VARCHAR(120) DEFAULT NULL,
                    precio DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                    stock DECIMAL(12,3) NOT NULL DEFAULT 0.000,
                    stock_minimo DECIMAL(12,3) NOT NULL DEFAULT 0.000,
                    estado_stock VARCHAR(30) NOT NULL DEFAULT 'ok',
                    unidad_venta VARCHAR(20) DEFAULT NULL,
                    es_pesable TINYINT(1) NOT NULL DEFAULT 0,
                    activo TINYINT(1) NOT NULL DEFAULT 1,
                    product_updated_at DATETIME DEFAULT NULL,
                    last_event_uid VARCHAR(120) DEFAULT NULL,
                    synced_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_cloud_sync_stock_installation_product (installation_id, product_uid),
                    KEY idx_cloud_sync_stock_client_state (client_id, estado_stock),
                    KEY idx_cloud_sync_stock_client_name (client_id, nombre),
                    KEY idx_cloud_sync_stock_branch (branch_id),
                    KEY idx_cloud_sync_stock_license (license_id),
                    CONSTRAINT fk_cloud_sync_stock_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT ON UPDATE CASCADE,
                    CONSTRAINT fk_cloud_sync_stock_branch FOREIGN KEY (branch_id) REFERENCES client_branches(id) ON DELETE SET NULL ON UPDATE CASCADE,
                    CONSTRAINT fk_cloud_sync_stock_installation FOREIGN KEY (installation_id) REFERENCES client_installations(id) ON DELETE RESTRICT ON UPDATE CASCADE,
                    CONSTRAINT fk_cloud_sync_stock_license FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE RESTRICT ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS cloud_commands (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    command_uid VARCHAR(120) NOT NULL,
                    portal_request_uid VARCHAR(120) NOT NULL,
                    client_id INT UNSIGNED NOT NULL,
                    branch_id INT UNSIGNED NOT NULL,
                    installation_id BIGINT UNSIGNED NOT NULL,
                    license_id INT UNSIGNED NOT NULL,
                    requested_by_user_id INT UNSIGNED DEFAULT NULL,
                    command_type VARCHAR(60) NOT NULL,
                    payload_json LONGTEXT NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'pending',
                    attempts INT UNSIGNED NOT NULL DEFAULT 0,
                    available_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    expires_at DATETIME NOT NULL,
                    claimed_at DATETIME DEFAULT NULL,
                    lease_until DATETIME DEFAULT NULL,
                    claim_token_hash CHAR(64) DEFAULT NULL,
                    completed_at DATETIME DEFAULT NULL,
                    result_json LONGTEXT DEFAULT NULL,
                    last_error VARCHAR(190) DEFAULT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_cloud_commands_uid (command_uid),
                    UNIQUE KEY uq_cloud_commands_portal_request (client_id, portal_request_uid),
                    KEY idx_cloud_commands_poll (installation_id, status, available_at),
                    KEY idx_cloud_commands_client_created (client_id, created_at),
                    KEY idx_cloud_commands_branch_created (branch_id, created_at),
                    CONSTRAINT fk_cloud_commands_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT ON UPDATE CASCADE,
                    CONSTRAINT fk_cloud_commands_branch FOREIGN KEY (branch_id) REFERENCES client_branches(id) ON DELETE RESTRICT ON UPDATE CASCADE,
                    CONSTRAINT fk_cloud_commands_installation FOREIGN KEY (installation_id) REFERENCES client_installations(id) ON DELETE RESTRICT ON UPDATE CASCADE,
                    CONSTRAINT fk_cloud_commands_license FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE RESTRICT ON UPDATE CASCADE,
                    CONSTRAINT fk_cloud_commands_portal_user FOREIGN KEY (requested_by_user_id) REFERENCES client_portal_users(id) ON DELETE SET NULL ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $ready = true;
        } catch (Throwable $e) {
            error_log('[FLUS Admin] cloud sync schema: ' . $e->getMessage());
            $ready = false;
        }

        return $ready;
    }
}

if (!function_exists('admin_cloud_sync_hash_ip')) {
    function admin_cloud_sync_hash_ip(): ?string
    {
        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($ip === '') {
            return null;
        }

        $security = admin_config('security', []);
        $salt = (string) ($security['rate_limit_salt'] ?? '');

        return hash('sha256', $salt . '|cloud-sync|' . $ip);
    }
}

if (!function_exists('admin_cloud_sync_normalize_uid')) {
    function admin_cloud_sync_normalize_uid(string $value, int $maxLength = 120): string
    {
        $value = trim($value);
        if ($value === '' || preg_match('/^[A-Za-z0-9._:@-]+$/', $value) !== 1) {
            return '';
        }

        return substr($value, 0, $maxLength);
    }
}

if (!function_exists('admin_cloud_sync_parse_datetime')) {
    function admin_cloud_sync_parse_datetime(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return gmdate('Y-m-d H:i:s');
        }

        try {
            return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
            return gmdate('Y-m-d H:i:s');
        }
    }
}

if (!function_exists('admin_cloud_sync_find_license')) {
    function admin_cloud_sync_find_license(PDO $pdo, string $licenseKey, bool $forUpdate = false): ?array
    {
        $lockClause = $forUpdate ? ' FOR UPDATE' : '';
        $stmt = $pdo->prepare('
            SELECT
                l.*,
                c.legal_name,
                c.trade_name,
                c.status AS client_status
            FROM licenses l
            INNER JOIN clients c ON c.id = l.client_id
            WHERE l.license_key = :license_key
            LIMIT 1
        ' . $lockClause);
        $stmt->execute(['license_key' => $licenseKey]);
        $license = $stmt->fetch();

        return is_array($license) ? $license : null;
    }
}

if (!function_exists('admin_cloud_sync_license_accepts_events')) {
    function admin_cloud_sync_license_accepts_events(array $license): bool
    {
        if (!admin_license_plan_cloud_enabled($license)) {
            return false;
        }

        if (in_array((string) ($license['client_status'] ?? ''), ['suspendido', 'inactivo'], true)) {
            return false;
        }

        $cloudStatus = admin_cloud_status_from_license(
            (string) ($license['status'] ?? ''),
            isset($license['expires_at']) ? (string) $license['expires_at'] : null
        );

        return $cloudStatus === 'active';
    }
}

if (!function_exists('admin_cloud_sync_license_reject_reason')) {
    function admin_cloud_sync_license_reject_reason(array $license): string
    {
        if (!admin_license_plan_cloud_enabled($license)) {
            return 'LICENSE_CLOUD_DISABLED';
        }

        if (in_array((string) ($license['client_status'] ?? ''), ['suspendido', 'inactivo'], true)) {
            return 'CLIENT_NOT_ACTIVE';
        }

        $cloudStatus = admin_cloud_status_from_license(
            (string) ($license['status'] ?? ''),
            isset($license['expires_at']) ? (string) $license['expires_at'] : null
        );

        return $cloudStatus === 'active' ? '' : 'LICENSE_NOT_ACTIVE';
    }
}

if (!function_exists('admin_cloud_sync_upsert_branch')) {
    function admin_cloud_sync_upsert_branch(PDO $pdo, int $clientId, array $branch): ?int
    {
        $code = admin_cloud_sync_normalize_uid((string) ($branch['code'] ?? ''), 60);
        if ($code === '') {
            return null;
        }

        $name = trim((string) ($branch['name'] ?? ''));
        if ($name === '') {
            $name = 'Sucursal ' . $code;
        }

        $stmt = $pdo->prepare('
            INSERT INTO client_branches (client_id, name, code, address, status)
            VALUES (:client_id, :name, :code, :address, :status)
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                address = VALUES(address),
                status = VALUES(status),
                updated_at = CURRENT_TIMESTAMP
        ');
        $stmt->execute([
            'client_id' => $clientId,
            'name' => substr($name, 0, 150),
            'code' => $code,
            'address' => normalize_nullable(isset($branch['address']) ? (string) $branch['address'] : null),
            'status' => substr(trim((string) ($branch['status'] ?? 'active')) ?: 'active', 0, 20),
        ]);

        $select = $pdo->prepare('SELECT id FROM client_branches WHERE client_id = :client_id AND code = :code LIMIT 1');
        $select->execute(['client_id' => $clientId, 'code' => $code]);
        $id = $select->fetchColumn();

        return $id !== false ? (int) $id : null;
    }
}

if (!function_exists('admin_cloud_sync_upsert_installation')) {
    function admin_cloud_sync_upsert_installation(PDO $pdo, array $license, string $installationUid, ?int $branchId, array $request): int
    {
        $clientId = (int) $license['client_id'];
        $licenseId = (int) $license['id'];
        $displayName = trim((string) ($request['display_name'] ?? $request['terminal_name'] ?? ''));
        $deviceLabel = trim((string) ($request['device_label'] ?? $request['machine_name'] ?? ''));
        $appVersion = trim((string) ($request['app_version'] ?? ''));

        $stmt = $pdo->prepare('
            INSERT INTO client_installations (
                client_id, branch_id, license_id, installation_uid, display_name, app_version,
                device_label, status, last_seen_at, last_payload_at, last_ip_hash
            ) VALUES (
                :client_id, :branch_id, :license_id, :installation_uid, :display_name, :app_version,
                :device_label, :status, UTC_TIMESTAMP(), UTC_TIMESTAMP(), :last_ip_hash
            )
            ON DUPLICATE KEY UPDATE
                branch_id = COALESCE(branch_id, VALUES(branch_id)),
                license_id = VALUES(license_id),
                display_name = VALUES(display_name),
                app_version = VALUES(app_version),
                device_label = VALUES(device_label),
                status = VALUES(status),
                last_seen_at = UTC_TIMESTAMP(),
                last_payload_at = UTC_TIMESTAMP(),
                last_ip_hash = VALUES(last_ip_hash),
                updated_at = CURRENT_TIMESTAMP
        ');
        $stmt->execute([
            'client_id' => $clientId,
            'branch_id' => $branchId,
            'license_id' => $licenseId,
            'installation_uid' => $installationUid,
            'display_name' => $displayName !== '' ? substr($displayName, 0, 150) : null,
            'app_version' => $appVersion !== '' ? substr($appVersion, 0, 40) : null,
            'device_label' => $deviceLabel !== '' ? substr($deviceLabel, 0, 150) : null,
            'status' => 'online',
            'last_ip_hash' => admin_cloud_sync_hash_ip(),
        ]);

        $select = $pdo->prepare('
            SELECT id
            FROM client_installations
            WHERE client_id = :client_id AND installation_uid = :installation_uid
            LIMIT 1
        ');
        $select->execute(['client_id' => $clientId, 'installation_uid' => $installationUid]);
        $id = $select->fetchColumn();

        if ($id === false) {
            throw new RuntimeException('No se pudo registrar la instalacion.');
        }

        return (int) $id;
    }
}

if (!function_exists('admin_cloud_sync_installation_branch_id')) {
    function admin_cloud_sync_installation_branch_id(PDO $pdo, int $clientId, int $installationId): ?int
    {
        if ($clientId <= 0 || $installationId <= 0) {
            return null;
        }

        $stmt = $pdo->prepare('SELECT branch_id FROM client_installations WHERE id = :installation_id AND client_id = :client_id LIMIT 1');
        $stmt->execute([
            'installation_id' => $installationId,
            'client_id' => $clientId,
        ]);
        $branchId = $stmt->fetchColumn();

        return $branchId !== false && $branchId !== null ? (int) $branchId : null;
    }
}

if (!function_exists('admin_cloud_sync_store_stock_event')) {
    function admin_cloud_sync_store_stock_event(PDO $pdo, array $license, int $installationId, ?int $branchId, string $eventUid, string $eventType, array $payload): int
    {
        if (!in_array($eventType, ['stock.snapshot', 'stock_snapshot', 'stock.updated', 'stock_updated'], true)) {
            return 0;
        }

        $products = $payload['products'] ?? [];
        if (!is_array($products)) {
            return 0;
        }

        $stmt = $pdo->prepare('
            INSERT INTO cloud_sync_stock_items (
                client_id, branch_id, installation_id, license_id, product_uid, local_product_id,
                codigo, nombre, categoria, marca, precio, stock, stock_minimo, estado_stock,
                unidad_venta, es_pesable, activo, product_updated_at, last_event_uid, synced_at
            ) VALUES (
                :client_id, :branch_id, :installation_id, :license_id, :product_uid, :local_product_id,
                :codigo, :nombre, :categoria, :marca, :precio, :stock, :stock_minimo, :estado_stock,
                :unidad_venta, :es_pesable, :activo, :product_updated_at, :last_event_uid, UTC_TIMESTAMP()
            )
            ON DUPLICATE KEY UPDATE
                branch_id = COALESCE(branch_id, VALUES(branch_id)),
                license_id = IF(product_updated_at IS NULL OR VALUES(product_updated_at) >= product_updated_at, VALUES(license_id), license_id),
                local_product_id = IF(product_updated_at IS NULL OR VALUES(product_updated_at) >= product_updated_at, VALUES(local_product_id), local_product_id),
                codigo = IF(product_updated_at IS NULL OR VALUES(product_updated_at) >= product_updated_at, VALUES(codigo), codigo),
                nombre = IF(product_updated_at IS NULL OR VALUES(product_updated_at) >= product_updated_at, VALUES(nombre), nombre),
                categoria = IF(product_updated_at IS NULL OR VALUES(product_updated_at) >= product_updated_at, VALUES(categoria), categoria),
                marca = IF(product_updated_at IS NULL OR VALUES(product_updated_at) >= product_updated_at, VALUES(marca), marca),
                precio = IF(product_updated_at IS NULL OR VALUES(product_updated_at) >= product_updated_at, VALUES(precio), precio),
                stock = IF(product_updated_at IS NULL OR VALUES(product_updated_at) >= product_updated_at, VALUES(stock), stock),
                stock_minimo = IF(product_updated_at IS NULL OR VALUES(product_updated_at) >= product_updated_at, VALUES(stock_minimo), stock_minimo),
                estado_stock = IF(product_updated_at IS NULL OR VALUES(product_updated_at) >= product_updated_at, VALUES(estado_stock), estado_stock),
                unidad_venta = IF(product_updated_at IS NULL OR VALUES(product_updated_at) >= product_updated_at, VALUES(unidad_venta), unidad_venta),
                es_pesable = IF(product_updated_at IS NULL OR VALUES(product_updated_at) >= product_updated_at, VALUES(es_pesable), es_pesable),
                activo = IF(product_updated_at IS NULL OR VALUES(product_updated_at) >= product_updated_at, VALUES(activo), activo),
                last_event_uid = IF(product_updated_at IS NULL OR VALUES(product_updated_at) >= product_updated_at, VALUES(last_event_uid), last_event_uid),
                synced_at = IF(product_updated_at IS NULL OR VALUES(product_updated_at) >= product_updated_at, UTC_TIMESTAMP(), synced_at),
                updated_at = IF(product_updated_at IS NULL OR VALUES(product_updated_at) >= product_updated_at, CURRENT_TIMESTAMP, updated_at),
                product_updated_at = IF(product_updated_at IS NULL OR VALUES(product_updated_at) >= product_updated_at, VALUES(product_updated_at), product_updated_at)
        ');

        $stored = 0;
        foreach (array_slice($products, 0, 200) as $product) {
            if (!is_array($product)) {
                continue;
            }

            $productUid = admin_cloud_sync_normalize_uid((string) ($product['product_uid'] ?? ''), 120);
            $name = trim((string) ($product['nombre'] ?? $product['name'] ?? ''));
            if ($productUid === '' || $name === '') {
                continue;
            }

            $stock = round((float) ($product['stock'] ?? 0), 3);
            $stockMin = round((float) ($product['stock_minimo'] ?? $product['stock_min'] ?? 0), 3);
            $state = trim((string) ($product['estado_stock'] ?? ''));
            if ($state === '') {
                $state = $stock <= 0 ? 'sin_stock' : ($stockMin > 0 && $stock <= $stockMin ? 'bajo_minimo' : 'ok');
            }

            $stmt->execute([
                'client_id' => (int) $license['client_id'],
                'branch_id' => $branchId,
                'installation_id' => $installationId,
                'license_id' => (int) $license['id'],
                'product_uid' => $productUid,
                'local_product_id' => ((int) ($product['producto_id'] ?? 0)) > 0 ? (int) $product['producto_id'] : null,
                'codigo' => normalize_nullable(substr(trim((string) ($product['codigo'] ?? '')), 0, 80)),
                'nombre' => substr($name, 0, 190),
                'categoria' => normalize_nullable(substr(trim((string) ($product['categoria'] ?? '')), 0, 120)),
                'marca' => normalize_nullable(substr(trim((string) ($product['marca'] ?? '')), 0, 120)),
                'precio' => round((float) ($product['precio'] ?? 0), 2),
                'stock' => $stock,
                'stock_minimo' => $stockMin,
                'estado_stock' => substr($state, 0, 30),
                'unidad_venta' => normalize_nullable(substr(trim((string) ($product['unidad_venta'] ?? '')), 0, 20)),
                'es_pesable' => !empty($product['es_pesable']) ? 1 : 0,
                'activo' => array_key_exists('activo', $product) ? (!empty($product['activo']) ? 1 : 0) : 1,
                'product_updated_at' => admin_cloud_sync_parse_datetime(isset($product['updated_at']) ? (string) $product['updated_at'] : null),
                'last_event_uid' => $eventUid,
            ]);
            $stored++;
        }

        return $stored;
    }
}

if (!function_exists('admin_cloud_sync_client_installations')) {
    function admin_cloud_sync_client_installations(PDO $pdo, int $clientId): array
    {
        if ($clientId <= 0 || !admin_cloud_sync_ensure_schema($pdo)) {
            return [];
        }

        $stmt = $pdo->prepare(<<<'SQL'
            SELECT
                i.id AS installation_id,
                i.branch_id,
                i.license_id,
                i.installation_uid,
                i.display_name,
                i.device_label,
                i.app_version,
                i.last_seen_at,
                b.name AS branch_name,
                l.license_key
            FROM client_installations i
            INNER JOIN licenses l ON l.id = i.license_id AND l.client_id = i.client_id
            LEFT JOIN client_branches b ON b.id = i.branch_id AND b.client_id = i.client_id
            WHERE i.client_id = :client_id
            ORDER BY i.branch_id IS NULL DESC, b.name ASC, i.last_seen_at DESC, i.id DESC
SQL
        );
        $stmt->execute(['client_id' => $clientId]);

        return $stmt->fetchAll();
    }
}

if (!function_exists('admin_cloud_sync_assign_installation_branch')) {
    function admin_cloud_sync_assign_installation_branch(PDO $pdo, int $clientId, int $installationId, int $branchId): array
    {
        if ($clientId <= 0 || $installationId <= 0 || $branchId <= 0) {
            throw new InvalidArgumentException('Instalacion o sucursal invalida.');
        }

        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $installationStmt = $pdo->prepare(<<<'SQL'
                SELECT id, license_id, branch_id, installation_uid, display_name, device_label
                FROM client_installations
                WHERE id = :installation_id AND client_id = :client_id
                LIMIT 1
                FOR UPDATE
SQL
            );
            $installationStmt->execute([
                'installation_id' => $installationId,
                'client_id' => $clientId,
            ]);
            $installation = $installationStmt->fetch();
            if (!is_array($installation)) {
                throw new RuntimeException('La instalacion no pertenece a este cliente.');
            }

            $branchStmt = $pdo->prepare(<<<'SQL'
                SELECT id, name
                FROM client_branches
                WHERE id = :branch_id AND client_id = :client_id AND status = 'active'
                LIMIT 1
                FOR UPDATE
SQL
            );
            $branchStmt->execute([
                'branch_id' => $branchId,
                'client_id' => $clientId,
            ]);
            $branch = $branchStmt->fetch();
            if (!is_array($branch)) {
                throw new RuntimeException('La sucursal no pertenece a este cliente o no esta activa.');
            }

            $updateInstallation = $pdo->prepare(<<<'SQL'
                UPDATE client_installations
                SET branch_id = :branch_id, updated_at = CURRENT_TIMESTAMP
                WHERE id = :installation_id AND client_id = :client_id
SQL
            );
            $updateInstallation->execute([
                'branch_id' => $branchId,
                'installation_id' => $installationId,
                'client_id' => $clientId,
            ]);

            $updateEvents = $pdo->prepare(<<<'SQL'
                UPDATE cloud_sync_events
                SET branch_id = :branch_id
                WHERE installation_id = :installation_id
                  AND client_id = :client_id
                  AND branch_id IS NULL
SQL
            );
            $updateEvents->execute([
                'branch_id' => $branchId,
                'installation_id' => $installationId,
                'client_id' => $clientId,
            ]);

            $updateStock = $pdo->prepare(<<<'SQL'
                UPDATE cloud_sync_stock_items
                SET branch_id = :branch_id, updated_at = CURRENT_TIMESTAMP
                WHERE installation_id = :installation_id AND client_id = :client_id
SQL
            );
            $updateStock->execute([
                'branch_id' => $branchId,
                'installation_id' => $installationId,
                'client_id' => $clientId,
            ]);

            if ($ownsTransaction) {
                $pdo->commit();
            }

            return [
                'installation_id' => $installationId,
                'installation_uid' => (string) ($installation['installation_uid'] ?? ''),
                'installation_name' => (string) ($installation['display_name'] ?: $installation['device_label'] ?: 'Instalacion FLUS'),
                'license_id' => (int) $installation['license_id'],
                'previous_branch_id' => $installation['branch_id'] !== null ? (int) $installation['branch_id'] : null,
                'branch_id' => $branchId,
                'branch_name' => (string) $branch['name'],
                'events_updated' => $updateEvents->rowCount(),
                'stock_updated' => $updateStock->rowCount(),
            ];
        } catch (Throwable $e) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}

if (!function_exists('admin_cloud_sync_store_events')) {
    function admin_cloud_sync_store_events(PDO $pdo, array $license, int $installationId, ?int $branchId, array $events): array
    {
        $accepted = 0;
        $duplicates = 0;
        $rejected = 0;
        $stockItems = 0;

        $insert = $pdo->prepare('
            INSERT IGNORE INTO cloud_sync_events (
                client_id, branch_id, installation_id, license_id, event_uid, event_type,
                occurred_at, payload_json, summary_json
            ) VALUES (
                :client_id, :branch_id, :installation_id, :license_id, :event_uid, :event_type,
                :occurred_at, :payload_json, :summary_json
            )
        ');

        foreach (array_slice($events, 0, 50) as $event) {
            if (!is_array($event)) {
                $rejected++;
                continue;
            }

            $eventUid = admin_cloud_sync_normalize_uid((string) ($event['event_uid'] ?? $event['uid'] ?? ''));
            $eventType = admin_cloud_sync_normalize_uid((string) ($event['event_type'] ?? $event['type'] ?? ''), 60);
            if ($eventUid === '' || $eventType === '') {
                $rejected++;
                continue;
            }

            $payload = $event['payload'] ?? null;
            $summary = $event['summary'] ?? null;
            $payloadJson = $payload === null ? null : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $summaryJson = $summary === null ? null : json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if (($payloadJson !== null && strlen($payloadJson) > 65535) || ($summaryJson !== null && strlen($summaryJson) > 16384)) {
                $rejected++;
                continue;
            }

            $insert->execute([
                'client_id' => (int) $license['client_id'],
                'branch_id' => $branchId,
                'installation_id' => $installationId,
                'license_id' => (int) $license['id'],
                'event_uid' => $eventUid,
                'event_type' => $eventType,
                'occurred_at' => admin_cloud_sync_parse_datetime(isset($event['occurred_at']) ? (string) $event['occurred_at'] : null),
                'payload_json' => $payloadJson,
                'summary_json' => $summaryJson,
            ]);

            if ($insert->rowCount() > 0) {
                $accepted++;
            } else {
                $duplicates++;
            }

            $stockItems += admin_cloud_sync_store_stock_event($pdo, $license, $installationId, $branchId, $eventUid, $eventType, is_array($payload) ? $payload : []);
        }

        return [
            'accepted' => $accepted,
            'duplicates' => $duplicates,
            'rejected' => $rejected,
            'stock_items' => $stockItems,
        ];
    }
}

if (!function_exists('admin_cloud_sync_recent_installations')) {
    function admin_cloud_sync_recent_installations(PDO $pdo, int $limit = 20, ?int $clientId = null): array
    {
        if (!admin_cloud_sync_ensure_schema($pdo)) {
            return [];
        }

        $limit = max(1, min(100, $limit));
        $where = '';
        $params = [];
        if ($clientId !== null && $clientId > 0) {
            $where = 'WHERE i.client_id = :client_id';
            $params['client_id'] = $clientId;
        }

        $stmt = $pdo->prepare("
            SELECT
                i.*,
                c.legal_name,
                c.trade_name,
                b.name AS branch_name,
                b.code AS branch_code,
                l.license_key,
                l.plan_type,
                l.status AS license_status,
                l.expires_at AS license_expires_at
            FROM client_installations i
            INNER JOIN clients c ON c.id = i.client_id
            INNER JOIN licenses l ON l.id = i.license_id
            LEFT JOIN client_branches b ON b.id = i.branch_id
            {$where}
            ORDER BY i.last_seen_at DESC, i.id DESC
            LIMIT " . $limit
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}

if (!function_exists('admin_cloud_sync_recent_events')) {
    function admin_cloud_sync_recent_events(PDO $pdo, int $limit = 25, ?int $clientId = null): array
    {
        if (!admin_cloud_sync_ensure_schema($pdo)) {
            return [];
        }

        $limit = max(1, min(100, $limit));
        $where = '';
        $params = [];
        if ($clientId !== null && $clientId > 0) {
            $where = 'WHERE e.client_id = :client_id';
            $params['client_id'] = $clientId;
        }

        $stmt = $pdo->prepare("
            SELECT
                e.*,
                c.legal_name,
                c.trade_name,
                b.name AS branch_name,
                b.code AS branch_code,
                i.installation_uid,
                l.license_key
            FROM cloud_sync_events e
            INNER JOIN clients c ON c.id = e.client_id
            INNER JOIN client_installations i ON i.id = e.installation_id
            INNER JOIN licenses l ON l.id = e.license_id
            LEFT JOIN client_branches b ON b.id = e.branch_id
            {$where}
            ORDER BY e.received_at DESC, e.id DESC
            LIMIT " . $limit
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}

if (!function_exists('admin_cloud_sync_clients_overview')) {
    function admin_cloud_sync_clients_overview(PDO $pdo, int $limit = 50): array
    {
        if (!admin_cloud_sync_ensure_schema($pdo)) {
            return [];
        }

        $limit = max(1, min(100, $limit));
        $cloudPlanWhere = admin_cloud_plan_sql_condition();

        $stmt = $pdo->query("
            SELECT
                c.id AS client_id,
                c.legal_name,
                c.trade_name,
                COALESCE(b.branches_count, 0) AS branches_count,
                COALESCE(b.active_branches_count, 0) AS active_branches_count,
                COALESCE(i.installations_count, 0) AS installations_count,
                COALESCE(i.online_count, 0) AS online_count,
                i.first_seen_at,
                i.last_seen_at,
                COALESCE(l.cloud_license_count, 0) AS cloud_license_count,
                COALESCE(l.cloud_plan_types, '') AS cloud_plan_types,
                COALESCE(sales.sales_24h, 0) AS sales_24h,
                COALESCE(stock.stock_attention, 0) AS stock_attention,
                stock.last_synced_at
            FROM clients c
            LEFT JOIN (
                SELECT
                    client_id,
                    COUNT(*) AS branches_count,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_branches_count
                FROM client_branches
                GROUP BY client_id
            ) b ON b.client_id = c.id
            LEFT JOIN (
                SELECT
                    client_id,
                    COUNT(*) AS installations_count,
                    SUM(CASE WHEN last_seen_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 10 MINUTE) THEN 1 ELSE 0 END) AS online_count,
                    COALESCE(NULLIF(MIN(created_at), '0000-00-00 00:00:00'), MIN(last_seen_at)) AS first_seen_at,
                    MAX(last_seen_at) AS last_seen_at
                FROM client_installations
                GROUP BY client_id
            ) i ON i.client_id = c.id
            LEFT JOIN (
                SELECT
                    client_id,
                    COUNT(*) AS cloud_license_count,
                    GROUP_CONCAT(DISTINCT plan_type ORDER BY plan_type SEPARATOR ', ') AS cloud_plan_types
                FROM licenses
                WHERE status NOT IN ('vencida','suspendida')
                  AND expires_at >= CURDATE()
                  AND {$cloudPlanWhere}
                GROUP BY client_id
            ) l ON l.client_id = c.id
            LEFT JOIN (
                SELECT
                    client_id,
                    COUNT(*) AS sales_24h
                FROM cloud_sync_events
                WHERE event_type IN ('sale.created', 'sale_created')
                  AND received_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY)
                GROUP BY client_id
            ) sales ON sales.client_id = c.id
            LEFT JOIN (
                SELECT
                    client_id,
                    SUM(CASE
                        WHEN activo = 1
                         AND (estado_stock IN ('sin_stock', 'bajo_minimo')
                              OR stock <= 0
                              OR (stock_minimo > 0 AND stock <= stock_minimo))
                        THEN 1 ELSE 0
                    END) AS stock_attention,
                    MAX(synced_at) AS last_synced_at
                FROM cloud_sync_stock_items
                GROUP BY client_id
            ) stock ON stock.client_id = c.id
            WHERE COALESCE(b.branches_count, 0) > 0
               OR COALESCE(i.installations_count, 0) > 0
               OR COALESCE(l.cloud_license_count, 0) > 0
            ORDER BY i.last_seen_at DESC, c.trade_name ASC, c.legal_name ASC
            LIMIT " . $limit
        );

        return $stmt->fetchAll();
    }
}

if (!function_exists('admin_cloud_sync_client_overview')) {
    function admin_cloud_sync_client_overview(PDO $pdo, int $clientId): array
    {
        if ($clientId <= 0 || !admin_cloud_sync_ensure_schema($pdo)) {
            return [];
        }

        $cloudPlanWhere = admin_cloud_plan_sql_condition();
        $stmt = $pdo->prepare("
            SELECT
                c.id AS client_id,
                c.legal_name,
                c.trade_name,
                COALESCE(b.branches_count, 0) AS branches_count,
                COALESCE(b.active_branches_count, 0) AS active_branches_count,
                COALESCE(i.installations_count, 0) AS installations_count,
                COALESCE(i.online_count, 0) AS online_count,
                i.first_seen_at,
                i.last_seen_at,
                COALESCE(l.cloud_license_count, 0) AS cloud_license_count,
                COALESCE(l.cloud_plan_types, '') AS cloud_plan_types,
                COALESCE(sales.sales_24h, 0) AS sales_24h,
                COALESCE(stock.stock_attention, 0) AS stock_attention,
                stock.last_synced_at
            FROM clients c
            LEFT JOIN (
                SELECT
                    client_id,
                    COUNT(*) AS branches_count,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_branches_count
                FROM client_branches
                WHERE client_id = :branches_client_id
                GROUP BY client_id
            ) b ON b.client_id = c.id
            LEFT JOIN (
                SELECT
                    client_id,
                    COUNT(*) AS installations_count,
                    SUM(CASE WHEN last_seen_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 10 MINUTE) THEN 1 ELSE 0 END) AS online_count,
                    COALESCE(NULLIF(MIN(created_at), '0000-00-00 00:00:00'), MIN(last_seen_at)) AS first_seen_at,
                    MAX(last_seen_at) AS last_seen_at
                FROM client_installations
                WHERE client_id = :installations_client_id
                GROUP BY client_id
            ) i ON i.client_id = c.id
            LEFT JOIN (
                SELECT
                    client_id,
                    COUNT(*) AS cloud_license_count,
                    GROUP_CONCAT(DISTINCT plan_type ORDER BY plan_type SEPARATOR ', ') AS cloud_plan_types
                FROM licenses
                WHERE client_id = :licenses_client_id
                  AND status NOT IN ('vencida','suspendida')
                  AND expires_at >= CURDATE()
                  AND {$cloudPlanWhere}
                GROUP BY client_id
            ) l ON l.client_id = c.id
            LEFT JOIN (
                SELECT
                    client_id,
                    COUNT(*) AS sales_24h
                FROM cloud_sync_events
                WHERE client_id = :sales_client_id
                  AND event_type IN ('sale.created', 'sale_created')
                  AND received_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY)
                GROUP BY client_id
            ) sales ON sales.client_id = c.id
            LEFT JOIN (
                SELECT
                    client_id,
                    SUM(CASE
                        WHEN activo = 1
                         AND (estado_stock IN ('sin_stock', 'bajo_minimo')
                              OR stock <= 0
                              OR (stock_minimo > 0 AND stock <= stock_minimo))
                        THEN 1 ELSE 0
                    END) AS stock_attention,
                    MAX(synced_at) AS last_synced_at
                FROM cloud_sync_stock_items
                WHERE client_id = :stock_client_id
                GROUP BY client_id
            ) stock ON stock.client_id = c.id
            WHERE c.id = :client_id
            LIMIT 1
        ");
        $stmt->execute([
            'branches_client_id' => $clientId,
            'installations_client_id' => $clientId,
            'licenses_client_id' => $clientId,
            'sales_client_id' => $clientId,
            'stock_client_id' => $clientId,
            'client_id' => $clientId,
        ]);

        $row = $stmt->fetch();
        return is_array($row) ? $row : [];
    }
}

if (!function_exists('admin_cloud_sync_client_branches')) {
    function admin_cloud_sync_client_branches(PDO $pdo, int $clientId): array
    {
        if ($clientId <= 0 || !admin_cloud_sync_ensure_schema($pdo)) {
            return [];
        }

        $stmt = $pdo->prepare("
            SELECT
                b.id AS branch_id,
                b.name AS branch_name,
                b.code AS branch_code,
                b.status AS branch_status,
                COALESCE(i.installations_count, 0) AS installations_count,
                COALESCE(i.online_count, 0) AS online_count,
                i.last_seen_at,
                COALESCE(stock.stock_items, 0) AS stock_items,
                stock.last_synced_at
            FROM client_branches b
            LEFT JOIN (
                SELECT
                    branch_id,
                    COUNT(*) AS installations_count,
                    SUM(CASE WHEN last_seen_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 10 MINUTE) THEN 1 ELSE 0 END) AS online_count,
                    MAX(last_seen_at) AS last_seen_at
                FROM client_installations
                WHERE client_id = :install_client_id
                GROUP BY branch_id
            ) i ON i.branch_id = b.id
            LEFT JOIN (
                SELECT
                    branch_id,
                    COUNT(*) AS stock_items,
                    MAX(synced_at) AS last_synced_at
                FROM cloud_sync_stock_items
                WHERE client_id = :stock_client_id
                  AND activo = 1
                GROUP BY branch_id
            ) stock ON stock.branch_id = b.id
            WHERE b.client_id = :branch_client_id
            ORDER BY
                CASE WHEN b.status = 'active' THEN 0 ELSE 1 END,
                b.name ASC,
                b.id ASC
        ");
        $stmt->execute([
            'install_client_id' => $clientId,
            'stock_client_id' => $clientId,
            'branch_client_id' => $clientId,
        ]);
        $branches = $stmt->fetchAll();

        $unassigned = $pdo->prepare("
            SELECT
                0 AS branch_id,
                'Sin sucursal' AS branch_name,
                '' AS branch_code,
                'active' AS branch_status,
                COUNT(*) AS installations_count,
                SUM(CASE WHEN last_seen_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 10 MINUTE) THEN 1 ELSE 0 END) AS online_count,
                MAX(last_seen_at) AS last_seen_at,
                0 AS stock_items,
                NULL AS last_synced_at
            FROM client_installations
            WHERE client_id = :client_id
              AND branch_id IS NULL
        ");
        $unassigned->execute(['client_id' => $clientId]);
        $row = $unassigned->fetch();
        if (is_array($row) && (int) ($row['installations_count'] ?? 0) > 0) {
            $branches[] = $row;
        }

        return $branches;
    }
}

if (!function_exists('admin_cloud_sync_decode_json')) {
    function admin_cloud_sync_decode_json($value): array
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('admin_cloud_sync_recent_sales')) {
    function admin_cloud_sync_recent_sales(
        PDO $pdo,
        int $limit = 12,
        ?int $clientId = null,
        ?int $branchId = null,
        ?string $fromUtc = null,
        ?string $toUtc = null,
        ?array $allowedBranchIds = null
    ): array
    {
        if (!admin_cloud_sync_ensure_schema($pdo)) {
            return [];
        }

        $limit = max(1, min(50, $limit));
        $where = "WHERE e.event_type IN ('sale.created', 'sale_created')";
        $params = [];
        if ($clientId !== null && $clientId > 0) {
            $where .= ' AND e.client_id = :client_id';
            $params['client_id'] = $clientId;
        }
        if ($branchId !== null && $branchId > 0) {
            if ($allowedBranchIds !== null && !in_array($branchId, array_map('intval', $allowedBranchIds), true)) {
                $where .= ' AND 1 = 0';
            } else {
                $where .= ' AND e.branch_id = :branch_id';
                $params['branch_id'] = $branchId;
            }
        } elseif ($allowedBranchIds !== null) {
            $branchPlaceholders = [];
            foreach (array_values(array_unique(array_map('intval', $allowedBranchIds))) as $index => $allowedBranchId) {
                if ($allowedBranchId <= 0) {
                    continue;
                }
                $key = 'recent_allowed_branch_' . $index;
                $branchPlaceholders[] = ':' . $key;
                $params[$key] = $allowedBranchId;
            }
            $where .= $branchPlaceholders ? ' AND e.branch_id IN (' . implode(',', $branchPlaceholders) . ')' : ' AND 1 = 0';
        }
        if ($fromUtc !== null && $fromUtc !== '') {
            $where .= ' AND e.occurred_at >= :from_utc';
            $params['from_utc'] = $fromUtc;
        }
        if ($toUtc !== null && $toUtc !== '') {
            $where .= ' AND e.occurred_at < :to_utc';
            $params['to_utc'] = $toUtc;
        }

        $stmt = $pdo->prepare("
            SELECT
                e.*,
                c.legal_name,
                c.trade_name,
                b.name AS branch_name,
                b.code AS branch_code,
                i.display_name,
                i.device_label,
                l.license_key
            FROM cloud_sync_events e
            INNER JOIN clients c ON c.id = e.client_id
            INNER JOIN client_installations i ON i.id = e.installation_id
            INNER JOIN licenses l ON l.id = e.license_id
            LEFT JOIN client_branches b ON b.id = e.branch_id
            {$where}
            ORDER BY e.occurred_at DESC, e.id DESC
            LIMIT " . $limit
        );
        $stmt->execute($params);

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $row['summary'] = admin_cloud_sync_decode_json($row['summary_json'] ?? null);
            $rows[] = $row;
        }

        return $rows;
    }
}

if (!function_exists('admin_cloud_sync_sales_list')) {
    function admin_cloud_sync_sales_filter_sql(int $clientId, array $filters = [], string $prefix = 'sales'): array
    {
        $branchId = max(0, (int) ($filters['branch_id'] ?? 0));
        $allowedBranchIds = array_key_exists('branch_ids', $filters) && is_array($filters['branch_ids'])
            ? array_values(array_unique(array_filter(array_map('intval', $filters['branch_ids']), static function (int $id): bool {
                return $id > 0;
            })))
            : null;
        $query = mb_substr(trim((string) ($filters['q'] ?? '')), 0, 80);
        $payment = mb_substr(strtoupper(trim((string) ($filters['payment'] ?? ''))), 0, 40);
        $cashier = mb_substr(trim((string) ($filters['cashier'] ?? '')), 0, 80);
        $fromUtc = trim((string) ($filters['from_utc'] ?? ''));
        $toUtc = trim((string) ($filters['to_utc'] ?? ''));

        $where = [
            "e.event_type IN ('sale.created', 'sale_created')",
            'e.client_id = :' . $prefix . '_client_id',
        ];
        $params = [$prefix . '_client_id' => $clientId];
        if ($fromUtc !== '') {
            $where[] = 'e.occurred_at >= :' . $prefix . '_from_utc';
            $params[$prefix . '_from_utc'] = $fromUtc;
        }
        if ($toUtc !== '') {
            $where[] = 'e.occurred_at < :' . $prefix . '_to_utc';
            $params[$prefix . '_to_utc'] = $toUtc;
        }
        if ($branchId > 0) {
            if ($allowedBranchIds !== null && !in_array($branchId, $allowedBranchIds, true)) {
                $where[] = '1 = 0';
            } else {
                $where[] = 'e.branch_id = :' . $prefix . '_branch_id';
                $params[$prefix . '_branch_id'] = $branchId;
            }
        } elseif ($allowedBranchIds !== null) {
            $placeholders = [];
            foreach ($allowedBranchIds as $index => $allowedBranchId) {
                $key = $prefix . '_allowed_branch_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $allowedBranchId;
            }
            $where[] = $placeholders ? 'e.branch_id IN (' . implode(',', $placeholders) . ')' : '1 = 0';
        }
        if ($payment !== '') {
            $where[] = "UPPER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(e.summary_json, '$.medio_pago')), '')) = :{$prefix}_payment";
            $params[$prefix . '_payment'] = $payment;
        }
        if ($cashier !== '') {
            $where[] = "CONCAT_WS(' ',"
                . " COALESCE(JSON_UNQUOTE(JSON_EXTRACT(e.summary_json, '$.cajero_nombre')), ''),"
                . " COALESCE(JSON_UNQUOTE(JSON_EXTRACT(e.payload_json, '$.cajero_nombre')), ''),"
                . " COALESCE(JSON_UNQUOTE(JSON_EXTRACT(e.summary_json, '$.user_id')), ''),"
                . " COALESCE(JSON_UNQUOTE(JSON_EXTRACT(e.payload_json, '$.user_id')), '')"
                . ") LIKE :{$prefix}_cashier ESCAPE '='";
            $params[$prefix . '_cashier'] = '%' . str_replace(['=', '%', '_'], ['==', '=%', '=_'], $cashier) . '%';
        }
        if ($query !== '') {
            $escaped = str_replace(['=', '%', '_'], ['==', '=%', '=_'], $query);
            $where[] = "(e.event_uid LIKE :{$prefix}_query_uid ESCAPE '='"
                . " OR e.summary_json LIKE :{$prefix}_query_summary ESCAPE '='"
                . " OR e.payload_json LIKE :{$prefix}_query_payload ESCAPE '=')";
            $searchPattern = '%' . $escaped . '%';
            $params[$prefix . '_query_uid'] = $searchPattern;
            $params[$prefix . '_query_summary'] = $searchPattern;
            $params[$prefix . '_query_payload'] = $searchPattern;
        }

        return ['sql' => 'WHERE ' . implode(' AND ', $where), 'params' => $params];
    }

    function admin_cloud_sync_sales_list(PDO $pdo, int $clientId, array $filters = []): array
    {
        $empty = [
            'items' => [],
            'total' => 0,
            'page' => 1,
            'per_page' => 15,
            'pages' => 0,
        ];
        if ($clientId <= 0 || !admin_cloud_sync_ensure_schema($pdo)) {
            return $empty;
        }

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 15)));
        $filterSql = admin_cloud_sync_sales_filter_sql($clientId, $filters, 'sales_list');
        $whereSql = $filterSql['sql'];
        $params = $filterSql['params'];
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM cloud_sync_events e {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $pages = $total > 0 ? (int) ceil($total / $perPage) : 0;
        if ($pages > 0 && $page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare("
            SELECT
                e.id,
                e.event_uid,
                e.occurred_at,
                e.received_at,
                e.summary_json,
                e.payload_json,
                e.installation_id,
                e.branch_id,
                b.name AS branch_name,
                b.code AS branch_code,
                i.display_name,
                i.device_label
            FROM cloud_sync_events e
            INNER JOIN client_installations i ON i.id = e.installation_id
            LEFT JOIN client_branches b ON b.id = e.branch_id AND b.client_id = e.client_id
            {$whereSql}
            ORDER BY e.occurred_at DESC, e.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmt->execute($params);

        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            $row['summary'] = admin_cloud_sync_decode_json($row['summary_json'] ?? null);
            $row['payload'] = admin_cloud_sync_decode_json($row['payload_json'] ?? null);
            unset($row['summary_json'], $row['payload_json']);
            $items[] = $row;
        }

        $annulments = admin_cloud_sync_sale_annulment_map($pdo, $clientId);
        foreach ($items as &$item) {
            $summary = is_array($item['summary'] ?? null) ? $item['summary'] : [];
            $key = (int)($item['installation_id'] ?? 0) . ':' . (int)($summary['venta_id'] ?? 0);
            $cloudAnnulment = $annulments[$key] ?? ['amount' => 0.0, 'status' => ''];
            $annulledAmount = max(0, (float)($summary['monto_anulado'] ?? 0) + (float)$cloudAnnulment['amount']);
            $item['annulled_amount'] = $annulledAmount;
            $item['net_amount'] = max(0, (float)($summary['total'] ?? 0) - $annulledAmount);
            $item['sale_status'] = (string)($cloudAnnulment['status'] ?: ($summary['estado'] ?? 'EMITIDA'));
        }
        unset($item);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => $pages,
        ];
    }
}

if (!function_exists('admin_cloud_sync_sale_annulment_map')) {
    function admin_cloud_sync_sale_annulment_map(PDO $pdo, int $clientId): array
    {
        static $cache = [];
        if ($clientId <= 0 || !admin_cloud_sync_ensure_schema($pdo)) {
            return [];
        }
        $cacheKey = spl_object_id($pdo) . ':' . $clientId;
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }
        $stmt = $pdo->prepare("
            SELECT installation_id, summary_json
            FROM cloud_sync_events
            WHERE client_id = :client_id
              AND event_type IN ('sale.annulled', 'sale_annulled')
            ORDER BY occurred_at ASC, id ASC
        ");
        $stmt->execute(['client_id' => $clientId]);
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $summary = admin_cloud_sync_decode_json($row['summary_json'] ?? null);
            $saleId = (int)($summary['venta_id'] ?? 0);
            if ($saleId <= 0) {
                continue;
            }
            $key = (int)$row['installation_id'] . ':' . $saleId;
            if (!isset($map[$key])) {
                $map[$key] = ['amount' => 0.0, 'status' => ''];
            }
            $map[$key]['amount'] += max(0, (float)($summary['monto_anulado'] ?? 0));
            $map[$key]['status'] = (string)($summary['estado_nuevo'] ?? $map[$key]['status']);
        }
        $cache[$cacheKey] = $map;
        return $map;
    }
}

if (!function_exists('admin_cloud_sync_sales_filtered_overview')) {
    function admin_cloud_sync_sales_filtered_overview(PDO $pdo, int $clientId, array $filters = []): array
    {
        $overview = ['sales' => 0, 'amount' => 0.0, 'avg_ticket' => 0.0, 'items' => 0];
        if ($clientId <= 0 || !admin_cloud_sync_ensure_schema($pdo)) {
            return $overview;
        }

        $filterSql = admin_cloud_sync_sales_filter_sql($clientId, $filters, 'sales_overview');
        $stmt = $pdo->prepare('SELECT e.installation_id, e.summary_json FROM cloud_sync_events e ' . $filterSql['sql']);
        $stmt->execute($filterSql['params']);
        $sales = $stmt->fetchAll() ?: [];
        $annulments = admin_cloud_sync_sale_annulment_map($pdo, $clientId);
        foreach ($sales as $sale) {
            $summary = admin_cloud_sync_decode_json($sale['summary_json'] ?? null);
            $key = (int)($sale['installation_id'] ?? 0) . ':' . (int)($summary['venta_id'] ?? 0);
            $annulledAmount = max(0, (float)($summary['monto_anulado'] ?? 0) + (float)($annulments[$key]['amount'] ?? 0));
            $overview['sales']++;
            $overview['amount'] += max(0, (float)($summary['total'] ?? 0) - $annulledAmount);
            $overview['items'] += (int) ($summary['items_count'] ?? 0);
        }
        if ($overview['sales'] > 0) {
            $overview['avg_ticket'] = $overview['amount'] / $overview['sales'];
        }
        return $overview;
    }
}

if (!function_exists('admin_cloud_sync_sales_export_rows')) {
    function admin_cloud_sync_sales_export_rows(PDO $pdo, int $clientId, array $filters = []): iterable
    {
        if ($clientId <= 0 || !admin_cloud_sync_ensure_schema($pdo)) {
            return;
        }

        $filterSql = admin_cloud_sync_sales_filter_sql($clientId, $filters, 'sales_export');
        $stmt = $pdo->prepare("
            SELECT e.event_uid, e.occurred_at, e.summary_json, e.payload_json, e.installation_id, e.branch_id,
                   b.name AS branch_name, i.display_name, i.device_label
            FROM cloud_sync_events e
            INNER JOIN client_installations i ON i.id = e.installation_id
            LEFT JOIN client_branches b ON b.id = e.branch_id AND b.client_id = e.client_id
            {$filterSql['sql']}
            ORDER BY e.occurred_at DESC, e.id DESC
        ");
        $stmt->execute($filterSql['params']);

        $annulments = admin_cloud_sync_sale_annulment_map($pdo, $clientId);
        while (($row = $stmt->fetch()) !== false) {
            $row['summary'] = admin_cloud_sync_decode_json($row['summary_json'] ?? null);
            $row['payload'] = admin_cloud_sync_decode_json($row['payload_json'] ?? null);
            unset($row['summary_json'], $row['payload_json']);
            $summary = is_array($row['summary'] ?? null) ? $row['summary'] : [];
            $key = (int)($row['installation_id'] ?? 0) . ':' . (int)($summary['venta_id'] ?? 0);
            $cloudAnnulment = $annulments[$key] ?? ['amount' => 0.0, 'status' => ''];
            $annulledAmount = max(0, (float)($summary['monto_anulado'] ?? 0) + (float)$cloudAnnulment['amount']);
            $row['annulled_amount'] = $annulledAmount;
            $row['net_amount'] = max(0, (float)($summary['total'] ?? 0) - $annulledAmount);
            $row['sale_status'] = (string)($cloudAnnulment['status'] ?: ($summary['estado'] ?? 'EMITIDA'));
            yield $row;
        }
    }
}

if (!function_exists('admin_cloud_sync_sales_period_overview')) {
    function admin_cloud_sync_sales_period_overview(
        PDO $pdo,
        int $clientId,
        string $fromUtc,
        string $toUtc,
        ?int $branchId = null,
        ?array $allowedBranchIds = null
    ): array {
        $overview = [
            'sales' => 0,
            'amount' => 0.0,
            'avg_ticket' => 0.0,
            'items' => 0,
            'payments' => [],
        ];
        if ($clientId <= 0 || !admin_cloud_sync_ensure_schema($pdo)) {
            return $overview;
        }

        $where = "
            WHERE event_type IN ('sale.created', 'sale_created')
              AND client_id = :client_id
              AND occurred_at >= :from_utc
              AND occurred_at < :to_utc
        ";
        $params = [
            'client_id' => $clientId,
            'from_utc' => $fromUtc,
            'to_utc' => $toUtc,
        ];
        if ($branchId !== null && $branchId > 0) {
            if ($allowedBranchIds !== null && !in_array($branchId, array_map('intval', $allowedBranchIds), true)) {
                $where .= ' AND 1 = 0';
            } else {
                $where .= ' AND branch_id = :branch_id';
                $params['branch_id'] = $branchId;
            }
        } elseif ($allowedBranchIds !== null) {
            $branchPlaceholders = [];
            foreach (array_values(array_unique(array_map('intval', $allowedBranchIds))) as $index => $allowedBranchId) {
                if ($allowedBranchId <= 0) {
                    continue;
                }
                $key = 'period_allowed_branch_' . $index;
                $branchPlaceholders[] = ':' . $key;
                $params[$key] = $allowedBranchId;
            }
            $where .= $branchPlaceholders ? ' AND branch_id IN (' . implode(',', $branchPlaceholders) . ')' : ' AND 1 = 0';
        }

        $stmt = $pdo->prepare("SELECT installation_id, summary_json FROM cloud_sync_events {$where}");
        $stmt->execute($params);
        $annulments = admin_cloud_sync_sale_annulment_map($pdo, $clientId);
        foreach ($stmt->fetchAll() as $sale) {
            $summary = admin_cloud_sync_decode_json($sale['summary_json'] ?? null);
            $key = (int) ($sale['installation_id'] ?? 0) . ':' . (int) ($summary['venta_id'] ?? 0);
            $annulledAmount = max(0, (float) ($summary['monto_anulado'] ?? 0) + (float) ($annulments[$key]['amount'] ?? 0));
            $total = max(0, (float) ($summary['total'] ?? 0) - $annulledAmount);
            $items = (int) ($summary['items_count'] ?? 0);
            $payment = strtoupper(trim((string) ($summary['medio_pago'] ?? 'SIN_DATO')));
            if ($payment === '') {
                $payment = 'SIN_DATO';
            }

            $overview['sales']++;
            $overview['amount'] += $total;
            $overview['items'] += $items;
            if (!isset($overview['payments'][$payment])) {
                $overview['payments'][$payment] = ['count' => 0, 'amount' => 0.0];
            }
            $overview['payments'][$payment]['count']++;
            $overview['payments'][$payment]['amount'] += $total;
        }
        if ($overview['sales'] > 0) {
            $overview['avg_ticket'] = $overview['amount'] / $overview['sales'];
        }
        uasort($overview['payments'], static function (array $a, array $b): int {
            return ($b['amount'] <=> $a['amount']) ?: ($b['count'] <=> $a['count']);
        });

        return $overview;
    }
}

if (!function_exists('admin_cloud_sync_branch_sales_comparison')) {
    function admin_cloud_sync_branch_sales_comparison(PDO $pdo, int $clientId, string $fromUtc, string $toUtc, ?array $allowedBranchIds = null): array
    {
        if ($clientId <= 0 || !admin_cloud_sync_ensure_schema($pdo)) {
            return [];
        }

        $scopeSql = '';
        $params = ['client_id' => $clientId, 'from_utc' => $fromUtc, 'to_utc' => $toUtc];
        if ($allowedBranchIds !== null) {
            $branchPlaceholders = [];
            foreach (array_values(array_unique(array_map('intval', $allowedBranchIds))) as $index => $allowedBranchId) {
                if ($allowedBranchId <= 0) {
                    continue;
                }
                $key = 'comparison_allowed_branch_' . $index;
                $branchPlaceholders[] = ':' . $key;
                $params[$key] = $allowedBranchId;
            }
            $scopeSql = $branchPlaceholders ? ' AND e.branch_id IN (' . implode(',', $branchPlaceholders) . ')' : ' AND 1 = 0';
        }
        $stmt = $pdo->prepare("
            SELECT e.branch_id, e.installation_id, b.name AS branch_name, e.summary_json
            FROM cloud_sync_events e
            LEFT JOIN client_branches b ON b.id = e.branch_id AND b.client_id = e.client_id
            WHERE e.event_type IN ('sale.created', 'sale_created')
              AND e.client_id = :client_id
              AND e.occurred_at >= :from_utc
              AND e.occurred_at < :to_utc
              {$scopeSql}
            ORDER BY e.occurred_at DESC, e.id DESC
        ");
        $stmt->execute($params);

        $comparison = [];
        $annulments = admin_cloud_sync_sale_annulment_map($pdo, $clientId);
        foreach ($stmt->fetchAll() as $row) {
            $branchId = (int) ($row['branch_id'] ?? 0);
            if ($branchId <= 0) {
                continue;
            }
            if (!isset($comparison[$branchId])) {
                $comparison[$branchId] = [
                    'branch_id' => $branchId,
                    'branch_name' => (string) ($row['branch_name'] ?? 'Sucursal'),
                    'sales' => 0,
                    'amount' => 0.0,
                    'avg_ticket' => 0.0,
                ];
            }
            $summary = admin_cloud_sync_decode_json($row['summary_json'] ?? null);
            $key = (int) ($row['installation_id'] ?? 0) . ':' . (int) ($summary['venta_id'] ?? 0);
            $annulledAmount = max(0, (float) ($summary['monto_anulado'] ?? 0) + (float) ($annulments[$key]['amount'] ?? 0));
            $comparison[$branchId]['sales']++;
            $comparison[$branchId]['amount'] += max(0, (float) ($summary['total'] ?? 0) - $annulledAmount);
        }
        foreach ($comparison as &$branch) {
            if ($branch['sales'] > 0) {
                $branch['avg_ticket'] = $branch['amount'] / $branch['sales'];
            }
        }
        unset($branch);

        return $comparison;
    }
}

if (!function_exists('admin_cloud_sync_cash_sessions')) {
    function admin_cloud_sync_cash_sessions(
        PDO $pdo,
        int $clientId,
        ?int $branchId = null,
        ?array $allowedBranchIds = null
    ): array {
        if ($clientId <= 0 || !admin_cloud_sync_ensure_schema($pdo)) {
            return [];
        }

        $where = "
            WHERE e.client_id = :client_id
              AND e.event_type IN ('cash.opened', 'cash.closed')
        ";
        $params = ['client_id' => $clientId];
        if ($branchId !== null && $branchId > 0) {
            if ($allowedBranchIds !== null && !in_array($branchId, array_map('intval', $allowedBranchIds), true)) {
                $where .= ' AND 1 = 0';
            } else {
                $where .= ' AND e.branch_id = :branch_id';
                $params['branch_id'] = $branchId;
            }
        } elseif ($allowedBranchIds !== null) {
            $placeholders = [];
            foreach (array_values(array_unique(array_map('intval', $allowedBranchIds))) as $index => $allowedBranchId) {
                if ($allowedBranchId <= 0) {
                    continue;
                }
                $key = 'cash_allowed_branch_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $allowedBranchId;
            }
            $where .= $placeholders ? ' AND e.branch_id IN (' . implode(',', $placeholders) . ')' : ' AND 1 = 0';
        }

        $stmt = $pdo->prepare("
            SELECT e.*, b.name AS branch_name, i.display_name, i.device_label
            FROM cloud_sync_events e
            INNER JOIN client_installations i ON i.id = e.installation_id
            LEFT JOIN client_branches b ON b.id = e.branch_id AND b.client_id = e.client_id
            {$where}
            ORDER BY e.occurred_at DESC, e.id DESC
            LIMIT 500
        ");
        $stmt->execute($params);

        $sessions = [];
        foreach ($stmt->fetchAll() as $row) {
            $summary = admin_cloud_sync_decode_json($row['summary_json'] ?? null);
            $cashId = (int)($summary['caja_id'] ?? 0);
            if ($cashId <= 0) {
                continue;
            }
            $key = (int)$row['installation_id'] . ':' . $cashId;
            if (isset($sessions[$key])) {
                continue;
            }
            $sessions[$key] = [
                'caja_id' => $cashId,
                'branch_id' => (int)($row['branch_id'] ?? 0),
                'branch_name' => (string)($row['branch_name'] ?: 'Sin sucursal'),
                'installation_id' => (int)$row['installation_id'],
                'installation_name' => (string)($row['display_name'] ?: $row['device_label'] ?: 'Instalacion FLUS'),
                'status' => (string)$row['event_type'] === 'cash.opened' ? 'open' : 'closed',
                'occurred_at' => (string)($row['occurred_at'] ?? ''),
                'summary' => $summary,
            ];
        }

        return array_values($sessions);
    }
}

if (!function_exists('admin_cloud_sync_sales_overview')) {
    function admin_cloud_sync_sales_overview(PDO $pdo, ?int $clientId = null, ?int $branchId = null): array
    {
        $overview = [
            'sales_24h' => 0,
            'amount_24h' => 0.0,
            'avg_ticket_24h' => 0.0,
            'items_24h' => 0,
            'payments_24h' => [],
        ];

        if (!admin_cloud_sync_ensure_schema($pdo)) {
            return $overview;
        }

        $where = "
            WHERE event_type IN ('sale.created', 'sale_created')
              AND received_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY)
        ";
        $params = [];
        if ($clientId !== null && $clientId > 0) {
            $where .= ' AND client_id = :client_id';
            $params['client_id'] = $clientId;
        }
        if ($branchId !== null && $branchId > 0) {
            $where .= ' AND branch_id = :branch_id';
            $params['branch_id'] = $branchId;
        }

        $stmt = $pdo->prepare("
            SELECT installation_id, summary_json
            FROM cloud_sync_events
            {$where}
        ");
        $stmt->execute($params);

        $annulments = $clientId !== null && $clientId > 0
            ? admin_cloud_sync_sale_annulment_map($pdo, $clientId)
            : [];
        foreach ($stmt->fetchAll() as $sale) {
            $summary = admin_cloud_sync_decode_json($sale['summary_json'] ?? null);
            $key = (int) ($sale['installation_id'] ?? 0) . ':' . (int) ($summary['venta_id'] ?? 0);
            $annulledAmount = max(0, (float) ($summary['monto_anulado'] ?? 0) + (float) ($annulments[$key]['amount'] ?? 0));
            $total = max(0, (float) ($summary['total'] ?? 0) - $annulledAmount);
            $items = (int) ($summary['items_count'] ?? 0);
            $payment = strtoupper(trim((string) ($summary['medio_pago'] ?? 'SIN_DATO')));
            if ($payment === '') {
                $payment = 'SIN_DATO';
            }

            $overview['sales_24h']++;
            $overview['amount_24h'] += $total;
            $overview['items_24h'] += $items;
            if (!isset($overview['payments_24h'][$payment])) {
                $overview['payments_24h'][$payment] = ['count' => 0, 'amount' => 0.0];
            }
            $overview['payments_24h'][$payment]['count']++;
            $overview['payments_24h'][$payment]['amount'] += $total;
        }

        if ($overview['sales_24h'] > 0) {
            $overview['avg_ticket_24h'] = $overview['amount_24h'] / $overview['sales_24h'];
        }

        uasort($overview['payments_24h'], static function (array $a, array $b): int {
            return ($b['amount'] <=> $a['amount']) ?: ($b['count'] <=> $a['count']);
        });

        return $overview;
    }
}

if (!function_exists('admin_cloud_sync_stock_overview')) {
    function admin_cloud_sync_stock_overview(PDO $pdo, int $clientId, ?int $branchId = null, ?array $allowedBranchIds = null): array
    {
        $overview = [
            'total' => 0,
            'sin_stock' => 0,
            'bajo_minimo' => 0,
            'ok' => 0,
            'last_synced_at' => null,
        ];

        if ($clientId <= 0 || !admin_cloud_sync_ensure_schema($pdo)) {
            return $overview;
        }

        $where = 'WHERE client_id = :client_id AND activo = 1';
        $params = ['client_id' => $clientId];
        if ($branchId !== null && $branchId > 0) {
            if ($allowedBranchIds !== null && !in_array($branchId, array_map('intval', $allowedBranchIds), true)) {
                $where .= ' AND 1 = 0';
            } else {
                $where .= ' AND branch_id = :branch_id';
                $params['branch_id'] = $branchId;
            }
        } elseif ($allowedBranchIds !== null) {
            $branchPlaceholders = [];
            foreach (array_values(array_unique(array_map('intval', $allowedBranchIds))) as $index => $allowedBranchId) {
                if ($allowedBranchId <= 0) continue;
                $key = 'stock_overview_allowed_branch_' . $index;
                $branchPlaceholders[] = ':' . $key;
                $params[$key] = $allowedBranchId;
            }
            $where .= $branchPlaceholders ? ' AND branch_id IN (' . implode(',', $branchPlaceholders) . ')' : ' AND 1 = 0';
        }

        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN estado_stock = 'sin_stock' OR stock <= 0 THEN 1 ELSE 0 END) AS sin_stock,
                SUM(CASE WHEN estado_stock = 'bajo_minimo' OR (stock_minimo > 0 AND stock > 0 AND stock <= stock_minimo) THEN 1 ELSE 0 END) AS bajo_minimo,
                SUM(CASE WHEN estado_stock = 'ok' AND stock > 0 THEN 1 ELSE 0 END) AS ok_count,
                MAX(synced_at) AS last_synced_at
            FROM cloud_sync_stock_items
            {$where}
        ");
        $stmt->execute($params);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return $overview;
        }

        return [
            'total' => (int) ($row['total'] ?? 0),
            'sin_stock' => (int) ($row['sin_stock'] ?? 0),
            'bajo_minimo' => (int) ($row['bajo_minimo'] ?? 0),
            'ok' => (int) ($row['ok_count'] ?? 0),
            'last_synced_at' => $row['last_synced_at'] ?? null,
        ];
    }
}

if (!function_exists('admin_cloud_sync_stock_branches')) {
    function admin_cloud_sync_stock_branches(PDO $pdo, int $clientId): array
    {
        if ($clientId <= 0 || !admin_cloud_sync_ensure_schema($pdo)) {
            return [];
        }

        $stmt = $pdo->prepare("
            SELECT DISTINCT
                COALESCE(b.id, 0) AS branch_id,
                COALESCE(b.name, 'Sin sucursal') AS branch_name
            FROM cloud_sync_stock_items s
            LEFT JOIN client_branches b ON b.id = s.branch_id
            WHERE s.client_id = :client_id
            ORDER BY branch_name ASC
        ");
        $stmt->execute(['client_id' => $clientId]);

        return $stmt->fetchAll();
    }
}

if (!function_exists('admin_cloud_sync_stock_items')) {
    function admin_cloud_sync_stock_items(PDO $pdo, int $clientId, array $filters = []): array
    {
        if ($clientId <= 0 || !admin_cloud_sync_ensure_schema($pdo)) {
            return [];
        }

        $limit = max(5, min(80, (int) ($filters['limit'] ?? 30)));
        $state = trim((string) ($filters['state'] ?? 'attention'));
        $query = trim((string) ($filters['q'] ?? ''));
        $branchId = (int) ($filters['branch_id'] ?? 0);
        $branchScopeRestricted = array_key_exists('branch_ids', $filters) && is_array($filters['branch_ids']);
        $allowedBranchIds = array_values(array_unique(array_filter(
            array_map('intval', (array) ($filters['branch_ids'] ?? [])),
            static function (int $id): bool {
                return $id > 0;
            }
        )));

        $where = ['s.client_id = :client_id', 's.activo = 1'];
        $params = ['client_id' => $clientId];

        if ($state === 'sin_stock') {
            $where[] = "(s.estado_stock = 'sin_stock' OR s.stock <= 0)";
        } elseif ($state === 'bajo_minimo') {
            $where[] = "(s.estado_stock = 'bajo_minimo' OR (s.stock_minimo > 0 AND s.stock > 0 AND s.stock <= s.stock_minimo))";
        } elseif ($state === 'ok') {
            $where[] = "s.estado_stock = 'ok' AND s.stock > 0";
        } elseif ($state === 'attention') {
            $where[] = "(s.estado_stock IN ('sin_stock', 'bajo_minimo') OR s.stock <= 0 OR (s.stock_minimo > 0 AND s.stock <= s.stock_minimo))";
        }

        if ($branchId > 0) {
            if ($branchScopeRestricted && !in_array($branchId, $allowedBranchIds, true)) {
                $where[] = '1 = 0';
            } else {
                $where[] = 's.branch_id = :branch_id';
                $params['branch_id'] = $branchId;
            }
        } elseif ($branchScopeRestricted) {
            $branchPlaceholders = [];
            foreach ($allowedBranchIds as $index => $allowedBranchId) {
                $key = 'stock_allowed_branch_' . $index;
                $branchPlaceholders[] = ':' . $key;
                $params[$key] = $allowedBranchId;
            }
            $where[] = $branchPlaceholders ? 's.branch_id IN (' . implode(',', $branchPlaceholders) . ')' : '1 = 0';
        }

        if ($query !== '') {
            $where[] = '(s.nombre LIKE :q_nombre OR s.codigo LIKE :q_codigo OR s.categoria LIKE :q_categoria)';
            $params['q_nombre'] = '%' . $query . '%';
            $params['q_codigo'] = '%' . $query . '%';
            $params['q_categoria'] = '%' . $query . '%';
        }

        $stmt = $pdo->prepare("
            SELECT
                s.*,
                COALESCE(b.name, 'Sin sucursal') AS branch_name,
                COALESCE(i.display_name, i.device_label, i.installation_uid) AS installation_name
            FROM cloud_sync_stock_items s
            LEFT JOIN client_branches b ON b.id = s.branch_id
            INNER JOIN client_installations i ON i.id = s.installation_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY
                CASE
                    WHEN s.estado_stock = 'sin_stock' OR s.stock <= 0 THEN 0
                    WHEN s.estado_stock = 'bajo_minimo' OR (s.stock_minimo > 0 AND s.stock <= s.stock_minimo) THEN 1
                    ELSE 2
                END,
                s.nombre ASC,
                s.codigo ASC
            LIMIT " . $limit
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}

if (!function_exists('admin_cloud_command_price_reasons')) {
    function admin_cloud_command_price_reasons(): array
    {
        return [
            'supplier_cost' => 'Cambio de costo',
            'price_list' => 'Nueva lista',
            'margin' => 'Ajuste de margen',
            'promotion' => 'Promocion',
            'correction' => 'Correccion de carga',
        ];
    }
}

if (!function_exists('admin_cloud_command_public_row')) {
    function admin_cloud_command_public_row(array $row): array
    {
        $payload = json_decode((string) ($row['payload_json'] ?? '{}'), true);
        $result = json_decode((string) ($row['result_json'] ?? '{}'), true);
        return [
            'command_uid' => (string) ($row['command_uid'] ?? ''),
            'status' => (string) ($row['status'] ?? 'pending'),
            'current_price' => (float) ($payload['expected_price'] ?? 0),
            'new_price' => (float) ($payload['new_price'] ?? 0),
            'product_name' => (string) ($payload['product_name'] ?? ''),
            'branch_name' => (string) ($row['branch_name'] ?? $payload['branch_name'] ?? ''),
            'reason' => (string) ($payload['reason_label'] ?? ''),
            'created_at' => $row['created_at'] ?? null,
            'completed_at' => $row['completed_at'] ?? null,
            'error_code' => (string) ($row['last_error'] ?? $result['error_code'] ?? ''),
            'applied_price' => isset($result['applied_price']) ? (float) $result['applied_price'] : null,
        ];
    }
}

if (!function_exists('admin_cloud_command_find_for_portal')) {
    function admin_cloud_command_find_for_portal(PDO $pdo, int $clientId, string $commandUid, ?array $allowedBranchIds = null): ?array
    {
        $commandUid = admin_cloud_sync_normalize_uid($commandUid, 120);
        if ($clientId <= 0 || $commandUid === '') {
            return null;
        }

        $where = ['c.client_id = :client_id', 'c.command_uid = :command_uid'];
        $params = ['client_id' => $clientId, 'command_uid' => $commandUid];
        if (is_array($allowedBranchIds)) {
            $allowedBranchIds = array_values(array_unique(array_filter(array_map('intval', $allowedBranchIds), static fn(int $id): bool => $id > 0)));
            $marks = [];
            foreach ($allowedBranchIds as $index => $branchId) {
                $key = 'allowed_branch_' . $index;
                $marks[] = ':' . $key;
                $params[$key] = $branchId;
            }
            $where[] = $marks ? 'c.branch_id IN (' . implode(',', $marks) . ')' : '1 = 0';
        }

        $stmt = $pdo->prepare('SELECT c.*, b.name AS branch_name FROM cloud_commands c INNER JOIN client_branches b ON b.id = c.branch_id WHERE ' . implode(' AND ', $where) . ' LIMIT 1');
        $stmt->execute($params);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }
}

if (!function_exists('admin_cloud_command_create_price')) {
    function admin_cloud_command_create_price(
        PDO $pdo,
        int $clientId,
        int $portalUserId,
        int $stockItemId,
        float $newPrice,
        string $reason,
        string $requestUid,
        ?array $allowedBranchIds = null
    ): array {
        $reasonLabels = admin_cloud_command_price_reasons();
        $requestUid = admin_cloud_sync_normalize_uid($requestUid, 120);
        $newPrice = round($newPrice, 2);
        if ($clientId <= 0 || $portalUserId <= 0 || $stockItemId <= 0 || $requestUid === '') {
            throw new InvalidArgumentException('Solicitud incompleta.');
        }
        if ($newPrice <= 0 || $newPrice > 999999999.99) {
            throw new InvalidArgumentException('El precio nuevo no es valido.');
        }
        if (!isset($reasonLabels[$reason])) {
            throw new InvalidArgumentException('Selecciona un motivo valido.');
        }

        $existingStmt = $pdo->prepare('SELECT c.*, b.name AS branch_name FROM cloud_commands c INNER JOIN client_branches b ON b.id = c.branch_id WHERE c.client_id = :client_id AND c.portal_request_uid = :request_uid LIMIT 1');
        $existingStmt->execute(['client_id' => $clientId, 'request_uid' => $requestUid]);
        $existing = $existingStmt->fetch();
        if (is_array($existing)) {
            return ['duplicate' => true, 'command' => admin_cloud_command_public_row($existing)];
        }

        try {
            $pdo->beginTransaction();
            $stockStmt = $pdo->prepare('SELECT s.*, b.name AS branch_name, b.status AS branch_status, i.status AS installation_status, i.license_id AS installation_license_id, l.status AS license_status, l.plan_type AS license_plan_type, l.expires_at AS license_expires_at, c.status AS client_status FROM cloud_sync_stock_items s INNER JOIN client_branches b ON b.id = s.branch_id INNER JOIN client_installations i ON i.id = s.installation_id INNER JOIN licenses l ON l.id = i.license_id AND l.client_id = s.client_id INNER JOIN clients c ON c.id = s.client_id WHERE s.id = :stock_item_id AND s.client_id = :client_id LIMIT 1 FOR UPDATE');
            $stockStmt->execute(['stock_item_id' => $stockItemId, 'client_id' => $clientId]);
            $stock = $stockStmt->fetch();
            if (!is_array($stock) || (int) ($stock['activo'] ?? 0) !== 1) {
                throw new InvalidArgumentException('El producto ya no esta disponible para esta sucursal.');
            }

            $branchId = (int) ($stock['branch_id'] ?? 0);
            if ($branchId <= 0 || (is_array($allowedBranchIds) && !in_array($branchId, array_map('intval', $allowedBranchIds), true))) {
                throw new RuntimeException('No tienes acceso a esta sucursal.');
            }
            if ((string) ($stock['branch_status'] ?? '') !== 'active') {
                throw new RuntimeException('La sucursal no esta activa.');
            }
            $licenseState = [
                'status' => (string) ($stock['license_status'] ?? ''),
                'plan_type' => (string) ($stock['license_plan_type'] ?? ''),
                'expires_at' => $stock['license_expires_at'] ?? null,
                'client_status' => (string) ($stock['client_status'] ?? ''),
            ];
            if (!admin_cloud_sync_license_accepts_events($licenseState)) {
                throw new RuntimeException('La licencia Cloud de esta sucursal no esta activa.');
            }

            $currentPrice = round((float) ($stock['precio'] ?? 0), 2);
            if (abs($currentPrice - $newPrice) < 0.005) {
                throw new InvalidArgumentException('El precio nuevo es igual al precio actual.');
            }

            $commandUid = 'price-' . bin2hex(random_bytes(16));
            $payload = [
                'schema_version' => 1,
                'operation' => 'price.update',
                'product_uid' => (string) ($stock['product_uid'] ?? ''),
                'local_product_id' => (int) ($stock['local_product_id'] ?? 0),
                'product_code' => (string) ($stock['codigo'] ?? ''),
                'product_name' => (string) ($stock['nombre'] ?? ''),
                'branch_name' => (string) ($stock['branch_name'] ?? ''),
                'expected_price' => number_format($currentPrice, 2, '.', ''),
                'new_price' => number_format($newPrice, 2, '.', ''),
                'reason' => $reason,
                'reason_label' => $reasonLabels[$reason],
                'requested_at' => gmdate(DATE_ATOM),
            ];
            $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!is_string($payloadJson) || strlen($payloadJson) > 16384) {
                throw new RuntimeException('No se pudo preparar la orden.');
            }

            $insert = $pdo->prepare('INSERT INTO cloud_commands (command_uid, portal_request_uid, client_id, branch_id, installation_id, license_id, requested_by_user_id, command_type, payload_json, status, available_at, expires_at) VALUES (:command_uid, :request_uid, :client_id, :branch_id, :installation_id, :license_id, :portal_user_id, :command_type, :payload_json, \'pending\', UTC_TIMESTAMP(), DATE_ADD(UTC_TIMESTAMP(), INTERVAL 24 HOUR))');
            $insert->execute([
                'command_uid' => $commandUid,
                'request_uid' => $requestUid,
                'client_id' => $clientId,
                'branch_id' => $branchId,
                'installation_id' => (int) $stock['installation_id'],
                'license_id' => (int) $stock['installation_license_id'],
                'portal_user_id' => $portalUserId,
                'command_type' => 'price.update',
                'payload_json' => $payloadJson,
            ]);
            $pdo->commit();

            $created = admin_cloud_command_find_for_portal($pdo, $clientId, $commandUid, $allowedBranchIds);
            if (!is_array($created)) {
                throw new RuntimeException('No se pudo recuperar la orden creada.');
            }
            return ['duplicate' => false, 'command' => admin_cloud_command_public_row($created)];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($e instanceof PDOException && (string) $e->getCode() === '23000') {
                $existingStmt->execute(['client_id' => $clientId, 'request_uid' => $requestUid]);
                $existing = $existingStmt->fetch();
                if (is_array($existing)) {
                    return ['duplicate' => true, 'command' => admin_cloud_command_public_row($existing)];
                }
            }
            throw $e;
        }
    }
}
