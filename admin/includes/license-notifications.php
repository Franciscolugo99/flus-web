<?php
declare(strict_types=1);

if (!function_exists('admin_notification_smtp_expect')) {
    function admin_notification_smtp_expect($socket, array $codes, ?string &$response = null): bool
    {
        $response = '';

        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        if ($response === '') {
            return false;
        }

        $code = (int) substr($response, 0, 3);
        return in_array($code, $codes, true);
    }
}

if (!function_exists('admin_notification_smtp_write')) {
    function admin_notification_smtp_write($socket, string $command): bool
    {
        return fwrite($socket, $command . "\r\n") !== false;
    }
}

if (!function_exists('admin_notification_header')) {
    function admin_notification_header(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
}

if (!function_exists('admin_notification_normalize_body')) {
    function admin_notification_normalize_body(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = str_replace("\n", "\r\n", $value);
        return preg_replace('/^\./m', '..', $value) ?? $value;
    }
}

if (!function_exists('admin_notification_html')) {
    function admin_notification_html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('admin_mail_configured')) {
    function admin_mail_configured(): bool
    {
        $mail = admin_config('mail', []);

        return trim((string) ($mail['host'] ?? '')) !== ''
            && trim((string) ($mail['username'] ?? '')) !== ''
            && (string) ($mail['password'] ?? '') !== '';
    }
}

if (!function_exists('admin_send_license_mail')) {
    function admin_send_license_mail(string $toEmail, string $subject, string $body, ?string $htmlBody = null): array
    {
        $mail = admin_config('mail', []);
        $host = trim((string) ($mail['host'] ?? ''));
        $port = (int) ($mail['port'] ?? 465);
        $secure = strtolower(trim((string) ($mail['secure'] ?? 'ssl')));
        $username = trim((string) ($mail['username'] ?? ''));
        $password = (string) ($mail['password'] ?? '');
        $fromEmail = trim((string) ($mail['from_email'] ?? $username));
        $fromName = trim((string) ($mail['from_name'] ?? 'FLUS Soporte'));
        $replyTo = trim((string) ($mail['reply_to'] ?? $fromEmail));
        $timeout = max(5, (int) ($mail['timeout'] ?? 15));

        if ($host === '' || $port <= 0 || $username === '' || $password === '' || $fromEmail === '') {
            return ['ok' => false, 'error' => 'MAIL_NOT_CONFIGURED'];
        }

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'INVALID_RECIPIENT'];
        }

        $transport = $secure === 'ssl' ? 'ssl://' . $host : $host;
        $socket = @stream_socket_client(
            $transport . ':' . $port,
            $errorNumber,
            $errorMessage,
            $timeout,
            STREAM_CLIENT_CONNECT
        );

        if (!is_resource($socket)) {
            return ['ok' => false, 'error' => 'SMTP_CONNECT_FAILED'];
        }

        stream_set_timeout($socket, $timeout);

        $success = admin_notification_smtp_expect($socket, [220]);

        if ($success) {
            $hostname = $_SERVER['SERVER_NAME'] ?? 'localhost';
            $success = admin_notification_smtp_write($socket, 'EHLO ' . $hostname)
                && admin_notification_smtp_expect($socket, [250]);
        }

        if ($success && $secure === 'tls') {
            $success = admin_notification_smtp_write($socket, 'STARTTLS')
                && admin_notification_smtp_expect($socket, [220]);
            if ($success) {
                $success = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            }
            if ($success) {
                $hostname = $_SERVER['SERVER_NAME'] ?? 'localhost';
                $success = admin_notification_smtp_write($socket, 'EHLO ' . $hostname)
                    && admin_notification_smtp_expect($socket, [250]);
            }
        }

        $success = $success
            && admin_notification_smtp_write($socket, 'AUTH LOGIN')
            && admin_notification_smtp_expect($socket, [334])
            && admin_notification_smtp_write($socket, base64_encode($username))
            && admin_notification_smtp_expect($socket, [334])
            && admin_notification_smtp_write($socket, base64_encode($password))
            && admin_notification_smtp_expect($socket, [235])
            && admin_notification_smtp_write($socket, 'MAIL FROM:<' . $fromEmail . '>')
            && admin_notification_smtp_expect($socket, [250])
            && admin_notification_smtp_write($socket, 'RCPT TO:<' . $toEmail . '>')
            && admin_notification_smtp_expect($socket, [250, 251])
            && admin_notification_smtp_write($socket, 'DATA')
            && admin_notification_smtp_expect($socket, [354]);

        if ($success) {
            $boundary = 'flus_mail_' . bin2hex(random_bytes(12));
            $headers = [
                'Date: ' . date(DATE_RFC2822),
                'From: ' . admin_notification_header($fromName) . ' <' . $fromEmail . '>',
                'To: ' . $toEmail,
                'Reply-To: ' . admin_notification_header($fromName) . ' <' . $replyTo . '>',
                'Subject: ' . admin_notification_header($subject),
                'MIME-Version: 1.0',
            ];

            if ($htmlBody !== null && trim($htmlBody) !== '') {
                $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
                $payloadBody = '--' . $boundary . "\r\n"
                    . "Content-Type: text/plain; charset=UTF-8\r\n"
                    . "Content-Transfer-Encoding: 8bit\r\n\r\n"
                    . admin_notification_normalize_body($body) . "\r\n\r\n"
                    . '--' . $boundary . "\r\n"
                    . "Content-Type: text/html; charset=UTF-8\r\n"
                    . "Content-Transfer-Encoding: 8bit\r\n\r\n"
                    . admin_notification_normalize_body($htmlBody) . "\r\n\r\n"
                    . '--' . $boundary . "--";
            } else {
                $headers[] = 'Content-Type: text/plain; charset=UTF-8';
                $headers[] = 'Content-Transfer-Encoding: 8bit';
                $payloadBody = admin_notification_normalize_body($body);
            }

            $payload = implode("\r\n", $headers) . "\r\n\r\n" . $payloadBody . "\r\n.";

            $success = admin_notification_smtp_write($socket, $payload)
                && admin_notification_smtp_expect($socket, [250]);
        }

        admin_notification_smtp_write($socket, 'QUIT');
        fclose($socket);

        return $success ? ['ok' => true, 'error' => null] : ['ok' => false, 'error' => 'SMTP_SEND_FAILED'];
    }
}

