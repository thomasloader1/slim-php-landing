-- =============================================================
--  SCHEMA – template-2mez-landing
--  Ejecutar primero, antes que 02_seed.sql
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
--  Tabla: users
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`                 INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `name`               VARCHAR(100)     NOT NULL,
    `email`              VARCHAR(150)     NOT NULL,
    `password_hash`      VARCHAR(255)     NOT NULL,
    `role`               ENUM('admin','editor') NOT NULL DEFAULT 'editor',
    `active`             TINYINT(1)       NOT NULL DEFAULT 1,
    `email_verified`     TINYINT(1)       NOT NULL DEFAULT 0,
    `email_verify_token` VARCHAR(64)          NULL DEFAULT NULL,
    `created_at`         TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
--  Tabla: links
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `links` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `title`      VARCHAR(150)  NOT NULL,
    `url`        VARCHAR(500)  NOT NULL,
    `type`       VARCHAR(30)   NOT NULL DEFAULT 'url',
    `icon`       VARCHAR(100)      NULL DEFAULT 'fa-link',
    `color`      VARCHAR(20)       NULL DEFAULT '#ffffff',
    `sort_order` INT UNSIGNED  NOT NULL DEFAULT 0,
    `active`     TINYINT(1)    NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_links_active_sort` (`active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
--  Tabla: settings
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
    `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `setting_key`   VARCHAR(80)   NOT NULL,
    `setting_value` TEXT              NULL,
    `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
--  Tabla: menu_sections
-- -------------------------------------------------------------
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

-- -------------------------------------------------------------
--  Tabla: menu_items
-- -------------------------------------------------------------
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

SET FOREIGN_KEY_CHECKS = 1;
