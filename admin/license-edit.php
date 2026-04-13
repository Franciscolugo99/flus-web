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
$pageTitle = $isEdit ? 'Editar / renovar licencia' : 'Nueva licencia';
$activeNav = 'licenses';
$clients = [];

$license = [
    'client_id' => $preselectedClientId ?: '',
    'license_key' => '',
    'status' => 'activa',
    'starts_at' => date('Y-m-d'),
    'expires_at' => date('Y-m-d', strtotime('+30 days')),
    'plan_type' => 'mensual',
    'seats' => '',
    'internal_notes' => '',
];

try {
    $pdo = admin_db();

    $clients = $pdo->query("SELECT id, legal_name FROM clients ORDER BY legal_name ASC")->fetchAll();

    if ($isEdit) {
        $stmt = $pdo->prepare('SELECT * FROM licenses WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $found = $stmt->fetch();

        if (!$found) {
            set_flash('error', 'Licencia no encontrada.');
            redirect_to(admin_url('licenses.php'));
        }

        $license = $found;
    }

    if (request_is_post()) {
        verify_csrf();

        $data = [
            'client_id' => (int) ($_POST['client_id'] ?? 0),
            'status' => trim((string) ($_POST['status'] ?? 'activa')),
            'starts_at' => trim((string) ($_POST['starts_at'] ?? '')),
            'expires_at' => trim((string) ($_POST['expires_at'] ?? '')),
            'plan_type' => trim((string) ($_POST['plan_type'] ?? 'mensual')),
            'seats' => trim((string) ($_POST['seats'] ?? '')),
            'internal_notes' => trim((string) ($_POST['internal_notes'] ?? '')),
        ];

        if ($data['client_id'] <= 0) {
            $errors[] = 'Debés seleccionar un cliente.';
        }

        if (!array_key_exists($data['status'], admin_license_statuses())) {
            $errors[] = 'El estado seleccionado no es válido.';
        }

        if ($data['starts_at'] === '' || $data['expires_at'] === '') {
            $errors[] = 'Las fechas de inicio y vencimiento son obligatorias.';
        } elseif (strtotime($data['expires_at']) < strtotime($data['starts_at'])) {
            $errors[] = 'La fecha de vencimiento no puede ser anterior a la fecha de inicio.';
        }

        if ($data['plan_type'] === '') {
            $errors[] = 'El tipo de plan es obligatorio.';
        }

        $license = array_merge($license, $data);

        if (!$errors) {
            if ($isEdit) {
                $sql = "
                    UPDATE licenses
                    SET client_id = :client_id,
                        status = :status,
                        starts_at = :starts_at,
                        expires_at = :expires_at,
                        plan_type = :plan_type,
                        seats = :seats,
                        internal_notes = :internal_notes
                    WHERE id = :id
                ";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'client_id' => $data['client_id'],
                    'status' => $data['status'],
                    'starts_at' => $data['starts_at'],
                    'expires_at' => $data['expires_at'],
                    'plan_type' => $data['plan_type'],
                    'seats' => $data['seats'] === '' ? null : (int) $data['seats'],
                    'internal_notes' => normalize_nullable($data['internal_notes']),
                    'id' => $id,
                ]);

                set_flash('success', 'Licencia actualizada correctamente.');
            } else {
                $licenseKey = generate_license_key($pdo);
                $sql = "
                    INSERT INTO licenses (
                        client_id, license_key, status, starts_at, expires_at, plan_type, seats, internal_notes
                    ) VALUES (
                        :client_id, :license_key, :status, :starts_at, :expires_at, :plan_type, :seats, :internal_notes
                    )
                ";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'client_id' => $data['client_id'],
                    'license_key' => $licenseKey,
                    'status' => $data['status'],
                    'starts_at' => $data['starts_at'],
                    'expires_at' => $data['expires_at'],
                    'plan_type' => $data['plan_type'],
                    'seats' => $data['seats'] === '' ? null : (int) $data['seats'],
                    'internal_notes' => normalize_nullable($data['internal_notes']),
                ]);

                set_flash('success', 'Licencia creada correctamente.');
            }

            redirect_to(admin_url('licenses.php'));
        }

    }

    if (!$isEdit && $license['license_key'] === '') {
        $license['license_key'] = 'Se generará automáticamente al guardar';
    }
} catch (Throwable $e) {
    $error = admin_public_error($e, 'No se pudo guardar la licencia.');
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
                    <option value="<?= (int) $client['id'] ?>" <?= (int) $license['client_id'] === (int) $client['id'] ? 'selected' : '' ?>>
                        <?= e($client['legal_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Clave de licencia
            <input type="text" value="<?= e($license['license_key']) ?>" readonly>
        </label>

        <label>
            Estado
            <select name="status">
                <?php foreach (admin_license_statuses() as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $license['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Tipo de plan *
            <input type="text" name="plan_type" value="<?= e($license['plan_type']) ?>" required>
        </label>

        <label>
            Fecha de inicio *
            <input type="date" name="starts_at" value="<?= e($license['starts_at']) ?>" required>
        </label>

        <label>
            Fecha de vencimiento *
            <input type="date" name="expires_at" value="<?= e($license['expires_at']) ?>" required>
        </label>

        <label>
            Cantidad de puestos/equipos
            <input type="number" min="1" name="seats" value="<?= e((string) $license['seats']) ?>">
        </label>

        <label class="full">
            Notas internas
            <textarea name="internal_notes"><?= e($license['internal_notes']) ?></textarea>
        </label>
    </div>

    <div class="actions" style="margin-top:20px;">
        <button type="submit"><?= $isEdit ? 'Guardar licencia' : 'Crear licencia' ?></button>
        <a class="button button--ghost" href="<?= e(admin_url('licenses.php')) ?>">Cancelar</a>
    </div>
</form>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
