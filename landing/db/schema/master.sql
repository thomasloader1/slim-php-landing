-- =============================================================
--  ESQUEMA COMPLETO DE LA MASTER (gestor de sitios)
--
--  Se aplica además de schema/site.sql (la master también es una
--  landing). Solo sobre una base sin tabla `sites`; las existentes se
--  actualizan con db/upgrades/master/. Mismas reglas que site.sql.
-- =============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `sites` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`              VARCHAR(150) NOT NULL,
    `domain`            VARCHAR(255) NOT NULL,
    `db_host`           VARCHAR(255) NOT NULL,
    `db_port`           INT UNSIGNED     NULL DEFAULT 3306,
    `db_name`           VARCHAR(100) NOT NULL,
    `db_user`           VARCHAR(100) NOT NULL,
    `db_pass_encrypted` TEXT         NOT NULL,                      -- AES-256-GCM con TENANT_ENCRYPTION_KEY
    `plan_name`         VARCHAR(100)     NULL DEFAULT 'basico',
    `plan_start`        DATE         NOT NULL,
    `plan_end`          DATE         NOT NULL,
    `domain_expiry`     DATE             NULL,
    `status`            ENUM('active','suspended','cancelled') NULL DEFAULT 'active',
    `redirect_url`      VARCHAR(500) NOT NULL DEFAULT '',
    `auto_suspend`      TINYINT(1)       NULL DEFAULT 1,
    `notes`             TEXT             NULL,
    `last_status_sync`  TIMESTAMP        NULL DEFAULT NULL,
    `initialized_at`    TIMESTAMP        NULL DEFAULT NULL,         -- esquema + admin creados
    `created_at`        TIMESTAMP        NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP        NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_sites_domain` (`domain`),
    KEY `idx_sites_status` (`status`),
    KEY `idx_sites_plan_end` (`plan_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `servers` (                            -- hosting remoto por SSH (Servidores)
    `id`                       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`                     VARCHAR(150) NOT NULL,
    `host`                     VARCHAR(255) NOT NULL,
    `port`                     INT UNSIGNED NOT NULL DEFAULT 22,
    `username`                 VARCHAR(100) NOT NULL,
    `key_path`                 VARCHAR(500) NOT NULL,
    `key_passphrase_encrypted` TEXT             NULL,
    `host_key_fingerprint`     VARCHAR(255)     NULL,
    `cpanel_user`              VARCHAR(100)     NULL,
    `remote_app_path`          VARCHAR(500) NOT NULL DEFAULT 'landing',
    `php_bin`                  VARCHAR(200) NOT NULL DEFAULT 'php',
    `mysql_host`               VARCHAR(255) NOT NULL DEFAULT 'localhost',
    `last_check_at`            TIMESTAMP        NULL DEFAULT NULL,
    `last_check_ok`            TINYINT(1)       NULL,
    `created_at`               TIMESTAMP        NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`               TIMESTAMP        NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
    ('module_site_manager_enabled',       '0'),
    ('site_manager_default_redirect_url', '')
ON DUPLICATE KEY UPDATE `setting_value` = `setting_value`;
