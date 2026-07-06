<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_admin_login();

$pageTitle = 'Vencimientos';
$activeNav = 'expirations';
$error = null;
$expiring7 = [];
$expiring15 = [];
$expired = [];

if (request_is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action !== 'send_license_notice') {
        set_flash('error', 'Accion no reconocida.');
        redirect_to(admin_url('expirations.php'));
    }

    $licenseId = (int) ($_POST['license_id'] ?? 0);
    $force = (int) ($_POST['force'] ?? 0) === 1;
    if ($licenseId <= 0) {
        set_flash('error', 'Licencia invalida.');
        redirect_to(admin_url('expirations.php'));
    }

    try {
        $pdo = admin_db();
        $result = send_license_notification_for_license($pdo, $licenseId, $force);
        if (($result['status'] ?? '') === 'sent') {
            set_flash('success', 'Aviso enviado por email.');
        } elseif (($result['status'] ?? '') === 'duplicate') {
            set_flash('warning', 'Ese aviso ya habia sido enviado para este vencimiento.');
        } elseif (($result['error'] ?? '') === 'INVALID_RECIPIENT') {
            set_flash('error', 'El cliente no tiene un email valido cargado.');
        } else {
            set_flash('error', 'No se pudo enviar el aviso por email.');
        }
    } catch (Throwable $e) {
        set_flash('error', admin_public_error($e, 'No se pudo enviar el aviso por email.'));
    }

    redirect_to(admin_url('expirations.php'));
}

function fetch_expirations(PDO $pdo, string $condition): array
{
    $sql = "
        SELECT
            l.*,
            DATEDIFF(l.expires_at, CURDATE()) AS days_left,
            c.legal_name,
            c.trade_name,
            c.email,
            c.phone
        FROM licenses l
        INNER JOIN clients c ON c.id = l.client_id
        WHERE {$condition}
        ORDER BY l.expires_at ASC, c.legal_name ASC
    ";

    return $pdo->query($sql)->fetchAll();
}

function enrich_expiration_notifications(PDO $pdo, array $rows): array
{
    foreach ($rows as &$row) {
        $type = license_notification_type_for($row, false);
        $row['_notification_type'] = $type;
        $row['_notice_sent_at'] = license_notification_last_sent_at($pdo, (int) $row['id'], $type);
    }
    unset($row);

    return $rows;
}

function expiration_message(array $row): string
{
    $clientName = (string) ($row['trade_name'] ?: $row['legal_name']);
    $licenseKey = (string) $row['license_key'];
    $expiresAt = format_date($row['expires_at'] ?? null);
    $currentStatus = license_current_status((string) $row['status'], $row['expires_at'] ?? null);

    if ($currentStatus === 'vencida') {
        return "Hola {$clientName}, te avisamos que la licencia FLUS {$licenseKey} esta vencida desde el {$expiresAt}. Para reactivarla, respondeme este mensaje.";
    }

    return "Hola {$clientName}, te avisamos que la licencia FLUS {$licenseKey} vence el {$expiresAt}. Para renovarla, respondeme este mensaje.";
}

function whatsapp_link(?string $phone, string $message): ?string
{
    $digits = preg_replace('/\D+/', '', (string) $phone);
    if (!$digits) {
        return null;
    }

    return 'https://wa.me/' . $digits . '?' . http_build_query(['text' => $message]);
}

try {
    $pdo = admin_db();

    $activeCondition = "l.status NOT IN ('vencida','suspendida','demo')";
    $expiring7 = enrich_expiration_notifications($pdo, fetch_expirations($pdo, "l.expires_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND {$activeCondition}"));
    $expiring15 = enrich_expiration_notifications($pdo, fetch_expirations($pdo, "l.expires_at BETWEEN DATE_ADD(CURDATE(), INTERVAL 8 DAY) AND DATE_ADD(CURDATE(), INTERVAL 15 DAY) AND {$activeCondition}"));
    $expired = enrich_expiration_notifications($pdo, fetch_expirations($pdo, "l.expires_at < CURDATE() AND l.status NOT IN ('suspendida','demo')"));
} catch (Throwable $e) {
    $error = admin_public_error($e, 'No se pudo cargar el reporte de vencimientos.');
}

