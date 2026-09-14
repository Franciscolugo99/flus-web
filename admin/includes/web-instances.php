<?php
declare(strict_types=1);

function admin_web_instance_statuses(): array
{
    return [
        'preparando' => 'Preparando',
        'activo' => 'Activo',
        'mantenimiento' => 'Mantenimiento',
        'suspendido' => 'Suspendido',
        'retirado' => 'Retirado',
    ];
}

function admin_web_instance_provisioning_statuses(): array
{
    return [
        'pendiente' => 'Pendiente',
        'credenciales' => 'Credenciales cargadas',
        'esquema' => 'Esquema preparado',
        'listo' => 'Listo',
        'error' => 'Requiere atención',
    ];
}

function admin_web_instance_secret_key(): string
{
    if (!function_exists('sodium_crypto_secretbox') && !function_exists('openssl_encrypt')) {
        throw new RuntimeException('El servidor no tiene disponible Sodium ni OpenSSL para cifrar credenciales.');
    }

    $section = (array) admin_config('web_instances', []);
    $encoded = trim((string) ($section['encryption_key'] ?? ''));
    $key = base64_decode($encoded, true);
    if (!is_string($key) || strlen($key) !== 32) {
        throw new RuntimeException('Falta configurar una clave valida para proteger credenciales de instancias web.');
    }

    return $key;
}

function admin_web_instance_encrypt_secret(string $plainText): string
{
    if ($plainText === '') {
        throw new InvalidArgumentException('La credencial no puede estar vacia.');
    }

    $key = admin_web_instance_secret_key();
    if (function_exists('sodium_crypto_secretbox')) {
        $nonceLength = defined('SODIUM_CRYPTO_SECRETBOX_NONCEBYTES') ? (int) constant('SODIUM_CRYPTO_SECRETBOX_NONCEBYTES') : 24;
        $nonce = random_bytes($nonceLength);
        $cipherText = sodium_crypto_secretbox($plainText, $nonce, $key);
        return 'v1:' . base64_encode($nonce . $cipherText);
    }

    $nonce = random_bytes(12);
    $tag = '';
    $cipherText = openssl_encrypt($plainText, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);
    if (!is_string($cipherText) || strlen($tag) !== 16) {
        throw new RuntimeException('No se pudo cifrar la credencial con OpenSSL.');
    }
    return 'v2:' . base64_encode($nonce . $tag . $cipherText);
}

function admin_web_instance_decrypt_secret(string $payload): string
{
    if (str_starts_with($payload, 'v2:')) {
        if (!function_exists('openssl_decrypt')) {
            throw new RuntimeException('OpenSSL no esta disponible para descifrar la credencial.');
        }
        $decoded = base64_decode(substr($payload, 3), true);
        if (!is_string($decoded) || strlen($decoded) <= 28) {
            throw new RuntimeException('La credencial cifrada esta dañada.');
        }
        $plainText = openssl_decrypt(
            substr($decoded, 28),
            'aes-256-gcm',
            admin_web_instance_secret_key(),
            OPENSSL_RAW_DATA,
            substr($decoded, 0, 12),
            substr($decoded, 12, 16)
        );
        if (!is_string($plainText)) {
            throw new RuntimeException('No se pudo descifrar la credencial de la instancia.');
        }
        return $plainText;
    }

    if (!str_starts_with($payload, 'v1:')) {
        throw new RuntimeException('Formato de credencial cifrada no reconocido.');
    }

    if (!function_exists('sodium_crypto_secretbox_open')) {
        throw new RuntimeException('Sodium no esta disponible para descifrar esta credencial.');
    }

    $decoded = base64_decode(substr($payload, 3), true);
    $nonceLength = defined('SODIUM_CRYPTO_SECRETBOX_NONCEBYTES') ? (int) constant('SODIUM_CRYPTO_SECRETBOX_NONCEBYTES') : 24;
    if (!is_string($decoded) || strlen($decoded) <= $nonceLength) {
        throw new RuntimeException('La credencial cifrada esta dañada.');
    }

    $nonce = substr($decoded, 0, $nonceLength);
    $cipherText = substr($decoded, $nonceLength);
    $plainText = sodium_crypto_secretbox_open($cipherText, $nonce, admin_web_instance_secret_key());
    if (!is_string($plainText)) {
        throw new RuntimeException('No se pudo descifrar la credencial de la instancia.');
    }

    return $plainText;
}

