<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (session_status() === PHP_SESSION_ACTIVE) {
    admin_logout_user();
}

admin_start_session();
set_flash('success', 'Sesión cerrada correctamente.');
redirect_to(admin_url('login.php'));
