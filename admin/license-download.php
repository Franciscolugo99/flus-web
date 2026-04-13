<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_admin_login();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    set_flash('error', 'Licencia no encontrada.');
    redirect_to(admin_url('licenses.php'));
}

try {
    $pdo = admin_db();
    $stmt = $pdo->prepare('
        SELECT
            l.*,
            c.legal_name,
            c.trade_name,
            c.email,
            c.tax_id
        FROM licenses l
        INNER JOIN clients c ON c.id = l.client_id
        WHERE l.id = :id
        LIMIT 1
    ');
    $stmt->execute(['id' => $id]);
    $license = $stmt->fetch();

    if (!$license) {
        set_flash('error', 'Licencia no encontrada.');
        redirect_to(admin_url('licenses.php'));
    }

    $document = build_signed_license_document($license);
    $json = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('No se pudo generar el archivo de licencia.');
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . license_file_name($license) . '"');
    header('X-Content-Type-Options: nosniff');
    echo $json;
    exit;
} catch (Throwable $e) {
    error_log('[FLUS Admin] license-download: ' . $e->getMessage());
    set_flash('error', 'No se pudo generar la licencia. Revisá que exista la clave privada en admin/config.');
    redirect_to(admin_url('licenses.php'));
}
