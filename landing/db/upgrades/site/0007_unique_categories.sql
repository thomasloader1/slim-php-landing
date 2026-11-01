-- =============================================================
--  MIGRACIÓN – Categorías (menu_sections) con nombre único
--
--  1. Quita espacios al inicio/fin de los nombres.
--  2. Si ya hay categorías repetidas (misma comparación que usa
--     MySQL: sin distinguir mayúsculas ni tildes), mueve sus
--     productos a la de menor id y borra las demás.
--  3. Agrega el índice UNIQUE sobre name.
--
--  Idempotente: los pasos 1-2 no hacen nada si no hay repetidas y
--  el 1061 (índice duplicado) del paso 3 se toma como ya aplicado.
--  Sin tablas temporales: el usuario de DB de un sitio puede no
--  tener el privilegio CREATE TEMPORARY TABLES.
-- =============================================================

SET NAMES utf8mb4;

UPDATE `menu_sections` SET `name` = TRIM(`name`);

UPDATE `menu_items` i
    JOIN `menu_sections` s ON s.`id` = i.`section_id`
    JOIN (SELECT `name`, MIN(`id`) AS `keep_id` FROM `menu_sections` GROUP BY `name`) k
        ON k.`name` = s.`name`
SET i.`section_id` = k.`keep_id`
WHERE s.`id` <> k.`keep_id`;

DELETE dup FROM `menu_sections` dup
    JOIN `menu_sections` keeper
        ON keeper.`name` = dup.`name` AND keeper.`id` < dup.`id`;

ALTER TABLE `menu_sections`
    ADD UNIQUE KEY `uq_menu_sections_name` (`name`);
