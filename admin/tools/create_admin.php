<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/bootstrap.php';

$args = $_SERVER['argv'] ?? [];
if (count($args) < 5) {
    fwrite(STDERR, "Uso: php admin/tools/create_admin.php <usuario> <email> <password> <nombre>\n");
    exit(1);
}

[$script, $username, $email, $password, $fullName] = $args;

$username = trim((string) $username);
$email = strtolower(trim((string) $email));
$fullName = trim((string) $fullName);

if ($username === '' || $email === '' || $password === '' || $fullName === '') {
    fwrite(STDERR, "Todos los campos son obligatorios.\n");
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

$pdo = admin_db();
$hash = password_hash((string) $password, PASSWORD_DEFAULT);
if (!is_string($hash) || $hash === '') {
    fwrite(STDERR, "No se pudo generar el hash de password.\n");
    exit(1);
}

$stmt = $pdo->prepare('
    INSERT INTO admin_users (username, email, full_name, password_hash, is_active)
    VALUES (:username, :email, :full_name, :password_hash, 1)
    ON DUPLICATE KEY UPDATE
        email = VALUES(email),
        full_name = VALUES(full_name),
        password_hash = VALUES(password_hash),
        is_active = 1,
        updated_at = NOW()
');
$stmt->execute([
    'username' => $username,
    'email' => $email,
    'full_name' => $fullName,
    'password_hash' => $hash,
]);

echo "Usuario administrador listo: {$username}\n";
