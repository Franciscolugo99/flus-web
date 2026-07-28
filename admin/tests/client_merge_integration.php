<?php
declare(strict_types=1);

if (getenv('FLUS_ADMIN_TEST_DB') !== '1') {
    fwrite(STDOUT, "SKIP: set FLUS_ADMIN_TEST_DB=1 to run the MariaDB integration test.\n");
    exit(0);
}

require_once __DIR__ . '/../includes/client-merge.php';
require_once __DIR__ . '/../includes/cloud-sync.php';
require_once __DIR__ . '/../includes/client-portal.php';

function test_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$host = getenv('FLUS_ADMIN_TEST_DB_HOST') ?: '127.0.0.1';
$port = (int) (getenv('FLUS_ADMIN_TEST_DB_PORT') ?: 3306);
$user = getenv('FLUS_ADMIN_TEST_DB_USER') ?: 'root';
$pass = getenv('FLUS_ADMIN_TEST_DB_PASS') ?: '';
$dbName = getenv('FLUS_ADMIN_TEST_DB_NAME') ?: ('flus_admin_it_' . bin2hex(random_bytes(4)));
if (strpos($dbName, 'flus_admin_it_') !== 0) {
    throw new RuntimeException('The integration database name must start with flus_admin_it_.');
}

$server = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
$quotedDb = '`' . str_replace('`', '``', $dbName) . '`';
$server->exec("CREATE DATABASE {$quotedDb} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
    if (!is_string($schema) || $schema === '') {
        throw new RuntimeException('Could not read admin/database/schema.sql.');
    }
    $pdo->exec($schema);

    $pdo->exec("INSERT INTO clients (id, legal_name, trade_name, status) VALUES
        (1, 'Owner Test', 'CANAAN', 'activo'),
        (3, 'Owner Test', 'CANAAN 24/7', 'activo')");
    $pdo->exec("INSERT INTO licenses (id, client_id, license_key, status, starts_at, expires_at, plan_type, seats) VALUES
        (7, 3, 'FLUS-AAAA-BBBB-0007', 'activa', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'cloud_mensual', 1),
        (8, 1, 'FLUS-AAAA-BBBB-0008', 'activa', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'cloud_mensual', 1)");
    $pdo->exec("INSERT INTO payments (client_id, license_id, paid_at, period_from, period_to, amount, method) VALUES
        (3, 7, CURDATE(), CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1000, 'transferencia')");
    $pdo->exec("INSERT INTO client_installations (id, client_id, license_id, installation_uid, display_name, status) VALUES
        (10, 1, 8, 'central-installation', 'Central PC', 'online'),
        (20, 3, 7, '247-installation', '24/7 PC', 'online')");
    $pdo->exec("INSERT INTO cloud_sync_events (client_id, installation_id, license_id, event_uid, event_type, occurred_at) VALUES
        (3, 20, 7, 'event-source-1', 'sale', UTC_TIMESTAMP())");
    $pdo->exec("INSERT INTO cloud_sync_stock_items (client_id, installation_id, license_id, product_uid, nombre, stock) VALUES
        (3, 20, 7, 'product-source-1', 'Product Test', 2)");
    $hash = password_hash('temporary-test-password', PASSWORD_DEFAULT);
    $userInsert = $pdo->prepare("INSERT INTO client_portal_users (id, email, password_hash) VALUES (1, 'owner@example.test', :hash)");
    $userInsert->execute(['hash' => $hash]);
    $pdo->exec("INSERT INTO client_portal_memberships (user_id, client_id, role, is_active) VALUES
        (1, 1, 'owner', 1), (1, 3, 'owner', 1)");

    $result = admin_client_merge($pdo, [
        'source_client_id' => 3,
        'target_client_id' => 1,
        'source_branch_name' => 'CANAAN 24/7',
        'source_branch_code' => 'canaan_247',
        'target_branch_name' => 'CANAAN Central',
        'target_branch_code' => 'canaan_central',
    ]);

    test_assert((int) $result['target_client_id'] === 1, 'Wrong target client.');
    test_assert((int) $pdo->query("SELECT COUNT(*) FROM licenses WHERE client_id = 1")->fetchColumn() === 2, 'Licenses were not merged.');
    test_assert((int) $pdo->query("SELECT COUNT(*) FROM payments WHERE client_id = 1")->fetchColumn() === 1, 'Payments were not merged.');
    test_assert((int) $pdo->query("SELECT COUNT(*) FROM client_installations WHERE client_id = 1")->fetchColumn() === 2, 'Installations were not merged.');
    test_assert((int) $pdo->query("SELECT COUNT(*) FROM client_branches WHERE client_id = 1")->fetchColumn() === 2, 'Both branches were not created.');
    test_assert((int) $pdo->query("SELECT COUNT(*) FROM cloud_sync_events WHERE client_id = 1 AND branch_id IS NOT NULL")->fetchColumn() === 1, 'Event branch was not assigned.');
    test_assert((int) $pdo->query("SELECT COUNT(*) FROM cloud_sync_stock_items WHERE client_id = 1 AND branch_id IS NOT NULL")->fetchColumn() === 1, 'Stock branch was not assigned.');
    test_assert((string) $pdo->query("SELECT status FROM clients WHERE id = 3")->fetchColumn() === 'inactivo', 'Source client was not archived.');
    test_assert((int) $pdo->query("SELECT COUNT(*) FROM client_merge_events WHERE source_client_id = 3 AND target_client_id = 1")->fetchColumn() === 1, 'Merge audit was not stored.');
    test_assert((int) $pdo->query("SELECT COUNT(*) FROM client_portal_memberships WHERE client_id = 1 AND is_active = 1")->fetchColumn() === 1, 'Target portal access changed unexpectedly.');
    test_assert((int) $pdo->query("SELECT COUNT(*) FROM client_portal_memberships WHERE client_id = 3 AND is_active = 0")->fetchColumn() === 1, 'Duplicate portal access was not archived.');

    $_SESSION['client_portal_user'] = [
        'id' => 1,
        'email' => 'owner@example.test',
        'full_name' => '',
        'client_id' => 3,
        'client_name' => 'CANAAN 24/7',
        'role' => 'owner',
    ];
    test_assert(portal_refresh_current_session($pdo), 'The stale portal session was not recovered.');
    test_assert((int) ($_SESSION['client_portal_user']['client_id'] ?? 0) === 1, 'The portal session did not move to the active client.');

    $portalBranches = portal_client_branches_summary($pdo, 1);
    test_assert(count($portalBranches) === 2, 'The portal did not return both client branches.');
    $portalBranchNames = array_column($portalBranches, 'branch_name');
    sort($portalBranchNames);
    test_assert($portalBranchNames === ['CANAAN 24/7', 'CANAAN Central'], 'The portal branch names are not the expected ones.');

    $inactiveUserInsert = $pdo->prepare("INSERT INTO client_portal_users (id, email, password_hash) VALUES (2, 'inactive@example.test', :hash)");
    $inactiveUserInsert->execute(['hash' => $hash]);
    $pdo->exec("INSERT INTO client_portal_memberships (user_id, client_id, role, is_active) VALUES (2, 3, 'viewer', 1)");
    test_assert(portal_find_user_membership($pdo, 'inactive@example.test') === null, 'An archived client remained available in the portal.');

    $retryBlocked = false;
    try {
        admin_client_merge($pdo, [
            'source_client_id' => 3,
            'target_client_id' => 1,
            'source_branch_name' => 'CANAAN 24/7',
            'source_branch_code' => 'canaan_247',
            'target_branch_name' => 'CANAAN Central',
            'target_branch_code' => 'canaan_central',
        ]);
    } catch (RuntimeException $e) {
        $retryBlocked = true;
    }
    test_assert($retryBlocked, 'A repeated merge was not blocked.');
    test_assert((int) $pdo->query("SELECT COUNT(*) FROM client_merge_events")->fetchColumn() === 1, 'Retry duplicated the audit event.');

    fwrite(STDOUT, "OK: client merge transaction, branches, audit and retry guard.\n");
} finally {
    if (getenv('FLUS_ADMIN_TEST_DB_KEEP') !== '1') {
        $server->exec("DROP DATABASE IF EXISTS {$quotedDb}");
    }
}
