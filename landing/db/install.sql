-- =============================================================
--  INSTALL.SQL — ÚNICO SCRIPT DE BASE DE DATOS
--
--  Crea o actualiza TODA la base (landing + gestor de sitios).
--  Se puede correr las veces que quieras, sobre una base vacía o
--  sobre una vieja: antes de cada cambio pregunta si ya existe
--  (CREATE TABLE IF NOT EXISTS / information_schema), así que
--  nunca falla por "ya existe" y nunca borra datos.
--
--  Cómo correrlo (cualquiera de las tres):
--    php bin/console migrate                          (recomendado)
--    mysql -u USUARIO -p NOMBRE_DB < db/install.sql
--    phpMyAdmin → elegir la base → Importar → este archivo
--
--  Usuario inicial (solo si la base no tiene ningún usuario):
--    admin@gotogo.com.ar / password   ← cambiala al entrar
--
--  Regla para cambios futuros: agregar la tabla/columna en su
--  CREATE TABLE de abajo Y un bloque "si no existe, agregar" en la
--  sección 2. tests/Unit/Services/MigrationDriftTest.php verifica
--  que una base nueva y una vieja terminen idénticas.
-- =============================================================

SET NAMES utf8mb4;


-- =============================================================
--  1. TABLAS (estado final). Si la tabla ya existe, no se toca.
-- =============================================================

-- ── Usuarios, roles y permisos ───────────────────────────────

