-- =============================================================
--  UPDATE 1.3.0 – Landing features: favicon, módulos, embed,
--                  display mode, bg_color links, logo size
--  Idempotente: usa IF NOT EXISTS y ON DUPLICATE KEY
-- =============================================================

-- Agregar bg_color a links solo si no existe
SET @col_exists = (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'links'
      AND COLUMN_NAME  = 'bg_color'
);
SET @ddl = IF(@col_exists = 0,
    'ALTER TABLE `links` ADD COLUMN `bg_color` VARCHAR(20) NULL DEFAULT NULL AFTER `color`',
    'SELECT 1'
);
PREPARE _stmt FROM @ddl;
EXECUTE _stmt;
DEALLOCATE PREPARE _stmt;

-- Settings nuevas (no sobreescribe si ya existen)
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
    ('landing_favicon_url',    ''),
    ('module_links_visible',   '1'),
    ('module_bio_visible',     '1'),
    ('module_title_visible',   '1'),
    ('module_avatar_visible',  '1'),
    ('landing_maps_embed_code',''),
    ('landing_links_display',  'list'),
    ('landing_logo_size',      'sm')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
