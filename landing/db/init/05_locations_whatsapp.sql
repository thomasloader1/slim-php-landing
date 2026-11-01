-- =============================================================
--  MIGRACIÓN – WhatsApp por ubicación
--  El modelo Location y los formularios usan `whatsapp` y
--  `whatsapp_message`, pero el esquema base no los tenía:
--  crear/editar una ubicación fallaba con "Unknown column".
--
--  Idempotente vía MigrationService (error 1060 "columna duplicada"
--  se toma como ya aplicado), igual que 03_menu_enhance.sql.
-- =============================================================

SET NAMES utf8mb4;

ALTER TABLE `locations`
    ADD COLUMN `whatsapp` VARCHAR(30) NULL DEFAULT NULL AFTER `address`;

ALTER TABLE `locations`
    ADD COLUMN `whatsapp_message` VARCHAR(500) NULL DEFAULT NULL AFTER `whatsapp`;
