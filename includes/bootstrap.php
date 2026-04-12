<?php
$site = [
    'name' => 'FLUS',
    'tagline' => 'Sistema de gestión comercial',
    'domain' => 'flus.com.ar',
    'contact_email' => 'info@flus.com.ar',
    'mail_to' => 'info@flus.com.ar',
    'mail_from' => 'info@flus.com.ar',
    'mail_host' => 'mail.flus.com.ar',
    'mail_port' => 465,
    'mail_secure' => 'ssl',
    'mail_username' => 'info@flus.com.ar',
    'mail_password' => '',
    'mail_timeout' => 15,
    'contact_phone' => '+54 261-273-1742',
    'whatsapp_number' => '+54 261-273-1742',
];

$localConfigPath = __DIR__ . '/config.local.php';
if (is_file($localConfigPath)) {
    $localConfig = require $localConfigPath;
    if (is_array($localConfig)) {
        $site = array_replace($site, $localConfig);
    }
}

$basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath = rtrim($basePath, '/');
if ($basePath === '/' || $basePath === '.') {
    $basePath = '';
}
$site['base_path'] = $basePath;

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function site_url(string $path = ''): string
{
    global $site;

    $path = ltrim($path, '/');
    if ($path === '') {
        return ($site['base_path'] !== '' ? $site['base_path'] : '') . '/';
    }

    return ($site['base_path'] !== '' ? $site['base_path'] : '') . '/' . $path;
}

function page_url(string $path = ''): string
{
    global $site;

    $path = ltrim($path, '/');
    if ($path === '') {
        return 'https://' . $site['domain'] . '/';
    }

    return 'https://' . $site['domain'] . '/' . $path;
}

function asset_url(string $path): string
{
    return site_url('assets/' . ltrim($path, '/'));
}

function is_active_page(string $file): bool
{
    $currentPage = basename($_SERVER['PHP_SELF'] ?? '');
    return $currentPage === $file;
}

function phone_href(string $phone): string
{
    return preg_replace('/\D+/', '', $phone);
}

function has_contact_info(): bool
{
    global $site;
    return $site['contact_email'] !== '' || $site['contact_phone'] !== '' || $site['whatsapp_number'] !== '';
}

function whatsapp_url(string $message = ''): string
{
    global $site;

    $number = preg_replace('/\D+/', '', $site['whatsapp_number'] ?? '');
    if ($number === '') {
        return '#';
    }

    $url = 'https://wa.me/' . $number;
    if ($message !== '') {
        $url .= '?text=' . rawurlencode($message);
    }

    return $url;
}

function breadcrumb_schema(array $items): array
{
    $list = [];
    $position = 1;

    foreach ($items as $item) {
        if (!isset($item['name'], $item['url'])) {
            continue;
        }

        $list[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => $item['name'],
            'item' => $item['url'],
        ];
    }

    return [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $list,
    ];
}

function smtp_encode_header(string $value): string
{
    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

function smtp_normalize_line(string $value): string
{
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    return str_replace("\n", "\r\n", $value);
}

function smtp_expect($socket, array $codes): bool
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

function smtp_write($socket, string $command): bool
{
    return fwrite($socket, $command . "\r\n") !== false;
}

function smtp_send_mail(array $message): bool
{
    global $site;

    $host = trim((string) ($site['mail_host'] ?? ''));
    $port = (int) ($site['mail_port'] ?? 465);
    $secure = strtolower(trim((string) ($site['mail_secure'] ?? 'ssl')));
    $username = trim((string) ($site['mail_username'] ?? ''));
    $password = (string) ($site['mail_password'] ?? '');
    $timeout = (int) ($site['mail_timeout'] ?? 15);

    if ($host === '' || $port <= 0 || $username === '' || $password === '') {
        return false;
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
        return false;
    }

    stream_set_timeout($socket, $timeout);

    $success = smtp_expect($socket, [220]);

    if ($success) {
        $hostname = $_SERVER['SERVER_NAME'] ?? $site['domain'] ?? 'localhost';
        $success = smtp_write($socket, 'EHLO ' . $hostname) && smtp_expect($socket, [250]);
    }

    if ($success && $secure === 'tls') {
        $success = smtp_write($socket, 'STARTTLS') && smtp_expect($socket, [220]);
        if ($success) {
            $success = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        }
        if ($success) {
            $hostname = $_SERVER['SERVER_NAME'] ?? $site['domain'] ?? 'localhost';
            $success = smtp_write($socket, 'EHLO ' . $hostname) && smtp_expect($socket, [250]);
        }
    }

    if ($success) {
        $success = smtp_write($socket, 'AUTH LOGIN') && smtp_expect($socket, [334]);
    }

    if ($success) {
        $success = smtp_write($socket, base64_encode($username)) && smtp_expect($socket, [334]);
    }

    if ($success) {
        $success = smtp_write($socket, base64_encode($password)) && smtp_expect($socket, [235]);
    }

    if ($success) {
        $success = smtp_write($socket, 'MAIL FROM:<' . $message['from_email'] . '>') && smtp_expect($socket, [250]);
    }

    if ($success) {
        $success = smtp_write($socket, 'RCPT TO:<' . $message['to_email'] . '>') && smtp_expect($socket, [250, 251]);
    }

    if ($success) {
        $success = smtp_write($socket, 'DATA') && smtp_expect($socket, [354]);
    }

    if ($success) {
        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . smtp_encode_header($message['from_name']) . ' <' . $message['from_email'] . '>',
            'To: ' . $message['to_email'],
            'Reply-To: ' . smtp_encode_header($message['reply_name']) . ' <' . $message['reply_email'] . '>',
            'Subject: ' . smtp_encode_header($message['subject']),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];

        $body = smtp_normalize_line($message['body']);
        $body = preg_replace('/^\./m', '..', $body) ?? $body;
        $payload = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
        $success = smtp_write($socket, $payload) && smtp_expect($socket, [250]);
    }

    smtp_write($socket, 'QUIT');
    fclose($socket);

    return $success;
}
