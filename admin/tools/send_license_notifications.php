<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/bootstrap.php';

function usage(): void
{
    echo "Uso:\n";
    echo "  php admin/tools/send_license_notifications.php --mode=due [--days=15,7,3,1,0] [--license-key=FLUS-XXXX-XXXX-XXXX] [--dry-run] [--force]\n";
    echo "  php admin/tools/send_license_notifications.php --mode=test --license-key=FLUS-XXXX-XXXX-XXXX [--dry-run]\n";
}

function option_value(array $options, string $name, ?string $default = null): ?string
{
    return isset($options[$name]) && is_string($options[$name]) ? $options[$name] : $default;
}

$options = getopt('', [
    'mode:',
    'days:',
    'license-key:',
    'dry-run',
    'force',
    'help',
]);

if (isset($options['help'])) {
    usage();
    exit(0);
}

$mode = option_value($options, 'mode', 'due');
$licenseKey = option_value($options, 'license-key');
$dryRun = isset($options['dry-run']);
$force = isset($options['force']);
$daysValue = option_value($options, 'days', '15,7,3,1,0');
$days = array_values(array_filter(
    array_map('intval', explode(',', (string) $daysValue)),
    static fn (int $day): bool => $day >= 0
));

if (!in_array($mode, ['due', 'test'], true)) {
    fwrite(STDERR, "Modo invalido: {$mode}\n");
    usage();
    exit(1);
}

if ($mode === 'test' && (!$licenseKey || trim($licenseKey) === '')) {
    fwrite(STDERR, "El modo test requiere --license-key.\n");
    usage();
    exit(1);
}

$pdo = admin_db();

if (!admin_mail_configured() && !$dryRun) {
    fwrite(STDERR, "MAIL_NOT_CONFIGURED\n");
    exit(1);
}

if ($mode === 'test') {
    $license = find_license_for_test_notification($pdo, (string) $licenseKey);
    $licenses = $license ? [$license] : [];
} else {
    $licenses = find_license_notification_candidates($pdo, $days, $licenseKey);
}

if ($licenses === []) {
    echo "Sin licencias para notificar.\n";
    exit(0);
}

$sent = 0;
$skipped = 0;
$failed = 0;

foreach ($licenses as $license) {
    $licenseId = (int) $license['id'];
    $clientId = (int) $license['client_id'];
    $toEmail = trim((string) ($license['email'] ?? ''));
    $type = license_notification_type_for($license, $mode === 'test');
    $label = (string) $license['license_key'] . ' -> ' . ($toEmail ?: 'sin-email');

    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        log_license_notification($pdo, $licenseId, $clientId, $type, $toEmail ?: null, 'skipped', 'INVALID_RECIPIENT');
        echo "[SKIP] {$label} INVALID_RECIPIENT\n";
        $skipped++;
        continue;
    }

    if ($mode === 'due' && !$force && license_notification_already_sent($pdo, $licenseId, $type)) {
        echo "[SKIP] {$label} ya enviado {$type}\n";
        $skipped++;
        continue;
    }

    $message = build_license_notification_message($license, $mode === 'test');

    if ($dryRun) {
        echo "[DRY] {$label} {$type} | " . $message['subject'] . "\n";
        $skipped++;
        continue;
    }

    $result = admin_send_license_mail($toEmail, $message['subject'], $message['body'], $message['html'] ?? null);
    if ($result['ok'] ?? false) {
        log_license_notification($pdo, $licenseId, $clientId, $type, $toEmail, 'sent', null);
        echo "[SENT] {$label} {$type}\n";
        $sent++;
        continue;
    }

    $error = (string) ($result['error'] ?? 'UNKNOWN_ERROR');
    log_license_notification($pdo, $licenseId, $clientId, $type, $toEmail, 'failed', $error);
    echo "[FAIL] {$label} {$error}\n";
    $failed++;
}

echo "Resumen: enviados={$sent} omitidos={$skipped} fallidos={$failed}\n";
exit($failed > 0 ? 1 : 0);
