<?php
declare(strict_types=1);
// ============================================================
// FLUS Admin — Ver detalle de cliente v2.0
// ============================================================
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/license-cloud.php';
require_once __DIR__ . '/includes/license-events.php';
require_once __DIR__ . '/includes/cloud-sync.php';
admin_start_session();
require_admin_login();
$pdo = admin_db();
admin_license_events_ensure_schema($pdo);

// Compat helpers (se redefinen en layout-header, aqui para el bloque pre-header)
if (!function_exists('redirect_with_flash')) {
    function redirect_with_flash(string $url, string $type, string $msg): void {
        set_flash($type, $msg); redirect_to($url);
    }
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect_with_flash(admin_url('clients.php'), 'error', 'Cliente no encontrado.');

$stmt = $pdo->prepare('SELECT * FROM clients WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$client = $stmt->fetch();
if (!$client) redirect_with_flash(admin_url('clients.php'), 'error', 'Cliente no encontrado.');

// Licencias del cliente
$licenses_stmt = $pdo->prepare('SELECT * FROM licenses WHERE client_id = ? ORDER BY expires_at DESC');
$licenses_stmt->execute([$id]);
$licenses = $licenses_stmt->fetchAll();

// Todos los pagos
$payments_stmt = $pdo->prepare('
    SELECT p.*, l.license_key FROM payments p
    LEFT JOIN licenses l ON l.id = p.license_id
    WHERE p.client_id = ?
    ORDER BY p.paid_at DESC, p.id DESC
');
$payments_stmt->execute([$id]);
$payments = $payments_stmt->fetchAll();

$license_events = admin_license_events_for_client($pdo, $id, 10);
$cloud_schema_ready = admin_cloud_sync_ensure_schema($pdo);
$cloud_overview = $cloud_schema_ready ? admin_cloud_sync_client_overview($pdo, $id) : [];
$cloud_branches = $cloud_schema_ready ? admin_cloud_sync_client_branches($pdo, $id) : [];
$cloud_stock_overview = $cloud_schema_ready ? admin_cloud_sync_stock_overview($pdo, $id) : [];
$cloud_started_label = format_datetime($cloud_overview['first_seen_at'] ?? null, 'Pendiente');
$cloud_last_seen_label = format_datetime($cloud_overview['last_seen_at'] ?? null, 'Sin contacto');
$cloud_stock_seen_label = format_datetime($cloud_stock_overview['last_synced_at'] ?? null, 'Sin stock');
$cloud_branches_count = (int) ($cloud_overview['active_branches_count'] ?? count($cloud_branches));
$cloud_installations_count = (int) ($cloud_overview['installations_count'] ?? 0);
$cloud_online_count = (int) ($cloud_overview['online_count'] ?? 0);
$cloud_offline_count = max(0, $cloud_installations_count - $cloud_online_count);
$cloud_sales_24h = (int) ($cloud_overview['sales_24h'] ?? 0);
$cloud_stock_attention = (int) ($cloud_stock_overview['sin_stock'] ?? 0) + (int) ($cloud_stock_overview['bajo_minimo'] ?? 0);
$portal_access_roles = [
    'owner' => 'Dueño',
    'manager' => 'Encargado',
    'viewer' => 'Consulta operativa',
];
$client_view_url = admin_url('client-view.php?id=' . $id);
$portal_access_url = $client_view_url . '#portal-access';
$has_cloud_plan = false;
foreach ($licenses as $lic) {
    if (admin_license_plan_cloud_enabled($lic)) {
        $has_cloud_plan = true;
        break;
    }
}

$cloud_health_class = 'is-muted';
$cloud_health_title = 'Cliente local';
$cloud_health_text = 'Este cliente opera sin portal ni sincronizacion web.';
$cloud_next_action = 'Ofrecer Cloud';
$cloud_next_text = 'Si necesita ver sucursales, stock o ventas desde el celular, corresponde un plan Cloud.';
if ($has_cloud_plan && !$cloud_schema_ready) {
    $cloud_health_class = 'is-danger';
    $cloud_health_title = 'Cloud no disponible';
    $cloud_health_text = 'No se pudo preparar el esquema de sincronizacion.';
    $cloud_next_action = 'Revisar sistema';
    $cloud_next_text = 'Validar base de datos y permisos antes de crear accesos.';
} elseif ($has_cloud_plan && $cloud_installations_count <= 0) {
    $cloud_health_class = 'is-warn';
    $cloud_health_title = 'Cloud contratado, sin PC vinculada';
    $cloud_health_text = 'La licencia permite portal, pero todavia no llego una instalacion FLUS.';
    $cloud_next_action = 'Configurar instalacion';
    $cloud_next_text = 'Cargar token cloud en FLUS local y ejecutar la tarea de sincronizacion.';
} elseif ($has_cloud_plan && $cloud_offline_count > 0) {
    $cloud_health_class = 'is-warn';
    $cloud_health_title = 'Sucursal sin contacto';
    $cloud_health_text = $cloud_offline_count . ' instalacion' . ($cloud_offline_count === 1 ? '' : 'es') . ' no reporta en los ultimos minutos.';
    $cloud_next_action = 'Revisar conexion';
    $cloud_next_text = 'Confirmar PC encendida, internet y tarea cloud activa.';
} elseif ($has_cloud_plan && $cloud_stock_attention > 0) {
    $cloud_health_class = 'is-warn';
    $cloud_health_title = 'Stock con atencion';
    $cloud_health_text = $cloud_stock_attention . ' producto' . ($cloud_stock_attention === 1 ? '' : 's') . ' requiere reposicion o revision.';
    $cloud_next_action = 'Ver stock';
    $cloud_next_text = 'Entrar a datos cloud para revisar faltantes y bajo minimo.';
} elseif ($has_cloud_plan) {
    $cloud_health_class = 'is-ok';
    $cloud_health_title = 'Cloud operativo';
    $cloud_health_text = 'El cliente tiene portal y datos sincronizados disponibles.';
    $cloud_next_action = 'Controlar portal';
    $cloud_next_text = 'Mantener accesos y revisar actividad cuando el cliente consulte.';
}

if (request_is_post() && isset($_POST['portal_action'])) {
    verify_csrf();

    if (!$cloud_schema_ready) {
        redirect_with_flash($portal_access_url, 'error', 'No se pudo preparar el esquema del portal.');
    }

    $portalAction = (string) ($_POST['portal_action'] ?? '');

    try {
        if ($portalAction === 'save_access') {
            if (!$has_cloud_plan) {
                throw new RuntimeException('Para crear accesos al portal, el cliente necesita un plan cloud activo.');
            }

            $email = strtolower(trim((string) ($_POST['portal_email'] ?? '')));
            $fullName = trim((string) ($_POST['portal_full_name'] ?? ''));
            $password = (string) ($_POST['portal_password'] ?? '');
            $role = (string) ($_POST['portal_role'] ?? 'owner');
            if (!array_key_exists($role, $portal_access_roles)) {
                $role = 'viewer';
            }

            if ($email === '' || $fullName === '' || $password === '') {
                throw new RuntimeException('Email, nombre y contraseña son obligatorios.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('El email no tiene un formato valido.');
            }
            if (strlen($password) < 10) {
                throw new RuntimeException('La contraseña debe tener al menos 10 caracteres.');
            }

            $conflict = $pdo->prepare("
                SELECT c.trade_name, c.legal_name
                FROM client_portal_users u
                INNER JOIN client_portal_memberships m ON m.user_id = u.id
                INNER JOIN clients c ON c.id = m.client_id
                WHERE u.email = :email
                  AND m.client_id <> :client_id
                  AND m.is_active = 1
                LIMIT 1
            ");
            $conflict->execute(['email' => $email, 'client_id' => $id]);
            $conflictRow = $conflict->fetch();
            if ($conflictRow) {
                $conflictClient = (string) ($conflictRow['trade_name'] ?: $conflictRow['legal_name']);
                throw new RuntimeException('Ese email ya tiene acceso activo al portal de ' . $conflictClient . '.');
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            if (!is_string($passwordHash) || $passwordHash === '') {
                throw new RuntimeException('No se pudo generar la contraseña.');
            }

            $pdo->beginTransaction();
            try {
                $userStmt = $pdo->prepare('
                    INSERT INTO client_portal_users (email, full_name, password_hash, is_active)
                    VALUES (:email, :full_name, :password_hash, 1)
                    ON DUPLICATE KEY UPDATE
                        full_name = VALUES(full_name),
                        password_hash = VALUES(password_hash),
                        is_active = 1,
                        updated_at = NOW()
                ');
                $userStmt->execute([
                    'email' => $email,
                    'full_name' => $fullName,
                    'password_hash' => $passwordHash,
                ]);

                $selectUser = $pdo->prepare('SELECT id FROM client_portal_users WHERE email = :email LIMIT 1');
                $selectUser->execute(['email' => $email]);
                $portalUserId = (int) $selectUser->fetchColumn();
                if ($portalUserId <= 0) {
                    throw new RuntimeException('No se pudo obtener el usuario del portal.');
                }

                $membershipStmt = $pdo->prepare('
                    INSERT INTO client_portal_memberships (user_id, client_id, role, is_active)
                    VALUES (:user_id, :client_id, :role, 1)
                    ON DUPLICATE KEY UPDATE
                        role = VALUES(role),
                        is_active = 1,
                        updated_at = NOW()
                ');
                $membershipStmt->execute([
                    'user_id' => $portalUserId,
                    'client_id' => $id,
                    'role' => $role,
                ]);

                $pdo->commit();
            } catch (Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }

            redirect_with_flash($portal_access_url, 'success', 'Acceso del portal guardado correctamente.');
        }

        if ($portalAction === 'activate_access' || $portalAction === 'deactivate_access') {
            if ($portalAction === 'activate_access' && !$has_cloud_plan) {
                throw new RuntimeException('No se puede activar el portal sin un plan cloud activo.');
            }

            $membershipId = (int) ($_POST['membership_id'] ?? 0);
            $lookup = $pdo->prepare('
                SELECT m.id, m.user_id, u.email
                FROM client_portal_memberships m
                INNER JOIN client_portal_users u ON u.id = m.user_id
                WHERE m.id = :membership_id
                  AND m.client_id = :client_id
                LIMIT 1
            ');
            $lookup->execute(['membership_id' => $membershipId, 'client_id' => $id]);
            $membership = $lookup->fetch();
            if (!$membership) {
                throw new RuntimeException('Acceso del portal no encontrado para este cliente.');
            }

            $targetActive = $portalAction === 'activate_access' ? 1 : 0;
            $update = $pdo->prepare('UPDATE client_portal_memberships SET is_active = :active, updated_at = NOW() WHERE id = :id AND client_id = :client_id');
            $update->execute(['active' => $targetActive, 'id' => $membershipId, 'client_id' => $id]);
            if ($targetActive === 1) {
                $pdo->prepare('UPDATE client_portal_users SET is_active = 1, updated_at = NOW() WHERE id = :id')
                    ->execute(['id' => (int) $membership['user_id']]);
            }

            redirect_with_flash($portal_access_url, 'success', $targetActive ? 'Acceso del portal activado.' : 'Acceso del portal desactivado.');
        }

        if ($portalAction === 'reset_password') {
            $membershipId = (int) ($_POST['membership_id'] ?? 0);
            $newPassword = (string) ($_POST['new_password'] ?? '');
            if (strlen($newPassword) < 10) {
                throw new RuntimeException('La nueva contraseña debe tener al menos 10 caracteres.');
            }

            $lookup = $pdo->prepare('
                SELECT m.id, m.user_id, u.email
                FROM client_portal_memberships m
                INNER JOIN client_portal_users u ON u.id = m.user_id
                WHERE m.id = :membership_id
                  AND m.client_id = :client_id
                LIMIT 1
            ');
            $lookup->execute(['membership_id' => $membershipId, 'client_id' => $id]);
            $membership = $lookup->fetch();
            if (!$membership) {
                throw new RuntimeException('Acceso del portal no encontrado para este cliente.');
            }

            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            if (!is_string($passwordHash) || $passwordHash === '') {
                throw new RuntimeException('No se pudo generar la nueva contraseña.');
            }

            $pdo->prepare('UPDATE client_portal_users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id')
                ->execute(['password_hash' => $passwordHash, 'id' => (int) $membership['user_id']]);

            redirect_with_flash($portal_access_url, 'success', 'Contraseña del portal actualizada.');
        }

        redirect_with_flash($portal_access_url, 'error', 'Accion del portal no reconocida.');
    } catch (Throwable $e) {
        redirect_with_flash($portal_access_url, 'error', $e->getMessage());
    }
}

$portal_accesses = [];
if ($cloud_schema_ready) {
    $portalAccessStmt = $pdo->prepare('
        SELECT
            m.id AS membership_id,
            m.role,
            m.is_active AS membership_active,
            m.created_at AS membership_created_at,
            m.updated_at AS membership_updated_at,
            u.id AS user_id,
            u.email,
            u.full_name,
            u.is_active AS user_active,
            u.last_login_at
        FROM client_portal_memberships m
        INNER JOIN client_portal_users u ON u.id = m.user_id
        WHERE m.client_id = :client_id
        ORDER BY m.is_active DESC, u.full_name ASC, u.email ASC
    ');
    $portalAccessStmt->execute(['client_id' => $id]);
    $portal_accesses = $portalAccessStmt->fetchAll();
}

// Métricas financieras
$r = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE client_id = ?');
$r->execute([$id]); $total_paid = (float)$r->fetchColumn();

$r = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE client_id = ? AND paid_at >= DATE_FORMAT(CURDATE(),"%Y-%m-01")');
$r->execute([$id]); $paid_this_month = (float)$r->fetchColumn();

$r = $pdo->prepare('SELECT COUNT(*) FROM payments WHERE client_id = ?');
$r->execute([$id]); $total_payments = (int)$r->fetchColumn();

$r = $pdo->prepare('SELECT MAX(paid_at) FROM payments WHERE client_id = ?');
$r->execute([$id]); $last_payment_date = $r->fetchColumn();

$avg_payment = $total_payments > 0 ? $total_paid / $total_payments : 0;

// Licencia activa
$active_license = null;
foreach ($licenses as $lic) {
    if ($lic['status'] === 'activa') { $active_license = $lic; break; }
}
if (!$active_license && !empty($licenses)) $active_license = $licenses[0];

$client_activity = [];
foreach (array_slice($payments, 0, 8) as $payment) {
    $client_activity[] = [
        'date' => (string) ($payment['paid_at'] ?? ''),
        'type' => 'payment',
        'title' => 'Pago registrado',
        'meta' => (($payment['period_from'] && $payment['period_to'])
            ? format_date($payment['period_from']) . ' a ' . format_date($payment['period_to'])
            : 'Sin periodo asociado'),
        'amount' => format_money($payment['amount'] ?? 0),
    ];
}
foreach ($license_events as $event) {
    $statusChange = trim(status_label((string) ($event['from_status'] ?? '')) . ' -> ' . status_label((string) ($event['to_status'] ?? '')));
    $client_activity[] = [
        'date' => (string) ($event['created_at'] ?? ''),
        'type' => (($event['to_status'] ?? '') === 'suspendida') ? 'alert' : 'license',
        'title' => admin_license_event_label((string) $event['event_type']),
        'meta' => trim($statusChange . (($event['reason'] ?? '') ? ' - ' . (string) $event['reason'] : '')),
        'amount' => '',
    ];
}
usort($client_activity, static function (array $a, array $b): int {
    return strtotime($b['date'] ?: '1970-01-01') <=> strtotime($a['date'] ?: '1970-01-01');
});
$client_activity = array_slice($client_activity, 0, 12);

// Mini chart — ingresos últimos 6 meses
$mini_months = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $mini_months[$m] = ['label' => date('M', strtotime("-$i months")), 'total' => 0];
}
$mini_rows = $pdo->prepare("
    SELECT DATE_FORMAT(paid_at,'%Y-%m') AS m, COALESCE(SUM(amount),0) AS total
    FROM payments WHERE client_id = ? AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(paid_at,'%Y-%m')
");
$mini_rows->execute([$id]);
foreach ($mini_rows->fetchAll() as $row) {
    if (isset($mini_months[$row['m']])) $mini_months[$row['m']]['total'] = (float)$row['total'];
}

// Acción: eliminar
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['delete_client'])) {
    csrf_check();
    $has_rel = $pdo->prepare('SELECT COUNT(*) FROM licenses WHERE client_id = ?');
    $has_rel->execute([$id]);
    if ((int)$has_rel->fetchColumn() > 0) {
        flash('error', 'No se puede eliminar un cliente con licencias asociadas.');
    } else {
        $pdo->prepare('DELETE FROM clients WHERE id = ?')->execute([$id]);
        redirect_with_flash(admin_url('clients.php'), 'success', 'Cliente eliminado.');
    }
}

$page_title  = 'Cliente: ' . ($client['trade_name'] ?: $client['legal_name']);
$active_menu = 'clients';
require_once __DIR__ . '/includes/layout-header.php';
?>

<!-- Barra de acciones -->
<div class="action-bar">
  <a href="<?= admin_url('clients.php') ?>" class="btn btn-secondary btn-sm">Clientes</a>
  <a href="<?= admin_url('client-edit.php?id=' . $id) ?>" class="btn btn-primary btn-sm">Editar</a>
  <a href="<?= admin_url('license-edit.php?client_id=' . $id) ?>" class="btn btn-secondary btn-sm">Nueva licencia</a>
  <a href="<?= admin_url('payment-edit.php?client_id=' . $id) ?>" class="btn btn-secondary btn-sm">Nuevo pago</a>
  <form method="POST" action="" class="action-bar__end">
    <?= csrf_field() ?>
    <button type="submit" name="delete_client" value="1" class="btn btn-ghost btn-sm"
            data-confirm="¿Eliminar este cliente? Esta acción no se puede deshacer.">Eliminar</button>
  </form>
</div>

<!-- Grid: datos + finanzas -->
<div class="client-view-grid">

  <!-- Datos del cliente -->
  <div class="detail-card client-view-grid__card">
    <div class="detail-card-header">
      Datos del cliente
      <?= client_status_badge($client['status']) ?>
      <?php if ($active_license): ?>
        <?= license_status_badge($active_license['status']) ?>
      <?php endif; ?>
    </div>
    <div class="detail-grid">
      <div class="detail-field">
        <div class="detail-field-label">Razón social</div>
        <div class="detail-field-value"><?= e($client['legal_name']) ?></div>
      </div>
      <div class="detail-field">
        <div class="detail-field-label">Nombre comercial</div>
        <div class="detail-field-value"><?= e($client['trade_name'] ?: '—') ?></div>
      </div>
      <div class="detail-field">
        <div class="detail-field-label">Email</div>
        <div class="detail-field-value"><a href="mailto:<?= e($client['email']) ?>"><?= e($client['email']) ?></a></div>
      </div>
      <div class="detail-field">
        <div class="detail-field-label">Teléfono</div>
        <div class="detail-field-value">
          <?php if ($client['phone']): ?>
            <a href="tel:<?= e($client['phone']) ?>"><?= e($client['phone']) ?></a>
          <?php else: ?>—<?php endif; ?>
        </div>
      </div>
      <div class="detail-field">
        <div class="detail-field-label">CUIT / DNI</div>
        <div class="detail-field-value"><?= e($client['tax_id'] ?: '—') ?></div>
      </div>
      <div class="detail-field">
        <div class="detail-field-label">Rubro</div>
        <div class="detail-field-value"><?= e($client['business_type'] ?: '—') ?></div>
      </div>
      <div class="detail-field">
        <div class="detail-field-label">Dirección</div>
        <div class="detail-field-value"><?= e($client['address'] ?: '—') ?></div>
      </div>
      <div class="detail-field">
        <div class="detail-field-label">Alta en sistema</div>
        <div class="detail-field-value"><?= format_datetime($client['created_at']) ?></div>
      </div>
      <?php if ($client['internal_notes']): ?>
      <div class="detail-field detail-field--full">
        <div class="detail-field-label">Notas internas</div>
        <div class="detail-field-value detail-field-value--preline"><?= e($client['internal_notes']) ?></div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Resumen financiero -->
  <div class="detail-card client-view-grid__card">
    <div class="detail-card-header">Resumen financiero</div>

    <div class="revenue-summary">
      <div class="revenue-summary-item">
        <div class="revenue-summary-label">Total pagado</div>
        <div class="revenue-summary-value"><?= format_money($total_paid) ?></div>
        <div class="revenue-summary-sub"><?= $total_payments ?> pago<?= $total_payments !== 1 ? 's' : '' ?></div>
      </div>
      <div class="revenue-summary-item">
        <div class="revenue-summary-label">Este mes</div>
        <div class="revenue-summary-value"><?= format_money($paid_this_month) ?></div>
        <div class="revenue-summary-sub"><?= date('M Y') ?></div>
      </div>
      <div class="revenue-summary-item">
        <div class="revenue-summary-label">Ticket promedio</div>
        <div class="revenue-summary-value"><?= format_money($avg_payment) ?></div>
        <div class="revenue-summary-sub">por pago</div>
      </div>
    </div>

    <div class="client-chart-card">
      <div class="client-chart-label">
        Ingresos últimos 6 meses
      </div>
      <div class="client-chart-canvas">
        <canvas id="clientMiniChart"></canvas>
      </div>
    </div>

    <?php if ($last_payment_date): ?>
    <div class="detail-field detail-field--full">
      <div class="detail-field-label">Último pago</div>
      <div class="detail-field-value"><?= format_date($last_payment_date) ?></div>
    </div>
    <?php endif; ?>

    <?php if ($active_license): ?>
    <div class="detail-field detail-field--full">
      <div class="detail-field-label">Licencia vigente</div>
      <div class="detail-field-value">
        <span class="td-mono"><?= e($active_license['license_key']) ?></span>
        <button class="btn btn-secondary btn-xs license-inline-copy" data-copy="<?= e($active_license['license_key']) ?>">Copiar</button>
        <br><span class="detail-field-note">
          <?= plan_type_label($active_license['plan_type']) ?> · vence: <?= format_date($active_license['expires_at']) ?>
        </span>
        <br><span class="badge <?= admin_license_plan_cloud_enabled($active_license) ? 'badge-blue' : 'badge-gray' ?>">
          <?= admin_license_plan_cloud_enabled($active_license) ? 'Cloud habilitado' : 'Solo local' ?>
        </span>
      </div>
    </div>
    <?php endif; ?>
  </div>

</div>

<div class="detail-card">
  <div class="detail-card-header">Actividad del cliente</div>
  <?php if (empty($client_activity)): ?>
    <div class="empty-panel">Sin actividad operativa todavía.</div>
  <?php else: ?>
    <div class="timeline">
      <?php foreach ($client_activity as $item): ?>
        <div class="timeline-item">
          <span class="timeline-dot <?= e($item['type']) ?>"><?= $item['type'] === 'payment' ? '$' : 'L' ?></span>
          <div class="timeline-content">
            <div class="timeline-title"><?= e($item['title']) ?></div>
            <div class="timeline-meta"><?= e($item['meta']) ?> - <?= e(format_datetime($item['date'])) ?></div>
          </div>
          <?php if ($item['amount'] !== ''): ?>
            <div class="timeline-amount"><?= e($item['amount']) ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Sucursales cloud -->
<div class="section-header">
  <div>
    <div class="section-title">Sucursales cloud</div>
    <div class="section-meta">Instalaciones y stock recibido desde FLUS local para este cliente.</div>
  </div>
  <a href="<?= admin_url('cloud-sync.php?client_id=' . $id) ?>" class="btn btn-secondary btn-sm">Ver datos cloud</a>
</div>

<div class="detail-card client-cloud-card">
  <?php if (!$cloud_schema_ready): ?>
    <div class="empty-panel">No se pudo preparar el esquema de sincronizacion cloud.</div>
  <?php elseif (empty($cloud_branches)): ?>
    <div class="client-cloud-overview <?= e($cloud_health_class) ?>">
      <div>
        <span>Situacion cloud</span>
        <strong><?= e($cloud_health_title) ?></strong>
        <small><?= e($cloud_health_text) ?></small>
      </div>
      <div>
        <span>Proximo paso</span>
        <strong><?= e($cloud_next_action) ?></strong>
        <small><?= e($cloud_next_text) ?></small>
      </div>
    </div>
    <div class="client-cloud-empty">
      <strong><?= $has_cloud_plan ? 'Sin sucursales sincronizadas todavia.' : 'Cliente sin plan cloud activo.' ?></strong>
      <span><?= $has_cloud_plan ? 'Cuando una instalacion FLUS envie datos, va a aparecer aca con su sucursal y ultimo contacto. El portal empieza a mostrar informacion desde la activacion cloud.' : 'Si el cliente contrata cloud, creale una licencia cloud y configurale el token en la instalacion local.' ?></span>
    </div>
  <?php else: ?>
    <div class="client-cloud-overview <?= e($cloud_health_class) ?>">
      <div>
        <span>Situacion cloud</span>
        <strong><?= e($cloud_health_title) ?></strong>
        <small><?= e($cloud_health_text) ?></small>
      </div>
      <div>
        <span>Proximo paso</span>
        <strong><?= e($cloud_next_action) ?></strong>
        <small><?= e($cloud_next_text) ?></small>
      </div>
    </div>

    <div class="client-cloud-summary">
      <span><strong><?= e($cloud_started_label) ?></strong>Datos desde</span>
      <span><strong><?= e($cloud_last_seen_label) ?></strong>Ultimo contacto</span>
      <span><strong><?= $cloud_branches_count ?></strong>Sucursales</span>
      <span><strong><?= $cloud_installations_count ?></strong>Instalaciones</span>
      <span><strong><?= $cloud_online_count ?></strong>Online</span>
      <span><strong><?= $cloud_sales_24h ?></strong>Ventas 24 hs</span>
      <span><strong><?= e($cloud_stock_seen_label) ?></strong>Ultimo stock</span>
      <span class="<?= $cloud_stock_attention > 0 ? 'is-warn-text' : '' ?>">
        <strong><?= $cloud_stock_attention ?></strong>Alertas stock
      </span>
    </div>

    <div class="client-cloud-branches">
      <?php foreach ($cloud_branches as $branch): ?>
        <?php $branchOnline = (int) ($branch['online_count'] ?? 0); ?>
        <article class="client-cloud-branch">
          <div>
            <strong><?= e((string) ($branch['branch_name'] ?: 'Sin sucursal')) ?></strong>
            <span><?= e((string) ($branch['branch_code'] ?: 'sin codigo')) ?></span>
          </div>
          <div class="client-cloud-branch__meta">
            <span><?= (int) ($branch['installations_count'] ?? 0) ?> instalacion<?= (int) ($branch['installations_count'] ?? 0) === 1 ? '' : 'es' ?></span>
            <span><?= (int) ($branch['stock_items'] ?? 0) ?> productos stock</span>
            <span>Ultimo contacto: <?= e(format_datetime($branch['last_seen_at'] ?? null)) ?></span>
          </div>
          <span class="badge <?= $branchOnline > 0 ? 'badge-green' : 'badge-yellow' ?>"><?= $branchOnline > 0 ? 'Online' : 'Sin contacto' ?></span>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Accesos portal -->
<div class="section-header" id="portal-access">
  <div>
    <div class="section-title">Accesos al portal</div>
    <div class="section-meta">Usuarios del cliente para consultar ventas, sucursales y stock desde el celular.</div>
  </div>
  <a href="<?= e(preg_replace('#/admin$#', '', admin_url()) . '/portal/login.php') ?>" class="btn btn-secondary btn-sm" target="_blank" rel="noopener">Abrir portal</a>
</div>

<div class="detail-card portal-access-card">
  <?php if (!$has_cloud_plan): ?>
    <div class="alert alert-warning portal-access-warning">
      Este cliente no tiene plan cloud activo. Los accesos al portal se administran solo para clientes Cloud o Cloud multi-sucursal.
    </div>
  <?php endif; ?>

  <div class="portal-access-layout">
    <form method="POST" action="<?= e($portal_access_url) ?>" class="portal-access-form">
      <?= csrf_field() ?>
      <input type="hidden" name="portal_action" value="save_access">

      <div>
        <div class="detail-card-header portal-access-form__header">Crear o actualizar acceso</div>
        <p class="portal-access-note">Dueño y Encargado ven ventas e importes. Consulta operativa solo ve sucursales, conexión y stock.</p>
      </div>

      <label>
        <span>Nombre visible</span>
        <input type="text" name="portal_full_name" placeholder="Ej: Dueño del negocio" <?= !$has_cloud_plan ? 'disabled' : '' ?> required>
      </label>
      <label>
        <span>Email de acceso</span>
        <input type="email" name="portal_email" placeholder="cliente@negocio.com" <?= !$has_cloud_plan ? 'disabled' : '' ?> required>
      </label>
      <label>
        <span>Rol</span>
        <select name="portal_role" <?= !$has_cloud_plan ? 'disabled' : '' ?>>
          <?php foreach ($portal_access_roles as $roleKey => $roleLabel): ?>
            <option value="<?= e($roleKey) ?>"><?= e($roleLabel) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>
        <span>Contraseña inicial</span>
        <input type="password" name="portal_password" minlength="10" placeholder="Mínimo 10 caracteres" <?= !$has_cloud_plan ? 'disabled' : '' ?> required>
      </label>

      <div class="form-actions">
        <button type="submit" class="button" <?= !$has_cloud_plan ? 'disabled' : '' ?>>Guardar acceso</button>
      </div>
    </form>

    <div class="portal-access-list">
      <div class="portal-access-list__header">
        <strong>Usuarios habilitados</strong>
        <span><?= count($portal_accesses) ?> acceso<?= count($portal_accesses) === 1 ? '' : 's' ?></span>
      </div>

      <?php if (!$cloud_schema_ready): ?>
        <div class="empty-panel">No se pudo preparar el esquema del portal.</div>
      <?php elseif (empty($portal_accesses)): ?>
        <div class="empty-panel">Todavía no hay accesos creados para este cliente.</div>
      <?php else: ?>
        <?php foreach ($portal_accesses as $access): ?>
          <?php
            $membershipId = (int) ($access['membership_id'] ?? 0);
            $accessActive = (int) ($access['membership_active'] ?? 0) === 1 && (int) ($access['user_active'] ?? 0) === 1;
            $roleLabel = $portal_access_roles[(string) ($access['role'] ?? '')] ?? ucfirst((string) ($access['role'] ?? 'Consulta'));
          ?>
          <article class="portal-access-row">
            <div class="portal-access-row__main">
              <strong><?= e((string) ($access['full_name'] ?: 'Sin nombre')) ?></strong>
              <span><?= e((string) $access['email']) ?></span>
              <small>Último ingreso: <?= e(format_datetime($access['last_login_at'] ?? null, 'Sin ingresos')) ?></small>
            </div>
            <div class="portal-access-row__state">
              <span class="badge <?= $accessActive ? 'badge-green' : 'badge-gray' ?>"><?= $accessActive ? 'Activo' : 'Inactivo' ?></span>
              <small><?= e($roleLabel) ?></small>
            </div>
            <div class="portal-access-row__actions">
              <form method="POST" action="<?= e($portal_access_url) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="portal_action" value="<?= $accessActive ? 'deactivate_access' : 'activate_access' ?>">
                <input type="hidden" name="membership_id" value="<?= $membershipId ?>">
                <button type="submit" class="button button--ghost button--compact" data-confirm="<?= $accessActive ? 'Desactivar este acceso al portal?' : 'Activar este acceso al portal?' ?>" <?= (!$has_cloud_plan && !$accessActive) ? 'disabled' : '' ?>>
                  <?= $accessActive ? 'Desactivar' : 'Activar' ?>
                </button>
              </form>

              <form method="POST" action="<?= e($portal_access_url) ?>" class="portal-access-reset">
                <?= csrf_field() ?>
                <input type="hidden" name="portal_action" value="reset_password">
                <input type="hidden" name="membership_id" value="<?= $membershipId ?>">
                <input type="password" name="new_password" minlength="10" placeholder="Nueva contraseña" required>
                <button type="submit" class="button button--ghost button--compact" data-confirm="Resetear contraseña de este acceso?">Resetear</button>
              </form>
            </div>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Licencias -->
<div class="section-header">
  <div class="section-title">Licencias</div>
  <a href="<?= admin_url('license-edit.php?client_id=' . $id) ?>" class="btn btn-secondary btn-sm">Agregar</a>
</div>

<div class="table-wrapper table-wrap--mobile-cards section-table">
  <table>
    <thead>
      <tr>
        <th>Clave</th><th>Plan</th><th>Estado</th><th>Cloud</th><th>Inicio</th><th>Vencimiento</th><th>Puestos</th><th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($licenses)): ?>
        <tr class="empty-row"><td colspan="8">Sin licencias.</td></tr>
      <?php else: ?>
        <?php foreach ($licenses as $lic): ?>
          <?php
            $cloudEnabled = admin_license_plan_cloud_enabled($lic);
            $cloudStatus = admin_cloud_status_from_license((string)$lic['status'], $lic['expires_at'] ?? null);
            if (!$cloudEnabled) {
                $cloudClass = 'badge-gray';
                $cloudLabel = 'No incluido';
            } else {
                $cloudClass = match ($cloudStatus) {
                    'suspended' => 'badge-gray',
                    'expired', 'revoked' => 'badge-red',
                    default => 'badge-green',
                };
                $cloudLabel = match ($cloudStatus) {
                    'suspended' => 'Suspendida',
                    'expired' => 'Vencida',
                    'revoked' => 'Revocada',
                    default => 'Activa',
                };
            }
          ?>
          <tr>
            <td data-label="Clave">
              <span class="td-mono"><?= e($lic['license_key']) ?></span>
              <button class="btn btn-secondary btn-xs license-inline-copy" data-copy="<?= e($lic['license_key']) ?>">Copiar</button>
            </td>
            <td data-label="Plan"><?= plan_type_label($lic['plan_type']) ?></td>
            <td data-label="Estado"><?= license_status_badge($lic['status']) ?></td>
            <td data-label="Cloud"><span class="badge <?= e($cloudClass) ?>"><?= e($cloudLabel) ?></span></td>
            <td data-label="Inicio"><?= format_date($lic['starts_at']) ?></td>
            <td data-label="Vencimiento" class="<?= strtotime($lic['expires_at']) < time() ? 'is-danger-text' : '' ?>"><?= format_date($lic['expires_at']) ?></td>
            <td data-label="Puestos"><?= $lic['seats'] ?? '—' ?></td>
            <td data-label="Acciones"><a href="<?= admin_url('license-edit.php?id=' . $lic['id']) ?>" class="btn btn-secondary btn-xs">Editar</a></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Historial de pagos -->
<div class="section-header">
  <div class="section-title">Historial de pagos</div>
  <div class="section-actions">
    <span class="section-meta"><?= $total_payments ?> registros · total: <?= format_money($total_paid) ?></span>
    <a href="<?= admin_url('payment-edit.php?client_id=' . $id) ?>" class="btn btn-secondary btn-sm">Cargar pago</a>
  </div>
</div>

<div class="table-wrapper table-wrap--mobile-cards">
  <table>
    <thead>
      <tr>
        <th>Fecha</th><th>Período</th><th>Monto</th><th>Método</th><th>Licencia</th><th>Referencia</th><th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($payments)): ?>
        <tr class="empty-row"><td colspan="7">Sin pagos registrados.</td></tr>
      <?php else: ?>
        <?php foreach ($payments as $pay): ?>
          <tr>
            <td data-label="Fecha"><?= format_date($pay['paid_at']) ?></td>
            <td data-label="Período" class="td-secondary">
              <?= ($pay['period_from'] && $pay['period_to'])
                  ? format_date($pay['period_from']) . ' – ' . format_date($pay['period_to'])
                  : '—' ?>
            </td>
            <td data-label="Monto"><strong><?= format_money($pay['amount']) ?></strong></td>
            <td data-label="Método"><?= payment_method_label($pay['method']) ?></td>
            <td data-label="Licencia" class="td-mono td-mono--compact"><?= e($pay['license_key'] ?? '—') ?></td>
            <td data-label="Referencia" class="td-secondary"><?= e($pay['reference'] ?? '—') ?></td>
            <td data-label="Acciones"><a href="<?= admin_url('payment-edit.php?id=' . $pay['id']) ?>" class="btn btn-secondary btn-xs">Editar</a></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
(function() {
  const ctx = document.getElementById('clientMiniChart');
  if (!ctx || typeof Chart === 'undefined') return;
  const labels = <?= json_encode(array_column($mini_months, 'label')) ?>;
  const data   = <?= json_encode(array_column($mini_months, 'total')) ?>;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{ data, backgroundColor: 'rgba(0,200,150,.2)', borderColor: '#00c896', borderWidth: 1.5, borderRadius: 3 }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#1e2438', borderColor: '#252d45', borderWidth: 1,
          titleColor: '#e2e6f0', bodyColor: '#8993ad', padding: 8,
          callbacks: { label: c => ' $' + Number(c.parsed.y).toLocaleString('es-AR') }
        }
      },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#4e586d', font:{size:10} } },
        y: { grid: { color: 'rgba(37,45,69,.3)' }, ticks: { color: '#4e586d', font:{size:10}, callback: v => '$'+Number(v).toLocaleString('es-AR') } }
      }
    }
  });
})();
</script>

<?php require_once __DIR__ . '/includes/layout-footer.php'; ?>
