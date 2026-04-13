<?php
declare(strict_types=1);

if (!function_exists('find_licenses_expiring_within')) {
    function find_licenses_expiring_within(PDO $pdo, int $days): array
    {
        $sql = "
            SELECT
                l.id,
                l.client_id,
                l.license_key,
                l.status,
                l.expires_at,
                c.legal_name,
                c.email
            FROM licenses l
            INNER JOIN clients c ON c.id = l.client_id
            WHERE l.expires_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
              AND l.status NOT IN ('suspendida')
            ORDER BY l.expires_at ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}

if (!function_exists('log_license_notification')) {
    function log_license_notification(PDO $pdo, int $licenseId, int $clientId, string $type, ?string $sentTo, string $status, ?string $errorMessage = null): void
    {
        $stmt = $pdo->prepare("
            INSERT INTO license_notifications (
                license_id,
                client_id,
                notification_type,
                sent_to,
                sent_at,
                status,
                error_message
            ) VALUES (
                :license_id,
                :client_id,
                :notification_type,
                :sent_to,
                NOW(),
                :status,
                :error_message
            )
        ");

        $stmt->execute([
            'license_id' => $licenseId,
            'client_id' => $clientId,
            'notification_type' => $type,
            'sent_to' => $sentTo,
            'status' => $status,
            'error_message' => $errorMessage,
        ]);
    }
}
