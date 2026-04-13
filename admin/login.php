<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (admin_is_logged_in()) {
    redirect_to(admin_url('index.php'));
}

$error = null;

if (request_is_post()) {
    verify_csrf();

    $login = trim((string) ($_POST['login'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($login === '' || $password === '') {
        $error = 'Ingresá usuario o email y contraseña.';
    } else {
        try {
            $pdo = admin_db();
            $stmt = $pdo->prepare("
                SELECT id, username, email, full_name, password_hash, is_active
                FROM admin_users
                WHERE (username = :login OR email = :login)
                LIMIT 1
            ");
            $stmt->execute(['login' => $login]);
            $user = $stmt->fetch();

            if (!$user || !(int) $user['is_active']) {
                $error = 'Credenciales inválidas.';
            } elseif (!password_verify($password, $user['password_hash'])) {
                $error = 'Credenciales inválidas.';
            } else {
                admin_login_user($user);

                $update = $pdo->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = :id');
                $update->execute(['id' => $user['id']]);

                set_flash('success', 'Bienvenido al panel.');
                redirect_to(admin_url('index.php'));
        }
    } catch (Throwable $e) {
        $error = admin_public_error($e, 'No se pudo iniciar sesión. Revisá la configuración del panel.');
    }
}
}
?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ingreso · <?= e(admin_config('app_name', 'FLUS Admin')) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="<?= e(admin_url('assets/css/admin.css')) ?>">
</head>
<body class="login-page">
    <section class="login-card">
        <span class="sidebar__eyebrow">Acceso privado</span>
        <h1><?= e(admin_config('app_name', 'FLUS Admin')) ?></h1>
        <p>Panel interno para gestionar clientes, licencias, pagos y vencimientos de FLUS.</p>

        <?php if ($error): ?>
            <div class="alert alert--error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($flash = get_flash()): ?>
            <div class="alert alert--<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?= csrf_input() ?>

            <label>
                Usuario o email
                <input type="text" name="login" value="<?= e(old_input('login')) ?>" autocomplete="username" required>
            </label>

            <label>
                Contraseña
                <input type="password" name="password" autocomplete="current-password" required>
            </label>

            <button type="submit" class="button button--block">Ingresar</button>
        </form>

        <p class="small" style="margin-top:16px;">
            Primer usuario: usá el script CLI <code>php admin/tools/create_admin.php</code>.
        </p>
    </section>
</body>
</html>
