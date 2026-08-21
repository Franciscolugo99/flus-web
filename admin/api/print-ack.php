<?php
declare(strict_types=1);

require __DIR__ . '/_print-agent-bootstrap.php';

$jobUid = (string) ($printAgentRequest['job_uid'] ?? '');
$claimToken = (string) ($printAgentRequest['claim_token'] ?? '');
$status = (string) ($printAgentRequest['status'] ?? '');
$result = is_array($printAgentRequest['result'] ?? null) ? $printAgentRequest['result'] : [];

try {
    if (!admin_print_ack_job($printAgentPdo, $printAgent, $jobUid, $claimToken, $status, $result)) {
        print_agent_json_response(409, ['ok' => false, 'error' => 'ACK_REJECTED']);
    }
    print_agent_json_response(200, ['ok' => true]);
} catch (Throwable $e) {
    error_log('[FLUS Print] ack: ' . $e->getMessage());
    print_agent_json_response(500, ['ok' => false, 'error' => 'ACK_FAILED']);
}
