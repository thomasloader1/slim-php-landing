-- =============================================================
--  MIGRACIÓN – Mejoras al módulo de menú (Focaccia-style)
--  Compatible con MySQL 8.0+
--
--  Ejecutar con:
--    mysql --force -u USER -p DATABASE < 03_menu_enhance.sql
--
--  Es idempotente: los ALTER fallan silenciosamente si la
--  columna ya existe (gracias a --force); el INSERT usa
--  ON DUPLICATE KEY para no pisar valores existentes.
-- =============================================================

SET NAMES utf8mb4;

-- -------------------------------------------------------------
--  Extensiones a menu_sections
-- -------------------------------------------------------------
ALTER TABLE `menu_sections`
    ADD COLUMN `icon`      VARCHAR(80)  NULL DEFAULT NULL AFTER `name`;

ALTER TABLE `menu_sections`
    ADD COLUMN `note`      VARCHAR(400) NULL DEFAULT NULL AFTER `icon`;

ALTER TABLE `menu_sections`
    ADD COLUMN `note_type` ENUM('none','info','preorder','time') NOT NULL DEFAULT 'none' AFTER `note`;

-- -------------------------------------------------------------
--  Extensiones a menu_items
-- -------------------------------------------------------------
ALTER TABLE `menu_items`
    ADD COLUMN `price_label_1` VARCHAR(30)  NULL DEFAULT NULL AFTER `price`;

ALTER TABLE `menu_items`
    ADD COLUMN `price_2`       DECIMAL(8,2) NULL DEFAULT NULL AFTER `price_label_1`;

ALTER TABLE `menu_items`
    ADD COLUMN `price_label_2` VARCHAR(30)  NULL DEFAULT NULL AFTER `price_2`;

ALTER TABLE `menu_items`
    ADD COLUMN `price_3`       DECIMAL(8,2) NULL DEFAULT NULL AFTER `price_label_2`;

ALTER TABLE `menu_items`
    ADD COLUMN `price_label_3` VARCHAR(30)  NULL DEFAULT NULL AFTER `price_3`;

ALTER TABLE `menu_items`
    ADD COLUMN `unit`          VARCHAR(30)  NULL DEFAULT NULL AFTER `price_label_3`;

ALTER TABLE `menu_items`
    ADD COLUMN `flavors`       TEXT         NULL DEFAULT NULL AFTER `unit`;

ALTER TABLE `menu_items`
    ADD COLUMN `gift_note`     VARCHAR(200) NULL DEFAULT NULL AFTER `flavors`;

ALTER TABLE `menu_items`
    ADD COLUMN `badge_type`    ENUM('none','premium','preorder','special') NOT NULL DEFAULT 'none' AFTER `active`;

-- -------------------------------------------------------------
--  Nuevos settings del módulo de menú
--  ON DUPLICATE KEY: no sobreescribe valores existentes
-- -------------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
    ('menu_accent_color',       '#fec771'),
    ('menu_bg_color',           '#1a1208'),
    ('menu_text_color',         '#f5efe0'),
    ('menu_bg_image_url',       ''),
    ('menu_bg_overlay',         '0'),
    ('menu_bg_overlay_opacity', '0.5'),
    ('menu_brand_name',         ''),
    ('menu_brand_subtitle',     ''),
    ('menu_show_payment',       '0'),
    ('menu_payment_alias',      ''),
    ('menu_payment_methods',    'cash,transfer')
ON DUPLICATE KEY UPDATE `setting_value` = `setting_value`;
