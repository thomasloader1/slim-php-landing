-- =============================================================
--  UPDATE 1.3.1 – Sistema de Módulos + CRUD Ubicaciones
--  Idempotente: usa IF NOT EXISTS y ON DUPLICATE KEY
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Tabla locations
CREATE TABLE IF NOT EXISTS `locations` (
    `id`          INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(150)   NOT NULL,
    `address`     VARCHAR(300)       NULL DEFAULT NULL,
    `embed_code`  TEXT               NULL,
    `url`         VARCHAR(500)       NULL DEFAULT NULL,
    `mode`        ENUM('button','embed') NOT NULL DEFAULT 'embed',
    `sort_order`  INT UNSIGNED   NOT NULL DEFAULT 0,
    `active`      TINYINT(1)     NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_locations_active_sort` (`active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Settings de módulos (inserta o actualiza, nunca borra)
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
    ('module_title_enabled',     '1'),
    ('module_avatar_enabled',    '1'),
    ('module_links_enabled',     '1'),
    ('module_bio_enabled',       '1'),
    ('module_locations_enabled', '1'),
    ('module_menu_enabled',      '1') ON DUPLICATE KEY UPDATE
    `setting_value` = VALUES(`setting_value`);
