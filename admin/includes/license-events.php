<?php
declare(strict_types=1);

if (!function_exists('admin_license_events_ensure_schema')) {
    function admin_license_events_ensure_schema(PDO $pdo): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS license_events (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    license_id INT UNSIGNED NOT NULL,
                    client_id INT UNSIGNED NOT NULL,
                    event_type VARCHAR(40) NOT NULL,
                    from_status VARCHAR(20) DEFAULT NULL,
                    to_status VARCHAR(20) DEFAULT NULL,
                    reason VARCHAR(190) DEFAULT NULL,
                    notes TEXT DEFAULT NULL,
                    actor VARCHAR(190) DEFAULT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    KEY idx_license_events_license_id (license_id),
                    KEY idx_license_events_client_id (client_id),
                    KEY idx_license_events_created_at (created_at),
                    CONSTRAINT fk_license_events_license FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE RESTRICT ON UPDATE CASCADE,
                    CONSTRAINT fk_license_events_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $ready = true;
        } catch (Throwable $e) {
            error_log('[FLUS Admin] license_events schema: ' . $e->getMessage());
            $ready = false;
        }

        return $ready;
    }
}

if (!function_exists('admin_license_event_actor')) {
    function admin_license_event_actor(): string
    {
        $admin = current_admin() ?? [];
        $name = trim((string) ($admin['full_name'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        return trim((string) ($admin['username'] ?? 'admin')) ?: 'admin';
    }
}

if (!function_exists('admin_license_event_log')) {
    function admin_license_event_log(
        PDO $pdo,
        int $licenseId,
        int $clientId,
        string $eventType,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $reason,
        ?string $notes = null
    ): void {
        if (!admin_license_events_ensure_schema($pdo)) {
            return;
        }

        $reason = trim((string) $reason);
        $notes = trim((string) $notes);

        $stmt = $pdo->prepare("
            INSERT INTO license_events (
                license_id, client_id, event_type, from_status, to_status, reason, notes, actor
            ) VALUES (
                :license_id, :client_id, :event_type, :from_status, :to_status, :reason, :notes, :actor
            )
        ");
        $stmt->execute([
            'license_id' => $licenseId,
            'client_id' => $clientId,
            'event_type' => substr($eventType, 0, 40),
            'from_status' => $fromStatus !== null ? substr($fromStatus, 0, 20) : null,
            'to_status' => $toStatus !== null ? substr($toStatus, 0, 20) : null,
            'reason' => $reason !== '' ? substr($reason, 0, 190) : null,
            'notes' => $notes !== '' ? $notes : null,
            'actor' => substr(admin_license_event_actor(), 0, 190),
        ]);
    }
}

if (!function_exists('admin_license_events_recent')) {
    function admin_license_events_recent(PDO $pdo, int $limit = 8): array
    {
        if (!admin_license_events_ensure_schema($pdo)) {
            return [];
        }

        $limit = max(1, min(50, $limit));
        $stmt = $pdo->query("
            SELECT
                le.*,
                l.license_key,
                c.legal_name,
                c.trade_name
            FROM license_events le
            INNER JOIN licenses l ON l.id = le.license_id
            INNER JOIN clients c ON c.id = le.client_id
            ORDER BY le.created_at DESC, le.id DESC
            LIMIT " . $limit
        );

        return $stmt->fetchAll();
    }
}

if (!function_exists('admin_license_events_for_client')) {
    function admin_license_events_for_client(PDO $pdo, int $clientId, int $limit = 12): array
    {
        if (!admin_license_events_ensure_schema($pdo)) {
            return [];
        }

        $limit = max(1, min(50, $limit));
        $stmt = $pdo->prepare("
            SELECT le.*, l.license_key
            FROM license_events le
            INNER JOIN licenses l ON l.id = le.license_id
            WHERE le.client_id = :client_id
            ORDER BY le.created_at DESC, le.id DESC
            LIMIT " . $limit
        );
        $stmt->execute(['client_id' => $clientId]);

        return $stmt->fetchAll();
    }
}

if (!function_exists('admin_license_event_label')) {
    function admin_license_event_label(string $eventType): string
    {
        return match ($eventType) {
            'status_change' => 'Cambio de estado',
            'license_created' => 'Licencia creada',
            'license_updated' => 'Licencia actualizada',
            default => ucfirst(str_replace('_', ' ', $eventType)),
        };
    }
}
