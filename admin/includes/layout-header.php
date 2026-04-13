<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Panel';
$activeNav = $activeNav ?? '';
$flash = get_flash();
$adminUser = current_admin();
?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title><?= e($pageTitle) ?> · <?= e(admin_config('app_name', 'FLUS Admin')) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="<?= e(admin_url('assets/css/admin.css')) ?>">
</head>
<body>
<div class="admin-shell">
    <aside class="sidebar">
        <div class="sidebar__brand">
            <span class="sidebar__eyebrow">Panel privado</span>
            <strong><?= e(admin_config('app_name', 'FLUS Admin')) ?></strong>
        </div>

        <nav class="sidebar__nav">
            <a class="<?= $activeNav === 'dashboard' ? 'is-active' : '' ?>" href="<?= e(admin_url('index.php')) ?>">Dashboard</a>
            <a class="<?= $activeNav === 'clients' ? 'is-active' : '' ?>" href="<?= e(admin_url('clients.php')) ?>">Clientes</a>
            <a class="<?= $activeNav === 'licenses' ? 'is-active' : '' ?>" href="<?= e(admin_url('licenses.php')) ?>">Licencias</a>
            <a class="<?= $activeNav === 'payments' ? 'is-active' : '' ?>" href="<?= e(admin_url('payments.php')) ?>">Pagos</a>
            <a class="<?= $activeNav === 'expirations' ? 'is-active' : '' ?>" href="<?= e(admin_url('expirations.php')) ?>">Vencimientos</a>
            <a class="<?= $activeNav === 'downloads' ? 'is-active' : '' ?>" href="<?= e(admin_url('downloads.php')) ?>">Descargas</a>
        </nav>

        <div class="sidebar__footer">
            <?php if ($adminUser): ?>
                <div class="sidebar__user">
                    <strong><?= e($adminUser['full_name'] ?: $adminUser['username']) ?></strong>
                    <span><?= e($adminUser['email'] ?: $adminUser['username']) ?></span>
                </div>
            <?php endif; ?>
            <a class="button button--ghost button--block" href="<?= e(admin_url('logout.php')) ?>">Cerrar sesión</a>
        </div>
    </aside>

    <main class="content">
        <header class="content__header">
            <div>
                <h1><?= e($pageTitle) ?></h1>
            </div>
        </header>

        <?php if ($flash): ?>
            <div class="alert alert--<?= e($flash['type']) ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>
