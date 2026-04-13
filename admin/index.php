<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_admin_login();

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';

$stats = [
    'clients_total' => 0,
    'licenses_active' => 0,
    'licenses_expiring' => 0,
    'licenses_expired' => 0,
];
$latestPayments = [];
$upcomingExpirations = [];
$error = null;

try {
    $pdo = admin_db();

    $stats['clients_total'] = (int) $pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn();

    $sqlStats = "
        SELECT
            SUM(CASE WHEN status = 'suspendida' THEN 0 WHEN status = 'demo' THEN 0 WHEN expires_at < CURDATE() THEN 0 WHEN expires_at <= DATE_ADD(CURDATE(), INTERVAL 15 DAY) THEN 0 ELSE 1 END) AS active_count,
            SUM(CASE WHEN status = 'suspendida' THEN 0 WHEN expires_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY) THEN 1 ELSE 0 END) AS expiring_count,
            SUM(CASE WHEN status = 'suspendida' THEN 0 WHEN expires_at < CURDATE() THEN 1 ELSE 0 END) AS expired_count
        FROM licenses
    ";
    $statsRow = $pdo->query($sqlStats)->fetch();
    if ($statsRow) {
        $stats['licenses_active'] = (int) ($statsRow['active_count'] ?? 0);
        $stats['licenses_expiring'] = (int) ($statsRow['expiring_count'] ?? 0);
        $stats['licenses_expired'] = (int) ($statsRow['expired_count'] ?? 0);
    }

    $paymentsStmt = $pdo->query("
        SELECT
            p.id,
            p.paid_at,
            p.period_from,
            p.period_to,
            p.amount,
            p.method,
            p.reference,
            c.legal_name,
            l.license_key
        FROM payments p
        INNER JOIN clients c ON c.id = p.client_id
        LEFT JOIN licenses l ON l.id = p.license_id
        ORDER BY p.paid_at DESC, p.id DESC
        LIMIT 8
    ");
    $latestPayments = $paymentsStmt->fetchAll();

    $expStmt = $pdo->query("
        SELECT
            l.id,
            l.license_key,
            l.status,
            l.expires_at,
            c.id AS client_id,
            c.legal_name,
            c.email,
            c.phone
        FROM licenses l
        INNER JOIN clients c ON c.id = l.client_id
        WHERE l.expires_at >= CURDATE()
        ORDER BY l.expires_at ASC
        LIMIT 8
    ");
    $upcomingExpirations = $expStmt->fetchAll();
} catch (Throwable $e) {
    $error = admin_public_error($e, 'No se pudo cargar el dashboard.');
}

require __DIR__ . '/includes/layout-header.php';
?>

<?php if ($error): ?>
    <div class="alert alert--error"><?= e($error) ?></div>
<?php else: ?>
    <section class="grid grid--cards">
        <article class="card">
            <div class="card__stat"><?= e((string) $stats['clients_total']) ?></div>
            <div class="card__label">Total de clientes</div>
        </article>
        <article class="card">
            <div class="card__stat"><?= e((string) $stats['licenses_active']) ?></div>
            <div class="card__label">Licencias activas</div>
        </article>
        <article class="card">
            <div class="card__stat"><?= e((string) $stats['licenses_expiring']) ?></div>
            <div class="card__label">Licencias por vencer</div>
        </article>
        <article class="card">
            <div class="card__stat"><?= e((string) $stats['licenses_expired']) ?></div>
            <div class="card__label">Licencias vencidas</div>
        </article>
    </section>

    <section class="grid grid--2">
        <article class="card">
            <div class="toolbar">
                <h2>Últimos pagos cargados</h2>
                <a class="button button--ghost" href="<?= e(admin_url('payments.php')) ?>">Ver todos</a>
            </div>

            <?php if (!$latestPayments): ?>
                <div class="empty-state">Todavía no hay pagos registrados.</div>
            <?php else: ?>
                <ul class="list-plain">
                    <?php foreach ($latestPayments as $payment): ?>
                        <li class="list-item">
                            <div class="split">
                                <div>
                                    <strong><?= e($payment['legal_name']) ?></strong>
                                    <div class="meta">
                                        <?= e(format_date($payment['paid_at'])) ?>
                                        · <?= e(status_label($payment['method'])) ?>
                                        <?php if (!empty($payment['license_key'])): ?>
                                            · <?= e($payment['license_key']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <strong><?= e(format_money($payment['amount'])) ?></strong>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>

        <article class="card">
            <div class="toolbar">
                <h2>Próximos vencimientos</h2>
                <a class="button button--ghost" href="<?= e(admin_url('expirations.php')) ?>">Ver vista completa</a>
            </div>

            <?php if (!$upcomingExpirations): ?>
                <div class="empty-state">No hay vencimientos próximos registrados.</div>
            <?php else: ?>
                <ul class="list-plain">
                    <?php foreach ($upcomingExpirations as $license): ?>
                        <?php $currentStatus = license_current_status($license['status'], $license['expires_at']); ?>
                        <li class="list-item">
                            <div class="split">
                                <div>
                                    <strong><?= e($license['legal_name']) ?></strong>
                                    <div class="meta">
                                        <?= e($license['license_key']) ?>
                                        · <?= e($license['email'] ?: 'sin email') ?>
                                        · <?= e($license['phone'] ?: 'sin teléfono') ?>
                                    </div>
                                </div>
                                <div style="text-align:right;">
                                    <div><?= e(format_date($license['expires_at'])) ?></div>
                                    <span class="badge <?= e(badge_class($currentStatus)) ?>"><?= e(status_label($currentStatus)) ?></span>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
