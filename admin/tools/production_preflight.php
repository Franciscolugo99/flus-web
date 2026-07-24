<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

$errors = 0;
$warnings = 0;

function preflight_line(string $status, string $label, string $detail = ''): void
{
    $suffix = $detail !== '' ? ' - ' . $detail : '';
    echo '[' . $status . '] ' . $label . $suffix . PHP_EOL;
}

function preflight_ok(string $label, string $detail = ''): void
{
    preflight_line('OK', $label, $detail);
}

function preflight_warn(string $label, string $detail = ''): void
{
    global $warnings;
    $warnings++;
    preflight_line('WARN', $label, $detail);
}

function preflight_fail(string $label, string $detail = ''): void
{
    global $errors;
    $errors++;
    preflight_line('FAIL', $label, $detail);
}

function preflight_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('
        SELECT COUNT(*)
        FROM information_schema.TABLES
        WHERE table_schema = DATABASE()
          AND table_name = :table
    ');
    $stmt->execute(['table' => $table]);

    return (int) $stmt->fetchColumn() > 0;
}

echo '=== FLUS Web production preflight ===' . PHP_EOL;
echo 'Este chequeo es solo lectura: no crea tablas, no modifica licencias y no muestra secretos.' . PHP_EOL . PHP_EOL;

$config = admin_config();
$env = (string) ($config['env'] ?? '');
if ($env === 'production') {
    preflight_ok('Entorno', 'production');
} else {
    preflight_warn('Entorno', 'FLUS_ADMIN_ENV/config env=' . ($env !== '' ? $env : 'vacio'));
}

$localConfigPath = __DIR__ . '/../config/config.local.php';
if (is_file($localConfigPath)) {
    preflight_ok('Config local', 'admin/config/config.local.php existe');
} else {
    preflight_fail('Config local', 'falta admin/config/config.local.php en el servidor');
}

$db = (array) ($config['db'] ?? []);
foreach (['host', 'name', 'user'] as $key) {
    $value = trim((string) ($db[$key] ?? ''));
    if ($value === '' || in_array($value, ['DB_HOST', 'DB_NAME', 'DB_USER'], true)) {
        preflight_fail('DB ' . $key, 'valor no configurado');
    } else {
        preflight_ok('DB ' . $key, 'configurado');
    }
}

$license = (array) ($config['license'] ?? []);
$privateKey = (string) ($license['private_key_path'] ?? '');
$publicKey = (string) ($license['public_key_path'] ?? '');
if ($privateKey !== '' && is_file($privateKey) && is_readable($privateKey)) {
    preflight_ok('Clave privada', 'existe y es legible para firmar licencias');
} else {
    preflight_fail('Clave privada', 'no existe o no es legible');
}
if ($publicKey !== '' && is_file($publicKey) && is_readable($publicKey)) {
    preflight_ok('Clave publica', 'existe y es legible');
} else {
    preflight_fail('Clave publica', 'no existe o no es legible');
}

$cloudToken = trim((string) ($license['cloud_api_token'] ?? ''));
if ($cloudToken !== '') {
    preflight_ok('Token cloud', 'configurado');
} else {
    preflight_warn('Token cloud', 'sin token compartido, la API cloud deberia rechazar sync si lo exige');
}

$security = (array) ($config['security'] ?? []);
if (trim((string) ($security['rate_limit_salt'] ?? '')) !== '') {
    preflight_ok('Rate limit salt', 'configurado');
} else {
    preflight_warn('Rate limit salt', 'conviene definir uno unico en produccion');
}

try {
    $pdo = admin_db();
    preflight_ok('Conexion DB', 'OK');

    $requiredTables = [
        'admin_users',
        'clients',
        'licenses',
        'payments',
        'license_notifications',
        'license_events',
        'client_portal_users',
        'client_portal_memberships',
        'client_branches',
        'client_installations',
        'cloud_sync_events',
        'cloud_sync_stock_items',
        'downloads',
        'web_events',
    ];

    foreach ($requiredTables as $table) {
        if (preflight_table_exists($pdo, $table)) {
            preflight_ok('Tabla ' . $table);
        } else {
            preflight_fail('Tabla ' . $table, 'no encontrada');
        }
    }

    if (preflight_table_exists($pdo, 'licenses')) {
        $totalLicenses = (int) $pdo->query('SELECT COUNT(*) FROM licenses')->fetchColumn();
        preflight_ok('Licencias', (string) $totalLicenses . ' registradas');

        $statusRows = $pdo->query('SELECT status, COUNT(*) AS total FROM licenses GROUP BY status ORDER BY status')->fetchAll();
        foreach ($statusRows as $row) {
            preflight_line('INFO', 'Licencias ' . (string) $row['status'], (string) $row['total']);
        }
    }

    if (preflight_table_exists($pdo, 'clients')) {
        $totalClients = (int) $pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn();
        preflight_ok('Clientes', (string) $totalClients . ' registrados');
    }

    if (preflight_table_exists($pdo, 'client_installations')) {
        $installations = (int) $pdo->query('SELECT COUNT(*) FROM client_installations')->fetchColumn();
        preflight_ok('Instalaciones cloud', (string) $installations . ' registradas');
    }
} catch (Throwable $e) {
    preflight_fail('Conexion DB', admin_public_error($e, 'No se pudo conectar a la base.'));
}

echo PHP_EOL;
echo 'Resultado: ' . $errors . ' error(es), ' . $warnings . ' advertencia(s).' . PHP_EOL;
exit($errors > 0 ? 1 : 0);

