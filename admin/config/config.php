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
                'env' => getenv('FLUS_ADMIN_ENV') ?: 'production',
                'base_path' => $basePath,
                'timezone' => 'America/Argentina/Mendoza',
                'session_name' => 'flus_admin_session',
                'license' => [
                    'issuer' => 'FLUS',
                    'private_key_path' => __DIR__ . '/license-private.pem',
                    'public_key_path' => __DIR__ . '/license-public.pem',
                    'private_key_passphrase' => getenv('FLUS_LICENSE_PASSPHRASE') ?: '',
                ],
                'security' => [
                    'rate_limit_salt' => getenv('FLUS_RATE_LIMIT_SALT') ?: '',
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
                    'host' => getenv('FLUS_DB_HOST') ?: 'DB_HOST',
                    'name' => getenv('FLUS_DB_NAME') ?: 'DB_NAME',
                    'user' => getenv('FLUS_DB_USER') ?: 'DB_USER',
                    'pass' => getenv('FLUS_DB_PASS') ?: '',
                    'charset' => getenv('FLUS_DB_CHARSET') ?: 'utf8mb4',
                ],
            ];

            $localConfigPath = __DIR__ . '/config.local.php';
            if (is_file($localConfigPath)) {
                $localConfig = require $localConfigPath;
                if (is_array($localConfig)) {
                    $config = array_replace_recursive($config, $localConfig);
                }
            }

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
