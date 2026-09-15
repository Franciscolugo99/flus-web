<?php
declare(strict_types=1);

require __DIR__ . '/_print-agent-bootstrap.php';

try {
    $job = admin_print_claim_next_job($printAgentPdo, $printAgent);
    print_agent_json_response(200, [
        'ok' => true,
        'job' => $job,
        'server_time' => gmdate(DATE_ATOM),
    ]);
} catch (Throwable $e) {
    error_log('[FLUS Print] poll: ' . $e->getMessage());
    print_agent_json_response(500, ['ok' => false, 'error' => 'POLL_FAILED']);
}
