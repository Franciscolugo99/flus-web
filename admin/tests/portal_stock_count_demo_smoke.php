<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$failures = [];

require_once $root . '/admin/includes/client-portal.php';

$portalPath = $root . '/portal/index.php';
$scriptPath = $root . '/portal/assets/js/stock-count-demo.js';
$portal = is_file($portalPath) ? (string) file_get_contents($portalPath) : '';
$script = is_file($scriptPath) ? (string) file_get_contents($scriptPath) : '';

if ($portal === '') {
    $failures[] = 'No se pudo leer portal/index.php.';
}
if ($script === '') {
    $failures[] = 'Falta el script del conteo movil.';
}

if (!portal_role_can('preview_stock_count', 'owner') || !portal_role_can('preview_stock_count', 'manager')) {
    $failures[] = 'Dueno o encargado no pueden abrir la demostracion.';
}
if (portal_role_can('preview_stock_count', 'viewer')) {
    $failures[] = 'Un usuario de consulta puede abrir la demostracion de ajuste.';
}

$portalContracts = [
    '$canPreviewStockCount' => 'La vista no aplica el permiso del conteo.',
    'data-stock-count-open' => 'Los productos no ofrecen iniciar el conteo.',
    'data-stock-count-sheet' => 'Falta la hoja movil de conteo.',
    'data-stock-count-form' => 'Falta el formulario local de simulacion.',
    'todavia no modifica FLUS' => 'La interfaz no informa que es una demostracion.',
    "portal_url('assets/js/stock-count-demo.js" => 'La vista no carga el calculo local.',
];

foreach ($portalContracts as $needle => $message) {
    if (strpos($portal, $needle) === false) {
        $failures[] = $message;
    }
}

$scriptContracts = [
    'event.preventDefault()' => 'El formulario puede enviarse accidentalmente.',
    'counted - currentStock' => 'No se calcula la diferencia contra el stock informado.',
    'No se modifico el stock real.' => 'La simulacion no confirma que no hubo escritura.',
    "event.key === 'Escape'" => 'La hoja movil no puede cerrarse con teclado.',
];

foreach ($scriptContracts as $needle => $message) {
    if (strpos($script, $needle) === false) {
        $failures[] = $message;
    }
}

foreach (['fetch(', 'XMLHttpRequest', 'localStorage', 'sessionStorage', 'FormData'] as $forbidden) {
    if (strpos($script, $forbidden) !== false) {
        $failures[] = 'La demostracion intenta enviar o persistir datos: ' . $forbidden;
    }
}

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, '[FAIL] ' . $failure . PHP_EOL);
    }
    exit(1);
}

fwrite(STDOUT, "OK: conteo movil simulado, restringido y sin escrituras.\n");
