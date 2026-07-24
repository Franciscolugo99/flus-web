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
            'client_branches',
            'client_installations',
            'cloud_sync_events',
            'cloud_sync_stock_items',
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
                $ready = true;
                return true;
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
    function admin_cloud_sync_find_license(PDO $pdo, string $licenseKey): ?array
    {
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
        ');
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
                branch_id = VALUES(branch_id),
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
                branch_id = VALUES(branch_id),
                license_id = VALUES(license_id),
                local_product_id = VALUES(local_product_id),
                codigo = VALUES(codigo),
                nombre = VALUES(nombre),
                categoria = VALUES(categoria),
                marca = VALUES(marca),
                precio = VALUES(precio),
                stock = VALUES(stock),
                stock_minimo = VALUES(stock_minimo),
                estado_stock = VALUES(estado_stock),
                unidad_venta = VALUES(unidad_venta),
                es_pesable = VALUES(es_pesable),
                activo = VALUES(activo),
                product_updated_at = VALUES(product_updated_at),
                last_event_uid = VALUES(last_event_uid),
                synced_at = UTC_TIMESTAMP(),
                updated_at = CURRENT_TIMESTAMP
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
    function admin_cloud_sync_recent_sales(PDO $pdo, int $limit = 12, ?int $clientId = null): array
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

if (!function_exists('admin_cloud_sync_sales_overview')) {
    function admin_cloud_sync_sales_overview(PDO $pdo, ?int $clientId = null): array
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

        $stmt = $pdo->prepare("
            SELECT summary_json
            FROM cloud_sync_events
            {$where}
        ");
        $stmt->execute($params);

        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $summaryJson) {
            $summary = admin_cloud_sync_decode_json($summaryJson);
            $total = (float) ($summary['total'] ?? 0);
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
    function admin_cloud_sync_stock_overview(PDO $pdo, int $clientId): array
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

        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN estado_stock = 'sin_stock' OR stock <= 0 THEN 1 ELSE 0 END) AS sin_stock,
                SUM(CASE WHEN estado_stock = 'bajo_minimo' OR (stock_minimo > 0 AND stock > 0 AND stock <= stock_minimo) THEN 1 ELSE 0 END) AS bajo_minimo,
                SUM(CASE WHEN estado_stock = 'ok' AND stock > 0 THEN 1 ELSE 0 END) AS ok_count,
                MAX(synced_at) AS last_synced_at
            FROM cloud_sync_stock_items
            WHERE client_id = :client_id
              AND activo = 1
        ");
        $stmt->execute(['client_id' => $clientId]);
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
            $where[] = 's.branch_id = :branch_id';
            $params['branch_id'] = $branchId;
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
