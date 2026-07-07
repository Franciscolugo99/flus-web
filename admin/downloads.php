<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_admin_login();

$pageTitle = 'Descargas';
$activeNav = 'downloads';
$error = null;
$downloads = [];

try {
    $pdo = admin_db();
    $downloads = $pdo->query('SELECT * FROM downloads ORDER BY uploaded_at DESC, id DESC')->fetchAll();
} catch (Throwable $e) {
    $error = admin_public_error($e, 'No se pudo cargar el listado de descargas.');
}

require __DIR__ . '/includes/layout-header.php';
?>

<section class="card">
    <h2>Archivos internos</h2>
    <p class="meta">
        Instaladores, actualizaciones y archivos administrativos disponibles para el equipo FLUS.
    </p>

    <?php if ($error): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php elseif (!$downloads): ?>
        <div class="empty-state">Todavía no hay archivos cargados.</div>
    <?php else: ?>
        <div class="table-wrap table-wrap--mobile-cards">
            <table>
                <thead>
                    <tr>
                        <th>Archivo</th>
                        <th>Ruta</th>
                        <th>Versión</th>
                        <th>Subido</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($downloads as $download): ?>
                        <tr>
                            <td data-label="Archivo"><?= e($download['file_name']) ?></td>
                            <td data-label="Ruta"><?= e($download['file_path']) ?></td>
                            <td data-label="Versión"><?= e($download['version'] ?: '—') ?></td>
                            <td data-label="Subido"><?= e(format_datetime($download['uploaded_at'])) ?></td>
                            <td data-label="Estado"><span class="badge <?= e(badge_class($download['status'] === 'activo' ? 'activa' : 'inactivo')) ?>"><?= e(ucfirst($download['status'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/layout-footer.php'; ?>
