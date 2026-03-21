-- Ejecutar en la DB de la landing que actuara como MASTER

CREATE TABLE IF NOT EXISTS `sites` (
    `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`                VARCHAR(150) NOT NULL,
    `domain`              VARCHAR(255) NOT NULL,
    `db_host`             VARCHAR(255) NOT NULL,
    `db_port`             INT UNSIGNED DEFAULT 3306,
    `db_name`             VARCHAR(100) NOT NULL,
    `db_user`             VARCHAR(100) NOT NULL,
    `db_pass_encrypted`   TEXT NOT NULL,
    `plan_name`           VARCHAR(100) DEFAULT 'basico',
    `plan_start`          DATE NOT NULL,
    `plan_end`            DATE NOT NULL,
    `domain_expiry`       DATE NULL,
    `status`              ENUM('active','suspended','cancelled') DEFAULT 'active',
    `redirect_url`        VARCHAR(500) NOT NULL DEFAULT '',
    `auto_suspend`        TINYINT(1) DEFAULT 1,
    `notes`               TEXT NULL,
    `last_status_sync`    TIMESTAMP NULL,
    `created_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_sites_domain` (`domain`),
    KEY `idx_sites_status` (`status`),
    KEY `idx_sites_plan_end` (`plan_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
    ('module_site_manager_enabled', '0'),
    ('site_manager_encryption_key', ''),
    ('site_manager_default_redirect_url', '')
ON DUPLICATE KEY UPDATE `setting_value` = `setting_value`;
