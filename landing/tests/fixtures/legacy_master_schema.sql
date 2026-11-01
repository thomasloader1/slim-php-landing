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
    ('site_manager_default_redirect_url', '')
ON DUPLICATE KEY UPDATE `setting_value` = `setting_value`;
-- =============================================================
--  MASTER: servidores remotos (cPanel) administrados por SSH
--  desde el panel local.
--  - La clave privada NUNCA se guarda acá: solo la ruta al archivo,
--    que debe estar dentro de SSH_KEYS_DIR (montado read-only).
--  - La passphrase se guarda cifrada con TENANT_ENCRYPTION_KEY.
--  - host_key_fingerprint: huella SHA256 confirmada por el usuario
--    (si el servidor cambia de clave, la conexión se rechaza).
--  - Las credenciales de la DB master de producción NO se guardan:
--    las operaciones corren `php bin/console` en el servidor, que
--    usa su propio .env.
-- =============================================================

CREATE TABLE IF NOT EXISTS `servers` (
    `id`                        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`                      VARCHAR(150) NOT NULL,
    `host`                      VARCHAR(255) NOT NULL,
    `port`                      INT UNSIGNED NOT NULL DEFAULT 22,
    `username`                  VARCHAR(100) NOT NULL,
    `key_path`                  VARCHAR(500) NOT NULL,
    `key_passphrase_encrypted`  TEXT NULL,
    `host_key_fingerprint`      VARCHAR(255) NULL,
    `cpanel_user`               VARCHAR(100) NULL,
    `remote_app_path`           VARCHAR(500) NOT NULL DEFAULT 'landing',
    `php_bin`                   VARCHAR(200) NOT NULL DEFAULT 'php',
    `mysql_host`                VARCHAR(255) NOT NULL DEFAULT 'localhost',
    `last_check_at`             TIMESTAMP NULL,
    `last_check_ok`             TINYINT(1) NULL,
    `created_at`                TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
