<?php
declare(strict_types=1);

if (!function_exists('admin_print_queue_ensure_schema')) {
    function admin_print_queue_ensure_schema(PDO $pdo): bool
    {
        static $ready = null;
        if ($ready !== null) {
            return $ready;
        }

        try {
            $stmt = $pdo->query("SELECT table_name FROM information_schema.TABLES WHERE table_schema = DATABASE() AND table_name IN ('print_agents','print_jobs')");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
            $ready = count(array_unique($tables)) === 2;
        } catch (Throwable $e) {
            error_log('[FLUS Print] schema check: ' . $e->getMessage());
            $ready = false;
        }

        return $ready;
    }
}

if (!function_exists('admin_print_normalize_uid')) {
    function admin_print_normalize_uid(string $value, int $maxLength = 120): string
    {
        $value = trim($value);
        if ($value === '' || preg_match('/^[A-Za-z0-9._:@-]+$/', $value) !== 1) {
            return '';
        }
        return substr($value, 0, $maxLength);
    }
}

if (!function_exists('admin_print_auth_agent')) {
    function admin_print_auth_agent(PDO $pdo, string $agentUid, string $token): ?array
    {
        $agentUid = admin_print_normalize_uid($agentUid, 120);
        if ($agentUid === '' || strlen($token) < 24 || strlen($token) > 512) {
            return null;
        }

        $stmt = $pdo->prepare('SELECT * FROM print_agents WHERE agent_uid = :agent_uid AND status = \'active\' LIMIT 1');
        $stmt->execute(['agent_uid' => $agentUid]);
        $agent = $stmt->fetch();
        if (!is_array($agent)) {
            return null;
        }

        $actualHash = hash('sha256', $token);
        if (!hash_equals((string) $agent['token_hash'], $actualHash)) {
            return null;
        }

        $touch = $pdo->prepare('UPDATE print_agents SET last_seen_at = UTC_TIMESTAMP(), last_error = NULL WHERE id = :id');
        $touch->execute(['id' => (int) $agent['id']]);
        return $agent;
    }
}

if (!function_exists('admin_print_claim_next_job')) {
    function admin_print_claim_next_job(PDO $pdo, array $agent): ?array
    {
        $agentId = (int) ($agent['id'] ?? 0);
        $clientId = (int) ($agent['client_id'] ?? 0);
        $branchId = (int) ($agent['branch_id'] ?? 0);
        if ($agentId <= 0 || $clientId <= 0 || $branchId <= 0) {
            return null;
        }

        for ($try = 0; $try < 3; $try++) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("SELECT * FROM print_jobs
                    WHERE client_id = :client_id
                      AND branch_id = :branch_id
                      AND (target_agent_id IS NULL OR target_agent_id = :agent_id)
                      AND attempts < 10
                      AND expires_at > UTC_TIMESTAMP()
                      AND available_at <= UTC_TIMESTAMP()
                      AND (status = 'pending' OR (status = 'claimed' AND lease_until < UTC_TIMESTAMP()))
                    ORDER BY id ASC
                    LIMIT 1
                    FOR UPDATE");
                $stmt->execute([
                    'client_id' => $clientId,
                    'branch_id' => $branchId,
                    'agent_id' => $agentId,
                ]);
                $job = $stmt->fetch();
                if (!is_array($job)) {
                    $pdo->commit();
                    return null;
                }

                $claimToken = bin2hex(random_bytes(24));
                $claimHash = hash('sha256', $claimToken);
                $update = $pdo->prepare("UPDATE print_jobs
                    SET status = 'claimed',
                        attempts = attempts + 1,
                        claimed_by_agent_id = :agent_id,
                        claimed_at = UTC_TIMESTAMP(),
                        lease_until = DATE_ADD(UTC_TIMESTAMP(), INTERVAL 90 SECOND),
                        claim_token_hash = :claim_hash,
                        last_error = NULL
                    WHERE id = :id");
                $update->execute([
                    'agent_id' => $agentId,
                    'claim_hash' => $claimHash,
                    'id' => (int) $job['id'],
                ]);
                $pdo->commit();

                $payload = json_decode((string) ($job['payload_json'] ?? '{}'), true);
                if (!is_array($payload)) {
                    $payload = [];
                }
                return [
                    'job_uid' => (string) $job['job_uid'],
                    'claim_token' => $claimToken,
                    'document_type' => (string) $job['document_type'],
                    'source_type' => (string) $job['source_type'],
                    'source_uid' => (string) ($job['source_uid'] ?? ''),
                    'payload' => $payload,
                    'attempt' => (int) $job['attempts'] + 1,
                ];
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                if ($try === 2) {
                    throw $e;
                }
                usleep(50000);
            }
        }
        return null;
    }
}

if (!function_exists('admin_print_ack_job')) {
    function admin_print_ack_job(PDO $pdo, array $agent, string $jobUid, string $claimToken, string $status, array $result = []): bool
    {
        $agentId = (int) ($agent['id'] ?? 0);
        $jobUid = admin_print_normalize_uid($jobUid, 120);
        $status = strtolower(trim($status));
        if ($agentId <= 0 || $jobUid === '' || strlen($claimToken) < 24 || !in_array($status, ['printed', 'failed'], true)) {
            return false;
        }

        $stmt = $pdo->prepare('SELECT * FROM print_jobs WHERE job_uid = :job_uid AND claimed_by_agent_id = :agent_id LIMIT 1');
        $stmt->execute(['job_uid' => $jobUid, 'agent_id' => $agentId]);
        $job = $stmt->fetch();
        if (!is_array($job) || !hash_equals((string) ($job['claim_token_hash'] ?? ''), hash('sha256', $claimToken))) {
            return false;
        }

        $resultJson = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($resultJson) || strlen($resultJson) > 16384) {
            $resultJson = '{}';
        }
        $error = mb_substr(trim((string) ($result['error'] ?? '')), 0, 190);

        if ($status === 'printed') {
            $update = $pdo->prepare("UPDATE print_jobs
                SET status = 'printed', printed_at = UTC_TIMESTAMP(), result_json = :result_json,
                    last_error = NULL, lease_until = NULL, claim_token_hash = NULL
                WHERE id = :id AND status = 'claimed'");
            $update->execute(['result_json' => $resultJson, 'id' => (int) $job['id']]);
            return $update->rowCount() === 1;
        }

        $retry = ((int) ($job['attempts'] ?? 0)) < 10 && strtotime((string) $job['expires_at']) > time();
        $nextStatus = $retry ? 'pending' : 'failed';
        $update = $pdo->prepare("UPDATE print_jobs
            SET status = :status,
                available_at = CASE WHEN :retry = 1 THEN DATE_ADD(UTC_TIMESTAMP(), INTERVAL 15 SECOND) ELSE available_at END,
                result_json = :result_json, last_error = :last_error,
                lease_until = NULL, claim_token_hash = NULL
            WHERE id = :id AND status = 'claimed'");
        $update->execute([
            'status' => $nextStatus,
            'retry' => $retry ? 1 : 0,
            'result_json' => $resultJson,
            'last_error' => $error !== '' ? $error : 'PRINT_FAILED',
            'id' => (int) $job['id'],
        ]);
        return $update->rowCount() === 1;
    }
}
