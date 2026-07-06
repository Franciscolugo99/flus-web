<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_admin_login();

$pageTitle = 'Licencias';
$activeNav = 'licenses';
$error = null;
$licenses = [];
$clientId = (int) ($_GET['client_id'] ?? 0);
$q = trim((string) ($_GET['q'] ?? ''));
$filter = trim((string) ($_GET['filter'] ?? 'all'));
$allowedFilters = ['all', 'active', 'expiring', 'expired', 'suspended', 'perpetual'];
if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}
$cloudIntervalMinutes = max(1, (int) ceil(max(30, (int) (admin_config('license', [])['cloud_check_interval_sec'] ?? 300)) / 60));
$eventsAvailable = false;
$summary = [
    'active' => 0,
    'suspended' => 0,
    'expired' => 0,
    'expiring' => 0,
    'perpetual' => 0,
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

    $summarySql = "
        SELECT
            SUM(CASE WHEN l.status NOT IN ('vencida','suspendida') AND l.expires_at >= CURDATE() THEN 1 ELSE 0 END) AS active,
            SUM(CASE WHEN l.status = 'suspendida' THEN 1 ELSE 0 END) AS suspended,
            SUM(CASE WHEN l.expires_at < CURDATE() AND l.status != 'suspendida' THEN 1 ELSE 0 END) AS expired,
            SUM(CASE WHEN l.expires_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY) AND l.status NOT IN ('vencida','suspendida') THEN 1 ELSE 0 END) AS expiring,
            SUM(CASE WHEN LOWER(l.plan_type) LIKE '%perpet%' OR l.expires_at >= '2099-01-01' THEN 1 ELSE 0 END) AS perpetual
        FROM licenses l
    ";
    $summaryParams = [];
    if ($clientId > 0) {
        $summarySql .= ' WHERE l.client_id = :client_id';
        $summaryParams['client_id'] = $clientId;
    }
    $summaryStmt = $pdo->prepare($summarySql);
    $summaryStmt->execute($summaryParams);
    $summaryRow = $summaryStmt->fetch() ?: [];
    foreach (array_keys($summary) as $key) {
        $summary[$key] = (int) ($summaryRow[$key] ?? 0);
    }

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
    $where = [];
    if ($clientId > 0) {
        $where[] = 'l.client_id = :client_id';
        $params['client_id'] = $clientId;
    }
    if ($q !== '') {
        $where[] = '(c.legal_name LIKE :q_legal_name OR c.trade_name LIKE :q_trade_name OR c.email LIKE :q_email OR l.license_key LIKE :q_license_key)';
        $likeQ = '%' . $q . '%';
        $params['q_legal_name'] = $likeQ;
        $params['q_trade_name'] = $likeQ;
        $params['q_email'] = $likeQ;
        $params['q_license_key'] = $likeQ;
    }

    $filterWhere = match ($filter) {
        'active' => "l.status NOT IN ('vencida','suspendida') AND l.expires_at >= CURDATE()",
        'expiring' => "l.expires_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY) AND l.status NOT IN ('vencida','suspendida')",
        'expired' => "l.expires_at < CURDATE() AND l.status != 'suspendida'",
        'suspended' => "l.status = 'suspendida'",
        'perpetual' => "(LOWER(l.plan_type) LIKE '%perpet%' OR l.expires_at >= '2099-01-01')",
        default => '',
    };
    if ($filterWhere !== '') {
        $where[] = $filterWhere;
    }

    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
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
    }
    unset($license);
} catch (Throwable $e) {
    $error = admin_public_error($e, 'No se pudo cargar el listado de licencias.');
}

$filterLabels = [
    'all' => 'Todas',
    'active' => 'Activas',
    'expiring' => 'Por vencer',
    'expired' => 'Vencidas',
    'suspended' => 'Suspendidas',
    'perpetual' => 'Perpetuas',
];
$licenseFilterUrl = static function (string $targetFilter) use ($clientId, $q): string {
    $params = ['filter' => $targetFilter];
    if ($clientId > 0) {
        $params['client_id'] = $clientId;
    }
    if ($q !== '') {
        $params['q'] = $q;
    }

    return admin_url('licenses.php?' . http_build_query($params));
};

require __DIR__ . '/includes/layout-header.php';
?>

<section class="admin-license-panel">
    <div class="admin-license-head">
        <div>
            <span class="section-eyebrow">Licencias</span>
            <h1>Control de licencias FLUS</h1>
            <p>
                <?= $clientId > 0 ? 'Licencias del cliente seleccionado.' : 'Estado cloud, vencimientos y acciones administrativas.' ?>
                Sincroniza cada <?= e((string) $cloudIntervalMinutes) ?> min cuando la instalacion esta online.
            </p>
        </div>
        <a class="button" href="<?= e(admin_url('license-edit.php' . ($clientId ? '?client_id=' . $clientId : ''))) ?>">Nueva licencia</a>
    </div>

    <form method="get" class="license-search license-search--panel" role="search">
        <?php if ($clientId > 0): ?>
            <input type="hidden" name="client_id" value="<?= e((string) $clientId) ?>">
        <?php endif; ?>
        <input type="hidden" name="filter" value="<?= e($filter) ?>">
        <label>
            <span>Buscar licencia</span>
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Cliente, email o clave">
        </label>
        <button class="button button--ghost" type="submit">Buscar</button>
        <?php if ($q !== '' || $filter !== 'all'): ?>
            <a class="button button--ghost" href="<?= e(admin_url('licenses.php' . ($clientId ? '?client_id=' . $clientId : ''))) ?>">Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="license-ops-grid">
        <a class="ops-card <?= $filter === 'active' ? 'is-active' : '' ?>" href="<?= e($licenseFilterUrl('active')) ?>">
            <span class="ops-card__label">Cloud activo</span>
            <strong><?= e((string) $summary['active']) ?></strong>
            <span>Responde active a FLUS</span>
        </a>
        <a class="ops-card ops-card--warn <?= $filter === 'expiring' ? 'is-active' : '' ?>" href="<?= e($licenseFilterUrl('expiring')) ?>">
            <span class="ops-card__label">Por vencer</span>
            <strong><?= e((string) $summary['expiring']) ?></strong>
            <span>Dentro de 15 dias</span>
        </a>
        <a class="ops-card ops-card--danger <?= $filter === 'expired' ? 'is-active' : '' ?>" href="<?= e($licenseFilterUrl('expired')) ?>">
            <span class="ops-card__label">Vencidas</span>
            <strong><?= e((string) $summary['expired']) ?></strong>
            <span>Cloud responde expired</span>
        </a>
        <a class="ops-card ops-card--muted <?= $filter === 'suspended' ? 'is-active' : '' ?>" href="<?= e($licenseFilterUrl('suspended')) ?>">
            <span class="ops-card__label">Suspendidas</span>
            <strong><?= e((string) $summary['suspended']) ?></strong>
            <span>Cloud responde suspended</span>
        </a>
        <a class="ops-card ops-card--info <?= $filter === 'perpetual' ? 'is-active' : '' ?>" href="<?= e($licenseFilterUrl('perpetual')) ?>">
            <span class="ops-card__label">Perpetuas</span>
            <strong><?= e((string) $summary['perpetual']) ?></strong>
            <span>Sin corte automatico</span>
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php elseif (!$licenses): ?>
        <div class="empty-state">
            <?= ($q !== '' || $filter !== 'all') ? 'No hay licencias para la busqueda o filtro seleccionado.' : 'No hay licencias registradas todavia.' ?>
        </div>
    <?php else: ?>
    <div class="table-wrap licenses-table admin-license-table">
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
</section>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
