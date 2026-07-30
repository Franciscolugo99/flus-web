<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$failures = [];

require_once $root . '/admin/includes/client-portal.php';

$portal = (string) file_get_contents($root . '/portal/index.php');
$scriptPath = $root . '/portal/assets/js/price-change-demo.js';
$script = is_file($scriptPath) ? (string) file_get_contents($scriptPath) : '';
$endpoint = (string) file_get_contents($root . '/portal/price-command.php');
$cloudLibrary = (string) file_get_contents($root . '/admin/includes/cloud-sync.php');
$pollEndpoint = (string) file_get_contents($root . '/admin/api/command-poll.php');
$ackEndpoint = (string) file_get_contents($root . '/admin/api/command-ack.php');

if ($script === '') {
    $failures[] = 'Falta el script de cambio de precio movil.';
}
if (!portal_role_can('change_price', 'owner') || !portal_role_can('change_price', 'manager')) {
    $failures[] = 'Dueno o encargado no pueden enviar cambios de precio.';
}
if (portal_role_can('change_price', 'viewer')) {
    $failures[] = 'Un usuario de consulta puede enviar cambios de precio.';
}

$portalContracts = [
    '$canPreviewPriceChange' => 'La vista no aplica el permiso financiero.',
    'data-price-change-open' => 'Los productos no ofrecen cambiar precio.',
    'data-price-change-sheet' => 'Falta la hoja movil de precio.',
    'data-price-change-percent="-5"' => 'Falta el acceso rapido de reduccion.',
    'data-price-change-percent="15"' => 'Falta el acceso rapido de aumento.',
    'data-price-command-enabled' => 'La interfaz no distingue el cambio real de la demostracion.',
    'La orden se enviara solamente a esta sucursal' => 'La interfaz no aclara el alcance por sucursal.',
    "portal_url('assets/js/price-change-demo.js" => 'La vista no carga el calculo local de precio.',
];

foreach ($portalContracts as $needle => $message) {
    if (strpos($portal, $needle) === false) {
        $failures[] = $message;
    }
}

$scriptContracts = [
    'event.preventDefault()' => 'El formulario de precio puede enviarse accidentalmente.',
    'nextPrice - currentPrice' => 'No se calcula la variacion de precio.',
    'No se modifico el precio real.' => 'La simulacion no confirma que no hubo escritura.',
    "action: 'create'" => 'El formulario no crea una orden remota.',
    "action: 'status'" => 'El formulario no consulta el estado de la orden.',
    'request_uid: requestUid.value' => 'El envio no conserva una identidad idempotente.',
    "submitInFlight || activeCommandUid !== ''" => 'La interfaz no bloquea doble envio mientras la orden esta activa.',
    "credentials: 'same-origin'" => 'El envio no limita las credenciales al portal.',
    "event.key === 'Escape'" => 'La hoja de precio no puede cerrarse con teclado.',
];

foreach ($scriptContracts as $needle => $message) {
    if (strpos($script, $needle) === false) {
        $failures[] = $message;
    }
}

foreach (['XMLHttpRequest', 'localStorage', 'sessionStorage', 'FormData'] as $forbidden) {
    if (strpos($script, $forbidden) !== false) {
        $failures[] = 'La demostracion de precio intenta enviar o persistir datos: ' . $forbidden;
    }
}

$endpointContracts = [
    "portal_role_can('change_price')" => 'El endpoint no exige permiso de cambio de precio.',
    'hash_equals(csrf_token(), $csrf)' => 'El endpoint no valida CSRF.',
    'portal_current_branch_scope()' => 'El endpoint no limita la sucursal.',
    'admin_cloud_command_create_price(' => 'El endpoint no delega la creacion segura.',
];
foreach ($endpointContracts as $needle => $message) {
    if (strpos($endpoint, $needle) === false) {
        $failures[] = $message;
    }
}

$cloudContracts = [
    'CREATE TABLE IF NOT EXISTS cloud_commands' => 'Falta la cola remota de comandos.',
    'UNIQUE KEY uq_cloud_commands_portal_request' => 'La cola no protege doble envio del portal.',
    'FOR UPDATE' => 'La creacion de orden no bloquea el stock sincronizado.',
    "'expected_price' => number_format(\$currentPrice" => 'La orden no toma el precio esperado desde Wiroos.',
];
foreach ($cloudContracts as $needle => $message) {
    if (strpos($cloudLibrary, $needle) === false) {
        $failures[] = $message;
    }
}
if (strpos($pollEndpoint, "status = 'processing'") === false || strpos($pollEndpoint, 'claim_token_hash') === false) {
    $failures[] = 'El poll no entrega comandos con lease seguro.';
}
if (strpos($ackEndpoint, 'hash_equals($claimHash') === false || strpos($ackEndpoint, 'acknowledged_uids') === false) {
    $failures[] = 'La confirmacion no valida el claim idempotente.';
}

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, '[FAIL] ' . $failure . PHP_EOL);
    }
    exit(1);
}

fwrite(STDOUT, "OK: cambio de precio remoto con permisos, CSRF e idempotencia.\n");
