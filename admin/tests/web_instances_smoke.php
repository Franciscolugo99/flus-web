<?php
declare(strict_types=1);

$testKey = base64_encode(random_bytes(32));
putenv('FLUS_WEB_INSTANCE_ENCRYPTION_KEY=' . $testKey);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/web-instances.php';

$failures = [];
$secret = 'Clave-de-prueba-2026!';

try {
    $encrypted = admin_web_instance_encrypt_secret($secret);
    if (!str_starts_with($encrypted, 'v1:') && !str_starts_with($encrypted, 'v2:')) {
        $failures[] = 'La credencial cifrada no incluye version de formato.';
    }
    if (str_contains($encrypted, $secret)) {
        $failures[] = 'El texto cifrado contiene la credencial original.';
    }
    if (admin_web_instance_decrypt_secret($encrypted) !== $secret) {
        $failures[] = 'El cifrado no completa correctamente el round-trip.';
    }
} catch (Throwable $e) {
    $failures[] = 'El cifrado fallo: ' . $e->getMessage();
}

$valid = admin_web_instance_validate([
    'client_id' => 1,
    'license_id' => 2,
    'slug' => 'kiosco-centro',
    'primary_domain' => 'kiosco-centro.app.flus.com.ar',
    'status' => 'preparando',
    'provisioning_status' => 'credenciales',
    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_name' => 'flus_kiosco_centro',
    'db_user' => 'flus_kiosco',
]);
if ($valid['errors'] !== []) {
    $failures[] = 'Una instancia valida fue rechazada: ' . implode(' ', $valid['errors']);
}

$invalid = admin_web_instance_validate([
    'client_id' => 0,
    'slug' => '../cliente',
    'primary_domain' => 'https://cliente.test/ruta',
    'status' => 'desconocido',
    'provisioning_status' => 'desconocido',
    'db_host' => '',
    'db_port' => 70000,
    'db_name' => 'base con espacios',
    'db_user' => '',
]);
if (count($invalid['errors']) < 7) {
    $failures[] = 'La validacion no rechazo suficientes datos inseguros.';
}

$schema = (string) file_get_contents(__DIR__ . '/../database/web_instances.sql');
foreach (['CREATE TABLE IF NOT EXISTS web_instances', 'CREATE TABLE IF NOT EXISTS web_instance_events', 'UNIQUE KEY uq_web_instances_domain', 'db_password_encrypted'] as $required) {
    if (!str_contains($schema, $required)) {
        $failures[] = 'La migracion no contiene: ' . $required;
    }
}

$editor = (string) file_get_contents(__DIR__ . '/../web-instance-edit.php');
if (!str_contains($editor, 'verify_csrf();')) {
    $failures[] = 'El formulario no valida CSRF.';
}
if (str_contains($editor, 'admin_web_instance_decrypt_secret(')) {
    $failures[] = 'El formulario no debe descifrar ni mostrar credenciales guardadas.';
}

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, '[FAIL] ' . $failure . PHP_EOL);
    }
    exit(1);
}

fwrite(STDOUT, "OK: instancias web, validacion y cifrado.\n");
