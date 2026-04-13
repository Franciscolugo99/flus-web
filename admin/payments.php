<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_admin_login();

$pageTitle = 'Pagos';
$activeNav = 'payments';
$error = null;
$payments = [];
$clientId = (int) ($_GET['client_id'] ?? 0);

try {
    $pdo = admin_db();

    if (request_is_post() && ($_POST['action'] ?? '') === 'delete') {
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);

        $delete = $pdo->prepare('DELETE FROM payments WHERE id = :id');
        $delete->execute(['id' => $id]);
        set_flash('success', 'Pago eliminado correctamente.');
        redirect_to(admin_url('payments.php' . ($clientId ? '?client_id=' . $clientId : '')));
    }

    $sql = "
        SELECT
            p.*,
            c.legal_name,
            l.license_key
        FROM payments p
        INNER JOIN clients c ON c.id = p.client_id
        LEFT JOIN licenses l ON l.id = p.license_id
    ";
    $params = [];
    if ($clientId > 0) {
        $sql .= " WHERE p.client_id = :client_id ";
        $params['client_id'] = $clientId;
    }
    $sql .= " ORDER BY p.paid_at DESC, p.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $payments = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = admin_public_error($e, 'No se pudo cargar el listado de pagos.');
}

require __DIR__ . '/includes/layout-header.php';
?>

<div class="toolbar">
    <div class="meta">
        <?= $clientId > 0 ? 'Mostrando pagos del cliente seleccionado.' : 'Listado general de pagos.' ?>
    </div>
    <a class="button" href="<?= e(admin_url('payment-edit.php' . ($clientId ? '?client_id=' . $clientId : ''))) ?>">Cargar pago</a>
</div>

<?php if ($error): ?>
    <div class="alert alert--error"><?= e($error) ?></div>
<?php elseif (!$payments): ?>
    <div class="empty-state">No hay pagos cargados todavía.</div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Licencia</th>
                    <th>Período</th>
                    <th>Método</th>
                    <th>Monto</th>
                    <th>Referencia</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?= e(format_date($payment['paid_at'])) ?></td>
                        <td><?= e($payment['legal_name']) ?></td>
                        <td><?= e($payment['license_key'] ?: '—') ?></td>
                        <td>
                            <?= e(format_date($payment['period_from'])) ?>
                            a
                            <?= e(format_date($payment['period_to'])) ?>
                        </td>
                        <td><?= e(status_label($payment['method'])) ?></td>
                        <td><?= e(format_money($payment['amount'])) ?></td>
                        <td><?= e($payment['reference'] ?: '—') ?></td>
                        <td>
                            <div class="actions">
                                <a class="button button--ghost" href="<?= e(admin_url('payment-edit.php?id=' . (int) $payment['id'])) ?>">Editar</a>
                                <form method="post" style="display:inline;">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $payment['id'] ?>">
                                    <button type="submit" class="button button--danger" data-confirm="¿Eliminar este pago?">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