function admin_web_instance_validate(array $input): array
{
    $data = [
        'client_id' => (int) ($input['client_id'] ?? 0),
        'license_id' => (int) ($input['license_id'] ?? 0),
        'slug' => strtolower(trim((string) ($input['slug'] ?? ''))),
        'primary_domain' => strtolower(trim((string) ($input['primary_domain'] ?? ''))),
        'status' => trim((string) ($input['status'] ?? 'preparando')),
        'provisioning_status' => trim((string) ($input['provisioning_status'] ?? 'pendiente')),
        'db_host' => trim((string) ($input['db_host'] ?? 'localhost')),
        'db_port' => (int) ($input['db_port'] ?? 3306),
        'db_name' => trim((string) ($input['db_name'] ?? '')),
        'db_user' => trim((string) ($input['db_user'] ?? '')),
        'app_version' => trim((string) ($input['app_version'] ?? '')),
        'schema_version' => trim((string) ($input['schema_version'] ?? '')),
        'internal_notes' => trim((string) ($input['internal_notes'] ?? '')),
    ];
    $errors = [];

    if ($data['client_id'] <= 0) $errors[] = 'Selecciona un cliente.';
    if (!preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $data['slug'])) {
        $errors[] = 'El identificador admite letras minusculas, numeros y guiones.';
    }
    if ($data['primary_domain'] === '' || str_contains($data['primary_domain'], '://') || str_contains($data['primary_domain'], '/')) {
        $errors[] = 'Carga solamente el dominio, sin https ni rutas.';
    } elseif (filter_var($data['primary_domain'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
        $errors[] = 'El dominio no tiene un formato valido.';
    }
    if (!array_key_exists($data['status'], admin_web_instance_statuses())) $errors[] = 'El estado no es valido.';
    if (!array_key_exists($data['provisioning_status'], admin_web_instance_provisioning_statuses())) $errors[] = 'El estado de preparacion no es valido.';
    if ($data['db_host'] === '' || strlen($data['db_host']) > 190) $errors[] = 'El servidor MySQL es obligatorio.';
    if ($data['db_port'] < 1 || $data['db_port'] > 65535) $errors[] = 'El puerto MySQL no es valido.';
    if (!preg_match('/^[A-Za-z0-9_$-]{1,190}$/', $data['db_name'])) $errors[] = 'El nombre de la base contiene caracteres no permitidos.';
    if (!preg_match('/^[A-Za-z0-9_$-]{1,190}$/', $data['db_user'])) $errors[] = 'El usuario MySQL contiene caracteres no permitidos.';
    if (strlen($data['app_version']) > 40 || strlen($data['schema_version']) > 40) $errors[] = 'Las versiones no pueden superar 40 caracteres.';

    return ['data' => $data, 'errors' => $errors];
}

function admin_web_instance_log(PDO $pdo, int $instanceId, int $clientId, string $eventType, ?string $fromStatus, ?string $toStatus, string $message, array $metadata = []): void
{
    $admin = current_admin() ?? [];
    $stmt = $pdo->prepare('
        INSERT INTO web_instance_events
            (web_instance_id, client_id, event_type, from_status, to_status, message, metadata_json, actor_admin_id, actor)
        VALUES
            (:instance_id, :client_id, :event_type, :from_status, :to_status, :message, :metadata_json, :actor_admin_id, :actor)
    ');
    $stmt->execute([
        'instance_id' => $instanceId,
        'client_id' => $clientId,
        'event_type' => $eventType,
        'from_status' => $fromStatus,
        'to_status' => $toStatus,
        'message' => $message,
        'metadata_json' => $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        'actor_admin_id' => (int) ($admin['id'] ?? 0) ?: null,
        'actor' => trim((string) ($admin['email'] ?? $admin['username'] ?? 'sistema')),
    ]);
}

function admin_web_instance_badge(string $status): string
{
    $labels = admin_web_instance_statuses();
    $classes = [
        'activo' => 'badge-green',
        'preparando' => 'badge-blue',
        'mantenimiento' => 'badge-yellow',
        'suspendido' => 'badge-red',
        'retirado' => 'badge-gray',
    ];
    return '<span class="badge ' . e($classes[$status] ?? 'badge-gray') . '">' . e($labels[$status] ?? ucfirst($status)) . '</span>';
}
