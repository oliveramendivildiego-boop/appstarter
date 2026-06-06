-- =============================================================================
-- Ampliar columnas valor_min / valor_max para valores de referencia largos
-- =============================================================================
-- Ejecutar ANTES de biocenter_importar_valores_referencia.sql si aparecen warnings:
--   #1265 Datos truncados para la columna 'valor_min'
--
-- Causa: dom_priresultados tenía varchar(11) y el Excel incluye listas de
-- opciones (NEGATIVO/POSITIVO, colores de copro, etc.) de cientos de caracteres.
-- =============================================================================

SET NAMES utf8mb4;

ALTER TABLE `dom_priresultados`
  MODIFY COLUMN `valor_min` TEXT NOT NULL,
  MODIFY COLUMN `valor_max` TEXT NOT NULL;

ALTER TABLE `dom_secanacategoria`
  MODIFY COLUMN `valor_min` TEXT NOT NULL,
  MODIFY COLUMN `valor_max` TEXT NOT NULL;

SELECT 'dom_priresultados' AS tabla, CHARACTER_MAXIMUM_LENGTH AS valor_min_len
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'dom_priresultados'
  AND COLUMN_NAME = 'valor_min';
