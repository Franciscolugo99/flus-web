<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/includes/bootstrap.php';
require_once __DIR__ . '/../admin/includes/client-portal.php';
require_once __DIR__ . '/../includes/security.php';

admin_start_session();

if (portal_is_logged_in()) {
    redirect_to(portal_url('index.php'));
}

$error = null;

if (request_is_post()) {
    verify_csrf();

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $clientIp = security_client_ip();
    $rateLimited = false;

    if ($email !== '' && $password !== '') {
        try {
            $ipLimit = security_rate_limit('client_portal_login_ip', $clientIp, 12, 900);
            $emailLimit = security_rate_limit('client_portal_login_email', $email, 6, 900);
            if (!$ipLimit['allowed'] || !$emailLimit['allowed']) {
                $rateLimited = true;
                http_response_code(429);
                header('Retry-After: ' . max($ipLimit['retry_after'], $emailLimit['retry_after']));
                $error = 'Demasiados intentos. Espera unos minutos y volve a probar.';
            }
        } catch (Throwable $e) {
            $rateLimited = true;
            $error = admin_public_error($e, 'No se pudo validar el acceso en este momento.');
        }
    }

    if ($rateLimited) {
        // Error already set.
    } elseif ($email === '' || $password === '') {
        $error = 'Ingresa email y contrasena.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no tiene un formato valido.';
    } else {
        try {
            $pdo = admin_db();
            admin_cloud_sync_ensure_schema($pdo);
            $auth = portal_authenticate($pdo, $email, $password);

            if (!$auth) {
                $error = 'Credenciales invalidas o acceso desactivado.';
            } else {
                security_rate_limit_reset('client_portal_login_ip', $clientIp);
                security_rate_limit_reset('client_portal_login_email', $email);
                portal_login_user($auth['user'], $auth['membership']);

                $stmt = $pdo->prepare('UPDATE client_portal_users SET last_login_at = NOW() WHERE id = :id');
                $stmt->execute(['id' => (int) $auth['user']['id']]);

                redirect_to(portal_url('index.php'));
            }
        } catch (Throwable $e) {
            $error = admin_public_error($e, 'No se pudo iniciar sesion. Intenta nuevamente.');
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>Ingreso clientes - FLUS</title>
  <link rel="icon" type="image/png" href="<?= e(portal_public_asset_url('img/favicon.png')) ?>">
  <link rel="stylesheet" href="<?= e(portal_admin_asset_url('css/admin.css?v=' . (is_file(__DIR__ . '/../admin/assets/css/admin.css') ? filemtime(__DIR__ . '/../admin/assets/css/admin.css') : time()))) ?>">
</head>
<body class="portal-page portal-login-page">
  <main class="portal-login-shell">
    <section class="portal-login-card">
      <div class="portal-brand">
        <img src="<?= e(portal_public_asset_url('img/flus-mark.webp')) ?>" alt="" aria-hidden="true">
        <div>
          <span>FLUS</span>
          <small>Panel del comercio</small>
        </div>
      </div>

      <h1>Acceso del cliente</h1>
      <p>Consulta ventas, estado de sucursales y actividad reciente de tu negocio.</p>

      <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
      <?php endif; ?>

      <?php if ($flash = get_flash()): ?>
        <div class="alert alert--<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
      <?php endif; ?>

      <form method="post" novalidate>
        <?= csrf_input() ?>

        <label>
          Email
          <input type="email" name="email" value="<?= e(old_input('email')) ?>" autocomplete="username" required>
        </label>

        <label>
          Contrasena
          <input type="password" name="password" autocomplete="current-password" required>
        </label>

        <button type="submit" class="button button--block">Ingresar</button>
      </form>
    </section>
  </main>
</body>
</html>
