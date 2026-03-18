-- =============================================================
--  Módulo: menu – Menú de Restaurante
--  Instala tablas menu_sections y menu_items más settings.
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `menu_sections` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(150)  NOT NULL,
    `sort_order` INT UNSIGNED  NOT NULL DEFAULT 0,
    `active`     TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_menu_sections_active_sort` (`active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `menu_items` (
    `id`          INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `section_id`  INT UNSIGNED       NULL DEFAULT NULL,
    `title`       VARCHAR(200)   NOT NULL,
    `price`       DECIMAL(8,2)   NOT NULL DEFAULT 0.00,
    `description` TEXT               NULL,
    `image_url`   VARCHAR(500)       NULL DEFAULT NULL,
    `sort_order`  INT UNSIGNED   NOT NULL DEFAULT 0,
    `active`      TINYINT(1)    NOT NULL DEFAULT 1,
    `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_menu_items_section`     (`section_id`),
    KEY `idx_menu_items_active_sort` (`active`, `sort_order`),
    CONSTRAINT `fk_menu_items_section`
        FOREIGN KEY (`section_id`) REFERENCES `menu_sections` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
    ('menu_enabled',     '0'),
    ('menu_header_text', 'Nuestra Carta'),
    ('menu_footer_text', '')
AS new_vals
ON DUPLICATE KEY UPDATE `setting_value` = new_vals.`setting_value`;

SET FOREIGN_KEY_CHECKS = 1;
