<?php
declare(strict_types=1);

if (!function_exists('admin_start_session')) {
    function admin_start_session(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (($_SERVER['SERVER_PORT'] ?? null) === '443')
        );

        session_name((string) admin_config('session_name', 'flus_admin_session'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }
}

if (!function_exists('admin_is_logged_in')) {
    function admin_is_logged_in(): bool
    {
        return !empty($_SESSION['admin_user']['id']);
    }
}

if (!function_exists('admin_login_user')) {
    function admin_login_user(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['admin_user'] = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
        ];
    }
}

if (!function_exists('admin_logout_user')) {
    function admin_logout_user(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
        }

        session_destroy();
    }
}

if (!function_exists('require_admin_login')) {
    function require_admin_login(): void
    {
        admin_start_session();

        if (!admin_is_logged_in()) {
            set_flash('error', 'Iniciá sesión para continuar.');
            redirect_to(admin_url('login.php'));
        }
    }
}
