<?php
declare(strict_types=1);

if (!function_exists('admin_client_merge_ensure_schema')) {
    function admin_client_merge_ensure_schema(PDO $pdo): bool
    {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS client_merge_events (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    source_client_id INT UNSIGNED NOT NULL,
                    target_client_id INT UNSIGNED NOT NULL,
                    source_snapshot_json LONGTEXT NOT NULL,
                    summary_json LONGTEXT NOT NULL,
                    actor VARCHAR(190) DEFAULT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_client_merge_events_source (source_client_id),
                    KEY idx_client_merge_events_target (target_client_id),
                    KEY idx_client_merge_events_created_at (created_at),
                    CONSTRAINT fk_client_merge_events_source FOREIGN KEY (source_client_id) REFERENCES clients(id) ON DELETE RESTRICT ON UPDATE CASCADE,
                    CONSTRAINT fk_client_merge_events_target FOREIGN KEY (target_client_id) REFERENCES clients(id) ON DELETE RESTRICT ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            return true;
        } catch (Throwable $e) {
            error_log('[FLUS Admin] client merge schema: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('admin_client_merge_branch_code')) {
    function admin_client_merge_branch_code(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_-]+/', '_', $value) ?? '';
        return trim(substr($value, 0, 60), '_-');
    }
}

if (!function_exists('admin_client_merge_table_exists')) {
    function admin_client_merge_table_exists(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare('
            SELECT COUNT(*)
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table_name
              AND TABLE_TYPE = \'BASE TABLE\'
        ');
        $stmt->execute(['table_name' => $table]);
        return (int) $stmt->fetchColumn() > 0;
    }
}

if (!function_exists('admin_client_merge_unknown_tables')) {
    function admin_client_merge_unknown_tables(PDO $pdo): array
    {
        $supported = [
            'licenses', 'payments', 'license_notifications', 'license_events',
            'client_portal_memberships', 'client_branches', 'client_installations',
            'cloud_sync_events', 'cloud_sync_stock_items',
        ];
        $stmt = $pdo->query("
            SELECT DISTINCT TABLE_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'client_id'
            ORDER BY TABLE_NAME
        ");
        return array_values(array_diff(array_map('strval', $stmt->fetchAll(PDO::FETCH_COLUMN)), $supported));
    }
}

if (!function_exists('admin_client_merge_counts')) {
    function admin_client_merge_counts(PDO $pdo, int $clientId): array
    {
        $tables = [
            'licenses', 'payments', 'license_notifications', 'license_events',
            'client_portal_memberships', 'client_branches', 'client_installations',
            'cloud_sync_events', 'cloud_sync_stock_items',
        ];
        $counts = [];
        foreach ($tables as $table) {
            if (!admin_client_merge_table_exists($pdo, $table)) {
                $counts[$table] = 0;
                continue;
            }
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE client_id = :client_id");
            $stmt->execute(['client_id' => $clientId]);
            $counts[$table] = (int) $stmt->fetchColumn();
        }
        return $counts;
    }
}

if (!function_exists('admin_client_merge_upsert_branch')) {
    function admin_client_merge_upsert_branch(PDO $pdo, int $clientId, string $name, string $code): int
    {
        $select = $pdo->prepare('SELECT id FROM client_branches WHERE client_id = :client_id AND code = :code LIMIT 1');
        $select->execute(['client_id' => $clientId, 'code' => $code]);
        $branchId = (int) $select->fetchColumn();
        if ($branchId > 0) {
            $update = $pdo->prepare("UPDATE client_branches SET name = :name, status = 'active', updated_at = NOW() WHERE id = :id");
            $update->execute(['name' => $name, 'id' => $branchId]);
            return $branchId;
        }

        $insert = $pdo->prepare("INSERT INTO client_branches (client_id, name, code, status) VALUES (:client_id, :name, :code, 'active')");
        $insert->execute(['client_id' => $clientId, 'name' => $name, 'code' => $code]);
        return (int) $pdo->lastInsertId();
    }
}

if (!function_exists('admin_client_merge_actor')) {
    function admin_client_merge_actor(): string
    {
        $admin = $_SESSION['admin_user'] ?? [];
        return trim((string) ($admin['email'] ?? $admin['username'] ?? 'admin')) ?: 'admin';
    }
}

if (!function_exists('admin_client_merge')) {
    function admin_client_merge(PDO $pdo, array $input): array
    {
        $sourceClientId = (int) ($input['source_client_id'] ?? 0);
        $targetClientId = (int) ($input['target_client_id'] ?? 0);
        $sourceBranchName = trim((string) ($input['source_branch_name'] ?? ''));
        $targetBranchName = trim((string) ($input['target_branch_name'] ?? ''));
        $sourceBranchCode = admin_client_merge_branch_code((string) ($input['source_branch_code'] ?? ''));
        $targetBranchCode = admin_client_merge_branch_code((string) ($input['target_branch_code'] ?? ''));

        if ($sourceClientId <= 0 || $targetClientId <= 0 || $sourceClientId === $targetClientId) {
            throw new InvalidArgumentException('Selecciona dos clientes distintos.');
        }
        if ($sourceBranchName === '' || $targetBranchName === '' || $sourceBranchCode === '' || $targetBranchCode === '') {
            throw new InvalidArgumentException('Los nombres y codigos de ambas sucursales son obligatorios.');
        }
        if ($sourceBranchCode === $targetBranchCode) {
            throw new InvalidArgumentException('Cada sucursal debe tener un codigo distinto.');
        }
        if (function_exists('admin_cloud_sync_ensure_schema') && !admin_cloud_sync_ensure_schema($pdo)) {
            throw new RuntimeException('No se pudo preparar el esquema cloud antes de fusionar.');
        }
        if (!admin_client_merge_ensure_schema($pdo)) {
            throw new RuntimeException('No se pudo preparar la auditoria de fusiones.');
        }

        $unknownTables = admin_client_merge_unknown_tables($pdo);
        if ($unknownTables) {
            error_log('[FLUS Admin] client merge blocked by unknown tables: ' . implode(', ', $unknownTables));
            throw new RuntimeException('La base contiene relaciones nuevas que esta version no puede fusionar de forma segura.');
        }

        $pdo->beginTransaction();
        try {
            $clientIds = [$sourceClientId, $targetClientId];
            sort($clientIds, SORT_NUMERIC);
            $lockClients = $pdo->prepare('SELECT * FROM clients WHERE id IN (?, ?) ORDER BY id FOR UPDATE');
            $lockClients->execute($clientIds);
            $clients = [];
            foreach ($lockClients->fetchAll() as $client) {
                $clients[(int) $client['id']] = $client;
            }
            if (!isset($clients[$sourceClientId], $clients[$targetClientId])) {
                throw new RuntimeException('Uno de los clientes ya no existe.');
            }

            $alreadyMerged = $pdo->prepare('SELECT target_client_id FROM client_merge_events WHERE source_client_id = :source_client_id LIMIT 1 FOR UPDATE');
            $alreadyMerged->execute(['source_client_id' => $sourceClientId]);
            if ((int) $alreadyMerged->fetchColumn() > 0) {
                throw new RuntimeException('Ese cliente ya fue fusionado anteriormente.');
            }

            $lockLicenses = $pdo->prepare('SELECT id FROM licenses WHERE client_id IN (?, ?) ORDER BY id FOR UPDATE');
            $lockLicenses->execute($clientIds);
            $lockLicenses->fetchAll();

            $uidConflict = $pdo->prepare('
                SELECT s.installation_uid
                FROM client_installations s
                INNER JOIN client_installations t ON t.client_id = :target_client_id AND t.installation_uid = s.installation_uid
                WHERE s.client_id = :source_client_id LIMIT 1
            ');
            $uidConflict->execute(['target_client_id' => $targetClientId, 'source_client_id' => $sourceClientId]);
            if ($uidConflict->fetchColumn() !== false) {
                throw new RuntimeException('Las instalaciones tienen un identificador duplicado. Revisa la vinculacion antes de fusionar.');
            }

            $branchConflict = $pdo->prepare('
                SELECT s.code
                FROM client_branches s
                INNER JOIN client_branches t ON t.client_id = :target_client_id AND t.code = s.code
                WHERE s.client_id = :source_client_id LIMIT 1
            ');
            $branchConflict->execute(['target_client_id' => $targetClientId, 'source_client_id' => $sourceClientId]);
            if ($branchConflict->fetchColumn() !== false) {
                throw new RuntimeException('Hay codigos de sucursal repetidos entre ambos clientes.');
            }

            $targetCodeInSource = $pdo->prepare('SELECT id FROM client_branches WHERE client_id = :client_id AND code = :code LIMIT 1');
            $targetCodeInSource->execute(['client_id' => $sourceClientId, 'code' => $targetBranchCode]);
            if ($targetCodeInSource->fetchColumn() !== false) {
                throw new RuntimeException('El codigo de la sucursal principal ya se usa en el cliente de origen.');
            }

            $sourceSnapshot = $clients[$sourceClientId];
            $summary = admin_client_merge_counts($pdo, $sourceClientId);
            $targetBranchId = admin_client_merge_upsert_branch($pdo, $targetClientId, $targetBranchName, $targetBranchCode);
            $sourceBranchLookup = $pdo->prepare('SELECT id FROM client_branches WHERE client_id = :client_id AND code = :code LIMIT 1');
            $sourceBranchLookup->execute(['client_id' => $sourceClientId, 'code' => $sourceBranchCode]);
            $sourceBranchId = (int) $sourceBranchLookup->fetchColumn();
            if ($sourceBranchId > 0) {
                $moveNamedBranch = $pdo->prepare("UPDATE client_branches SET client_id = :target_client_id, name = :name, status = 'active', updated_at = NOW() WHERE id = :id");
                $moveNamedBranch->execute(['target_client_id' => $targetClientId, 'name' => $sourceBranchName, 'id' => $sourceBranchId]);
            } else {
                $sourceBranchId = admin_client_merge_upsert_branch($pdo, $targetClientId, $sourceBranchName, $sourceBranchCode);
            }

            $assignTarget = $pdo->prepare('UPDATE client_installations SET branch_id = :branch_id, updated_at = NOW() WHERE client_id = :client_id AND branch_id IS NULL');
            $assignTarget->execute(['branch_id' => $targetBranchId, 'client_id' => $targetClientId]);
            $assignSource = $pdo->prepare('UPDATE client_installations SET branch_id = :branch_id, updated_at = NOW() WHERE client_id = :client_id AND branch_id IS NULL');
            $assignSource->execute(['branch_id' => $sourceBranchId, 'client_id' => $sourceClientId]);

            foreach (['cloud_sync_events', 'cloud_sync_stock_items'] as $cloudTable) {
                if (!admin_client_merge_table_exists($pdo, $cloudTable)) {
                    continue;
                }
                $assignTargetCloud = $pdo->prepare("UPDATE {$cloudTable} SET branch_id = :branch_id WHERE client_id = :client_id AND branch_id IS NULL");
                $assignTargetCloud->execute(['branch_id' => $targetBranchId, 'client_id' => $targetClientId]);
                $assignSourceCloud = $pdo->prepare("UPDATE {$cloudTable} SET branch_id = :branch_id WHERE client_id = :client_id AND branch_id IS NULL");
                $assignSourceCloud->execute(['branch_id' => $sourceBranchId, 'client_id' => $sourceClientId]);
            }

            $moveTables = [
                'licenses', 'payments', 'license_notifications', 'license_events',
                'cloud_sync_events', 'cloud_sync_stock_items', 'client_installations', 'client_branches',
            ];
            foreach ($moveTables as $table) {
                if (admin_client_merge_table_exists($pdo, $table)) {
                    $move = $pdo->prepare("UPDATE {$table} SET client_id = :target_client_id WHERE client_id = :source_client_id");
                    $move->execute(['target_client_id' => $targetClientId, 'source_client_id' => $sourceClientId]);
                }
            }

            if (admin_client_merge_table_exists($pdo, 'client_portal_memberships')) {
                $deactivateDuplicates = $pdo->prepare('
                    UPDATE client_portal_memberships source_membership
                    INNER JOIN client_portal_memberships target_membership
                        ON target_membership.user_id = source_membership.user_id AND target_membership.client_id = :target_client_id
                    SET source_membership.is_active = 0, source_membership.updated_at = NOW()
                    WHERE source_membership.client_id = :source_client_id
                ');
                $deactivateDuplicates->execute(['target_client_id' => $targetClientId, 'source_client_id' => $sourceClientId]);
                $moveMemberships = $pdo->prepare('
                    UPDATE client_portal_memberships source_membership
                    LEFT JOIN client_portal_memberships target_membership
                        ON target_membership.user_id = source_membership.user_id
                       AND target_membership.client_id = :target_client_check
                    SET source_membership.client_id = :target_client_id, source_membership.updated_at = NOW()
                    WHERE source_membership.client_id = :source_client_id
                      AND target_membership.id IS NULL
                ');
                $moveMemberships->execute([
                    'target_client_id' => $targetClientId,
                    'source_client_id' => $sourceClientId,
                    'target_client_check' => $targetClientId,
                ]);
            }

            $mergeNote = sprintf('[%s] Fusionado en cliente #%d. Datos historicos preservados en auditoria.', date('Y-m-d H:i:s'), $targetClientId);
            $notes = trim((string) ($sourceSnapshot['internal_notes'] ?? ''));
            $archive = $pdo->prepare("UPDATE clients SET status = 'inactivo', internal_notes = :notes, updated_at = NOW() WHERE id = :id");
            $archive->execute(['notes' => trim($notes . ($notes === '' ? '' : "\n") . $mergeNote), 'id' => $sourceClientId]);

            $audit = $pdo->prepare('
                INSERT INTO client_merge_events (source_client_id, target_client_id, source_snapshot_json, summary_json, actor)
                VALUES (:source_client_id, :target_client_id, :source_snapshot_json, :summary_json, :actor)
            ');
            $audit->execute([
                'source_client_id' => $sourceClientId,
                'target_client_id' => $targetClientId,
                'source_snapshot_json' => json_encode($sourceSnapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                'summary_json' => json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                'actor' => admin_client_merge_actor(),
            ]);

            $remaining = admin_client_merge_counts($pdo, $sourceClientId);
            foreach ($remaining as $table => $count) {
                if ($table !== 'client_portal_memberships' && $count > 0) {
                    throw new RuntimeException('La fusion no pudo completar todas las relaciones.');
                }
            }

            $pdo->commit();
            return [
                'source_client_id' => $sourceClientId,
                'target_client_id' => $targetClientId,
                'source_branch_id' => $sourceBranchId,
                'target_branch_id' => $targetBranchId,
                'moved' => $summary,
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
