CREATE TABLE IF NOT EXISTS license_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    event_type VARCHAR(40) NOT NULL,
    from_status VARCHAR(20) DEFAULT NULL,
    to_status VARCHAR(20) DEFAULT NULL,
    reason VARCHAR(190) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    actor VARCHAR(190) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_license_events_license_id (license_id),
    KEY idx_license_events_client_id (client_id),
    KEY idx_license_events_created_at (created_at),
    CONSTRAINT fk_license_events_license FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_license_events_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
