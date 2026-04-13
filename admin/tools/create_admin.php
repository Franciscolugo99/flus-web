<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo se puede ejecutar por CLI.');
}

$args = $argv ?? [];
$username = $args[1] ?? null;
$email = $args[2] ?? null;
$password = $args[3] ?? null;
$fullName = $args[4] ?? 'Administrador FLUS';

if (!$username || !$email || !$password) {
    echo "Uso:\n";
    echo "php admin/tools/create_admin.php usuario email@dominio.com contraseña \"Nombre Completo\"\n";
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Email inválido.\n";
    exit(1);
}

if (strlen($password) < 8) {
    echo "La contraseña debe tener al menos 8 caracteres.\n";
    exit(1);
}

try {
    $pdo = admin_db();

    $check = $pdo->prepare('SELECT id FROM admin_users WHERE username = :username OR email = :email LIMIT 1');
    $check->execute([
        'username' => $username,
        'email' => $email,
    ]);

    if ($check->fetch()) {
        echo "Ya existe un usuario con ese username o email.\n";
        exit(1);
    }

    $stmt = $pdo->prepare("
        INSERT INTO admin_users (username, email, full_name, password_hash, is_active)
        VALUES (:username, :email, :full_name, :password_hash, 1)
    ");

    $stmt->execute([
        'username' => $username,
        'email' => strtolower($email),
        'full_name' => $fullName,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    echo "Usuario administrador creado correctamente.\n";
    echo "Username: {$username}\n";
    echo "Email: " . strtolower($email) . "\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
