-- =============================================================
--  MIGRACIÓN – Menú → Productos
--  Agrega a menu_items:
--    - sku:   código único del producto (clave para la importación
--             masiva por CSV: si coincide se actualiza, si no se crea).
--             NULL permitido; el índice UNIQUE admite varios NULL.
--    - stock: unidades disponibles. NULL = sin control de stock;
--             0 = se muestra "Sin stock" en la landing.
--
--  Idempotente vía MigrationService (1060 columna duplicada y
--  1061 índice duplicado se toman como ya aplicados).
-- =============================================================

SET NAMES utf8mb4;

ALTER TABLE `menu_items`
    ADD COLUMN `sku` VARCHAR(64) NULL DEFAULT NULL AFTER `section_id`;

ALTER TABLE `menu_items`
    ADD UNIQUE KEY `uq_menu_items_sku` (`sku`);

ALTER TABLE `menu_items`
    ADD COLUMN `stock` INT NULL DEFAULT NULL AFTER `price`;
