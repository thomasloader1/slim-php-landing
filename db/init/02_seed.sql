-- =============================================================
--  SEED – usuario admin inicial + settings por defecto
--
--  ANTES DE EJECUTAR personaliza las líneas marcadas con ✏️
--
--  Password insertada: "password"  (cambiala en el admin)
--  Hash generado con: password_hash('password', PASSWORD_BCRYPT)
-- =============================================================

-- -------------------------------------------------------------
--  Usuario admin
--  ✏️  Cambia 'name' y 'email' según el nombre de tu landing
-- -------------------------------------------------------------
INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `active`, `email_verified`)
VALUES (
    'Admin Mi Landing',                                         -- ✏️ nombre del admin
    'admin@mi-landing.com',                                    -- ✏️ email de acceso
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- password: "password"
    'admin',
    1,
    1
)
ON DUPLICATE KEY UPDATE `id` = `id`;   -- no falla si ya existe

-- -------------------------------------------------------------
--  Settings por defecto de la landing
--  ✏️  Ajusta site_name, landing_title, etc. a tu proyecto
-- -------------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
    ('site_name',                   'Mi Landing'),             -- ✏️
    ('landing_title',               'Mi Landing'),             -- ✏️
    ('landing_subtitle',            'Todos mis links en un solo lugar'),
    ('landing_bio',                 ''),
    ('landing_accent_color',        '#fec771'),
    ('landing_bg_color',            '#202020'),
    ('landing_text_color',          '#ffffff'),
    ('landing_avatar_url',          ''),
    ('landing_logo_url',            ''),
    ('landing_bg_image_url',        ''),
    ('landing_bg_overlay',          '0'),
    ('landing_bg_overlay_opacity',  '0.5'),
    ('seo_description',             ''),
    ('seo_keywords',                ''),
    ('seo_author',                  ''),
    ('landing_accent_force',        '1')
AS new_values
ON DUPLICATE KEY UPDATE
    `setting_value` = new_values.`setting_value`;
