SET @branch_scope_column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'client_portal_memberships'
      AND COLUMN_NAME = 'branch_scope'
);
SET @branch_scope_sql := IF(
    @branch_scope_column_exists = 0,
    'ALTER TABLE client_portal_memberships ADD COLUMN branch_scope VARCHAR(20) NOT NULL DEFAULT ''all'' AFTER role',
    'SELECT 1'
);
PREPARE branch_scope_stmt FROM @branch_scope_sql;
EXECUTE branch_scope_stmt;
DEALLOCATE PREPARE branch_scope_stmt;

CREATE TABLE IF NOT EXISTS client_portal_membership_branches (
    membership_id INT UNSIGNED NOT NULL,
    branch_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (membership_id, branch_id),
    KEY idx_portal_membership_branches_branch (branch_id),
    CONSTRAINT fk_portal_membership_branches_membership FOREIGN KEY (membership_id) REFERENCES client_portal_memberships(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_portal_membership_branches_branch FOREIGN KEY (branch_id) REFERENCES client_branches(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
