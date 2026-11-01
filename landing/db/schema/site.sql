-- =============================================================
--  ESQUEMA COMPLETO DE UN SITIO (landing)
--
--  Lo aplica `MigrationService` SOLO sobre una base vacía (sin tabla
--  `users`): crea todo en su estado final y registra los archivos de
--  db/upgrades/site/ como ya aplicados. Las bases existentes se
--  actualizan únicamente con esos upgrades.
--
--  Regla: todo cambio de esquema va en un upgrade nuevo Y acá.
--  tests/Unit/Services/MigrationDriftTest.php verifica que ambos
--  caminos den exactamente el mismo esquema.
--
--  Uso normal:  php bin/console migrate [--seed]
-- =============================================================

SET NAMES utf8mb4;

-- ── Usuarios y permisos ──────────────────────────────────────

CREATE TABLE IF NOT EXISTS `users` (
    `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`               VARCHAR(100) NOT NULL,
    `email`              VARCHAR(150) NOT NULL,
    `password_hash`      VARCHAR(255) NOT NULL,
    `role`               VARCHAR(40)  NOT NULL DEFAULT 'editor',   -- slug de roles; 'root' solo en la master
    `active`             TINYINT(1)   NOT NULL DEFAULT 1,
    `email_verified`     TINYINT(1)   NOT NULL DEFAULT 0,
    `email_verify_token` VARCHAR(64)      NULL DEFAULT NULL,
    `created_at`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `roles` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`       VARCHAR(40)  NOT NULL,
    `name`       VARCHAR(100) NOT NULL,
    `is_system`  TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_roles_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_permissions` (
    `role_id`    INT UNSIGNED NOT NULL,
    `permission` VARCHAR(60)  NOT NULL,                             -- clave de App\Support\Permissions::CATALOG
    PRIMARY KEY (`role_id`, `permission`),
    CONSTRAINT `fk_role_permissions_role`
        FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Configuración ────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `settings` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key`   VARCHAR(80)  NOT NULL,
    `setting_value` TEXT             NULL,
    `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Contenido de la landing ──────────────────────────────────

CREATE TABLE IF NOT EXISTS `links` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`      VARCHAR(150) NOT NULL,
    `url`        VARCHAR(500) NOT NULL,
    `type`       VARCHAR(30)  NOT NULL DEFAULT 'url',
    `icon`       VARCHAR(100)     NULL DEFAULT 'fa-link',
    `color`      VARCHAR(20)      NULL DEFAULT '#ffffff',
    `bg_color`   VARCHAR(20)      NULL DEFAULT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `active`     TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_links_active_sort` (`active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `locations` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`             VARCHAR(150) NOT NULL,
    `address`          VARCHAR(300)     NULL DEFAULT NULL,
    `whatsapp`         VARCHAR(30)      NULL DEFAULT NULL,
    `whatsapp_message` VARCHAR(500)     NULL DEFAULT NULL,
    `embed_code`       TEXT             NULL,
    `url`              VARCHAR(500)     NULL DEFAULT NULL,
    `mode`             ENUM('button','embed') NOT NULL DEFAULT 'embed',
    `sort_order`       INT UNSIGNED NOT NULL DEFAULT 0,
    `active`           TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_locations_active_sort` (`active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `faq_items` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `question`   VARCHAR(500) NOT NULL,
    `answer`     TEXT         NOT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `active`     TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_faq_active_sort` (`active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Productos (tablas menu_*) ────────────────────────────────

CREATE TABLE IF NOT EXISTS `menu_sections` (                     -- categorías
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(150) NOT NULL,
    `icon`       VARCHAR(80)      NULL DEFAULT NULL,
    `note`       VARCHAR(400)     NULL DEFAULT NULL,
    `note_type`  ENUM('none','info','preorder','time') NOT NULL DEFAULT 'none',
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `active`     TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_menu_sections_name` (`name`),
    KEY `idx_menu_sections_active_sort` (`active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `menu_items` (                        -- productos
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `section_id`    INT UNSIGNED     NULL DEFAULT NULL,
    `sku`           VARCHAR(64)      NULL DEFAULT NULL,
    `title`         VARCHAR(200) NOT NULL,
    `price`         DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    `stock`         INT              NULL DEFAULT NULL,             -- NULL = sin control de stock
    `price_label_1` VARCHAR(30)      NULL DEFAULT NULL,
    `price_2`       DECIMAL(8,2)     NULL DEFAULT NULL,
    `price_label_2` VARCHAR(30)      NULL DEFAULT NULL,
    `price_3`       DECIMAL(8,2)     NULL DEFAULT NULL,
    `price_label_3` VARCHAR(30)      NULL DEFAULT NULL,
    `unit`          VARCHAR(30)      NULL DEFAULT NULL,
    `flavors`       TEXT             NULL,
    `gift_note`     VARCHAR(200)     NULL DEFAULT NULL,
    `description`   TEXT             NULL,
    `image_url`     VARCHAR(500)     NULL DEFAULT NULL,
    `sort_order`    INT UNSIGNED NOT NULL DEFAULT 0,
    `active`        TINYINT(1)   NOT NULL DEFAULT 1,
    `badge_type`    ENUM('none','premium','preorder','special') NOT NULL DEFAULT 'none',
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_menu_items_sku` (`sku`),
    KEY `idx_menu_items_section` (`section_id`),
    KEY `idx_menu_items_active_sort` (`active`, `sort_order`),
    CONSTRAINT `fk_menu_items_section`
        FOREIGN KEY (`section_id`) REFERENCES `menu_sections` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Roles por defecto ────────────────────────────────────────
-- admin: todo lo del sitio. editor: solo contenido. El root (master)
-- puede cambiarlos después desde Roles y permisos.

INSERT IGNORE INTO `roles` (`slug`, `name`, `is_system`) VALUES
    ('admin',  'Administrador', 1),
    ('editor', 'Editor',        1);

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission`)
SELECT r.`id`, p.`permission`
FROM `roles` r
JOIN (
    SELECT 'builder' AS `permission` UNION ALL SELECT 'links' UNION ALL SELECT 'locations'
    UNION ALL SELECT 'products' UNION ALL SELECT 'settings' UNION ALL SELECT 'seo'
    UNION ALL SELECT 'favicon' UNION ALL SELECT 'modules' UNION ALL SELECT 'users'
) p
WHERE r.`slug` = 'admin';

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission`)
SELECT r.`id`, p.`permission`
FROM `roles` r
JOIN (
    SELECT 'builder' AS `permission` UNION ALL SELECT 'links' UNION ALL SELECT 'locations'
    UNION ALL SELECT 'products' UNION ALL SELECT 'seo' UNION ALL SELECT 'favicon'
) p
WHERE r.`slug` = 'editor';
