CREATE TABLE IF NOT EXISTS client_merge_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_client_id INT UNSIGNED NOT NULL,
    target_client_id INT UNSIGNED NOT NULL,
    source_snapshot_json LONGTEXT NOT NULL,
    summary_json LONGTEXT NOT NULL,
    actor VARCHAR(190) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_client_merge_events_source (source_client_id),
    KEY idx_client_merge_events_target (target_client_id),
    KEY idx_client_merge_events_created_at (created_at),
    CONSTRAINT fk_client_merge_events_source FOREIGN KEY (source_client_id) REFERENCES clients(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_client_merge_events_target FOREIGN KEY (target_client_id) REFERENCES clients(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
