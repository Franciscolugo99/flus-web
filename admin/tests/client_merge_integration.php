<?php
declare(strict_types=1);

if (getenv('FLUS_ADMIN_TEST_DB') !== '1') {
    fwrite(STDOUT, "SKIP: set FLUS_ADMIN_TEST_DB=1 to run the MariaDB integration test.\n");
    exit(0);
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
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
    $pdo->exec('ALTER TABLE client_portal_memberships DROP COLUMN branch_scope');
    $branchScopeMigration = file_get_contents(__DIR__ . '/../database/client_portal_membership_branches.sql');
    if (!is_string($branchScopeMigration) || $branchScopeMigration === '') {
        throw new RuntimeException('Could not read the portal branch scope migration.');
    }
    $pdo->exec($branchScopeMigration);
    $pdo->exec($branchScopeMigration);

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

    $centralBranchId = (int) $pdo->query("SELECT id FROM client_branches WHERE client_id = 1 AND code = 'canaan_central'")->fetchColumn();
    $branch247Id = (int) $pdo->query("SELECT id FROM client_branches WHERE client_id = 1 AND code = 'canaan_247'")->fetchColumn();
    test_assert($centralBranchId > 0 && $branch247Id > 0, 'Expected branch ids were not created.');

    $centralInstallationId = admin_cloud_sync_upsert_installation(
        $pdo,
        ['id' => 8, 'client_id' => 1],
        'central-installation',
        null,
        ['display_name' => 'Central PC', 'app_version' => '4.2.5']
    );
    test_assert(
        admin_cloud_sync_installation_branch_id($pdo, 1, $centralInstallationId) === $centralBranchId,
        'A sync without branch erased the existing installation branch.'
    );
    admin_cloud_sync_upsert_installation(
        $pdo,
        ['id' => 8, 'client_id' => 1],
        'central-installation',
        $branch247Id,
        ['display_name' => 'Central PC', 'app_version' => '4.2.5']
    );
    test_assert(
        admin_cloud_sync_installation_branch_id($pdo, 1, $centralInstallationId) === $centralBranchId,
        'A stale kiosk branch replaced the server-side assignment.'
    );

    $effectiveBranchId = admin_cloud_sync_installation_branch_id($pdo, 1, $centralInstallationId);
    admin_cloud_sync_store_events($pdo, ['id' => 8, 'client_id' => 1], $centralInstallationId, $effectiveBranchId, [[
        'event_uid' => 'stock-after-merge-1',
        'event_type' => 'stock.snapshot',
        'payload' => [
            'products' => [[
                'product_uid' => 'product-after-merge-1',
                'nombre' => 'Product after merge',
                'stock' => 3,
            ]],
        ],
    ]]);
    test_assert((int) $pdo->query("SELECT branch_id FROM cloud_sync_events WHERE event_uid = 'stock-after-merge-1'")->fetchColumn() === $centralBranchId, 'A new event lost the preserved branch.');
    test_assert((int) $pdo->query("SELECT branch_id FROM cloud_sync_stock_items WHERE product_uid = 'product-after-merge-1'")->fetchColumn() === $centralBranchId, 'A stock snapshot lost the preserved branch.');

    $saleInsert = $pdo->prepare("
        INSERT INTO cloud_sync_events
            (client_id, branch_id, installation_id, license_id, event_uid, event_type, occurred_at, received_at, summary_json)
        VALUES
            (1, :branch_id, :installation_id, :license_id, :event_uid, 'sale.created', UTC_TIMESTAMP(), UTC_TIMESTAMP(), :summary_json)
    ");
    $saleInsert->execute([
        'branch_id' => $centralBranchId,
        'installation_id' => 10,
        'license_id' => 8,
        'event_uid' => 'sale-central-filter-1',
        'summary_json' => json_encode(['venta_id' => 101, 'total' => 1200, 'items_count' => 2, 'medio_pago' => 'efectivo']),
    ]);
    $saleInsert->execute([
        'branch_id' => $branch247Id,
        'installation_id' => 20,
        'license_id' => 7,
        'event_uid' => 'sale-247-filter-1',
        'summary_json' => json_encode(['venta_id' => 202, 'total' => 3400, 'items_count' => 3, 'medio_pago' => 'debito']),
    ]);
    $pdo->exec("
        INSERT INTO cloud_sync_events
            (client_id, branch_id, installation_id, license_id, event_uid, event_type, occurred_at, received_at, summary_json)
        VALUES
            (1, {$centralBranchId}, 10, 8, 'sale-outside-period-1', 'sale.created', DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 DAY), DATE_SUB(UTC_TIMESTAMP(), INTERVAL 2 DAY),
             '{\"venta_id\":99,\"total\":9000,\"items_count\":1,\"medio_pago\":\"efectivo\"}')
    ");

    $allSales = admin_cloud_sync_sales_overview($pdo, 1);
    $centralSales = admin_cloud_sync_sales_overview($pdo, 1, $centralBranchId);
    $branch247Sales = admin_cloud_sync_sales_overview($pdo, 1, $branch247Id);
    test_assert((int) $allSales['sales_24h'] === 2 && (float) $allSales['amount_24h'] === 4600.0, 'The global sales overview did not combine both branches.');
    test_assert((int) $centralSales['sales_24h'] === 1 && (float) $centralSales['amount_24h'] === 1200.0, 'The central branch sales filter leaked data.');
    test_assert((int) $branch247Sales['sales_24h'] === 1 && (float) $branch247Sales['amount_24h'] === 3400.0, 'The 24/7 branch sales filter leaked data.');
    test_assert((string) (admin_cloud_sync_recent_sales($pdo, 5, 1, $centralBranchId)[0]['event_uid'] ?? '') === 'sale-central-filter-1', 'Recent sales ignored the selected branch.');

    $periodFrom = gmdate('Y-m-d H:i:s', time() - 3600);
    $periodTo = gmdate('Y-m-d H:i:s', time() + 3600);
    $periodSales = admin_cloud_sync_sales_period_overview($pdo, 1, $periodFrom, $periodTo);
    $centralPeriodSales = admin_cloud_sync_sales_period_overview($pdo, 1, $periodFrom, $periodTo, $centralBranchId);
    test_assert((int) $periodSales['sales'] === 2 && (float) $periodSales['amount'] === 4600.0, 'The period overview included a sale outside its UTC boundaries.');
    test_assert((int) $centralPeriodSales['sales'] === 1 && (float) $centralPeriodSales['avg_ticket'] === 1200.0, 'The period and branch filters were not combined.');
    test_assert(count(admin_cloud_sync_recent_sales($pdo, 5, 1, null, $periodFrom, $periodTo)) === 2, 'Recent sales ignored the selected period.');
    $branchComparison = admin_cloud_sync_branch_sales_comparison($pdo, 1, $periodFrom, $periodTo);
    test_assert((int) ($branchComparison[$centralBranchId]['sales'] ?? 0) === 1, 'The comparison returned the wrong central sales count.');
    test_assert((float) ($branchComparison[$branch247Id]['amount'] ?? 0) === 3400.0, 'The comparison returned the wrong 24/7 amount.');

    $restrictedUserInsert = $pdo->prepare("INSERT INTO client_portal_users (id, email, password_hash) VALUES (3, 'manager@example.test', :hash)");
    $restrictedUserInsert->execute(['hash' => $hash]);
    $pdo->exec("INSERT INTO client_portal_memberships (id, user_id, client_id, role, branch_scope, is_active) VALUES (30, 3, 1, 'manager', 'selected', 1)");
    $pdo->exec("INSERT INTO client_portal_membership_branches (membership_id, branch_id) VALUES (30, {$branch247Id})");
    $restrictedBranchIds = portal_membership_branch_ids($pdo, 30, 1, 'manager', 'selected');
    test_assert($restrictedBranchIds === [$branch247Id], 'The manager branch scope was not loaded.');
    $restrictedSales = admin_cloud_sync_sales_period_overview($pdo, 1, $periodFrom, $periodTo, null, $restrictedBranchIds);
    $forbiddenSales = admin_cloud_sync_sales_period_overview($pdo, 1, $periodFrom, $periodTo, $centralBranchId, $restrictedBranchIds);
    test_assert((int) $restrictedSales['sales'] === 1 && (float) $restrictedSales['amount'] === 3400.0, 'The restricted manager received sales from another branch.');
    test_assert((int) $forbiddenSales['sales'] === 0, 'A manipulated branch id bypassed the manager scope.');
    test_assert(count(admin_cloud_sync_recent_sales($pdo, 5, 1, null, $periodFrom, $periodTo, $restrictedBranchIds)) === 1, 'Recent sales leaked another branch.');
    test_assert((int) admin_cloud_sync_stock_overview($pdo, 1, null, $restrictedBranchIds)['total'] === 1, 'Stock overview leaked another branch.');
    $restrictedStockItems = admin_cloud_sync_stock_items($pdo, 1, ['state' => 'all', 'branch_ids' => $restrictedBranchIds]);
    test_assert(count($restrictedStockItems) === 1 && (int) $restrictedStockItems[0]['branch_id'] === $branch247Id, 'Stock items leaked another branch.');
    test_assert((int) portal_client_installations_summary($pdo, 1, null, $restrictedBranchIds)['total'] === 1, 'Installations leaked another branch.');
    $restrictedComparison = admin_cloud_sync_branch_sales_comparison($pdo, 1, $periodFrom, $periodTo, $restrictedBranchIds);
    test_assert(array_keys($restrictedComparison) === [$branch247Id], 'Branch comparison leaked an unauthorized branch.');

    $_SESSION['client_portal_user'] = ['id' => 3, 'client_id' => 1, 'role' => 'manager'];
    test_assert(portal_refresh_current_session($pdo), 'The restricted manager session could not be refreshed.');
    test_assert(portal_current_branch_ids() === [$branch247Id], 'The refreshed session lost its branch scope.');
    test_assert(portal_current_branch_scope() === [$branch247Id], 'The refreshed session lost its restricted mode.');
    test_assert(portal_membership_branch_ids($pdo, 30, 1, 'owner', 'selected') === null, 'An owner was incorrectly restricted by branch mappings.');
    $pdo->exec("DELETE FROM client_portal_membership_branches WHERE membership_id = 30");
    test_assert(portal_membership_branch_ids($pdo, 30, 1, 'manager', 'selected') === [], 'A selected scope without active mappings must deny every branch.');
    test_assert((int) admin_cloud_sync_sales_period_overview($pdo, 1, $periodFrom, $periodTo, null, [])['sales'] === 0, 'An empty selected scope expanded to every branch.');

    $centralStock = admin_cloud_sync_stock_overview($pdo, 1, $centralBranchId);
    $branch247Stock = admin_cloud_sync_stock_overview($pdo, 1, $branch247Id);
    test_assert((int) $centralStock['total'] === 1, 'The central branch stock overview leaked data.');
    test_assert((int) $branch247Stock['total'] === 1, 'The 24/7 branch stock overview leaked data.');
    test_assert((int) portal_client_installations_summary($pdo, 1, $centralBranchId)['total'] === 1, 'The installation overview ignored the central branch.');
    test_assert((int) portal_client_installations_summary($pdo, 1, $branch247Id)['total'] === 1, 'The installation overview ignored the 24/7 branch.');

    $pdo->exec("INSERT INTO client_installations (id, client_id, license_id, installation_uid, display_name, status) VALUES (30, 1, 8, 'unassigned-installation', 'Unassigned PC', 'online')");
    $pdo->exec("INSERT INTO cloud_sync_events (client_id, installation_id, license_id, event_uid, event_type, occurred_at) VALUES (1, 30, 8, 'unassigned-event-1', 'sale.created', UTC_TIMESTAMP())");
    $pdo->exec("INSERT INTO cloud_sync_stock_items (client_id, installation_id, license_id, product_uid, nombre, stock) VALUES (1, 30, 8, 'unassigned-product-1', 'Unassigned Product', 2)");
    $assignment = admin_cloud_sync_assign_installation_branch($pdo, 1, 30, $branch247Id);
    test_assert((int) $assignment['branch_id'] === $branch247Id, 'The manual branch assignment returned the wrong branch.');
    test_assert((int) $pdo->query('SELECT branch_id FROM client_installations WHERE id = 30')->fetchColumn() === $branch247Id, 'The installation branch was not assigned.');
    test_assert((int) $pdo->query("SELECT branch_id FROM cloud_sync_events WHERE event_uid = 'unassigned-event-1'")->fetchColumn() === $branch247Id, 'Unassigned historical events were not repaired.');
    test_assert((int) $pdo->query("SELECT branch_id FROM cloud_sync_stock_items WHERE product_uid = 'unassigned-product-1'")->fetchColumn() === $branch247Id, 'Current stock was not moved with the installation.');

    $pdo->exec("INSERT INTO clients (id, legal_name, trade_name, status) VALUES (4, 'Other Owner', 'Other Client', 'activo')");
    $pdo->exec("INSERT INTO client_branches (client_id, name, code, status) VALUES (4, 'Other Branch', 'other_branch', 'active')");
    $foreignBranchId = (int) $pdo->lastInsertId();
    $foreignAssignmentBlocked = false;
    try {
        admin_cloud_sync_assign_installation_branch($pdo, 1, 30, $foreignBranchId);
    } catch (RuntimeException $e) {
        $foreignAssignmentBlocked = true;
    }
    test_assert($foreignAssignmentBlocked, 'A branch from another client was accepted.');

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
