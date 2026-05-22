-- =============================================================================
-- Migración: dirección del paciente (dom_people.address_1)
-- =============================================================================
-- Permite guardar y mostrar la dirección en:
--   - Ficha de clientes / pacientes
--   - Hoja de trabajo (registers/orden)
--   - Expediente
--
-- Ejecución recomendada (CodeIgniter, desde la raíz del proyecto):
--   php spark migrate
--
-- Migración PHP equivalente:
--   app/Database/Migrations/2026-05-21-120000_AddAddress1ToPeople.php
--
-- Ejecución manual (phpMyAdmin / mysql), una sola vez.
-- Si la columna ya existe, omitir este script (error #1060 Duplicate column).
-- =============================================================================

ALTER TABLE `dom_people`
  ADD COLUMN `address_1` VARCHAR(255) NULL DEFAULT NULL
  COMMENT 'Dirección del paciente'
  AFTER `gender`;

-- =============================================================================
-- Reversión (solo si necesita deshacer el cambio):
-- ALTER TABLE `dom_people` DROP COLUMN `address_1`;
-- =============================================================================