if (!function_exists('find_licenses_expiring_within')) {
    function find_licenses_expiring_within(PDO $pdo, int $days): array
    {
        $sql = "
            SELECT
                l.id,
                l.client_id,
                l.license_key,
                l.status,
                l.plan_type,
                l.expires_at,
                DATEDIFF(l.expires_at, CURDATE()) AS days_left,
                c.legal_name,
                c.trade_name,
                c.email
            FROM licenses l
            INNER JOIN clients c ON c.id = l.client_id
            WHERE l.expires_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
              AND l.status NOT IN ('suspendida', 'demo')
            ORDER BY l.expires_at ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}

if (!function_exists('find_license_notification_candidates')) {
    function find_license_notification_candidates(PDO $pdo, array $days, ?string $licenseKey = null): array
    {
        $days = array_values(array_unique(array_map('intval', $days)));
        $days = array_values(array_filter($days, static fn (int $day): bool => $day >= 0 && $day <= 60));
        if ($days === []) {
            $days = [15, 7, 3, 1, 0];
        }

        $placeholders = [];
        $params = [];
        foreach ($days as $idx => $day) {
            $key = ':day_' . $idx;
            $placeholders[] = $key;
            $params[$key] = $day;
        }

        $whereLicense = '';
        if ($licenseKey !== null && $licenseKey !== '') {
            $whereLicense = ' AND l.license_key = :license_key';
            $params[':license_key'] = strtoupper(trim($licenseKey));
        }

        $sql = "
            SELECT
                l.id,
                l.client_id,
                l.license_key,
                l.status,
                l.plan_type,
                l.expires_at,
                DATEDIFF(l.expires_at, CURDATE()) AS days_left,
                c.legal_name,
                c.trade_name,
                c.email
            FROM licenses l
            INNER JOIN clients c ON c.id = l.client_id
            WHERE DATEDIFF(l.expires_at, CURDATE()) IN (" . implode(',', $placeholders) . ")
              AND l.status NOT IN ('suspendida', 'demo')
              {$whereLicense}
            ORDER BY l.expires_at ASC, c.legal_name ASC
        ";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }
}

if (!function_exists('find_license_for_test_notification')) {
    function find_license_for_test_notification(PDO $pdo, string $licenseKey): ?array
    {
        $stmt = $pdo->prepare("
            SELECT
                l.id,
                l.client_id,
                l.license_key,
                l.status,
                l.plan_type,
                l.expires_at,
                DATEDIFF(l.expires_at, CURDATE()) AS days_left,
                c.legal_name,
                c.trade_name,
                c.email
            FROM licenses l
            INNER JOIN clients c ON c.id = l.client_id
            WHERE l.license_key = :license_key
            LIMIT 1
        ");
        $stmt->execute(['license_key' => strtoupper(trim($licenseKey))]);
        $license = $stmt->fetch();

        return $license ?: null;
    }
}

if (!function_exists('find_license_for_notification')) {
    function find_license_for_notification(PDO $pdo, int $licenseId): ?array
    {
        $stmt = $pdo->prepare("
            SELECT
                l.id,
                l.client_id,
                l.license_key,
                l.status,
                l.plan_type,
                l.expires_at,
                DATEDIFF(l.expires_at, CURDATE()) AS days_left,
                c.legal_name,
                c.trade_name,
                c.email
            FROM licenses l
            INNER JOIN clients c ON c.id = l.client_id
            WHERE l.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $licenseId]);
        $license = $stmt->fetch();

        return $license ?: null;
    }
}

if (!function_exists('license_notification_type_for')) {
    function license_notification_type_for(array $license, bool $test = false): string
    {
        if ($test) {
            return 'test_email_' . date('Ymd_His');
        }

        $expiresAt = (string) ($license['expires_at'] ?? '');
        $daysLeft = (int) ($license['days_left'] ?? 0);
        if ($daysLeft <= 0) {
            return 'expired_' . $expiresAt;
        }

        return 'expiring_' . $daysLeft . '_' . $expiresAt;
    }
}

if (!function_exists('license_notification_already_sent')) {
    function license_notification_already_sent(PDO $pdo, int $licenseId, string $type): bool
    {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM license_notifications
            WHERE license_id = :license_id
              AND notification_type = :notification_type
              AND status = 'sent'
        ");
        $stmt->execute([
            'license_id' => $licenseId,
            'notification_type' => $type,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }
}

if (!function_exists('license_notification_last_sent_at')) {
    function license_notification_last_sent_at(PDO $pdo, int $licenseId, string $type): ?string
    {
        $stmt = $pdo->prepare("
            SELECT sent_at
            FROM license_notifications
            WHERE license_id = :license_id
              AND notification_type = :notification_type
              AND status = 'sent'
            ORDER BY sent_at DESC, id DESC
            LIMIT 1
        ");
        $stmt->execute([
            'license_id' => $licenseId,
            'notification_type' => $type,
        ]);
        $sentAt = $stmt->fetchColumn();

        return is_string($sentAt) && $sentAt !== '' ? $sentAt : null;
    }
}

if (!function_exists('send_license_notification_for_license')) {
    function send_license_notification_for_license(PDO $pdo, int $licenseId, bool $force = false): array
    {
        $license = find_license_for_notification($pdo, $licenseId);
        if (!$license) {
            return ['ok' => false, 'status' => 'missing', 'error' => 'LICENSE_NOT_FOUND'];
        }

        $toEmail = trim((string) ($license['email'] ?? ''));
        $type = license_notification_type_for($license, false);
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            log_license_notification($pdo, $licenseId, (int) $license['client_id'], $type, $toEmail ?: null, 'skipped', 'INVALID_RECIPIENT');
            return ['ok' => false, 'status' => 'skipped', 'error' => 'INVALID_RECIPIENT', 'type' => $type];
        }

        if (!$force && license_notification_already_sent($pdo, $licenseId, $type)) {
            return ['ok' => true, 'status' => 'duplicate', 'error' => null, 'type' => $type];
        }

        $message = build_license_notification_message($license, false);
        $result = admin_send_license_mail($toEmail, $message['subject'], $message['body'], $message['html'] ?? null);
        if ($result['ok'] ?? false) {
            log_license_notification($pdo, $licenseId, (int) $license['client_id'], $type, $toEmail, 'sent', null);
            return ['ok' => true, 'status' => 'sent', 'error' => null, 'type' => $type];
        }

        $error = (string) ($result['error'] ?? 'UNKNOWN_ERROR');
        log_license_notification($pdo, $licenseId, (int) $license['client_id'], $type, $toEmail, 'failed', $error);

        return ['ok' => false, 'status' => 'failed', 'error' => $error, 'type' => $type];
    }
}

if (!function_exists('build_license_notification_message')) {
    function build_license_notification_message(array $license, bool $test = false): array
    {
        $clientName = trim((string) ($license['trade_name'] ?: $license['legal_name']));
        $licenseKey = (string) $license['license_key'];
        $expiresAt = format_date((string) $license['expires_at']);
        $daysLeft = (int) ($license['days_left'] ?? 0);
        $mail = admin_config('mail', []);
        $baseUrl = rtrim((string) ($mail['public_base_url'] ?? 'https://flus.com.ar'), '/');
        $logoUrl = $baseUrl . '/assets/img/flus-mark.png';

        if ($test) {
            $subject = 'Prueba de avisos de licencia FLUS';
            $summary = 'Correo de prueba del panel FLUS Admin';
            $body = "Hola {$clientName},\n\nEste es un correo de prueba del panel FLUS Admin para confirmar que los avisos de licencias salen correctamente.\n\nLicencia: {$licenseKey}\nVencimiento registrado: {$expiresAt}\n\nNo hace falta que realices ninguna acción por este mensaje.\n\nFLUS Soporte";

            return [
                'subject' => $subject,
                'body' => $body,
                'html' => build_license_notification_html($summary, $clientName, $licenseKey, $expiresAt, 'Prueba', 'No hace falta que realices ninguna acción por este mensaje.', $logoUrl),
            ];
        }

        if ($daysLeft <= 0) {
            $subject = 'Licencia FLUS vencida';
            $summary = 'Tu licencia FLUS esta vencida';
            $body = "Hola {$clientName},\n\nTe avisamos que la licencia FLUS {$licenseKey} está vencida desde el {$expiresAt}.\n\nPara reactivar el servicio o informar un pago, respondé este correo.\n\nFLUS Soporte";

            return [
                'subject' => $subject,
                'body' => $body,
                'html' => build_license_notification_html($summary, $clientName, $licenseKey, $expiresAt, 'Vencida', 'Para reactivar el servicio o informar un pago, respondé este correo.', $logoUrl),
            ];
        }

        $subject = 'Tu licencia FLUS vence el ' . $expiresAt;
        $summary = 'Tu licencia FLUS vence pronto';
        $body = "Hola {$clientName},\n\nTe avisamos que la licencia FLUS {$licenseKey} vence el {$expiresAt}.\n\nDías restantes: {$daysLeft}\n\nPara renovar la licencia o informar un pago, respondé este correo.\n\nFLUS Soporte";

        return [
            'subject' => $subject,
            'body' => $body,
            'html' => build_license_notification_html($summary, $clientName, $licenseKey, $expiresAt, $daysLeft . ' días restantes', 'Para renovar la licencia o informar un pago, respondé este correo.', $logoUrl),
        ];
    }
}

if (!function_exists('build_license_notification_html')) {
    function build_license_notification_html(
        string $summary,
        string $clientName,
        string $licenseKey,
        string $expiresAt,
        string $status,
        string $actionText,
        string $logoUrl
    ): string {
        $summaryHtml = admin_notification_html($summary);
        $clientHtml = admin_notification_html($clientName);
        $licenseHtml = admin_notification_html($licenseKey);
        $expiresHtml = admin_notification_html($expiresAt);
        $statusHtml = admin_notification_html($status);
        $actionHtml = admin_notification_html($actionText);
        $logoHtml = admin_notification_html($logoUrl);

        return '<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>' . $summaryHtml . '</title>
</head>
<body style="margin:0;padding:0;background:#eef3f7;color:#132032;font-family:Arial,Helvetica,sans-serif;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef3f7;padding:28px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#fbfdff;border:1px solid #d8e3ec;border-radius:14px;overflow:hidden;">
          <tr>
            <td style="padding:28px 30px 18px 30px;background:#132032;">
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                <tr>
                  <td style="vertical-align:middle;">
                    <img src="' . $logoHtml . '" width="42" height="47" alt="FLUS" style="display:inline-block;vertical-align:middle;border:0;margin-right:12px;">
                    <span style="display:inline-block;vertical-align:middle;color:#fbfdff;font-size:24px;font-weight:800;letter-spacing:.4px;">FLUS</span>
                  </td>
                  <td align="right" style="color:#7de2ca;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;">Licencias</td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td style="padding:30px;">
              <p style="margin:0 0 10px 0;color:#63758a;font-size:13px;text-transform:uppercase;letter-spacing:.12em;font-weight:700;">' . $summaryHtml . '</p>
              <h1 style="margin:0 0 16px 0;color:#132032;font-size:26px;line-height:1.2;">Hola ' . $clientHtml . '</h1>
              <p style="margin:0 0 22px 0;color:#405266;font-size:16px;line-height:1.55;">' . $actionHtml . '</p>

              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #d8e3ec;border-radius:12px;background:#f5f9fc;margin:0 0 24px 0;">
                <tr>
                  <td style="padding:16px 18px;border-bottom:1px solid #d8e3ec;color:#63758a;font-size:12px;text-transform:uppercase;letter-spacing:.1em;font-weight:700;">Licencia</td>
                  <td align="right" style="padding:16px 18px;border-bottom:1px solid #d8e3ec;color:#132032;font-size:15px;font-weight:800;">' . $licenseHtml . '</td>
                </tr>
                <tr>
                  <td style="padding:16px 18px;border-bottom:1px solid #d8e3ec;color:#63758a;font-size:12px;text-transform:uppercase;letter-spacing:.1em;font-weight:700;">Vencimiento</td>
                  <td align="right" style="padding:16px 18px;border-bottom:1px solid #d8e3ec;color:#132032;font-size:15px;font-weight:800;">' . $expiresHtml . '</td>
                </tr>
                <tr>
                  <td style="padding:16px 18px;color:#63758a;font-size:12px;text-transform:uppercase;letter-spacing:.1em;font-weight:700;">Estado</td>
                  <td align="right" style="padding:16px 18px;color:#0d8f72;font-size:15px;font-weight:800;">' . $statusHtml . '</td>
                </tr>
              </table>

              <p style="margin:0;color:#405266;font-size:15px;line-height:1.55;">Podés responder este correo para coordinar la renovación, informar un pago o consultar el estado de la licencia.</p>
            </td>
          </tr>
          <tr>
            <td style="padding:18px 30px;background:#f5f9fc;border-top:1px solid #d8e3ec;color:#63758a;font-size:12px;line-height:1.5;">
              FLUS Soporte<br>
              Este mensaje fue generado desde el panel administrativo de FLUS.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
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
