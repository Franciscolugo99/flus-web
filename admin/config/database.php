<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (!function_exists('admin_database_config')) {
    function admin_database_config(): array
    {
        return admin_config('db', []);
    }
}
