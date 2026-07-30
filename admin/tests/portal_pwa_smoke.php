<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$failures = [];
$requiredFiles = [
    'portal/.htaccess',
    'portal/manifest.webmanifest',
    'portal/sw.js',
    'portal/assets/js/pwa.js',
    'portal/assets/icons/flus-192.png',
    'portal/assets/icons/flus-512.png',
    'portal/assets/icons/flus-maskable-512.png',
];

foreach ($requiredFiles as $relativePath) {
    if (!is_file($root . '/' . $relativePath)) {
        $failures[] = 'Falta ' . $relativePath;
    }
}

$manifestPath = $root . '/portal/manifest.webmanifest';
$manifest = is_file($manifestPath) ? json_decode((string) file_get_contents($manifestPath), true) : null;
if (!is_array($manifest)) {
    $failures[] = 'El manifest no contiene JSON valido.';
} else {
    if (($manifest['display'] ?? '') !== 'standalone') {
        $failures[] = 'El manifest no abre el portal en modo aplicacion.';
    }
    if (($manifest['start_url'] ?? '') !== './index.php' || ($manifest['scope'] ?? '') !== './') {
        $failures[] = 'El inicio o alcance del manifest no esta limitado al portal.';
    }
    if (count($manifest['icons'] ?? []) < 3) {
        $failures[] = 'El manifest no declara todos los iconos requeridos.';
    }
}

$serviceWorker = is_file($root . '/portal/sw.js') ? (string) file_get_contents($root . '/portal/sw.js') : '';
if (strpos($serviceWorker, 'respondWith(fetch(event.request))') === false) {
    $failures[] = 'El service worker no mantiene las solicitudes privadas contra la red.';
}
if (strpos($serviceWorker, '.put(') !== false || strpos($serviceWorker, 'cache.add') !== false) {
    $failures[] = 'El service worker intenta guardar contenido privado en cache.';
}

$portalHtaccess = is_file($root . '/portal/.htaccess') ? (string) file_get_contents($root . '/portal/.htaccess') : '';
if (strpos($portalHtaccess, 'application/manifest+json') === false) {
    $failures[] = 'El hosting no tiene declarado el MIME del manifest.';
}

foreach (['portal/login.php', 'portal/index.php'] as $relativePath) {
    $source = (string) file_get_contents($root . '/' . $relativePath);
    if (strpos($source, "portal_url('manifest.webmanifest')") === false) {
        $failures[] = $relativePath . ' no enlaza el manifest.';
    }
    if (strpos($source, "portal_url('assets/js/pwa.js')") === false) {
        $failures[] = $relativePath . ' no carga el instalador PWA.';
    }
}

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, '[FAIL] ' . $failure . PHP_EOL);
    }
    exit(1);
}

fwrite(STDOUT, "OK: portal instalable sin cache de datos privados.\n");
