-- Tabela de Servidores IPTV
CREATE TABLE IF NOT EXISTS servers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    billing_type ENUM('fixed', 'per_active') NOT NULL DEFAULT 'fixed',
    cost DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    panel_type VARCHAR(50) NULL,
    panel_url VARCHAR(255) NULL,
    reseller_user VARCHAR(100) NULL,
    sigma_token VARCHAR(500) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
