<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_admin_login();

$pageTitle = 'Licencias';
$activeNav = 'licenses';
$error = null;
$licenses = [];
$clientId = (int) ($_GET['client_id'] ?? 0);

try {
    $pdo = admin_db();

    if (request_is_post() && ($_POST['action'] ?? '') === 'delete') {
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);

        $checkStmt = $pdo->prepare("
            SELECT
                (SELECT COUNT(*) FROM payments WHERE license_id = :id) AS payments_count,
                (SELECT COUNT(*) FROM license_notifications WHERE license_id = :id) AS notifications_count
        ");
        $checkStmt->execute(['id' => $id]);
        $counts = $checkStmt->fetch();

        if (!$counts) {
            set_flash('error', 'Licencia no encontrada.');
        } elseif ((int) $counts['payments_count'] > 0 || (int) $counts['notifications_count'] > 0) {
            set_flash('warning', 'No se puede eliminar la licencia porque tiene pagos o notificaciones asociadas.');
        } else {
            $delete = $pdo->prepare('DELETE FROM licenses WHERE id = :id');
            $delete->execute(['id' => $id]);
            set_flash('success', 'Licencia eliminada correctamente.');
        }

        redirect_to(admin_url('licenses.php' . ($clientId ? '?client_id=' . $clientId : '')));
    }

    $sql = "
        SELECT
            l.*,
            c.legal_name,
            c.email,
            c.phone
        FROM licenses l
        INNER JOIN clients c ON c.id = l.client_id
    ";
    $params = [];
    if ($clientId > 0) {
        $sql .= " WHERE l.client_id = :client_id ";
        $params['client_id'] = $clientId;
    }
    $sql .= " ORDER BY l.expires_at ASC, l.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $licenses = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = admin_public_error($e, 'No se pudo cargar el listado de licencias.');
}

require __DIR__ . '/includes/layout-header.php';
?>

<div class="toolbar">
    <div class="meta">
        <?= $clientId > 0 ? 'Mostrando licencias del cliente seleccionado.' : 'Listado general de licencias.' ?>
    </div>
    <a class="button" href="<?= e(admin_url('license-edit.php' . ($clientId ? '?client_id=' . $clientId : ''))) ?>">Nueva licencia</a>
</div>

<?php if ($error): ?>
    <div class="alert alert--error"><?= e($error) ?></div>
<?php elseif (!$licenses): ?>
    <div class="empty-state">No hay licencias registradas todavía.</div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Clave</th>
                    <th>Plan</th>
                    <th>Inicio</th>
                    <th>Vencimiento</th>
                    <th>Estado actual</th>
                    <th>Puestos</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($licenses as $license): ?>
                    <?php $currentStatus = license_current_status($license['status'], $license['expires_at']); ?>
                    <tr>
                        <td>
                            <strong><?= e($license['legal_name']) ?></strong><br>
                            <span class="meta"><?= e($license['email'] ?: 'Sin email') ?></span>
                        </td>
                        <td><?= e($license['license_key']) ?></td>
                        <td><?= e($license['plan_type']) ?></td>
                        <td><?= e(format_date($license['starts_at'])) ?></td>
                        <td><?= e(format_date($license['expires_at'])) ?></td>
                        <td><span class="badge <?= e(badge_class($currentStatus)) ?>"><?= e(status_label($currentStatus)) ?></span></td>
                        <td><?= e((string) ($license['seats'] ?: '—')) ?></td>
                        <td>
                            <div class="actions">
                                <a class="button button--ghost" href="<?= e(admin_url('license-edit.php?id=' . (int) $license['id'])) ?>">Editar / renovar</a>
                                <a class="button button--ghost" href="<?= e(admin_url('license-download.php?id=' . (int) $license['id'])) ?>">Descargar licencia</a>
                                <form method="post" style="display:inline;">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $license['id'] ?>">
                                    <button type="submit" class="button button--danger" data-confirm="¿Eliminar esta licencia?">Eliminar</button>
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
