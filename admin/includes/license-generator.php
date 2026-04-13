<?php
declare(strict_types=1);

if (!function_exists('license_canonical_json')) {
    function license_canonical_json(array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('No se pudo serializar la licencia.');
        }

        return $json;
    }
}

if (!function_exists('license_file_name')) {
    function license_file_name(array $license): string
    {
        $base = strtolower((string) ($license['license_key'] ?? 'flus-license'));
        $base = preg_replace('/[^a-z0-9-]+/', '-', $base) ?: 'flus-license';

        return trim($base, '-') . '.license.json';
    }
}

if (!function_exists('build_license_payload')) {
    function build_license_payload(array $license): array
    {
        $currentStatus = license_current_status((string) $license['status'], $license['expires_at'] ?? null);

        return [
            'plan' => (string) $license['plan_type'],
            'expires_at' => (string) $license['expires_at'],
            'customer' => (string) ($license['trade_name'] ?: $license['legal_name']),
            'license_key' => (string) $license['license_key'],
            'issued_at' => gmdate(DATE_ATOM),
            'starts_at' => (string) $license['starts_at'],
            'status' => $currentStatus,
            'seats' => $license['seats'] === null || $license['seats'] === '' ? null : (int) $license['seats'],
            'client_id' => (int) $license['client_id'],
            'client_legal_name' => (string) $license['legal_name'],
            'client_email' => (string) ($license['email'] ?? ''),
            'client_tax_id' => (string) ($license['tax_id'] ?? ''),
        ];
    }
}

if (!function_exists('sign_license_payload')) {
    function sign_license_payload(string $canonicalPayload): string
    {
        if (!function_exists('openssl_sign')) {
            throw new RuntimeException('OpenSSL no está disponible en este PHP.');
        }

        $config = admin_config('license', []);
        $privateKeyPath = (string) ($config['private_key_path'] ?? '');
        if ($privateKeyPath === '' || !is_file($privateKeyPath)) {
            throw new RuntimeException('No se encontró la clave privada de licencias. Ejecutá admin/tools/create_license_keys.php.');
        }

        $privateKeyContents = file_get_contents($privateKeyPath);
        if ($privateKeyContents === false) {
            throw new RuntimeException('No se pudo leer la clave privada de licencias.');
        }

        $passphrase = (string) ($config['private_key_passphrase'] ?? '');
        $privateKey = openssl_pkey_get_private($privateKeyContents, $passphrase !== '' ? $passphrase : null);
        if ($privateKey === false) {
            throw new RuntimeException('La clave privada de licencias no es válida o la contraseña es incorrecta.');
        }

        $signature = '';
        if (!openssl_sign($canonicalPayload, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('No se pudo firmar la licencia.');
        }

        return base64_encode($signature);
    }
}

if (!function_exists('build_signed_license_document')) {
    function build_signed_license_document(array $license): array
    {
        $payload = build_license_payload($license);
        $canonicalPayload = license_canonical_json($payload);

        return [
            'alg' => 'RSA-SHA256',
            'payload_b64' => base64_encode($canonicalPayload),
            'sig_b64' => sign_license_payload($canonicalPayload),
        ];
    }
}
