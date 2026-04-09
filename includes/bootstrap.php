<?php
$site = [
    'name' => 'FLUS',
    'tagline' => 'Sistema de gestión comercial',
    'domain' => 'flus.com.ar',
    'contact_email' => 'info@flus.com.ar',
    'contact_phone' => '+54 261-273-1742',
    'whatsapp_number' => '+54 261-273-1742',
];

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
