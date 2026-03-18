-- =============================================================
--  Migración: 1.1.0 – Nuevas settings SEO
--  Fecha: 2026-03-16
--  Descripción: Agrega campos para Open Graph, Twitter Cards,
--               Schema.org, canonical URL, noindex y locale.
--  Commit: eeadfbc (Add SEO improvements)
-- =============================================================

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
    ('seo_site_url',       ''),
    ('seo_og_image',       ''),
    ('seo_locale',         'es_AR'),
    ('seo_twitter_handle', ''),
    ('seo_schema_type',    'Person'),
    ('seo_business_type',  ''),
    ('seo_address',        ''),
    ('seo_noindex',        '0')
AS new_vals
ON DUPLICATE KEY UPDATE
    `setting_value` = new_vals.`setting_value`;
