<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_admin_login();

$pdo = admin_db();
$id = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
$pageTitle = $isEdit ? 'Administrar instancia web' : 'Nueva instancia web';
$activeNav = 'web-instances';
$error = null;
$errors = [];
$clients = $pdo->query('SELECT id, legal_name, trade_name, status FROM clients ORDER BY COALESCE(trade_name, legal_name), legal_name')->fetchAll();
$licenses = $pdo->query('SELECT id, client_id, license_key, status, plan_type, expires_at FROM licenses ORDER BY expires_at DESC, id DESC')->fetchAll();
$instance = [
    'client_id' => (int) ($_GET['client_id'] ?? 0),
    'license_id' => 0,
    'slug' => '',
    'primary_domain' => '',
    'status' => 'preparando',
    'provisioning_status' => 'pendiente',
    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_name' => '',
    'db_user' => '',
    'db_password_encrypted' => null,
    'app_version' => '',
    'schema_version' => '',
    'internal_notes' => '',
];
$events = [];

try {
    if ($isEdit) {
        $stmt = $pdo->prepare('SELECT * FROM web_instances WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $found = $stmt->fetch();
        if (!$found) {
            set_flash('error', 'Instancia web no encontrada.');
            redirect_to(admin_url('web-instances.php'));
        }
        $instance = $found;
    }

    if (request_is_post()) {
        verify_csrf();
        $validation = admin_web_instance_validate($_POST);
        $data = $validation['data'];
        $errors = $validation['errors'];
        $password = (string) ($_POST['db_password'] ?? '');

        if ($password !== '' && strlen($password) < 16) {
            $errors[] = 'La contraseña MySQL debe tener al menos 16 caracteres.';
        }

        $clientStmt = $pdo->prepare('SELECT id FROM clients WHERE id = :id LIMIT 1');
        $clientStmt->execute(['id' => $data['client_id']]);
        if (!$clientStmt->fetchColumn()) $errors[] = 'El cliente seleccionado no existe.';

        if ($data['license_id'] > 0) {
            $licenseStmt = $pdo->prepare('SELECT id FROM licenses WHERE id = :id AND client_id = :client_id LIMIT 1');
            $licenseStmt->execute(['id' => $data['license_id'], 'client_id' => $data['client_id']]);
            if (!$licenseStmt->fetchColumn()) $errors[] = 'La licencia no pertenece al cliente seleccionado.';
        }

        $hasStoredCredential = $isEdit && !empty($instance['db_password_encrypted']);
        if ($data['provisioning_status'] !== 'pendiente' && $password === '' && !$hasStoredCredential) {
            $errors[] = 'Carga la contraseña MySQL antes de avanzar la preparación.';
        }
        if ($data['status'] === 'activo' && $data['provisioning_status'] !== 'listo') {
            $errors[] = 'Una instancia solo puede quedar activa cuando su preparación está lista.';
        }

        $duplicateStmt = $pdo->prepare('
            SELECT id FROM web_instances
            WHERE id <> :id
              AND (slug = :slug OR primary_domain = :domain OR (db_host = :db_host AND db_name = :db_name))
            LIMIT 1
        ');
        $duplicateStmt->execute([
            'id' => $id,
            'slug' => $data['slug'],
            'domain' => $data['primary_domain'],
            'db_host' => $data['db_host'],
            'db_name' => $data['db_name'],
        ]);
        if ($duplicateStmt->fetchColumn()) $errors[] = 'El identificador, dominio o base ya esta asignado a otra instancia.';

        $encryptedPassword = null;
        if ($password !== '') {
            try {
                $encryptedPassword = admin_web_instance_encrypt_secret($password);
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        $instance = array_merge($instance, $data);
        if (!$errors) {
            $oldStatus = $isEdit ? (string) ($found['status'] ?? '') : null;
            $pdo->beginTransaction();
            try {
                if ($isEdit) {
                    $stmt = $pdo->prepare('
                        UPDATE web_instances SET
                            client_id = :client_id,
                            license_id = :license_id,
                            slug = :slug,
                            primary_domain = :primary_domain,
                            status = :status,
                            provisioning_status = :provisioning_status,
                            db_host = :db_host,
                            db_port = :db_port,
                            db_name = :db_name,
                            db_user = :db_user,
                            db_password_encrypted = COALESCE(:db_password_encrypted, db_password_encrypted),
                            app_version = :app_version,
                            schema_version = :schema_version,
                            internal_notes = :internal_notes
                        WHERE id = :id
                    ');
                } else {
                    $stmt = $pdo->prepare('
                        INSERT INTO web_instances
                            (client_id, license_id, slug, primary_domain, status, provisioning_status, db_host, db_port, db_name, db_user, db_password_encrypted, app_version, schema_version, internal_notes, created_by_admin_id)
                        VALUES
                            (:client_id, :license_id, :slug, :primary_domain, :status, :provisioning_status, :db_host, :db_port, :db_name, :db_user, :db_password_encrypted, :app_version, :schema_version, :internal_notes, :created_by_admin_id)
                    ');
                }
                $params = [
                    'client_id' => $data['client_id'],
                    'license_id' => $data['license_id'] ?: null,
                    'slug' => $data['slug'],
                    'primary_domain' => $data['primary_domain'],
                    'status' => $data['status'],
                    'provisioning_status' => $data['provisioning_status'],
                    'db_host' => $data['db_host'],
                    'db_port' => $data['db_port'],
                    'db_name' => $data['db_name'],
                    'db_user' => $data['db_user'],
                    'db_password_encrypted' => $encryptedPassword,
                    'app_version' => normalize_nullable($data['app_version']),
                    'schema_version' => normalize_nullable($data['schema_version']),
                    'internal_notes' => normalize_nullable($data['internal_notes']),
                ];
                if ($isEdit) {
                    $params['id'] = $id;
                } else {
                    $params['created_by_admin_id'] = (int) (current_admin()['id'] ?? 0) ?: null;
                }
                $stmt->execute($params);
                if (!$isEdit) $id = (int) $pdo->lastInsertId();

                admin_web_instance_log(
                    $pdo,
                    $id,
                    $data['client_id'],
                    $isEdit ? 'updated' : 'created',
                    $oldStatus,
                    $data['status'],
                    $isEdit ? 'Instancia web actualizada.' : 'Instancia web registrada.',
                    [
                        'provisioning_status' => $data['provisioning_status'],
                        'credential_rotated' => $encryptedPassword !== null,
                    ]
                );
                $pdo->commit();
                set_flash('success', $isEdit ? 'Instancia actualizada correctamente.' : 'Instancia creada. La contraseña quedo cifrada y no volvera a mostrarse.');
                redirect_to(admin_url('web-instance-edit.php?id=' . $id));
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }
        }
    }

    if ($id > 0) {
        $eventStmt = $pdo->prepare('SELECT * FROM web_instance_events WHERE web_instance_id = :id ORDER BY created_at DESC, id DESC LIMIT 20');
        $eventStmt->execute(['id' => $id]);
        $events = $eventStmt->fetchAll();
    }
} catch (Throwable $e) {
    $error = admin_public_error($e, 'No se pudo guardar la instancia web. Ejecuta la migracion y revisa la configuracion de cifrado.');
}

require __DIR__ . '/includes/layout-header.php';
?>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($errors): ?><div class="alert alert-error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>

<div class="alert alert-info">
  Esta etapa registra la instancia y protege la credencial. La base y el usuario se crean manualmente en cPanel hasta validar el flujo completo.
</div>

<form method="post" class="card web-instance-form" autocomplete="off">
  <?= csrf_input() ?>
  <div class="form-grid">
    <label>
      Cliente *
      <select name="client_id" id="web-instance-client" required>
        <option value="">Seleccionar cliente</option>
        <?php foreach ($clients as $client): ?>
          <?php $clientName = (string) ($client['trade_name'] ?: $client['legal_name']); ?>
          <option value="<?= (int) $client['id'] ?>" <?= (int) $instance['client_id'] === (int) $client['id'] ? 'selected' : '' ?>><?= e($clientName) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>
      Licencia
      <select name="license_id" id="web-instance-license">
        <option value="0">Sin asociar</option>
        <?php foreach ($licenses as $license): ?>
          <option value="<?= (int) $license['id'] ?>" data-client-id="<?= (int) $license['client_id'] ?>" <?= (int) $instance['license_id'] === (int) $license['id'] ? 'selected' : '' ?>><?= e($license['license_key'] . ' · ' . $license['plan_type'] . ' · ' . $license['expires_at']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>
      Identificador *
      <input type="text" name="slug" value="<?= e($instance['slug']) ?>" required maxlength="63" pattern="[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?" placeholder="kiosco-centro">
    </label>

    <label>
      Dominio principal *
      <input type="text" name="primary_domain" value="<?= e($instance['primary_domain']) ?>" required placeholder="kiosco-centro.app.flus.com.ar">
    </label>

    <label>
      Estado operativo
      <select name="status">
        <?php foreach (admin_web_instance_statuses() as $value => $label): ?>
          <option value="<?= e($value) ?>" <?= $instance['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>
      Preparación
      <select name="provisioning_status">
        <?php foreach (admin_web_instance_provisioning_statuses() as $value => $label): ?>
          <option value="<?= e($value) ?>" <?= $instance['provisioning_status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>
      Servidor MySQL *
      <input type="text" name="db_host" value="<?= e($instance['db_host']) ?>" required>
    </label>

    <label>
      Puerto MySQL *
      <input type="number" name="db_port" value="<?= (int) $instance['db_port'] ?>" required min="1" max="65535">
    </label>

    <label>
      Nombre de base *
      <input type="text" name="db_name" value="<?= e($instance['db_name']) ?>" required autocomplete="off">
    </label>

    <label>
      Usuario MySQL *
      <input type="text" name="db_user" value="<?= e($instance['db_user']) ?>" required autocomplete="off">
    </label>

    <label class="full web-instance-secret-field">
      <?= $isEdit && !empty($instance['db_password_encrypted']) ? 'Rotar contraseña MySQL' : 'Contraseña MySQL' ?>
      <input type="password" name="db_password" value="" autocomplete="new-password" minlength="16">
      <span class="form-hint">
        <?= $isEdit && !empty($instance['db_password_encrypted']) ? 'Ya existe una credencial cifrada. Deja vacio para conservarla.' : 'Se cifra antes de guardarse y no se mostrara nuevamente.' ?>
      </span>
    </label>

    <label>
      Versión FLUS Web
      <input type="text" name="app_version" value="<?= e($instance['app_version']) ?>" maxlength="40" placeholder="1.0.3">
    </label>

    <label>
      Versión de esquema
      <input type="text" name="schema_version" value="<?= e($instance['schema_version']) ?>" maxlength="40" placeholder="045">
    </label>

    <label class="full">
      Notas internas
      <textarea name="internal_notes"><?= e($instance['internal_notes']) ?></textarea>
    </label>
  </div>

  <div class="actions">
    <button type="submit"><?= $isEdit ? 'Guardar cambios' : 'Registrar instancia' ?></button>
    <a class="button button--ghost" href="<?= e(admin_url('web-instances.php')) ?>">Volver</a>
  </div>
</form>

<script>
(() => {
  const clientSelect = document.getElementById('web-instance-client');
  const licenseSelect = document.getElementById('web-instance-license');
  if (!clientSelect || !licenseSelect) return;

  const syncLicenses = () => {
    const clientId = clientSelect.value;
    Array.from(licenseSelect.options).forEach((option, index) => {
      if (index === 0) return;
      option.disabled = clientId === '' || option.dataset.clientId !== clientId;
    });
    if (licenseSelect.selectedOptions[0]?.disabled) licenseSelect.value = '0';
  };

  clientSelect.addEventListener('change', syncLicenses);
  syncLicenses();
})();
</script>

<?php if ($events): ?>
  <section class="card web-instance-events" aria-labelledby="instance-events-title">
    <h2 id="instance-events-title">Actividad administrativa</h2>
    <div class="table-wrap table-wrap--mobile-cards">
      <table>
        <thead><tr><th>Fecha</th><th>Evento</th><th>Estado</th><th>Actor</th><th>Detalle</th></tr></thead>
        <tbody>
          <?php foreach ($events as $event): ?>
            <tr>
              <td data-label="Fecha"><?= e(format_datetime($event['created_at'])) ?></td>
              <td data-label="Evento"><?= e($event['event_type']) ?></td>
              <td data-label="Estado"><?= e(trim((string) ($event['from_status'] ?? '')) . (($event['from_status'] ?? null) !== ($event['to_status'] ?? null) ? ' → ' . (string) ($event['to_status'] ?? '') : '')) ?></td>
              <td data-label="Actor"><?= e((string) ($event['actor'] ?? 'sistema')) ?></td>
              <td data-label="Detalle"><?= e((string) ($event['message'] ?? '')) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