function render_expiration_table_legacy(array $rows): void
{
    if (!$rows) {
        echo '<div class="empty-state">Sin registros para este rango.</div>';
        return;
    }
    echo '<div class="table-wrap"><table><thead><tr><th>Cliente</th><th>Email</th><th>Teléfono</th><th>Licencia</th><th>Vence</th><th>Estado</th></tr></thead><tbody>';
    foreach ($rows as $row) {
        $currentStatus = license_current_status($row['status'], $row['expires_at']);
        echo '<tr>';
        echo '<td><strong>' . e($row['legal_name']) . '</strong></td>';
        echo '<td>' . e($row['email'] ?: '—') . '</td>';
        echo '<td>' . e($row['phone'] ?: '—') . '</td>';
        echo '<td>' . e($row['license_key']) . '</td>';
        echo '<td>' . e(format_date($row['expires_at'])) . '</td>';
        echo '<td><span class="badge ' . e(badge_class($currentStatus)) . '">' . e(status_label($currentStatus)) . '</span></td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

function render_expiration_table(array $rows): void
{
    if (!$rows) {
        echo '<div class="empty-state">Sin registros para este rango.</div>';
        return;
    }

    echo '<div class="table-wrap expirations-table"><table><thead><tr><th>Cliente</th><th>Email</th><th>Telefono</th><th>Licencia</th><th>Vence</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>';
    foreach ($rows as $row) {
        $currentStatus = license_current_status($row['status'], $row['expires_at']);
        $message = expiration_message($row);
        $whatsapp = whatsapp_link($row['phone'] ?? null, $message);
        $email = (string) ($row['email'] ?? '');
        $noticeSentAt = $row['_notice_sent_at'] ?? null;

        echo '<tr>';
        echo '<td><strong>' . e($row['legal_name']) . '</strong></td>';
        echo '<td>' . e($row['email'] ?: '-') . '</td>';
        echo '<td>' . e($row['phone'] ?: '-') . '</td>';
        echo '<td>' . e($row['license_key']) . '</td>';
        echo '<td>' . e(format_date($row['expires_at'])) . '</td>';
        echo '<td><span class="badge ' . e(badge_class($currentStatus)) . '">' . e(status_label($currentStatus)) . '</span></td>';
        echo '<td><div class="actions expiration-actions">';
        if ($whatsapp) {
            echo '<a class="button button--ghost button--compact" href="' . e($whatsapp) . '" target="_blank" rel="noopener">WhatsApp</a>';
        }
        if ($noticeSentAt) {
            echo '<span class="notice-status">Email enviado ' . e(format_datetime((string) $noticeSentAt)) . '</span>';
            echo '<form method="post" class="expiration-notice-form">';
            echo csrf_input();
            echo '<input type="hidden" name="action" value="send_license_notice">';
            echo '<input type="hidden" name="license_id" value="' . (int) $row['id'] . '">';
            echo '<input type="hidden" name="force" value="1">';
            echo '<button type="submit" class="button button--ghost button--compact" data-confirm="Reenviar el aviso por email a ' . e($email ?: 'este cliente') . '?">Reenviar email</button>';
            echo '</form>';
        } elseif ($email !== '') {
            echo '<form method="post" class="expiration-notice-form">';
            echo csrf_input();
            echo '<input type="hidden" name="action" value="send_license_notice">';
            echo '<input type="hidden" name="license_id" value="' . (int) $row['id'] . '">';
            echo '<button type="submit" class="button button--ghost button--compact">Enviar email</button>';
            echo '</form>';
        }
        echo '<button type="button" class="button button--ghost button--compact" data-copy="' . e($message) . '" data-copy-label="Mensaje copiado">Copiar</button>';
        echo '</div></td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

require __DIR__ . '/includes/layout-header.php';
?>

<?php if ($error): ?>
    <div class="alert alert--error"><?= e($error) ?></div>
<?php else: ?>
    <section class="grid">
        <article class="card">
            <div class="toolbar">
                <h2>Vencen en los próximos 7 días</h2>
                <span class="badge is-warning"><?= count($expiring7) ?> registros</span>
            </div>
            <?php render_expiration_table($expiring7); ?>
        </article>

        <article class="card">
            <div class="toolbar">
                <h2>Vencen entre 8 y 15 días</h2>
                <span class="badge is-warning"><?= count($expiring15) ?> registros</span>
            </div>
            <?php render_expiration_table($expiring15); ?>
        </article>

        <article class="card">
            <div class="toolbar">
                <h2>Licencias vencidas</h2>
                <span class="badge is-danger"><?= count($expired) ?> registros</span>
            </div>
            <?php render_expiration_table($expired); ?>
        </article>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
