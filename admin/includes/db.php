<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (!function_exists('admin_db')) {
    function admin_db(): PDO
    {
        static $pdo = null;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $db = admin_database_config();

        $placeholders = ['DB_HOST', 'DB_NAME', 'DB_USER'];
        foreach (['host', 'name', 'user'] as $requiredKey) {
            if (empty($db[$requiredKey]) || in_array($db[$requiredKey], $placeholders, true)) {
                throw new RuntimeException('Configuración de base de datos incompleta en /admin/config/config.php');
            }
        }

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $db['host'],
            $db['name'],
            $db['charset'] ?? 'utf8mb4'
        );

        $pdo = new PDO($dsn, $db['user'], $db['pass'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $pdo;
    }
}