CREATE TABLE IF NOT EXISTS `users` (
    `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`               VARCHAR(100) NOT NULL,
    `email`              VARCHAR(150) NOT NULL,
    `password_hash`      VARCHAR(255) NOT NULL,
    `role`               VARCHAR(40)  NOT NULL DEFAULT 'editor',   -- slug de roles, o 'root'
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

-- ── Shop: pedidos (comandas) que llegan por WhatsApp ─────────

CREATE TABLE IF NOT EXISTS `orders` (
    `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `code`           VARCHAR(20)   NOT NULL,                        -- ej. P-000123
    `customer_name`  VARCHAR(100)  NOT NULL,
    `customer_phone` VARCHAR(30)   NOT NULL,
    `status`         ENUM('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
    `total`          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `ip_hash`        CHAR(40)          NULL DEFAULT NULL,
    `decided_at`     TIMESTAMP         NULL DEFAULT NULL,
    `decided_by`     VARCHAR(100)      NULL DEFAULT NULL,
    `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_orders_code` (`code`),
    KEY `idx_orders_status_created` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_items` (
    `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `order_id`       INT UNSIGNED  NOT NULL,
    `menu_item_id`   INT UNSIGNED      NULL DEFAULT NULL,           -- NULL si borraron el producto
    `title`          VARCHAR(200)  NOT NULL,                        -- copia al momento del pedido
    `variant_label`  VARCHAR(30)       NULL DEFAULT NULL,
    `unit_price`     DECIMAL(8,2)  NOT NULL,
    `quantity`       INT UNSIGNED  NOT NULL,
    `subtotal`       DECIMAL(10,2) NOT NULL,
    `stock_reserved` TINYINT(1)    NOT NULL DEFAULT 0,              -- 1 = se descontó stock (se devuelve al rechazar)
    PRIMARY KEY (`id`),
    KEY `idx_order_items_order` (`order_id`),
    KEY `idx_order_items_item` (`menu_item_id`),
    CONSTRAINT `fk_order_items_order`
        FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_order_items_item`
        FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Límite de pedidos por IP del formulario público (filas vencidas se limpian solas)
CREATE TABLE IF NOT EXISTS `rate_limits` (
    `key`      VARCHAR(64) NOT NULL,
    `hits`     INT UNSIGNED NOT NULL DEFAULT 0,
    `reset_at` DATETIME    NOT NULL,
    PRIMARY KEY (`key`),
    KEY `idx_rate_limits_reset` (`reset_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Gestor de sitios (master). En un sitio cliente quedan vacías. ──

CREATE TABLE IF NOT EXISTS `sites` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`              VARCHAR(150) NOT NULL,
    `domain`            VARCHAR(255) NOT NULL,
    `db_host`           VARCHAR(255) NOT NULL,
    `db_port`           INT UNSIGNED     NULL DEFAULT 3306,
    `db_name`           VARCHAR(100) NOT NULL,
    `db_user`           VARCHAR(100) NOT NULL,
    `db_pass_encrypted` TEXT         NOT NULL,                      -- cifrada con TENANT_ENCRYPTION_KEY
    `plan_name`         VARCHAR(100)     NULL DEFAULT 'basico',
    `plan_start`        DATE         NOT NULL,
    `plan_end`          DATE         NOT NULL,
    `domain_expiry`     DATE             NULL,
    `status`            ENUM('active','suspended','cancelled') NULL DEFAULT 'active',
    `redirect_url`      VARCHAR(500) NOT NULL DEFAULT '',
    `auto_suspend`      TINYINT(1)       NULL DEFAULT 1,
    `notes`             TEXT             NULL,
    `last_status_sync`  TIMESTAMP        NULL DEFAULT NULL,
    `initialized_at`    TIMESTAMP        NULL DEFAULT NULL,
    `created_at`        TIMESTAMP        NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP        NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_sites_domain` (`domain`),
    KEY `idx_sites_status` (`status`),
    KEY `idx_sites_plan_end` (`plan_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
--  2. ACTUALIZAR BASES VIEJAS
--  MySQL no tiene "ADD COLUMN IF NOT EXISTS": cada bloque pregunta
--  en information_schema y solo ejecuta el ALTER si falta. Si ya
--  está, ejecuta "DO 0" (no hace nada). En una base nueva no cambia nada.
-- =============================================================

-- ── Categorías: icono y aviso ────────────────────────────────

SET @sql = (SELECT IF(COUNT(*) = 0, "ALTER TABLE `menu_sections` ADD COLUMN `icon` VARCHAR(80) NULL DEFAULT NULL AFTER `name`", "DO 0")
    FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'menu_sections' AND COLUMN_NAME = 'icon');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0, "ALTER TABLE `menu_sections` ADD COLUMN `note` VARCHAR(400) NULL DEFAULT NULL AFTER `icon`", "DO 0")
    FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'menu_sections' AND COLUMN_NAME = 'note');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0, "ALTER TABLE `menu_sections` ADD COLUMN `note_type` ENUM('none','info','preorder','time') NOT NULL DEFAULT 'none' AFTER `note`", "DO 0")
    FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'menu_sections' AND COLUMN_NAME = 'note_type');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── Productos: precios múltiples, sabores, insignias ─────────

SET @sql = (SELECT IF(COUNT(*) = 0, "ALTER TABLE `menu_items` ADD COLUMN `price_label_1` VARCHAR(30) NULL DEFAULT NULL AFTER `price`", "DO 0")
    FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'menu_items' AND COLUMN_NAME = 'price_label_1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0, "ALTER TABLE `menu_items` ADD COLUMN `price_2` DECIMAL(8,2) NULL DEFAULT NULL AFTER `price_label_1`", "DO 0")
    FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'menu_items' AND COLUMN_NAME = 'price_2');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0, "ALTER TABLE `menu_items` ADD COLUMN `price_label_2` VARCHAR(30) NULL DEFAULT NULL AFTER `price_2`", "DO 0")
    FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'menu_items' AND COLUMN_NAME = 'price_label_2');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0, "ALTER TABLE `menu_items` ADD COLUMN `price_3` DECIMAL(8,2) NULL DEFAULT NULL AFTER `price_label_2`", "DO 0")
    FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'menu_items' AND COLUMN_NAME = 'price_3');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0, "ALTER TABLE `menu_items` ADD COLUMN `price_label_3` VARCHAR(30) NULL DEFAULT NULL AFTER `price_3`", "DO 0")
    FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'menu_items' AND COLUMN_NAME = 'price_label_3');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0, "ALTER TABLE `menu_items` ADD COLUMN `unit` VARCHAR(30) NULL DEFAULT NULL AFTER `price_label_3`", "DO 0")
    FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'menu_items' AND COLUMN_NAME = 'unit');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0, "ALTER TABLE `menu_items` ADD COLUMN `flavors` TEXT NULL DEFAULT NULL AFTER `unit`", "DO 0")
    FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'menu_items' AND COLUMN_NAME = 'flavors');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0, "ALTER TABLE `menu_items` ADD COLUMN `gift_note` VARCHAR(200) NULL DEFAULT NULL AFTER `flavors`", "DO 0")
    FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'menu_items' AND COLUMN_NAME = 'gift_note');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0, "ALTER TABLE `menu_items` ADD COLUMN `badge_type` ENUM('none','premium','preorder','special') NOT NULL DEFAULT 'none' AFTER `active`", "DO 0")
    FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'menu_items' AND COLUMN_NAME = 'badge_type');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── Ubicaciones: WhatsApp ────────────────────────────────────

