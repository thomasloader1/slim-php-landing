-- =============================================================
--  SEED MENÚ – Focaccia & Co
--  Ejecutar DESPUÉS de 01_schema.sql y 03_menu_enhance.sql
--
--  Uso:
--    mysql -u USER -p DATABASE < 04_focaccia_seed.sql
--
--  Es idempotente: usa ON DUPLICATE KEY para no duplicar datos.
-- =============================================================

SET NAMES utf8mb4;

-- -------------------------------------------------------------
--  Activar módulo de menú en settings
-- -------------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
    ('menu_enabled',       '1'),
    ('menu_brand_name',    'Focaccia & Co'),
    ('menu_brand_subtitle','Lista de Precios · 2026'),
    ('menu_accent_color',  '#fec771'),
    ('menu_bg_color',      '#1a1208'),
    ('menu_text_color',    '#f5efe0'),
    ('menu_show_payment',  '1'),
    ('menu_payment_alias', 'amamoscocinar'),
    ('menu_payment_methods','cash,transfer')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- -------------------------------------------------------------
--  Secciones del menú
-- -------------------------------------------------------------
INSERT INTO `menu_sections` (`id`, `name`, `icon`, `note`, `note_type`, `sort_order`, `active`) VALUES
    (1, 'Nuestras Especialidades',       'fa-solid fa-star',              NULL,                                               'none',     1, 1),
    (2, 'Tartas y Canastitas',           'fa-solid fa-circle-half-stroke','Masa Integral Casera',                             'info',     2, 1),
    (3, 'Línea Premium & Delicatessen',  'fa-solid fa-gem',               'Entregas los viernes, solo por pedido previo',     'preorder', 3, 1),
    (4, 'Panificados y Repostería',      'fa-solid fa-cookie-bite',       'Pedidos con 72 hs. de anticipación',               'time',     4, 1)
ON DUPLICATE KEY UPDATE
    `name`       = VALUES(`name`),
    `icon`       = VALUES(`icon`),
    `note`       = VALUES(`note`),
    `note_type`  = VALUES(`note_type`),
    `sort_order` = VALUES(`sort_order`),
    `active`     = VALUES(`active`);

-- -------------------------------------------------------------
--  Items del menú
-- -------------------------------------------------------------
INSERT INTO `menu_items`
    (`section_id`, `title`, `description`,
     `price`, `price_label_1`,
     `price_2`, `price_label_2`,
     `price_3`, `price_label_3`,
     `unit`, `flavors`, `gift_note`, `badge_type`, `sort_order`, `active`)
VALUES

    -- ── NUESTRAS ESPECIALIDADES (section_id = 1) ────────────
    (1, 'Focaccia Simple',
        'Masa artesanal, aceite de oliva y hierbas',
        10000, 'Chica',
        13000, 'Grande',
        NULL,  NULL,
        NULL, NULL, NULL, 'none', 1, 1),

    (1, 'Focaccia Rellena Caprese',
        'Tomate, mozzarella y albahaca fresca',
        15000, 'Chica',
        20000, 'Grande',
        NULL,  NULL,
        NULL, NULL, NULL, 'none', 2, 1),

    (1, 'Focaccia Rellena Rúcula',
        'Rúcula fresca y aceite de oliva',
        15000, 'Chica',
        20000, 'Grande',
        NULL,  NULL,
        NULL, NULL, NULL, 'none', 3, 1),

    (1, 'Focaccia Jamón & Parmesano',
        'Jamón crudo y queso parmesano',
        15000, 'Chica',
        20000, 'Grande',
        NULL,  NULL,
        NULL, NULL, NULL, 'none', 4, 1),

    -- ── TARTAS Y CANASTITAS (section_id = 2) ────────────────
    (2, 'Tarta Grande',
        'Masa integral casera, rellenos a elección',
        20000, NULL,
        NULL,  NULL,
        NULL,  NULL,
        'c/u',
        'Acelga y bechamel,Calabaza y champiñones,Espinaca,Caprese,Zucchinis,Puerros,Brócoli,entre otros…',
        NULL, 'none', 1, 1),

    (2, 'Tarta Chica',
        'Porción individual, masa integral',
        6500, NULL,
        NULL, NULL,
        NULL, NULL,
        'c/u',
        'Acelga y bechamel,Calabaza y champiñones,Espinaca,Caprese',
        NULL, 'none', 2, 1),

    (2, 'Canastitas Integrales',
        'Masa integral casera',
        35000, NULL,
        NULL,  NULL,
        NULL,  NULL,
        'docena', NULL, NULL, 'none', 3, 1),

    (2, 'Tapas de Tarta Integral',
        'Masa integral casera',
        2200, NULL,
        NULL, NULL,
        NULL, NULL,
        'c/u', NULL, NULL, 'none', 4, 1),

    -- ── LÍNEA PREMIUM & DELICATESSEN (section_id = 3) ───────
    (3, 'Escabeches',
        'Berenjenas, Verduras o Pollo',
        10000, 'Chico',
        20000, 'Grande',
        NULL,  NULL,
        NULL, NULL, NULL, 'premium', 1, 1),

    (3, 'Untables',
        'Mayonesa de Zanahoria, Remolacha o Hummus',
        7000, NULL,
        NULL, NULL,
        NULL, NULL,
        'c/u', NULL, NULL, 'premium', 2, 1),

    (3, 'Buñuelos de Acelga',
        'Docena, garbanzo y especias',
        12000, NULL,
        NULL, NULL,
        NULL, NULL,
        'docena', NULL, 'Con salsa de regalo', 'preorder', 3, 1),

    (3, 'Falafel',
        'Docena — garbanzo y especias',
        15000, NULL,
        NULL, NULL,
        NULL, NULL,
        'docena', NULL, NULL, 'preorder', 4, 1),

    -- ── PANIFICADOS Y REPOSTERÍA (section_id = 4) ───────────
    (4, 'Panes de Molde Integrales',
        'Pan artesanal de harina integral',
        5000, NULL,
        NULL, NULL,
        NULL, NULL,
        NULL, NULL, NULL, 'preorder', 1, 1),

    (4, 'Prepizzas Integrales',
        'Masa integral casera lista para usar',
        3000, 'Chica',
        4500, 'Grande',
        NULL, NULL,
        NULL, NULL, NULL, 'preorder', 2, 1),

    (4, 'Budines (chicos)',
        'Artesanales, opciones comunes e integrales',
        7000, 'Comunes',
        8500, 'Mascabo/Integral',
        NULL, NULL,
        NULL, NULL, NULL, 'preorder', 3, 1),

    (4, 'Budines Grandes',
        'Formato grande, comunes e integrales',
        13000, 'Comunes',
        15000, 'Mascabo/Integral',
        NULL, NULL,
        NULL, NULL, NULL, 'preorder', 4, 1),

    (4, 'Torta Galesa Especial',
        'Receta tradicional, tres tamaños disponibles',
        15000, 'Chica',
        20000, 'Mediana',
        35000, 'Grande',
        NULL, NULL, NULL, 'special', 5, 1);
