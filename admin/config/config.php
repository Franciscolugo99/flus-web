<?php
declare(strict_types=1);

if (!function_exists('admin_config')) {
    function admin_config(?string $key = null, $default = null)
    {
        static $config = null;

        if ($config === null) {
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/admin/index.php';
            $basePath = str_replace('\\', '/', dirname($scriptName));
            $basePath = rtrim($basePath, '/');
            if ($basePath === '' || $basePath === '.') {
                $basePath = '/admin';
            }

            $config = [
                'app_name' => 'FLUS Admin',
                'env' => 'production', // production | development
                'base_path' => $basePath,
                'timezone' => 'America/Argentina/Mendoza',
                'session_name' => 'flus_admin_session',
                'license' => [
                    'issuer' => 'FLUS',
                    'private_key_path' => __DIR__ . '/license-private.pem',
                    'public_key_path' => __DIR__ . '/license-public.pem',
                    'private_key_passphrase' => '',
                ],
                'analytics' => [
                    'exclude_local_requests' => true,
                    'exclude_admin_sessions' => true,
                    'excluded_ips' => [
                        '127.0.0.1',
                        '::1',
                    ],
                    'excluded_user_agents' => [
                        'bot',
                        'crawl',
                        'spider',
                        'slurp',
                        'wget',
                        'curl',
                        'facebookexternalhit',
                        'telegrambot',
                        'preview',
                        'monitoring',
                    ],
                ],
                'db' => [
                    'host' => 'localhost',
                    'name' => 'flus-licenciadb',
                    'user' => 'root',
                    'pass' => '',
                    'charset' => 'utf8mb4',
                ],
            ];

            date_default_timezone_set($config['timezone']);

            if ($config['env'] === 'development') {
                ini_set('display_errors', '1');
                error_reporting(E_ALL);
            } else {
                ini_set('display_errors', '0');
                error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
            }
        }

        if ($key === null) {
            return $config;
        }

        return $config[$key] ?? $default;
    }
}