SET @sql = (SELECT IF(COUNT(*) = 0, "ALTER TABLE `locations` ADD COLUMN `whatsapp` VARCHAR(30) NULL DEFAULT NULL AFTER `address`", "DO 0")
    FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'locations' AND COLUMN_NAME = 'whatsapp');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0, "ALTER TABLE `locations` ADD COLUMN `whatsapp_message` VARCHAR(500) NULL DEFAULT NULL AFTER `whatsapp`", "DO 0")
    FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'locations' AND COLUMN_NAME = 'whatsapp_message');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── Productos: código (SKU) único y stock ────────────────────

SET @sql = (SELECT IF(COUNT(*) = 0, "ALTER TABLE `menu_items` ADD COLUMN `sku` VARCHAR(64) NULL DEFAULT NULL AFTER `section_id`", "DO 0")
    FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'menu_items' AND COLUMN_NAME = 'sku');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0, "ALTER TABLE `menu_items` ADD UNIQUE KEY `uq_menu_items_sku` (`sku`)", "DO 0")
    FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'menu_items' AND INDEX_NAME = 'uq_menu_items_sku');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0, "ALTER TABLE `menu_items` ADD COLUMN `stock` INT NULL DEFAULT NULL AFTER `price`", "DO 0")
    FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'menu_items' AND COLUMN_NAME = 'stock');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── Categorías con nombre único ──────────────────────────────
-- Antes del índice: si hay categorías repetidas (sin distinguir
-- mayúsculas/tildes), sus productos pasan a la de menor id y las
-- repetidas se borran. Sin repetidas, estas 3 sentencias no cambian nada.

UPDATE `menu_sections` SET `name` = TRIM(`name`);

UPDATE `menu_items` i
    JOIN `menu_sections` s ON s.`id` = i.`section_id`
    JOIN (SELECT `name`, MIN(`id`) AS `keep_id` FROM `menu_sections` GROUP BY `name`) k ON k.`name` = s.`name`
SET i.`section_id` = k.`keep_id`
WHERE s.`id` <> k.`keep_id`;

DELETE dup FROM `menu_sections` dup
    JOIN `menu_sections` keeper ON keeper.`name` = dup.`name` AND keeper.`id` < dup.`id`;

SET @sql = (SELECT IF(COUNT(*) = 0, "ALTER TABLE `menu_sections` ADD UNIQUE KEY `uq_menu_sections_name` (`name`)", "DO 0")
    FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'menu_sections' AND INDEX_NAME = 'uq_menu_sections_name');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── Usuarios: el rol pasa de ENUM('admin','editor') a texto ──

