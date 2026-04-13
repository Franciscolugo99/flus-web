<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_admin_login();

$pageTitle = 'Detalle del cliente';
$activeNav = 'clients';
$id = (int) ($_GET['id'] ?? 0);
$client = null;
$licenses = [];
$payments = [];
$error = null;

try {
    $pdo = admin_db();

    $stmt = $pdo->prepare('SELECT * FROM clients WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $client = $stmt->fetch();

    if (!$client) {
        set_flash('error', 'Cliente no encontrado.');
        redirect_to(admin_url('clients.php'));
    }

    $licensesStmt = $pdo->prepare('SELECT * FROM licenses WHERE client_id = :id ORDER BY expires_at ASC, id DESC');
    $licensesStmt->execute(['id' => $id]);
    $licenses = $licensesStmt->fetchAll();

    $paymentsStmt = $pdo->prepare('
        SELECT p.*, l.license_key
        FROM payments p
        LEFT JOIN licenses l ON l.id = p.license_id
        WHERE p.client_id = :id
        ORDER BY p.paid_at DESC, p.id DESC
        LIMIT 20
    ');
    $paymentsStmt->execute(['id' => $id]);
    $payments = $paymentsStmt->fetchAll();
} catch (Throwable $e) {
    $error = admin_public_error($e, 'No se pudo cargar el detalle del cliente.');
}

require __DIR__ . '/includes/layout-header.php';
?>

<?php if ($error): ?>
    <div class="alert alert--error"><?= e($error) ?></div>
<?php elseif ($client): ?>
    <section class="card">
        <div class="toolbar">
            <h2><?= e($client['legal_name']) ?></h2>
            <div class="actions">
                <a class="button button--ghost" href="<?= e(admin_url('client-edit.php?id=' . (int) $client['id'])) ?>">Editar cliente</a>
                <a class="button" href="<?= e(admin_url('license-edit.php?client_id=' . (int) $client['id'])) ?>">Nueva licencia</a>
                <a class="button" href="<?= e(admin_url('payment-edit.php?client_id=' . (int) $client['id'])) ?>">Cargar pago</a>
            </div>
        </div>

        <div class="kv">
            <div class="kv__row"><strong>Nombre comercial</strong><span><?= e($client['trade_name'] ?: '—') ?></span></div>
            <div class="kv__row"><strong>Email</strong><span><?= e($client['email'] ?: '—') ?></span></div>
            <div class="kv__row"><strong>Teléfono</strong><span><?= e($client['phone'] ?: '—') ?></span></div>
            <div class="kv__row"><strong>CUIT / DNI</strong><span><?= e($client['tax_id'] ?: '—') ?></span></div>
            <div class="kv__row"><strong>Rubro</strong><span><?= e($client['business_type'] ?: '—') ?></span></div>
            <div class="kv__row"><strong>Dirección</strong><span><?= e($client['address'] ?: '—') ?></span></div>
            <div class="kv__row"><strong>Estado</strong><span class="badge <?= e(badge_class($client['status'])) ?>"><?= e(status_label($client['status'])) ?></span></div>
            <div class="kv__row"><strong>Fecha de alta</strong><span><?= e(format_datetime($client['created_at'])) ?></span></div>
            <div class="kv__row"><strong>Última actualización</strong><span><?= e(format_datetime($client['updated_at'])) ?></span></div>
            <div class="kv__row"><strong>Notas internas</strong><span><?= nl2br(e($client['internal_notes'] ?: '—')) ?></span></div>
        </div>
    </section>

    <section class="grid grid--2">
        <article class="card">
            <div class="toolbar">
                <h2>Licencias del cliente</h2>
                <a class="button button--ghost" href="<?= e(admin_url('licenses.php?client_id=' . (int) $client['id'])) ?>">Ver listado</a>
            </div>

            <?php if (!$licenses): ?>
                <div class="empty-state">No tiene licencias registradas.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Clave</th>
                                <th>Plan</th>
                                <th>Vence</th>
                                <th>Estado</th>
                                <th>Archivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($licenses as $license): ?>
                                <?php $currentStatus = license_current_status($license['status'], $license['expires_at']); ?>
                                <tr>
                                    <td><?= e($license['license_key']) ?></td>
                                    <td><?= e($license['plan_type']) ?></td>
                                    <td><?= e(format_date($license['expires_at'])) ?></td>
                                    <td><span class="badge <?= e(badge_class($currentStatus)) ?>"><?= e(status_label($currentStatus)) ?></span></td>
                                    <td><a class="button button--ghost" href="<?= e(admin_url('license-download.php?id=' . (int) $license['id'])) ?>">Descargar</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </article>

        <article class="card">
            <div class="toolbar">
                <h2>Pagos del cliente</h2>
                <a class="button button--ghost" href="<?= e(admin_url('payments.php?client_id=' . (int) $client['id'])) ?>">Ver listado</a>
            </div>

            <?php if (!$payments): ?>
                <div class="empty-state">No tiene pagos registrados.</div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Monto</th>
                                <th>Método</th>
                                <th>Licencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= e(format_date($payment['paid_at'])) ?></td>
                                    <td><?= e(format_money($payment['amount'])) ?></td>
                                    <td><?= e(status_label($payment['method'])) ?></td>
                                    <td><?= e($payment['license_key'] ?: '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </article>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
