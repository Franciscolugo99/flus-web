<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_admin_login();

$pdo = admin_db();
$sourceClientId = max(0, (int) ($_GET['source_id'] ?? $_POST['source_client_id'] ?? 0));
if ($sourceClientId <= 0) {
    set_flash('error', 'Cliente de origen no encontrado.');
    redirect_to(admin_url('clients.php'));
}

$sourceStmt = $pdo->prepare('SELECT * FROM clients WHERE id = :id LIMIT 1');
$sourceStmt->execute(['id' => $sourceClientId]);
$sourceClient = $sourceStmt->fetch();
if (!$sourceClient) {
    set_flash('error', 'Cliente de origen no encontrado.');
    redirect_to(admin_url('clients.php'));
}

$targetStmt = $pdo->prepare("SELECT id, legal_name, trade_name FROM clients WHERE id <> :id AND status <> 'inactivo' ORDER BY COALESCE(NULLIF(trade_name, ''), legal_name)");
$targetStmt->execute(['id' => $sourceClientId]);
$targetClients = $targetStmt->fetchAll();
$sourceCounts = admin_client_merge_counts($pdo, $sourceClientId);
$errors = [];
$form = [
    'target_client_id' => (int) ($_POST['target_client_id'] ?? 0),
    'source_branch_name' => trim((string) ($_POST['source_branch_name'] ?? ($sourceClient['trade_name'] ?: $sourceClient['legal_name']))),
    'source_branch_code' => trim((string) ($_POST['source_branch_code'] ?? admin_client_merge_branch_code((string) ($sourceClient['trade_name'] ?: $sourceClient['legal_name'])))),
    'target_branch_name' => trim((string) ($_POST['target_branch_name'] ?? 'Casa central')),
    'target_branch_code' => trim((string) ($_POST['target_branch_code'] ?? 'central')),
];

if (request_is_post()) {
    verify_csrf();
    if (trim((string) ($_POST['confirmation'] ?? '')) !== 'FUSIONAR') {
        $errors[] = 'Escribe FUSIONAR para confirmar la operacion.';
    }

    if (!$errors) {
        try {
            $result = admin_client_merge($pdo, [
                'source_client_id' => $sourceClientId,
                'target_client_id' => $form['target_client_id'],
                'source_branch_name' => $form['source_branch_name'],
                'source_branch_code' => $form['source_branch_code'],
                'target_branch_name' => $form['target_branch_name'],
                'target_branch_code' => $form['target_branch_code'],
            ]);
            set_flash('success', 'Clientes fusionados. Las licencias e instalaciones quedaron separadas por sucursal.');
            redirect_to(admin_url('cloud-sync.php?client_id=' . (int) $result['target_client_id']));
        } catch (InvalidArgumentException | RuntimeException $e) {
            $errors[] = $e->getMessage();
        } catch (Throwable $e) {
            error_log('[FLUS Admin] client merge: ' . $e->getMessage());
            $errors[] = 'No se pudo completar la fusion. No se modifico ningun dato.';
        }
    }
}

$pageTitle = 'Fusionar cliente';
$activeNav = 'clients';
require __DIR__ . '/includes/layout-header.php';
?>

<?php if ($errors): ?>
    <div class="alert alert--error"><?= e(implode(' ', $errors)) ?></div>
<?php endif; ?>

<div class="card">
    <div class="section-heading">
        <div>
            <span class="eyebrow">Operacion administrativa</span>
            <h2>Fusionar <?= e((string) ($sourceClient['trade_name'] ?: $sourceClient['legal_name'])) ?></h2>
            <p class="meta">El cliente de origen quedara inactivo. Licencias, pagos y datos cloud se conservaran en el cliente principal.</p>
        </div>
    </div>

    <div class="stats-grid" style="margin-top:20px;">
        <div class="stat-card"><span>Licencias</span><strong><?= (int) $sourceCounts['licenses'] ?></strong></div>
        <div class="stat-card"><span>Pagos</span><strong><?= (int) $sourceCounts['payments'] ?></strong></div>
        <div class="stat-card"><span>Instalaciones</span><strong><?= (int) $sourceCounts['client_installations'] ?></strong></div>
        <div class="stat-card"><span>Eventos cloud</span><strong><?= (int) $sourceCounts['cloud_sync_events'] ?></strong></div>
    </div>
</div>

<form method="post" class="card" style="margin-top:20px;">
    <?= csrf_input() ?>
    <input type="hidden" name="source_client_id" value="<?= $sourceClientId ?>">

    <div class="form-grid">
        <label class="full">
            Cliente principal
            <select name="target_client_id" required>
                <option value="">Seleccionar cliente</option>
                <?php foreach ($targetClients as $target): ?>
                    <?php $targetLabel = (string) ($target['trade_name'] ?: $target['legal_name']); ?>
                    <option value="<?= (int) $target['id'] ?>" <?= (int) $form['target_client_id'] === (int) $target['id'] ? 'selected' : '' ?>><?= e($targetLabel) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Nombre de la sucursal principal
            <input type="text" name="target_branch_name" value="<?= e($form['target_branch_name']) ?>" required>
        </label>
        <label>
            Codigo de la sucursal principal
            <input type="text" name="target_branch_code" value="<?= e($form['target_branch_code']) ?>" required>
        </label>
        <label>
            Nombre de esta sucursal
            <input type="text" name="source_branch_name" value="<?= e($form['source_branch_name']) ?>" required>
        </label>
        <label>
            Codigo de esta sucursal
            <input type="text" name="source_branch_code" value="<?= e($form['source_branch_code']) ?>" required>
        </label>
        <label class="full">
            Confirmacion
            <input type="text" name="confirmation" autocomplete="off" placeholder="Escribe FUSIONAR" required>
            <span class="meta">La operacion usa una transaccion y se cancela completa ante cualquier conflicto.</span>
        </label>
    </div>

    <div class="actions" style="margin-top:20px;">
        <button type="submit" class="button button--danger">Fusionar clientes</button>
        <a class="button button--ghost" href="<?= e(admin_url('client-view.php?id=' . $sourceClientId)) ?>">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
