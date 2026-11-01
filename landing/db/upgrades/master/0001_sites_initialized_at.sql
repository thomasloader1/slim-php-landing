-- =============================================================
--  UPGRADE (master) – sites.initialized_at
--  Marca los sitios ya inicializados (esquema + admin) para que
--  `migrate` sepa cuáles migrar. Estaba como ALTER suelto en
--  master/01_sites.sql.
-- =============================================================

ALTER TABLE `sites`
    ADD COLUMN `initialized_at` TIMESTAMP NULL DEFAULT NULL AFTER `last_status_sync`;
