CREATE TABLE IF NOT EXISTS security_rate_limits (
    rate_key CHAR(64) PRIMARY KEY,
    scope VARCHAR(50) NOT NULL,
    hits INT UNSIGNED NOT NULL DEFAULT 1,
    window_started_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_security_rate_limits_scope (scope),
    KEY idx_security_rate_limits_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
