<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_admin_login();

$pdo = null;
$error = null;
$errors = [];
$id = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
$preselectedClientId = (int) ($_GET['client_id'] ?? 0);
$pageTitle = $isEdit ? 'Editar pago' : 'Cargar pago';
$activeNav = 'payments';
$clients = [];
$licenses = [];

$payment = [
    'client_id' => $preselectedClientId ?: '',
    'license_id' => '',
    'paid_at' => date('Y-m-d'),
    'period_from' => date('Y-m-d'),
    'period_to' => date('Y-m-d', strtotime('+30 days')),
    'amount' => '',
    'method' => 'transferencia',
    'reference' => '',
    'internal_notes' => '',
];

try {
    $pdo = admin_db();

    $clients = $pdo->query('SELECT id, legal_name FROM clients ORDER BY legal_name ASC')->fetchAll();
    $licenses = $pdo->query('
        SELECT l.id, l.license_key, c.legal_name
        FROM licenses l
        INNER JOIN clients c ON c.id = l.client_id
        ORDER BY c.legal_name ASC, l.license_key ASC
    ')->fetchAll();

    if ($isEdit) {
        $stmt = $pdo->prepare('SELECT * FROM payments WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $found = $stmt->fetch();

        if (!$found) {
            set_flash('error', 'Pago no encontrado.');
            redirect_to(admin_url('payments.php'));
        }

        $payment = $found;
    }

    if (request_is_post()) {
        verify_csrf();

        $data = [
            'client_id' => (int) ($_POST['client_id'] ?? 0),
            'license_id' => (int) ($_POST['license_id'] ?? 0),
            'paid_at' => trim((string) ($_POST['paid_at'] ?? '')),
            'period_from' => trim((string) ($_POST['period_from'] ?? '')),
            'period_to' => trim((string) ($_POST['period_to'] ?? '')),
            'amount' => trim((string) ($_POST['amount'] ?? '')),
            'method' => trim((string) ($_POST['method'] ?? 'transferencia')),
            'reference' => trim((string) ($_POST['reference'] ?? '')),
            'internal_notes' => trim((string) ($_POST['internal_notes'] ?? '')),
        ];

        if ($data['client_id'] <= 0) {
            $errors[] = 'Debés seleccionar un cliente.';
        }

        if (!array_key_exists($data['method'], admin_payment_methods())) {
            $errors[] = 'El método de pago no es válido.';
        }

        if ($data['paid_at'] === '' || $data['period_from'] === '' || $data['period_to'] === '') {
            $errors[] = 'La fecha de pago y el período abonado son obligatorios.';
        } elseif (strtotime($data['period_to']) < strtotime($data['period_from'])) {
            $errors[] = 'El período abonado hasta no puede ser anterior al período desde.';
        }

        if ($data['amount'] === '' || !is_numeric($data['amount']) || (float) $data['amount'] <= 0) {
            $errors[] = 'El monto debe ser numérico y mayor a cero.';
        }

        $payment = array_merge($payment, $data);

        if (!$errors) {
            if ($isEdit) {
                $sql = "
                    UPDATE payments
                    SET client_id = :client_id,
                        license_id = :license_id,
                        paid_at = :paid_at,
                        period_from = :period_from,
                        period_to = :period_to,
                        amount = :amount,
                        method = :method,
                        reference = :reference,
                        internal_notes = :internal_notes
                    WHERE id = :id
                ";
            } else {
                $sql = "
                    INSERT INTO payments (
                        client_id, license_id, paid_at, period_from, period_to, amount, method, reference, internal_notes
                    ) VALUES (
                        :client_id, :license_id, :paid_at, :period_from, :period_to, :amount, :method, :reference, :internal_notes
                    )
                ";
            }

            $stmt = $pdo->prepare($sql);
            $params = [
                'client_id' => $data['client_id'],
                'license_id' => $data['license_id'] > 0 ? $data['license_id'] : null,
                'paid_at' => $data['paid_at'],
                'period_from' => $data['period_from'],
                'period_to' => $data['period_to'],
                'amount' => (float) $data['amount'],
                'method' => $data['method'],
                'reference' => normalize_nullable($data['reference']),
                'internal_notes' => normalize_nullable($data['internal_notes']),
            ];

            if ($isEdit) {
                $params['id'] = $id;
            }

            $stmt->execute($params);

            set_flash('success', $isEdit ? 'Pago actualizado correctamente.' : 'Pago cargado correctamente.');
            redirect_to(admin_url('payments.php'));
        }

    }
} catch (Throwable $e) {
    $error = admin_public_error($e, 'No se pudo guardar el pago.');
}

require __DIR__ . '/includes/layout-header.php';
?>

<?php if ($error): ?>
    <div class="alert alert--error"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="alert alert--error"><?= e(implode(' ', $errors)) ?></div>
<?php endif; ?>

<form method="post" class="card">
    <?= csrf_input() ?>

    <div class="form-grid">
        <label>
            Cliente *
            <select name="client_id" required>
                <option value="">Seleccionar cliente</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?= (int) $client['id'] ?>" <?= (int) $payment['client_id'] === (int) $client['id'] ? 'selected' : '' ?>>
                        <?= e($client['legal_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Licencia asociada
            <select name="license_id">
                <option value="">Sin asociar</option>
                <?php foreach ($licenses as $license): ?>
                    <option value="<?= (int) $license['id'] ?>" <?= (int) $payment['license_id'] === (int) $license['id'] ? 'selected' : '' ?>>
                        <?= e($license['legal_name'] . ' · ' . $license['license_key']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Fecha de pago *
            <input type="date" name="paid_at" value="<?= e($payment['paid_at']) ?>" required>
        </label>

        <label>
            Método de pago *
            <select name="method">
                <?php foreach (admin_payment_methods() as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $payment['method'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Período abonado desde *
            <input type="date" name="period_from" value="<?= e($payment['period_from']) ?>" required>
        </label>

        <label>
            Período abonado hasta *
            <input type="date" name="period_to" value="<?= e($payment['period_to']) ?>" required>
        </label>

        <label>
            Monto *
            <input type="number" name="amount" step="0.01" min="0.01" value="<?= e((string) $payment['amount']) ?>" required>
        </label>

        <label>
            Referencia / comprobante
            <input type="text" name="reference" value="<?= e($payment['reference']) ?>">
        </label>

        <label class="full">
            Notas internas
            <textarea name="internal_notes"><?= e($payment['internal_notes']) ?></textarea>
        </label>
    </div>

    <div class="actions" style="margin-top:20px;">
        <button type="submit"><?= $isEdit ? 'Guardar pago' : 'Registrar pago' ?></button>
        <a class="button button--ghost" href="<?= e(admin_url('payments.php')) ?>">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
