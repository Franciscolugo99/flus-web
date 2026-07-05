<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_admin_login();

$pageTitle = 'Licencias';
$activeNav = 'licenses';
$error = null;
$licenses = [];
$clientId = (int) ($_GET['client_id'] ?? 0);
$cloudIntervalMinutes = max(1, (int) ceil(max(30, (int) (admin_config('license', [])['cloud_check_interval_sec'] ?? 300)) / 60));
$eventsAvailable = false;
$summary = [
    'active' => 0,
    'suspended' => 0,
    'expired' => 0,
    'expiring' => 0,
];

if (!function_exists('admin_table_exists')) {
    function admin_table_exists(PDO $pdo, string $table): bool
    {
        $allowedTables = ['payments', 'license_notifications', 'license_events'];
        if (!in_array($table, $allowedTables, true)) {
            throw new InvalidArgumentException('Tabla no permitida para operacion de licencia.');
        }

        $stmt = $pdo->prepare('
            SELECT COUNT(*)
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table
        ');
        $stmt->execute(['table' => $table]);

        return (int) $stmt->fetchColumn() > 0;
    }
}

if (!function_exists('admin_count_license_rows')) {
    function admin_count_license_rows(PDO $pdo, string $table, int $licenseId): int
    {
        if (!admin_table_exists($pdo, $table)) {
            return 0;
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$table}` WHERE license_id = :id");
        $stmt->execute(['id' => $licenseId]);

        return (int) $stmt->fetchColumn();
    }
}

if (!function_exists('admin_delete_license_rows')) {
    function admin_delete_license_rows(PDO $pdo, string $table, int $licenseId): int
    {
        if (!admin_table_exists($pdo, $table)) {
            return 0;
        }

        $stmt = $pdo->prepare("DELETE FROM `{$table}` WHERE license_id = :id");
        $stmt->execute(['id' => $licenseId]);

        return $stmt->rowCount();
    }
}

try {
    $pdo = admin_db();
    $eventsAvailable = admin_license_events_ensure_schema($pdo);

    if (request_is_post()) {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');
        $id = (int) ($_POST['id'] ?? 0);

        if ($action === 'set_status') {
            $status = trim((string) ($_POST['status'] ?? ''));
            $reasonPreset = trim((string) ($_POST['reason_preset'] ?? ''));
            $reasonNote = trim((string) ($_POST['reason_note'] ?? ''));
            $reason = trim($reasonPreset . ($reasonNote !== '' ? ': ' . $reasonNote : ''));
            $allowedStatusActions = [
                'activa' => 'Licencia reactivada correctamente.',
                'suspendida' => 'Licencia suspendida correctamente.',
                'vencida' => 'Licencia marcada como vencida.',
            ];

            if ($id <= 0 || !array_key_exists($status, $allowedStatusActions)) {
                set_flash('error', 'Accion de licencia invalida.');
            } else {
                $pdo->beginTransaction();
                try {
                    $select = $pdo->prepare('
                        SELECT l.*, c.legal_name
                        FROM licenses l
                        INNER JOIN clients c ON c.id = l.client_id
                        WHERE l.id = :id
                        FOR UPDATE
                    ');
                    $select->execute(['id' => $id]);
                    $license = $select->fetch();

                    if (!$license) {
                        $pdo->rollBack();
                        set_flash('error', 'Licencia no encontrada.');
                    } elseif ((string) $license['status'] === $status) {
                        $pdo->rollBack();
                        set_flash('warning', 'La licencia ya tenia ese estado.');
                    } else {
                        $update = $pdo->prepare('UPDATE licenses SET status = :status, updated_at = NOW() WHERE id = :id');
                        $update->execute(['status' => $status, 'id' => $id]);

                        admin_license_event_log(
                            $pdo,
                            (int) $license['id'],
                            (int) $license['client_id'],
                            'status_change',
                            (string) $license['status'],
                            $status,
                            $reason !== '' ? $reason : null,
                            'Cambio manual desde listado de licencias.'
                        );

                        $pdo->commit();
                        set_flash('success', $allowedStatusActions[$status]);
                    }
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $e;
                }
            }
        } elseif ($action === 'delete') {
            if ($id <= 0) {
                set_flash('error', 'Licencia no encontrada.');
            } else {
                $pdo->beginTransaction();
                try {
                    $select = $pdo->prepare('
                        SELECT l.id, l.license_key
                        FROM licenses l
                        WHERE l.id = :id
                        FOR UPDATE
                    ');
                    $select->execute(['id' => $id]);
                    $license = $select->fetch();

                    if (!$license) {
                        $pdo->rollBack();
                        set_flash('error', 'Licencia no encontrada.');
                    } else {
                        $paymentsCount = admin_count_license_rows($pdo, 'payments', $id);

                        if ($paymentsCount > 0) {
                            $pdo->rollBack();
                            set_flash('warning', 'No se puede eliminar la licencia porque tiene pagos registrados. Anulala o suspendela para conservar el historial financiero.');
                        } else {
                            $notificationsDeleted = admin_delete_license_rows($pdo, 'license_notifications', $id);
                            $eventsDeleted = admin_delete_license_rows($pdo, 'license_events', $id);

                            $delete = $pdo->prepare('DELETE FROM licenses WHERE id = :id');
                            $delete->execute(['id' => $id]);

                            $pdo->commit();

                            $cleanupParts = [];
                            if ($notificationsDeleted > 0) {
                                $cleanupParts[] = $notificationsDeleted . ' notificacion(es)';
                            }
                            if ($eventsDeleted > 0) {
                                $cleanupParts[] = $eventsDeleted . ' evento(s)';
                            }

                            $message = 'Licencia eliminada correctamente.';
                            if ($cleanupParts !== []) {
                                $message .= ' Tambien se limpio ' . implode(' y ', $cleanupParts) . ' administrativo(s).';
                            }
                            set_flash('success', $message);
                        }
                    }
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $e;
                }
            }
        } else {
            set_flash('error', 'Accion no registrada.');
        }

        redirect_to(admin_url('licenses.php' . ($clientId ? '?client_id=' . $clientId : '')));
    }

    $eventSelect = $eventsAvailable
        ? 'le_last.last_event_at, le_last.last_event_reason'
        : 'NULL AS last_event_at, NULL AS last_event_reason';
    $eventJoin = $eventsAvailable ? "
        LEFT JOIN (
            SELECT x.license_id, x.created_at AS last_event_at, x.reason AS last_event_reason
            FROM license_events x
            INNER JOIN (
                SELECT license_id, MAX(id) AS last_id
                FROM license_events
                GROUP BY license_id
            ) y ON y.last_id = x.id
        ) le_last ON le_last.license_id = l.id
    " : '';

    $sql = "
        SELECT
            l.*,
            c.legal_name,
            c.email,
            c.phone,
            " . $eventSelect . "
        FROM licenses l
        INNER JOIN clients c ON c.id = l.client_id
        " . $eventJoin . "
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

    $today = new DateTimeImmutable('today');
    foreach ($licenses as &$license) {
        $currentStatus = license_current_status((string) $license['status'], $license['expires_at'] ?? null);
        $cloudStatus = admin_cloud_status_from_license((string) $license['status'], $license['expires_at'] ?? null);
        $expiry = DateTimeImmutable::createFromFormat('Y-m-d', (string) ($license['expires_at'] ?? ''));
        $daysLeft = $expiry ? (int) $today->diff($expiry)->format('%r%a') : null;

        $license['_current_status'] = $currentStatus;
        $license['_cloud_status'] = $cloudStatus;
        $license['_days_left'] = $daysLeft;

        if ($cloudStatus === 'suspended') {
            $summary['suspended']++;
        } elseif ($cloudStatus === 'expired') {
            $summary['expired']++;
        } else {
            $summary['active']++;
        }

        if ($daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 15 && $cloudStatus === 'active') {
            $summary['expiring']++;
        }
    }
    unset($license);
} catch (Throwable $e) {
    $error = admin_public_error($e, 'No se pudo cargar el listado de licencias.');
}

require __DIR__ . '/includes/layout-header.php';
?>

<div class="toolbar toolbar--stack">
    <div>
        <div class="meta">
            <?= $clientId > 0 ? 'Mostrando licencias del cliente seleccionado.' : 'Listado general de licencias.' ?>
        </div>
        <div class="small">FLUS vuelve a consultar el cloud cada <?= e((string) $cloudIntervalMinutes) ?> min aprox. cuando la instalacion esta online.</div>
    </div>
    <a class="button" href="<?= e(admin_url('license-edit.php' . ($clientId ? '?client_id=' . $clientId : ''))) ?>">Nueva licencia</a>
</div>

<div class="license-ops-grid">
    <article class="ops-card">
        <span class="ops-card__label">Cloud activo</span>
        <strong><?= e((string) $summary['active']) ?></strong>
        <span>Responde active a FLUS</span>
    </article>
    <article class="ops-card ops-card--warn">
        <span class="ops-card__label">Por vencer</span>
        <strong><?= e((string) $summary['expiring']) ?></strong>
        <span>Dentro de 15 dias</span>
    </article>
    <article class="ops-card ops-card--danger">
        <span class="ops-card__label">Vencidas</span>
        <strong><?= e((string) $summary['expired']) ?></strong>
        <span>Cloud responde expired</span>
    </article>
    <article class="ops-card ops-card--muted">
        <span class="ops-card__label">Suspendidas</span>
        <strong><?= e((string) $summary['suspended']) ?></strong>
        <span>Cloud responde suspended</span>
    </article>
</div>

<?php if ($error): ?>
    <div class="alert alert--error"><?= e($error) ?></div>
<?php elseif (!$licenses): ?>
    <div class="empty-state">No hay licencias registradas todavia.</div>
<?php else: ?>
    <div class="table-wrap licenses-table">
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Clave</th>
                    <th>Plan</th>
                    <th>Vence</th>
                    <th>Estado</th>
                    <th>Cloud</th>
                    <th>Ultimo cambio</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($licenses as $license): ?>
                    <?php
                        $cloudStatus = (string) $license['_cloud_status'];
                        $currentStatus = (string) $license['_current_status'];
                        $daysLeft = $license['_days_left'];
                        $cloudBadge = match ($cloudStatus) {
                            'suspended' => 'is-muted',
                            'expired', 'revoked' => 'is-danger',
                            default => 'is-success',
                        };
                        $cloudLabel = match ($cloudStatus) {
                            'suspended' => 'Suspendida',
                            'expired' => 'Vencida',
                            'revoked' => 'Revocada',
                            default => 'Activa',
                        };
                    ?>
                    <tr>
                        <td>
                            <strong><?= e($license['legal_name']) ?></strong><br>
                            <span class="meta"><?= e($license['email'] ?: 'Sin email') ?></span>
                        </td>
                        <td>
                            <span class="td-mono"><?= e($license['license_key']) ?></span>
                            <button type="button" class="button button--ghost button--compact" data-copy="<?= e($license['license_key']) ?>">Copiar</button>
                        </td>
                        <td><?= e(status_label((string) $license['plan_type'])) ?></td>
                        <td>
                            <?= e(format_date($license['expires_at'])) ?><br>
                            <span class="meta">
                                <?php if ($daysLeft === null): ?>
                                    Sin calculo
                                <?php elseif ($daysLeft < 0): ?>
                                    <?= e((string) abs($daysLeft)) ?> dias vencida
                                <?php elseif ($daysLeft === 0): ?>
                                    Vence hoy
                                <?php else: ?>
                                    Faltan <?= e((string) $daysLeft) ?> dias
                                <?php endif; ?>
                            </span>
                        </td>
                        <td><span class="badge <?= e(badge_class($currentStatus)) ?>"><?= e(status_label($currentStatus)) ?></span></td>
                        <td>
                            <span class="badge <?= e($cloudBadge) ?>"><?= e($cloudLabel) ?></span><br>
                            <span class="meta"><?= e(admin_cloud_status_message($cloudStatus) ?: 'Operacion habilitada') ?></span>
                        </td>
                        <td>
                            <?= e(format_datetime($license['last_event_at'] ?? null)) ?><br>
                            <span class="meta"><?= e($license['last_event_reason'] ?: 'Sin auditoria reciente') ?></span>
                        </td>
                        <td>
                            <div class="actions license-actions">
                                <a class="button button--ghost" href="<?= e(admin_url('license-edit.php?id=' . (int) $license['id'])) ?>">Editar / renovar</a>
                                <a class="button button--ghost" href="<?= e(admin_url('license-download.php?id=' . (int) $license['id'])) ?>">Descargar</a>
                                <form method="post" class="license-status-form">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="set_status">
                                    <input type="hidden" name="id" value="<?= (int) $license['id'] ?>">
                                    <?php if ($license['status'] === 'suspendida'): ?>
                                        <input type="hidden" name="status" value="activa">
                                        <select name="reason_preset" aria-label="Motivo de reactivacion">
                                            <option value="Pago recibido">Pago recibido</option>
                                            <option value="Gracia comercial">Gracia comercial</option>
                                            <option value="Correccion administrativa">Correccion administrativa</option>
                                        </select>
                                        <input type="text" name="reason_note" placeholder="Nota opcional">
                                        <button type="submit" class="button button--ghost">Reactivar</button>
                                    <?php else: ?>
                                        <input type="hidden" name="status" value="suspendida">
                                        <select name="reason_preset" aria-label="Motivo de suspension">
                                            <option value="Falta de pago">Falta de pago</option>
                                            <option value="Baja solicitada">Baja solicitada</option>
                                            <option value="Incidencia de soporte">Incidencia de soporte</option>
                                            <option value="Revision administrativa">Revision administrativa</option>
                                        </select>
                                        <input type="text" name="reason_note" placeholder="Nota opcional">
                                        <button type="submit" class="button button--danger" data-confirm="Suspender esta licencia? FLUS quedara limitado cuando sincronice.">Suspender</button>
                                    <?php endif; ?>
                                </form>
                                <form method="post" style="display:inline;">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $license['id'] ?>">
                                    <button type="submit" class="button button--danger" data-confirm="Eliminar esta licencia?">Eliminar</button>
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
