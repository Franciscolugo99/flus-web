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
    'turnstile_site_key' => '',
    'turnstile_secret_key' => '',
    // SEO / medición: completar en includes/config.local.php cuando tengas los IDs.
    'google_site_verification' => '',
    'ga4_measurement_id' => '',
    'contact_phone' => '+54 261-273-1742',
    'whatsapp_number' => '+54 261-273-1742',
    'demo_url' => 'https://app.flus.com.ar/',
    'commercial' => [
        'web' => [
            'name' => 'FLUS Web',
            'setup_price' => '$120.000',
            'monthly_price' => '$45.000',
            'currency' => 'ARS',
            'base_scope' => '1 local + 1 caja',
            'extra_cash_price' => '$7.500 / mes',
        ],
        'server' => [
            'name' => 'FLUS Server',
            'pricing_label' => 'Cotización personalizada',
            'currency' => 'ARS',
        ],
    ],
];

$localConfigPath = __DIR__ . '/config.local.php';
if (is_file($localConfigPath)) {
    $localConfig = require $localConfigPath;
    if (is_array($localConfig)) {
        $site = array_replace($site, $localConfig);
    }
}

// FLUS se publica en la raíz de flus.com.ar. Si alguna instalación futura usa
// un subdirectorio, puede definir 'base_path' en config.local.php.
$basePath = trim((string)($site['base_path'] ?? ''));
if ($basePath === '/' || $basePath === '.') {
    $basePath = '';
}

// En el entorno local de XAMPP el proyecto suele abrirse como /flus-web/.
// La instalación publicada sigue viviendo en la raíz del dominio, por eso
// esta detección queda limitada a hosts locales y no cambia las URLs públicas.
if ($basePath === '') {
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $isLocalHost = $host === 'localhost' || str_starts_with($host, 'localhost:')
        || $host === '127.0.0.1' || str_starts_with($host, '127.0.0.1:')
        || $host === '::1' || str_starts_with($host, '[::1]:');
    if ($isLocalHost && (str_starts_with($script, '/flus-web/') || $script === '/flus-web')) {
        $basePath = '/flus-web';
    }
}

if ($basePath !== '') {
    $basePath = '/' . trim($basePath, '/');
}
$site['base_path'] = $basePath;

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function text_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
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

function current_script_path(): string
{
    $path = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? ''));
    return ltrim($path, '/');
}

function is_active_page(string $file): bool
{
    global $site;

    $file = ltrim(str_replace('\\', '/', $file), '/');
    $current = current_script_path();
    $basePath = trim((string)($site['base_path'] ?? ''), '/');
    if ($basePath !== '' && ($current === $basePath || str_starts_with($current, $basePath . '/'))) {
        $current = ltrim(substr($current, strlen($basePath)), '/');
    }

    // Para archivos de raíz (index.php, contacto.php, etc.) evitamos que un
    // /recursos/index.php sea confundido con la home.
    if (strpos($file, '/') === false) {
        return $current === $file;
    }

    return $current === $file;
}

function is_active_section(string $prefix): bool
{
    global $site;

    $prefix = trim(str_replace('\\', '/', $prefix), '/');
    $current = current_script_path();
    $basePath = trim((string)($site['base_path'] ?? ''), '/');
    if ($basePath !== '' && ($current === $basePath || str_starts_with($current, $basePath . '/'))) {
        $current = ltrim(substr($current, strlen($basePath)), '/');
    }
    return $prefix !== '' && ($current === $prefix || str_starts_with($current, $prefix . '/'));
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

function turnstile_enabled(): bool
{
    global $site;

    return trim((string) ($site['turnstile_site_key'] ?? '')) !== ''
        && trim((string) ($site['turnstile_secret_key'] ?? '')) !== '';
}

function turnstile_site_key(): string
{
    global $site;

    return trim((string) ($site['turnstile_site_key'] ?? ''));
}

function turnstile_remote_ip(): ?string
{
    $candidates = [
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null,
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ];

    foreach ($candidates as $candidate) {
        $candidate = trim((string) $candidate);
        if ($candidate === '') {
            continue;
        }

        if (strpos($candidate, ',') !== false) {
            $parts = array_map('trim', explode(',', $candidate));
            $candidate = (string) ($parts[0] ?? '');
        }

        if ($candidate !== '') {
            return $candidate;
        }
    }

    return null;
}

function turnstile_validate_token(string $token): array
{
    global $site;

    $secret = trim((string) ($site['turnstile_secret_key'] ?? ''));
    $token = trim($token);

    if ($secret === '' || $token === '') {
        return [
            'success' => false,
            'error-codes' => ['missing-input'],
        ];
    }

    $payload = [
        'secret' => $secret,
        'response' => $token,
    ];

    $remoteIp = turnstile_remote_ip();
    if ($remoteIp !== null) {
        $payload['remoteip'] = $remoteIp;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($payload),
            'timeout' => 10,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents(
        'https://challenges.cloudflare.com/turnstile/v0/siteverify',
        false,
        $context
    );

    if (!is_string($response) || trim($response) === '') {
        return [
            'success' => false,
            'error-codes' => ['internal-error'],
        ];
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        return [
            'success' => false,
            'error-codes' => ['invalid-json'],
        ];
    }

    return $decoded;
}
