<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_admin_login();

$pageTitle = 'Clientes';
$activeNav = 'clients';
$error = null;
$clients = [];
$q = trim((string) ($_GET['q'] ?? ''));

try {
    $pdo = admin_db();

    if (request_is_post() && ($_POST['action'] ?? '') === 'delete') {
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);

        $checkStmt = $pdo->prepare("
            SELECT
                (SELECT COUNT(*) FROM licenses WHERE client_id = :id) AS licenses_count,
                (SELECT COUNT(*) FROM payments WHERE client_id = :id) AS payments_count
        ");
        $checkStmt->execute(['id' => $id]);
        $counts = $checkStmt->fetch();

        if (!$counts) {
            set_flash('error', 'Cliente no encontrado.');
        } elseif ((int) $counts['licenses_count'] > 0 || (int) $counts['payments_count'] > 0) {
            set_flash('warning', 'No se puede eliminar el cliente porque tiene licencias o pagos asociados.');
        } else {
            $delete = $pdo->prepare('DELETE FROM clients WHERE id = :id');
            $delete->execute(['id' => $id]);
            set_flash('success', 'Cliente eliminado correctamente.');
        }

        redirect_to(admin_url('clients.php'));
    }

    $sql = "
        SELECT c.*,
               (SELECT COUNT(*) FROM licenses l WHERE l.client_id = c.id) AS licenses_count,
               (SELECT COUNT(*) FROM payments p WHERE p.client_id = c.id) AS payments_count
        FROM clients c
    ";
    $params = [];
    if ($q !== '') {
        $sql .= " WHERE c.legal_name LIKE :q OR c.trade_name LIKE :q OR c.email LIKE :q OR c.phone LIKE :q ";
        $params['q'] = '%' . $q . '%';
    }
    $sql .= " ORDER BY c.updated_at DESC, c.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clients = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = admin_public_error($e, 'No se pudo cargar el listado de clientes.');
}

require __DIR__ . '/includes/layout-header.php';
?>

<div class="toolbar">
    <form method="get">
        <input type="text" name="q" placeholder="Buscar por nombre, email o teléfono" value="<?= e($q) ?>">
        <button type="submit">Buscar</button>
    </form>
    <a class="button" href="<?= e(admin_url('client-edit.php')) ?>">Nuevo cliente</a>
</div>

<?php if ($error): ?>
    <div class="alert alert--error"><?= e($error) ?></div>
<?php elseif (!$clients): ?>
    <div class="empty-state">No hay clientes cargados todavía.</div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Contacto</th>
                    <th>Estado</th>
                    <th>Licencias</th>
                    <th>Pagos</th>
                    <th>Actualización</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $client): ?>
                    <tr>
                        <td>
                            <strong><?= e($client['legal_name']) ?></strong><br>
                            <span class="meta"><?= e($client['trade_name'] ?: 'Sin nombre comercial') ?></span>
                        </td>
                        <td>
                            <?= e($client['email'] ?: 'Sin email') ?><br>
                            <span class="meta"><?= e($client['phone'] ?: 'Sin teléfono') ?></span>
                        </td>
                        <td>
                            <span class="badge <?= e(badge_class($client['status'])) ?>"><?= e(status_label($client['status'])) ?></span>
                        </td>
                        <td><?= e((string) $client['licenses_count']) ?></td>
                        <td><?= e((string) $client['payments_count']) ?></td>
                        <td><?= e(format_datetime($client['updated_at'])) ?></td>
                        <td>
                            <div class="actions">
                                <a class="button button--ghost" href="<?= e(admin_url('client-view.php?id=' . (int) $client['id'])) ?>">Ver</a>
                                <a class="button button--ghost" href="<?= e(admin_url('client-edit.php?id=' . (int) $client['id'])) ?>">Editar</a>
                                <form method="post" style="display:inline;">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $client['id'] ?>">
                                    <button type="submit" class="button button--danger" data-confirm="¿Eliminar este cliente? Esta acción no se puede deshacer.">Eliminar</button>
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
