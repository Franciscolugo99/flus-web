
CREATE TABLE IF NOT EXISTS admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL,
    email VARCHAR(190) DEFAULT NULL,
    full_name VARCHAR(150) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_admin_users_username (username),
    UNIQUE KEY uq_admin_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    legal_name VARCHAR(190) NOT NULL,
    trade_name VARCHAR(190) DEFAULT NULL,
    email VARCHAR(190) DEFAULT NULL,
    phone VARCHAR(60) DEFAULT NULL,
    tax_id VARCHAR(40) DEFAULT NULL,
    business_type VARCHAR(120) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    internal_notes TEXT DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'activo',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_clients_email (email),
    KEY idx_clients_status (status),
    KEY idx_clients_legal_name (legal_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS licenses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    license_key VARCHAR(40) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'activa',
    starts_at DATE NOT NULL,
    expires_at DATE NOT NULL,
    plan_type VARCHAR(50) NOT NULL DEFAULT 'mensual',
    seats INT UNSIGNED DEFAULT NULL,
    internal_notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_licenses_license_key (license_key),
    KEY idx_licenses_client_id (client_id),
    KEY idx_licenses_expires_at (expires_at),
    KEY idx_licenses_status (status),
    CONSTRAINT fk_licenses_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    license_id INT UNSIGNED DEFAULT NULL,
    paid_at DATE NOT NULL,
    period_from DATE NOT NULL,
    period_to DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    method VARCHAR(30) NOT NULL DEFAULT 'transferencia',
    reference VARCHAR(190) DEFAULT NULL,
    internal_notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_payments_client_id (client_id),
    KEY idx_payments_license_id (license_id),
    KEY idx_payments_paid_at (paid_at),
    CONSTRAINT fk_payments_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_payments_license FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS license_notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    sent_to VARCHAR(190) DEFAULT NULL,
    sent_at DATETIME DEFAULT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    error_message TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_license_notifications_license_id (license_id),
    KEY idx_license_notifications_client_id (client_id),
    KEY idx_license_notifications_sent_at (sent_at),
    KEY idx_license_notifications_status (status),
    CONSTRAINT fk_notifications_license FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_notifications_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS downloads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    file_name VARCHAR(190) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    version VARCHAR(60) DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'activo',
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_downloads_status (status),
    KEY idx_downloads_uploaded_at (uploaded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS web_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    page_url VARCHAR(255) NOT NULL,
    page_title VARCHAR(190) DEFAULT NULL,
    referrer VARCHAR(255) DEFAULT NULL,
    utm_source VARCHAR(100) DEFAULT NULL,
    utm_medium VARCHAR(100) DEFAULT NULL,
    utm_campaign VARCHAR(100) DEFAULT NULL,
    session_id VARCHAR(100) DEFAULT NULL,
    ip_hash CHAR(64) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    device_type VARCHAR(20) DEFAULT NULL,
    extra_json TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_web_events_event_type (event_type),
    KEY idx_web_events_created_at (created_at),
    KEY idx_web_events_page_url (page_url),
    KEY idx_web_events_session_id (session_id),
    KEY idx_web_events_device_type (device_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
