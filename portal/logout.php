<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/includes/bootstrap.php';
require_once __DIR__ . '/../admin/includes/client-portal.php';

admin_start_session();
portal_logout_user();
set_flash('success', 'Sesion cerrada.');
redirect_to(portal_url('login.php'));
