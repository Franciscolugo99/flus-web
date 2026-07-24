<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/bootstrap.php';

$args = $_SERVER['argv'] ?? [];
if (count($args) < 5) {
    fwrite(STDERR, "Uso: php admin/tools/create_client_portal_user.php <client_id> <email> <password> <nombre> [role]\n");
    exit(1);
}

[$script, $clientIdRaw, $email, $password, $fullName] = array_pad($args, 5, '');
$role = trim((string) ($args[5] ?? 'owner'));
$clientId = (int) $clientIdRaw;
$email = strtolower(trim((string) $email));
$fullName = trim((string) $fullName);

if ($clientId <= 0 || $email === '' || $password === '' || $fullName === '') {
    fwrite(STDERR, "client_id, email, password y nombre son obligatorios.\n");
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "El email no es valido.\n");
    exit(1);
}

if (strlen((string) $password) < 10) {
    fwrite(STDERR, "La password debe tener al menos 10 caracteres.\n");
    exit(1);
}

if (!preg_match('/^[a-z_]{3,30}$/', $role)) {
    fwrite(STDERR, "El role solo puede usar letras minusculas y guion bajo.\n");
    exit(1);
}

$pdo = admin_db();
admin_cloud_sync_ensure_schema($pdo);

$client = $pdo->prepare('SELECT id, legal_name, trade_name FROM clients WHERE id = :id LIMIT 1');
$client->execute(['id' => $clientId]);
$clientRow = $client->fetch();
if (!$clientRow) {
    fwrite(STDERR, "No existe el cliente ID {$clientId}.\n");
    exit(1);
}

$hash = password_hash((string) $password, PASSWORD_DEFAULT);
if (!is_string($hash) || $hash === '') {
    fwrite(STDERR, "No se pudo generar el hash de password.\n");
    exit(1);
}

$pdo->beginTransaction();
try {
    $userStmt = $pdo->prepare('
        INSERT INTO client_portal_users (email, full_name, password_hash, is_active)
        VALUES (:email, :full_name, :password_hash, 1)
        ON DUPLICATE KEY UPDATE
            full_name = VALUES(full_name),
            password_hash = VALUES(password_hash),
            is_active = 1,
            updated_at = NOW()
    ');
    $userStmt->execute([
        'email' => $email,
        'full_name' => $fullName,
        'password_hash' => $hash,
    ]);

    $selectUser = $pdo->prepare('SELECT id FROM client_portal_users WHERE email = :email LIMIT 1');
    $selectUser->execute(['email' => $email]);
    $userId = (int) $selectUser->fetchColumn();
    if ($userId <= 0) {
        throw new RuntimeException('No se pudo obtener el usuario creado.');
    }

    $membershipStmt = $pdo->prepare('
        INSERT INTO client_portal_memberships (user_id, client_id, role, is_active)
        VALUES (:user_id, :client_id, :role, 1)
        ON DUPLICATE KEY UPDATE
            role = VALUES(role),
            is_active = 1,
            updated_at = NOW()
    ');
    $membershipStmt->execute([
        'user_id' => $userId,
        'client_id' => $clientId,
        'role' => $role,
    ]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "No se pudo crear el acceso: " . $e->getMessage() . "\n");
    exit(1);
}

$clientName = (string) ($clientRow['trade_name'] ?: $clientRow['legal_name']);
echo "Acceso cliente listo: {$email} -> {$clientName} (ID {$clientId})\n";
