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

function fetch_expirations(PDO $pdo, string $condition): array
{
    $sql = "
        SELECT
            l.*,
            c.legal_name,
            c.email,
            c.phone
        FROM licenses l
        INNER JOIN clients c ON c.id = l.client_id
        WHERE {$condition}
        ORDER BY l.expires_at ASC, c.legal_name ASC
    ";

    return $pdo->query($sql)->fetchAll();
}

try {
    $pdo = admin_db();

    $expiring7 = fetch_expirations($pdo, "l.expires_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
    $expiring15 = fetch_expirations($pdo, "l.expires_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 15 DAY)");
    $expired = fetch_expirations($pdo, "l.expires_at < CURDATE()");
} catch (Throwable $e) {
    $error = admin_public_error($e, 'No se pudo cargar el reporte de vencimientos.');
}

function render_expiration_table(array $rows): void
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
                <h2>Vencen en los próximos 15 días</h2>
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
