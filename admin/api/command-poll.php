<?php
declare(strict_types=1);

require_once __DIR__ . '/_cloud-command-bootstrap.php';

$limit = max(1, min(10, (int) ($cloudCommandRequest['limit'] ?? 5)));
$installationId = (int) $cloudCommandInstallation['id'];

try {
    $cloudCommandPdo->beginTransaction();
    $cloudCommandPdo->prepare("UPDATE cloud_commands SET status = 'pending', claim_token_hash = NULL, claimed_at = NULL, lease_until = NULL, available_at = UTC_TIMESTAMP(), updated_at = CURRENT_TIMESTAMP WHERE installation_id = :installation_id AND status = 'processing' AND lease_until < UTC_TIMESTAMP()")
        ->execute(['installation_id' => $installationId]);
    $cloudCommandPdo->prepare("UPDATE cloud_commands SET status = 'expired', completed_at = UTC_TIMESTAMP(), last_error = 'COMMAND_EXPIRED', updated_at = CURRENT_TIMESTAMP WHERE installation_id = :installation_id AND status = 'pending' AND expires_at <= UTC_TIMESTAMP()")
        ->execute(['installation_id' => $installationId]);

    $select = $cloudCommandPdo->prepare("SELECT id, command_uid, command_type, payload_json, expires_at FROM cloud_commands WHERE installation_id = :installation_id AND status = 'pending' AND available_at <= UTC_TIMESTAMP() AND expires_at > UTC_TIMESTAMP() ORDER BY id ASC LIMIT {$limit} FOR UPDATE");
    $select->execute(['installation_id' => $installationId]);
    $rows = $select->fetchAll();
    $commands = [];
    $claim = $cloudCommandPdo->prepare("UPDATE cloud_commands SET status = 'processing', attempts = attempts + 1, claimed_at = UTC_TIMESTAMP(), lease_until = DATE_ADD(UTC_TIMESTAMP(), INTERVAL 5 MINUTE), claim_token_hash = :claim_hash, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND status = 'pending'");
    foreach ($rows as $row) {
        $payload = json_decode((string) $row['payload_json'], true);
        if (!is_array($payload)) {
            continue;
        }
        $claimToken = bin2hex(random_bytes(24));
        $claim->execute(['claim_hash' => hash('sha256', $claimToken), 'id' => (int) $row['id']]);
        if ($claim->rowCount() !== 1) {
            continue;
        }
        $commands[] = [
            'command_uid' => (string) $row['command_uid'],
            'command_type' => (string) $row['command_type'],
            'claim_token' => $claimToken,
            'expires_at' => (string) $row['expires_at'],
            'payload' => $payload,
        ];
    }
    $cloudCommandPdo->prepare("UPDATE client_installations SET status = 'online', last_seen_at = UTC_TIMESTAMP(), updated_at = CURRENT_TIMESTAMP WHERE id = :id")
        ->execute(['id' => $installationId]);
    $cloudCommandPdo->commit();

    cloud_command_json_response(200, ['ok' => true, 'commands' => $commands, 'next_poll_after_sec' => 60]);
} catch (Throwable $e) {
    if ($cloudCommandPdo->inTransaction()) {
        $cloudCommandPdo->rollBack();
    }
    error_log('[FLUS Admin] cloud command poll: ' . $e->getMessage());
    cloud_command_json_response(500, ['ok' => false, 'error' => 'SERVER_ERROR']);
}
