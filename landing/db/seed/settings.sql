-- =============================================================
--  CONFIGURACIÓN POR DEFECTO DE UN SITIO
--
--  Lo aplica `php bin/console migrate --seed` y la inicialización de
--  un sitio (TenantProvisioner::initialize). Se puede repetir: solo
--  inserta las claves que faltan y NUNCA pisa un valor existente.
--
--  No crea usuarios: el primer admin lo crea el gestor de sitios o
--  `php bin/console admin:create <email> --role=root|admin`.
-- =============================================================

SET NAMES utf8mb4;

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
    ('seo_schema_type',            'Person'),
    ('seo_business_type',          ''),
    ('seo_address',                ''),
    ('seo_noindex',                '0'),

    -- Módulos visibles (el root decide además qué ve cada rol)
    ('module_title_enabled',       '1'),
    ('module_avatar_enabled',      '1'),
    ('module_links_enabled',       '1'),
    ('module_bio_enabled',         '1'),
    ('module_locations_enabled',   '1'),
    ('module_menu_enabled',        '1'),

    -- Productos: página pública /menu (apagada hasta configurarla)
    ('menu_enabled',               '0'),
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
