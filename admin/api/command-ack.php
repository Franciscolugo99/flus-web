<?php
declare(strict_types=1);

require_once __DIR__ . '/_cloud-command-bootstrap.php';

$results = $cloudCommandRequest['results'] ?? [];
if (!is_array($results) || count($results) > 10) {
    cloud_command_json_response(400, ['ok' => false, 'error' => 'RESULTS_INVALID']);
}

$installationId = (int) $cloudCommandInstallation['id'];
$terminalStatuses = ['applied', 'rejected', 'conflict', 'failed'];
$accepted = 0;
$duplicates = 0;
$rejected = 0;
$acknowledgedUids = [];

try {
    $cloudCommandPdo->beginTransaction();
    $select = $cloudCommandPdo->prepare('SELECT id, status, claim_token_hash FROM cloud_commands WHERE installation_id = :installation_id AND command_uid = :command_uid LIMIT 1 FOR UPDATE');
    $update = $cloudCommandPdo->prepare('UPDATE cloud_commands SET status = :status, completed_at = UTC_TIMESTAMP(), result_json = :result_json, last_error = :last_error, claim_token_hash = NULL, lease_until = NULL, updated_at = CURRENT_TIMESTAMP WHERE id = :id');

    foreach ($results as $result) {
        if (!is_array($result)) {
            $rejected++;
            continue;
        }
        $commandUid = admin_cloud_sync_normalize_uid((string) ($result['command_uid'] ?? ''), 120);
        $claimToken = trim((string) ($result['claim_token'] ?? ''));
        $status = strtolower(trim((string) ($result['status'] ?? '')));
        if ($commandUid === '' || strlen($claimToken) < 32 || !in_array($status, $terminalStatuses, true)) {
            $rejected++;
            continue;
        }

        $select->execute(['installation_id' => $installationId, 'command_uid' => $commandUid]);
        $command = $select->fetch();
        if (!is_array($command)) {
            $rejected++;
            continue;
        }
        if (in_array((string) $command['status'], $terminalStatuses, true)) {
            $duplicates++;
            $acknowledgedUids[] = $commandUid;
            continue;
        }
        $claimHash = (string) ($command['claim_token_hash'] ?? '');
        if ($claimHash === '' || !hash_equals($claimHash, hash('sha256', $claimToken))) {
            $rejected++;
            continue;
        }

        $publicResult = is_array($result['result'] ?? null) ? $result['result'] : [];
        $publicResult = [
            'product_id' => max(0, (int) ($publicResult['product_id'] ?? 0)),
            'previous_price' => isset($publicResult['previous_price']) ? round((float) $publicResult['previous_price'], 2) : null,
            'applied_price' => isset($publicResult['applied_price']) ? round((float) $publicResult['applied_price'], 2) : null,
            'history_id' => max(0, (int) ($publicResult['history_id'] ?? 0)),
            'error_code' => substr(preg_replace('/[^A-Z0-9._-]/', '', strtoupper((string) ($publicResult['error_code'] ?? ''))) ?: '', 0, 80),
        ];
        $resultJson = json_encode($publicResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $lastError = $status === 'applied' ? null : ($publicResult['error_code'] ?: strtoupper($status));
        $update->execute([
            'status' => $status,
            'result_json' => is_string($resultJson) ? $resultJson : '{}',
            'last_error' => $lastError,
            'id' => (int) $command['id'],
        ]);
        $accepted++;
        $acknowledgedUids[] = $commandUid;
    }
    $cloudCommandPdo->commit();
    cloud_command_json_response(200, ['ok' => true, 'accepted' => $accepted, 'duplicates' => $duplicates, 'rejected' => $rejected, 'acknowledged_uids' => $acknowledgedUids]);
} catch (Throwable $e) {
    if ($cloudCommandPdo->inTransaction()) {
        $cloudCommandPdo->rollBack();
    }
    error_log('[FLUS Admin] cloud command ack: ' . $e->getMessage());
    cloud_command_json_response(500, ['ok' => false, 'error' => 'SERVER_ERROR']);
}
