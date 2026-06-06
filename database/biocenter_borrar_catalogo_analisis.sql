-- =============================================================================
-- Biocenter: vaciar catálogo de análisis clínicos (grupos y pruebas)
-- =============================================================================
-- Base: laboratorio | Prefijo: dom_ (app/Config/Database.php)
--
-- ELIMINA todo el catálogo de labotests:
--   grupos (dom_anacategoria), pruebas (dom_prianacategoria), sub-clases,
--   referencias, manuales, perfiles y config reactivo↔análisis.
--
-- NO elimina: órdenes (dom_registro), resultados de pacientes (dom_regvalues),
--             poblaciones, fórmulas ni opciones de resultado.
--
-- IMPORTANTE: haga backup antes de ejecutar.
--   mysqldump -u root -p laboratorio dom_anacategoria dom_prianacategoria ... > backup.sql
--
-- Ejecutar:
--   mysql -u root -p laboratorio < database/biocenter_borrar_catalogo_analisis.sql
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

START TRANSACTION;

-- Comente la siguiente línea si no tiene la tabla dom_labotest_reactivo_config
DELETE FROM `dom_labotest_reactivo_config`;
DELETE FROM `dom_manuals`;
DELETE FROM `dom_perfil_examen`;
DELETE FROM `dom_secanacategoria`;
DELETE FROM `dom_priresultados`;
DELETE FROM `dom_prianacategoria`;
DELETE FROM `dom_anacategoria`;

ALTER TABLE `dom_anacategoria` AUTO_INCREMENT = 1;
ALTER TABLE `dom_prianacategoria` AUTO_INCREMENT = 1;
ALTER TABLE `dom_priresultados` AUTO_INCREMENT = 1;
ALTER TABLE `dom_secanacategoria` AUTO_INCREMENT = 1;
ALTER TABLE `dom_manuals` AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;

SELECT 'dom_anacategoria' AS tabla, COUNT(*) AS filas FROM `dom_anacategoria`
UNION ALL
SELECT 'dom_prianacategoria', COUNT(*) FROM `dom_prianacategoria`;
