<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_admin_login();

$pdo = null;
$error = null;
$errors = [];
$id = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
$pageTitle = $isEdit ? 'Editar cliente' : 'Nuevo cliente';
$activeNav = 'clients';

$client = [
    'legal_name' => '',
    'trade_name' => '',
    'email' => '',
    'phone' => '',
    'tax_id' => '',
    'business_type' => '',
    'address' => '',
    'internal_notes' => '',
    'status' => 'activo',
];

try {
    $pdo = admin_db();

    if ($isEdit) {
        $stmt = $pdo->prepare('SELECT * FROM clients WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $found = $stmt->fetch();

        if (!$found) {
            set_flash('error', 'Cliente no encontrado.');
            redirect_to(admin_url('clients.php'));
        }

        $client = $found;
    }

    if (request_is_post()) {
        verify_csrf();

        $data = [
            'legal_name' => trim((string) ($_POST['legal_name'] ?? '')),
            'trade_name' => trim((string) ($_POST['trade_name'] ?? '')),
            'email' => normalize_email($_POST['email'] ?? null),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'tax_id' => trim((string) ($_POST['tax_id'] ?? '')),
            'business_type' => trim((string) ($_POST['business_type'] ?? '')),
            'address' => trim((string) ($_POST['address'] ?? '')),
            'internal_notes' => trim((string) ($_POST['internal_notes'] ?? '')),
            'status' => trim((string) ($_POST['status'] ?? 'activo')),
        ];

        if ($data['legal_name'] === '') {
            $errors[] = 'La razón social o nombre es obligatoria.';
        }

        if ($data['status'] === '' || !array_key_exists($data['status'], admin_client_statuses())) {
            $errors[] = 'El estado seleccionado no es válido.';
        }

        if (!is_valid_email_optional($data['email'])) {
            $errors[] = 'El email no tiene un formato válido.';
        }

        $client = array_merge($client, $data);

        if (!$errors) {
            if ($isEdit) {
                $sql = "
                    UPDATE clients
                    SET legal_name = :legal_name,
                        trade_name = :trade_name,
                        email = :email,
                        phone = :phone,
                        tax_id = :tax_id,
                        business_type = :business_type,
                        address = :address,
                        internal_notes = :internal_notes,
                        status = :status
                    WHERE id = :id
                ";
            } else {
                $sql = "
                    INSERT INTO clients (
                        legal_name, trade_name, email, phone, tax_id, business_type, address, internal_notes, status
                    ) VALUES (
                        :legal_name, :trade_name, :email, :phone, :tax_id, :business_type, :address, :internal_notes, :status
                    )
                ";
            }

            $stmt = $pdo->prepare($sql);
            $params = [
                'legal_name' => $data['legal_name'],
                'trade_name' => normalize_nullable($data['trade_name']),
                'email' => $data['email'],
                'phone' => normalize_nullable($data['phone']),
                'tax_id' => normalize_nullable($data['tax_id']),
                'business_type' => normalize_nullable($data['business_type']),
                'address' => normalize_nullable($data['address']),
                'internal_notes' => normalize_nullable($data['internal_notes']),
                'status' => $data['status'],
            ];

            if ($isEdit) {
                $params['id'] = $id;
            }

            $stmt->execute($params);

            set_flash('success', $isEdit ? 'Cliente actualizado correctamente.' : 'Cliente creado correctamente.');
            redirect_to(admin_url('clients.php'));
        }

    }
} catch (Throwable $e) {
    $error = admin_public_error($e, 'No se pudo guardar el cliente.');
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
            Nombre / Razón social *
            <input type="text" name="legal_name" value="<?= e($client['legal_name']) ?>" required>
        </label>

        <label>
            Nombre comercial
            <input type="text" name="trade_name" value="<?= e($client['trade_name']) ?>">
        </label>

        <label>
            Email
            <input type="email" name="email" value="<?= e($client['email']) ?>">
        </label>

        <label>
            Teléfono
            <input type="text" name="phone" value="<?= e($client['phone']) ?>">
        </label>

        <label>
            CUIT / DNI
            <input type="text" name="tax_id" value="<?= e($client['tax_id']) ?>">
        </label>

        <label>
            Rubro
            <input type="text" name="business_type" value="<?= e($client['business_type']) ?>">
        </label>

        <label class="full">
            Dirección
            <input type="text" name="address" value="<?= e($client['address']) ?>">
        </label>

        <label>
            Estado
            <select name="status">
                <?php foreach (admin_client_statuses() as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $client['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="full">
            Notas internas
            <textarea name="internal_notes"><?= e($client['internal_notes']) ?></textarea>
        </label>
    </div>

    <div class="actions" style="margin-top:20px;">
        <button type="submit"><?= $isEdit ? 'Guardar cambios' : 'Crear cliente' ?></button>
        <a class="button button--ghost" href="<?= e(admin_url('clients.php')) ?>">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
