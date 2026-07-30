<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$failures = [];
$requiredFiles = [
    'portal/assets/js/stock-scanner.js',
    'portal/assets/vendor/zxing/zxing-browser.min.js',
    'portal/assets/vendor/zxing/LICENSE',
];

foreach ($requiredFiles as $relativePath) {
    if (!is_file($root . '/' . $relativePath) || filesize($root . '/' . $relativePath) === 0) {
        $failures[] = 'Falta o esta vacio ' . $relativePath;
    }
}

$portal = (string) file_get_contents($root . '/portal/index.php');
$scanner = is_file($root . '/portal/assets/js/stock-scanner.js')
    ? (string) file_get_contents($root . '/portal/assets/js/stock-scanner.js')
    : '';
$rootHtaccess = (string) file_get_contents($root . '/.htaccess');

if (strpos($rootHtaccess, 'camera=(self)') === false) {
    $failures[] = 'La politica HTTP no permite usar la camara desde el portal.';
}
if (strpos($rootHtaccess, 'microphone=()') === false || strpos($rootHtaccess, 'geolocation=()') === false) {
    $failures[] = 'La politica HTTP dejo de bloquear sensores no requeridos.';
}

$portalContracts = [
    'id="portalStockForm"' => 'El formulario de stock no tiene identificador estable.',
    'data-stock-scanner' => 'La vista no inicializa el lector.',
    'data-stock-scanner-panel' => 'Falta el panel de camara.',
    'data-stock-scanner-video' => 'Falta el video de camara.',
    "portal_url('assets/js/stock-scanner.js" => 'La vista no carga el script del lector.',
    'name="sucursal"' => 'La busqueda perdio el alcance de sucursal.',
    'id="portalStockState"' => 'El estado de stock no se preserva.',
    'portal-stock-detail-disclosure' => 'La ficha de producto no esta disponible.',
];

foreach ($portalContracts as $needle => $message) {
    if (strpos($portal, $needle) === false) {
        $failures[] = $message;
    }
}

$scannerContracts = [
    'navigator.mediaDevices.getUserMedia' => 'No se solicita la camara con la API del navegador.',
    'window.BarcodeDetector' => 'No se intenta el lector nativo.',
    'BrowserMultiFormatReader' => 'No existe alternativa ZXing.',
    "stockState.value = 'all'" => 'El codigo leido puede quedar oculto por el filtro actual.',
    'form.requestSubmit()' => 'El codigo leido no reutiliza la busqueda existente.',
    'stream.getTracks().forEach' => 'La camara no libera sus pistas al cerrar.',
    "window.addEventListener('pagehide', stopMedia)" => 'La camara no se detiene al abandonar la vista.',
];

foreach ($scannerContracts as $needle => $message) {
    if (strpos($scanner, $needle) === false) {
        $failures[] = $message;
    }
}

foreach (['fetch(', 'XMLHttpRequest', 'localStorage', 'sessionStorage'] as $forbidden) {
    if (strpos($scanner, $forbidden) !== false) {
        $failures[] = 'El lector incorpora persistencia o una API paralela no autorizada: ' . $forbidden;
    }
}

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, '[FAIL] ' . $failure . PHP_EOL);
    }
    exit(1);
}

fwrite(STDOUT, "OK: lector movil de stock reutiliza la consulta segura y no modifica datos.\n");
