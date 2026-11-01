-- =============================================================
--  UPGRADE – Roles y permisos por módulo
--  - roles / role_permissions: qué secciones del admin ve cada rol.
--  - users.role pasa de ENUM a VARCHAR (guarda el slug del rol;
--    'root' solo tiene sentido en la DB master).
--  - Roles por defecto: admin (todo lo del sitio) y editor (contenido).
--  Los permisos son las claves de App\Support\Permissions::CATALOG.
-- =============================================================

SET NAMES utf8mb4;

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
    `permission` VARCHAR(60)  NOT NULL,
    PRIMARY KEY (`role_id`, `permission`),
    CONSTRAINT `fk_role_permissions_role`
        FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `users`
    MODIFY COLUMN `role` VARCHAR(40) NOT NULL DEFAULT 'editor';

INSERT IGNORE INTO `roles` (`slug`, `name`, `is_system`) VALUES
    ('admin',  'Administrador', 1),
    ('editor', 'Editor',        1);

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission`)
SELECT r.`id`, p.`permission`
FROM `roles` r
JOIN (
    SELECT 'builder' AS `permission` UNION ALL SELECT 'links' UNION ALL SELECT 'locations'
    UNION ALL SELECT 'products' UNION ALL SELECT 'settings' UNION ALL SELECT 'seo'
    UNION ALL SELECT 'favicon' UNION ALL SELECT 'modules' UNION ALL SELECT 'users'
) p
WHERE r.`slug` = 'admin';

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission`)
SELECT r.`id`, p.`permission`
FROM `roles` r
JOIN (
    SELECT 'builder' AS `permission` UNION ALL SELECT 'links' UNION ALL SELECT 'locations'
    UNION ALL SELECT 'products' UNION ALL SELECT 'seo' UNION ALL SELECT 'favicon'
) p
WHERE r.`slug` = 'editor';
