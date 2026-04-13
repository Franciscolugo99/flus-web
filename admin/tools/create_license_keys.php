<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo se puede ejecutar por CLI.');
}

if (!function_exists('openssl_pkey_new')) {
    echo "OpenSSL no está disponible en este PHP.\n";
    exit(1);
}

$config = admin_config('license', []);
$privateKeyPath = (string) ($config['private_key_path'] ?? '');
$publicKeyPath = (string) ($config['public_key_path'] ?? '');
$passphrase = (string) ($config['private_key_passphrase'] ?? '');

if ($privateKeyPath === '' || $publicKeyPath === '') {
    echo "Faltan las rutas de claves en admin/config/config.php.\n";
    exit(1);
}

if (file_exists($privateKeyPath) || file_exists($publicKeyPath)) {
    echo "Ya existen claves de licencia. No se sobreescribieron archivos.\n";
    echo "Privada: {$privateKeyPath}\n";
    echo "Pública: {$publicKeyPath}\n";
    exit(1);
}

function flus_admin_openssl_config_path(): ?string
{
    $candidates = array_filter([
        getenv('OPENSSL_CONF') ?: null,
        dirname(PHP_BINARY) . '/extras/openssl/openssl.cnf',
        dirname(PHP_BINARY) . '/extras/ssl/openssl.cnf',
        'C:/xampp/apache/conf/openssl.cnf',
        'C:/xampp/php/extras/openssl/openssl.cnf',
        'C:/xampp/php/extras/ssl/openssl.cnf',
    ]);

    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return null;
}

$keyConfig = [
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
];

$opensslConfigPath = flus_admin_openssl_config_path();
if ($opensslConfigPath !== null) {
    $keyConfig['config'] = $opensslConfigPath;
}

$key = openssl_pkey_new($keyConfig);

if ($key === false) {
    echo "No se pudo crear el par de claves RSA.\n";
    while ($message = openssl_error_string()) {
        echo "OpenSSL: {$message}\n";
    }
    exit(1);
}

$privateKey = '';
if (!openssl_pkey_export($key, $privateKey, $passphrase !== '' ? $passphrase : null, $keyConfig)) {
    echo "No se pudo exportar la clave privada.\n";
    while ($message = openssl_error_string()) {
        echo "OpenSSL: {$message}\n";
    }
    exit(1);
}

$details = openssl_pkey_get_details($key);
if (!$details || empty($details['key'])) {
    echo "No se pudo obtener la clave pública.\n";
    exit(1);
}

if (file_put_contents($privateKeyPath, $privateKey) === false) {
    echo "No se pudo escribir la clave privada.\n";
    exit(1);
}

if (file_put_contents($publicKeyPath, $details['key']) === false) {
    echo "No se pudo escribir la clave pública.\n";
    exit(1);
}

@chmod($privateKeyPath, 0600);
@chmod($publicKeyPath, 0644);

echo "Claves de licencia creadas correctamente.\n";
echo "Privada: {$privateKeyPath}\n";
echo "Pública: {$publicKeyPath}\n";