SET @sql = (SELECT IF(COUNT(*) > 0, "ALTER TABLE `users` MODIFY COLUMN `role` VARCHAR(40) NOT NULL DEFAULT 'editor'", "DO 0")
    FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role' AND DATA_TYPE = 'enum');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── Sitios: marca de inicialización ──────────────────────────

SET @sql = (SELECT IF(COUNT(*) = 0, "ALTER TABLE `sites` ADD COLUMN `initialized_at` TIMESTAMP NULL DEFAULT NULL AFTER `last_status_sync`", "DO 0")
    FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sites' AND COLUMN_NAME = 'initialized_at');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- =============================================================
--  3. DATOS INICIALES. Solo agrega lo que falta; nunca pisa nada.
-- =============================================================

-- ── Roles por defecto ────────────────────────────────────────
-- Los permisos se cargan solo si el rol todavía no tiene ninguno:
-- si después el root se los cambia, correr el script no los repone.

INSERT IGNORE INTO `roles` (`slug`, `name`, `is_system`) VALUES
    ('admin',  'Administrador', 1),
    ('editor', 'Editor',        1);

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission`)
SELECT r.`id`, p.`permission`
FROM `roles` r
JOIN (
    SELECT 'builder' AS `permission` UNION ALL SELECT 'links' UNION ALL SELECT 'locations'
    UNION ALL SELECT 'products' UNION ALL SELECT 'settings' UNION ALL SELECT 'seo'
    UNION ALL SELECT 'favicon' UNION ALL SELECT 'users'
) p
WHERE r.`slug` = 'admin'
  AND NOT EXISTS (SELECT 1 FROM `role_permissions` x WHERE x.`role_id` = r.`id`);

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission`)
SELECT r.`id`, p.`permission`
FROM `roles` r
JOIN (
    SELECT 'builder' AS `permission` UNION ALL SELECT 'links' UNION ALL SELECT 'locations'
    UNION ALL SELECT 'products' UNION ALL SELECT 'seo' UNION ALL SELECT 'favicon'
) p
WHERE r.`slug` = 'editor'
  AND NOT EXISTS (SELECT 1 FROM `role_permissions` x WHERE x.`role_id` = r.`id`);

-- Permiso "Pedidos" (Shop) para el admin, UNA sola vez: el marcador en settings
-- evita reponerlo si después el root se lo quita.
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission`)
SELECT r.`id`, 'orders'
FROM `roles` r
WHERE r.`slug` = 'admin'
  AND NOT EXISTS (SELECT 1 FROM `settings` s WHERE s.`setting_key` = 'seeded_perm_orders');

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES ('seeded_perm_orders', '1');

-- ── Usuario inicial (solo si la base no tiene usuarios) ──────
-- admin@gotogo.com.ar / password  → CAMBIALA al entrar (Usuarios).

INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `active`, `email_verified`)
SELECT 'Admin', 'admin@gotogo.com.ar',
       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',   -- bcrypt de "password"
       'root', 1, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `users`);

-- ── Configuración por defecto ────────────────────────────────

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
    -- Landing
    ('site_name',                  'Mi Landing'),
    ('landing_title',              'Mi Landing'),
    ('landing_subtitle',           'Todos mis links en un solo lugar'),
    ('landing_bio',                ''),
    ('landing_accent_color',       '#fec771'),
    ('landing_accent_force',       '1'),
    ('landing_bg_color',           '#202020'),
    ('landing_text_color',         '#ffffff'),
    ('landing_avatar_url',         ''),
    ('landing_logo_url',           ''),
    ('landing_logo_size',          'sm'),
    ('landing_bg_image_url',       ''),
    ('landing_bg_overlay',         '0'),
    ('landing_bg_overlay_opacity', '0.5'),
    ('landing_links_display',      'list'),
    ('landing_favicon_url',        ''),
    ('favicon_version',            ''),
    -- SEO
    ('seo_description',            ''),
    ('seo_keywords',               ''),
    ('seo_author',                 ''),
    ('seo_site_url',               ''),
    ('seo_og_image',               ''),
    ('seo_locale',                 'es_AR'),
    ('seo_twitter_handle',         ''),
    ('seo_schema_type',            'LocalBusiness'),
    ('seo_business_type',          ''),
    ('seo_address',                ''),
    ('seo_noindex',                '0'),
    ('seo_shop_description',       ''),
    ('seo_currency',               'ARS'),
    -- Módulos visibles
    ('module_title_enabled',       '1'),
    ('module_avatar_enabled',      '1'),
    ('module_links_enabled',       '1'),
    ('module_bio_enabled',         '1'),
    ('module_locations_enabled',   '1'),
    ('module_menu_enabled',        '1'),
    ('module_orders_enabled',      '1'),
    ('module_builder_enabled',     '1'),
    ('module_users_enabled',       '1'),
    ('module_settings_enabled',    '1'),
    ('module_seo_enabled',         '1'),
    ('module_favicon_enabled',     '1'),
    -- Footer de la landing (solo lo edita el root en Módulos)
    ('landing_footer_enabled',     '1'),
    ('landing_footer_text',        'Desarrollado por'),
    ('landing_footer_link_label',  'gomepab.com.ar'),
    ('landing_footer_url',         'https://gomepab.com.ar'),
    ('module_site_manager_enabled','0'),
    ('site_manager_default_redirect_url', ''),
    -- Shop: página pública /shop (apagada hasta configurarla)
    ('menu_enabled',               '0'),
    ('shop_enabled',               '0'),                -- carrito + pedidos por WhatsApp
    ('shop_max_items',             '30'),               -- unidades máximas por pedido
    ('menu_header_text',           'Nuestra Carta'),
    ('menu_footer_text',           ''),
    ('menu_accent_color',          '#fec771'),
    ('menu_bg_color',              '#1a1208'),
    ('menu_text_color',            '#f5efe0'),
    ('menu_bg_image_url',          ''),
    ('menu_bg_overlay',            '0'),
    ('menu_bg_overlay_opacity',    '0.5'),
    ('menu_brand_name',            ''),
    ('menu_brand_subtitle',        ''),
    ('menu_show_payment',          '0'),
    ('menu_payment_alias',         ''),
    ('menu_payment_methods',       'cash,transfer')
ON DUPLICATE KEY UPDATE `setting_value` = `setting_value`;
