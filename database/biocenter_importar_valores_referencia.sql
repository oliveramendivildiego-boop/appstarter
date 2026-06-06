-- =============================================================================
-- Biocenter: importar valores de referencia desde Excel
-- =============================================================================
-- Fuente: valores_referencia_BIOCENTER_2026-04-21_13-22-10 (2).xlsx
-- Catálogo destino: precioscompletosubir.csv (grupos/pruebas biocenter_importar_precioscompletosubir.sql)
--
-- Ejecutar DESPUÉS de:
--   1) biocenter_borrar_catalogo_analisis.sql
--   2) biocenter_importar_precioscompletosubir.sql
--
-- Filas emparejadas: 517 (simple: 284, compuesto: 228, cultivo: 5)
-- Sin coincidencia: 24 (ver biocenter_valores_referencia_sin_coincidencia.txt)
--
-- Población por defecto si no hay banda etaria: 15 (Todos)
-- Bandas etarias usadas: 6-12 (requiere database/insert_poblaciones_edad.sql)
--
-- Requisito opcional: columna sexo en dom_priresultados y dom_secanacategoria
--   database/migration_priresultados_sexo.sql
-- Si no existe sexo, comente esa columna en los INSERT/UPDATE de abajo.
-- =============================================================================

SET NAMES utf8mb4;

-- dom_priresultados usa varchar(11) en instalaciones antiguas; el Excel trae textos
-- largos (NEGATIVO/POSITIVO, listas de opciones, etc.). Ampliar antes de importar.
ALTER TABLE `dom_priresultados`
  MODIFY COLUMN `valor_min` TEXT NOT NULL,
  MODIFY COLUMN `valor_max` TEXT NOT NULL;

ALTER TABLE `dom_secanacategoria`
  MODIFY COLUMN `valor_min` TEXT NOT NULL,
  MODIFY COLUMN `valor_max` TEXT NOT NULL;

START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS `tmp_biocenter_vref`;
CREATE TEMPORARY TABLE `tmp_biocenter_vref` (
  `modo` ENUM('simple','compuesto','cultivo') NOT NULL,
  `grupo` VARCHAR(255) NOT NULL,
  `prueba` VARCHAR(255) NOT NULL,
  `sec_nombre` VARCHAR(255) NOT NULL,
  `poblacion_id` INT NOT NULL,
  `sexo` VARCHAR(20) NOT NULL,
  `valor_min` VARCHAR(255) NOT NULL,
  `valor_max` VARCHAR(255) NOT NULL,
  `unidad` VARCHAR(255) NOT NULL,
  `opcion_id` INT NOT NULL,
  KEY `idx_simple` (`modo`,`grupo`(100),`prueba`(100),`poblacion_id`,`sexo`),
  KEY `idx_sec` (`modo`,`grupo`(100),`prueba`(100),`sec_nombre`(100),`poblacion_id`,`sexo`)
) ENGINE=Memory DEFAULT CHARSET=utf8mb4;

INSERT INTO `tmp_biocenter_vref`
  (`modo`, `grupo`, `prueba`, `sec_nombre`, `poblacion_id`, `sexo`, `valor_min`, `valor_max`, `unidad`, `opcion_id`)
VALUES

  ('compuesto', 'Hematologia', 'Hemograma', 'ERITROCITOS', 6, 'ambos', '3000', '6300', '10⁶/mm³', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'ERITROCITOS', 7, 'ambos', '2700', '5300', 'x 10³/ml³', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'ERITROCITOS', 15, 'ambos', '5000', '10000', 'x 10³/ml³', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'ERITROCITOS', 9, 'ambos', '4000', '5200', 'x 10³/ml³', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'ERITROCITOS', 10, 'femenino', '4144', '5264', 'x 10³/ml³', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'ERITROCITOS', 10, 'masculino', '4480', '6048', 'x 10³/ml³', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'ERITROCITOS', 11, 'femenino', '4144', '5264', 'x 10³/ml³', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'ERITROCITOS', 11, 'masculino', '4480', '6048', 'x 10³/ml³', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'ERITROCITOS', 12, 'masculino', '4144', '5264', 'x 10³/ml³', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'ERITROCITOS', 12, 'femenino', '4144', '6048', 'x 10³/ml³', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'HEMATOCRITO', 6, 'ambos', '31', '66', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'HEMATOCRITO', 7, 'ambos', '28', '39', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'HEMATOCRITO', 15, 'ambos', '30', '45', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'HEMATOCRITO', 9, 'ambos', '35', '45', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'HEMATOCRITO', 10, 'femenino', '36', '47', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'HEMATOCRITO', 10, 'masculino', '37', '50', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'HEMATOCRITO', 11, 'femenino', '36', '47', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'HEMATOCRITO', 11, 'masculino', '37', '50', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'HEMATOCRITO', 12, 'femenino', '36', '47', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'HEMATOCRITO', 12, 'masculino', '37', '50', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'HEMOGLOBINA', 6, 'ambos', '10', '19.5', 'Gr%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'HEMOGLOBINA', 7, 'ambos', '9', '13.5', 'Gr%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'HEMOGLOBINA', 15, 'ambos', '8', '15', 'Gr%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'HEMOGLOBINA', 9, 'ambos', '11.5', '15.5', 'Gr%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'HEMOGLOBINA', 10, 'femenino', '11', '16', 'Gr%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'HEMOGLOBINA', 10, 'masculino', '13', '18', 'Gr%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'HEMOGLOBINA', 11, 'femenino', '11', '16', 'Gr%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'HEMOGLOBINA', 11, 'masculino', '13', '18', 'Gr%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'HEMOGLOBINA', 12, 'femenino', '11', '16', 'Gr%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'HEMOGLOBINA', 12, 'masculino', '13', '18', 'Gr%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'LEUCOCITOS', 6, 'ambos', '5000', '21000', '10³/mm³', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'LEUCOCITOS', 7, 'ambos', '6000', '17000', '10³/mm³', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'LEUCOCITOS', 15, 'ambos', '5500', '19000', '10³/mm³', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'LEUCOCITOS', 9, 'ambos', '4500', '13500', '10³/mm³', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'LEUCOCITOS', 10, 'ambos', '5000', '10000', '10³/mm³', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'LEUCOCITOS', 11, 'ambos', '5000', '10000', '10³/mm³', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'LEUCOCITOS', 12, 'ambos', '5000', '10000', '10³/mm³', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'CAYADOS', 15, 'ambos', '0', '2', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'SEGMENTADOS', 6, 'ambos', '36', '55', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'SEGMENTADOS', 7, 'ambos', '39', '55', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'SEGMENTADOS', 15, 'ambos', '45', '63', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'SEGMENTADOS', 9, 'ambos', '55', '60', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'SEGMENTADOS', 10, 'ambos', '57', '65', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'SEGMENTADOS', 11, 'ambos', '57', '65', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'SEGMENTADOS', 12, 'ambos', '57', '65', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'EOSINOFILOS', 6, 'ambos', '0', '3', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'EOSINOFILOS', 7, 'ambos', '0', '3', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'EOSINOFILOS', 15, 'ambos', '2', '8', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'EOSINOFILOS', 9, 'ambos', '0', '3', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'EOSINOFILOS', 10, 'ambos', '0', '3', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'EOSINOFILOS', 11, 'ambos', '0', '3', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'EOSINOFILOS', 12, 'ambos', '0', '3', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'BASOFILO', 15, 'ambos', '0', '1', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'LINFOCITO', 6, 'ambos', '50', '55', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'LINFOCITO', 7, 'ambos', '45', '52', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'LINFOCITO', 15, 'ambos', '27', '37', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'LINFOCITO', 9, 'ambos', '30', '45', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'LINFOCITO', 10, 'ambos', '25', '35', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'LINFOCITO', 11, 'ambos', '25', '35', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'LINFOCITO', 12, 'ambos', '35', '25', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'MONOCITO', 6, 'ambos', '5', '10', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'MONOCITO', 7, 'ambos', '5', '10', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'MONOCITO', 15, 'ambos', '0', '5', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'MONOCITO', 9, 'ambos', '1', '7', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'MONOCITO', 10, 'ambos', '1', '7', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'MONOCITO', 11, 'ambos', '1', '7', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'MONOCITO', 12, 'ambos', '1', '7', '%', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'V.C.M.', 6, 'ambos', '88', '126', 'fL', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'V.C.M.', 7, 'ambos', '70', '86', 'fL', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'V.C.M.', 15, 'ambos', '39', '55', 'fL', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'V.C.M.', 9, 'ambos', '77', '95', 'fL', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'V.C.M.', 10, 'ambos', '82', '92', 'fL', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'V.C.M.', 11, 'ambos', '82', '92', 'fL', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'V.C.M.', 12, 'ambos', '82', '92', 'fL', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'Hb.C.M.', 6, 'ambos', '28', '40', 'pg', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'Hb.C.M.', 7, 'ambos', '23', '31', 'pg', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'Hb.C.M.', 15, 'ambos', '12.5', '17.5', 'pg', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'Hb.C.M.', 9, 'ambos', '25', '33', 'pg', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'Hb.C.M.', 10, 'ambos', '27', '31', 'pg', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'Hb.C.M.', 11, 'ambos', '27', '31', 'pg', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'Hb.C.M.', 12, 'ambos', '27', '31', 'pg', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'C.Hb.C.M.', 6, 'ambos', '28', '38', 'g/dL', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'C.Hb.C.M.', 7, 'ambos', '28', '38', 'g/dL', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'C.Hb.C.M.', 15, 'ambos', '30', '36', 'g/dL', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'C.Hb.C.M.', 9, 'ambos', '31', '37', 'g/dL', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'C.Hb.C.M.', 10, 'ambos', '31', '36', 'g/dL', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'C.Hb.C.M.', 11, 'ambos', '31', '36', 'g/dL', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'C.Hb.C.M.', 12, 'ambos', '31', '36', 'g/dL', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'PLAQUETAS', 6, 'ambos', '150', '350', 'x 10³/ml³', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'PLAQUETAS', 7, 'ambos', '150', '350', 'x 10³/ml³', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'PLAQUETAS', 15, 'ambos', '150', '700', 'x 10³/ml³', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'PLAQUETAS', 9, 'ambos', '150', '450', 'x 10³/ml³', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'PLAQUETAS', 10, 'ambos', '150', '450', 'x 10³/ml³', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'PLAQUETAS', 11, 'ambos', '150', '450', 'x 10³/ml³', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'PLAQUETAS', 12, 'ambos', '150', '450', 'x 10³/ml³', 3),
  ('compuesto', 'Hematologia', 'Eritrosedimentacion', '1ra Hora', 15, 'femenino', '0', '20', 'mm/1hrs', 3),
  ('compuesto', 'Hematologia', 'Eritrosedimentacion', '1ra Hora', 15, 'masculino', '0', '15', 'mm/1hrs', 3),
  ('compuesto', 'Hematologia', 'Eritrosedimentacion', '2da Hora', 15, 'femenino', '0', '40', 'mm/2hrs', 3),
  ('compuesto', 'Hematologia', 'Eritrosedimentacion', '2da Hora', 15, 'masculino', '0', '30', 'mm/2hrs', 3),
  ('compuesto', 'Hematologia', 'Eritrosedimentacion', 'Indice de Katz', 15, 'ambos', '5', '22', '', 3),
  ('compuesto', 'Hematologia', 'Hemograma', 'RECUENTO DE PLAQUETAS', 15, 'ambos', '150', '450', 'x 10³/ml³', 3),
  ('compuesto', 'Hematologia', 'Recuento de Reticulocitos', 'RECUENTO DE RETICULOCITOS', 6, 'ambos', '2', '6', '%', 3),
  ('compuesto', 'Hematologia', 'Recuento de Reticulocitos', 'RECUENTO DE RETICULOCITOS', 7, 'ambos', '2', '6', '%', 3),
  ('compuesto', 'Hematologia', 'Recuento de Reticulocitos', 'RECUENTO DE RETICULOCITOS', 15, 'ambos', '0', '1.5', '%', 3),
  ('compuesto', 'Hematologia', 'Recuento de Reticulocitos', 'RECUENTO DE RETICULOCITOS', 9, 'ambos', '0.5', '1.5', '%', 3),
  ('compuesto', 'Hematologia', 'Recuento de Reticulocitos', 'RECUENTO DE RETICULOCITOS', 10, 'ambos', '0.5', '1.5', '%', 3),
  ('compuesto', 'Hematologia', 'Recuento de Reticulocitos', 'RECUENTO DE RETICULOCITOS', 11, 'ambos', '0.5', '1.5', '%', 3),
  ('compuesto', 'Hematologia', 'Recuento de Reticulocitos', 'RECUENTO DE RETICULOCITOS', 12, 'ambos', '0.5', '1.5', '%', 3),
  ('compuesto', 'Coagulograma', 'Coagulograma', 'TIEMPO DE PROTROMBINA', 15, 'ambos', '12', '14', 'Seg.', 3),
  ('compuesto', 'Coagulograma', 'Coagulograma', 'INR', 15, 'ambos', '1', '1.2', '', 3),
  ('compuesto', 'Coagulograma', 'Coagulograma', '% de Actividad', 15, 'ambos', '80', '100', '%', 3),
  ('compuesto', 'Coagulograma', 'Coagulograma', 'APTT', 15, 'ambos', '25', '43.5', 'Seg.', 3),
  ('compuesto', 'Coagulograma', 'Coagulograma', 'DIMERO - D', 15, 'ambos', '0', '630', 'ng/ml', 3),
  ('compuesto', 'Coagulograma', 'Coagulograma', 'FIBRINOGENO', 15, 'ambos', '180', '350', 'mg/dl', 3),
  ('compuesto', 'Coagulograma', 'Coagulograma', 'TIEMPO DE TROMBINA', 15, 'ambos', '13', '20', 'Seg', 3),
  ('compuesto', 'Coagulograma', 'Coagulograma', 'TIEMPO DE SANGRIA', 15, 'ambos', '1', '3', 'Min.', 3),
  ('compuesto', 'Coagulograma', 'Coagulograma', 'TIEMPO DE COAGULACION', 15, 'ambos', '6', '12', 'Min.', 3),
  ('simple', 'Hematologia', 'Gota Gruesa', 'Gota Gruesa', 15, 'ambos', 'NEGATIVO POSITIVO', '', '', 3),
  ('cultivo', 'Hematologia', 'Grupo Sanguineo', 'MUESTRA', 15, 'ambos', '¨O¨ POSITIVO ¨A¨ POSITIVO ¨B¨ POSITIVO ¨AB¨ POSITIVO ¨O¨ NEGATIVO ¨A¨ NEGATIVO ¨B¨ NEGATIVO ¨O¨ NEGATIVO "AB" NEGATIVO', '', '', 3),
  ('simple', 'Hematologia', 'Grupo Sanguineo', 'Grupo Sanguineo', 15, 'ambos', '¨O¨ POSITIVO ¨A¨ POSITIVO ¨B¨ POSITIVO ¨AB¨ POSITIVO ¨O¨ NEGATIVO ¨A¨ NEGATIVO ¨B¨ NEGATIVO ¨O¨ NEGATIVO "AB" NEGATIVO', '', '', 3),
  ('simple', 'Hematologia', 'Coombs Directo', 'Coombs Directo', 15, 'ambos', 'NEGATIVO POSITIVO', '', '', 3),
  ('simple', 'Hematologia', 'Coombs Indirecto', 'Coombs Indirecto', 15, 'ambos', 'NEGATIVO POSITIVO', '', '', 3),
  ('simple', 'Hematologia', 'Celulas LE', 'Celulas LE', 15, 'ambos', '', '', '', 3),
  ('simple', 'Quimica Sanguinea', 'Glicemia', 'Glicemia', 15, 'ambos', '70', '100', 'mg/dl', 3),
  ('simple', 'Quimica Sanguinea', 'Glicemia', 'Glicemia', 6, 'ambos', '70', '100', 'mg/dl', 3),
  ('simple', 'Quimica Sanguinea', 'Glicemia', 'Glicemia', 12, 'ambos', '60', '100', 'mg/dl', 3),
  ('simple', 'Quimica Sanguinea', 'Gluc. Post. Prandial', 'Gluc. Post. Prandial', 6, 'ambos', '70', '105', 'mg/dl', 3),
  ('simple', 'Quimica Sanguinea', 'Hb. Glicosilada', 'Hb. Glicosilada', 15, 'ambos', '', '', '', 3),
  ('compuesto', 'Quimica Sanguinea', 'Tolerancia de Glucosa', 'GLUCOSA BASAL', 6, 'ambos', '70', '105', 'mg/dl', 3),
  ('compuesto', 'Quimica Sanguinea', 'Tolerancia de Glucosa', 'GLUCOSA BASAL', 12, 'ambos', '70', '100', 'mg/dl', 3),
  ('compuesto', 'Quimica Sanguinea', 'Tolerancia de Glucosa', 'Glucosa Primera Hora', 15, 'ambos', '70', '180', 'mg/dL', 3),
  ('compuesto', 'Quimica Sanguinea', 'Tolerancia de Glucosa', 'Glucosa Segunda Hora', 15, 'ambos', '70', '155', 'mg/dL', 3),
  ('compuesto', 'Quimica Sanguinea', 'Tolerancia de Glucosa', 'Glucosa Tercera Hora', 15, 'ambos', '70', '140', 'mg/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Amilasa', 'Amilasa', 15, 'ambos', '269', '1462', 'UA/dl', 3),
  ('simple', 'Quimica Sanguinea', 'Lipasa', 'Lipasa', 15, 'ambos', '10', '150', 'U/L', 3),
  ('simple', 'Quimica Sanguinea', 'Urea', 'Urea', 6, 'ambos', '10', '46', 'mg/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Urea', 'Urea', 12, 'ambos', '10', '50', 'mg/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Urea', 'Urea', 15, 'ambos', '20', '65', 'mg/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Nitrogeno Ureico', 'Nitrogeno Ureico', 15, 'ambos', '10', '30', 'mg/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Creatinina', 'Creatinina', 6, 'masculino', '0.2', '0.4', 'mg/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Creatinina', 'Creatinina', 6, 'femenino', '0.2', '0.4', 'mg/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Creatinina', 'Creatinina', 7, 'masculino', '0.2', '0.4', 'mg/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Creatinina', 'Creatinina', 7, 'femenino', '0.2', '0.4', 'mg/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Creatinina', 'Creatinina', 15, 'masculino', '0.3', '0.7', 'mg/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Creatinina', 'Creatinina', 15, 'femenino', '0.3', '0.7', 'mg/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Creatinina', 'Creatinina', 9, 'masculino', '0.3', '0.7', 'mg/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Creatinina', 'Creatinina', 9, 'femenino', '0.3', '0.7', 'mg/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Creatinina', 'Creatinina', 10, 'masculino', '0.5', '1', 'mg/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Creatinina', 'Creatinina', 10, 'femenino', '0.5', '1', 'mg/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Creatinina', 'Creatinina', 11, 'masculino', '0.9', '1.3', 'mg/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Creatinina', 'Creatinina', 11, 'femenino', '0.9', '1.3', 'mg/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Creatinina', 'Creatinina', 12, 'masculino', '0.8', '1.3', 'mg/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Creatinina', 'Creatinina', 12, 'femenino', '0.6', '1.3', 'mg/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Creatinina', 'Creatinina', 15, 'ambos', '0.8', '2', 'mg/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Acido Urico', 'Acido Urico', 15, 'masculino', '3.4', '7', 'mg/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Acido Urico', 'Acido Urico', 15, 'femenino', '2.4', '5.7', 'mg/dL', 3),
  ('simple', 'Perfil Lipidico', 'Colesterol', 'Colesterol', 15, 'ambos', '80', '180', 'mg/dL', 3),
  ('simple', 'Perfil Lipidico', 'C-HDL', 'C-HDL', 15, 'masculino', '35', '80', 'mg/dL', 3),
  ('simple', 'Perfil Lipidico', 'C-HDL', 'C-HDL', 15, 'femenino', '45', '85', 'mg/dL', 3),
  ('simple', 'Perfil Lipidico', 'C-LDL', 'C-LDL', 15, 'ambos', '0', '60', 'mg/dL', 3),
  ('simple', 'Perfil Lipidico', 'VLDL', 'VLDL', 15, 'ambos', '5', '40', 'mg/dl', 3),
  ('simple', 'Perfil Lipidico', 'Trigliceridos', 'Trigliceridos', 15, 'ambos', '0', '60', 'mg/dL', 3),
  ('simple', 'Endocrinología', 'Riesgo cardiaco', 'Riesgo cardiaco', 15, 'ambos', 'BAJO MODERADO ELEVADO', '', '', 3),
  ('simple', 'Quimica Sanguinea', 'Proteinas', 'Proteinas', 15, 'ambos', '5.5', '8', 'g/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Albumina', 'Albumina', 15, 'ambos', '2.1', '3.4', 'g/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Globulina', 'Globulina', 15, 'ambos', '2', '5', 'g/dL', 3),
  ('simple', 'Quimica Sanguinea', 'R A/G', 'R A/G', 15, 'ambos', '1.2', '2.2', '', 3),
  ('simple', 'Quimica Sanguinea', 'Bilirrubina (D,I,T)', 'Bilirrubina (D,I,T)', 6, 'ambos', '0', '0', 'mg/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Bilirrubina (D,I,T)', 'Bilirrubina (D,I,T)', 7, 'ambos', '0', '12', 'mg/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Bilirrubina (D,I,T)', 'Bilirrubina (D,I,T)', 12, 'ambos', '0', '0.8', 'mg/dL', 3),
  ('simple', 'Quimica Sanguinea', 'Bilirrubina (D,I,T)', 'Bilirrubina (D,I,T)', 15, 'ambos', '0', '0.6', 'mg/dL', 3),
  ('simple', 'Perfil Hepatico', 'GOT/AST', 'GOT/AST', 15, 'femenino', '0', '31', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'GOT/AST', 'GOT/AST', 15, 'masculino', '0', '37', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'GOT/AST', 'GOT/AST', 15, 'ambos', '10', '80', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'GPT/ALT', 'GPT/ALT', 15, 'masculino', '0', '42', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'GPT/ALT', 'GPT/ALT', 15, 'femenino', '0', '32', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'GPT/ALT', 'GPT/ALT', 15, 'ambos', '10', '80', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'GGT', 'GGT', 6, 'masculino', '11', '61', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'GGT', 'GGT', 6, 'femenino', '9', '39', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'GGT', 'GGT', 7, 'masculino', '11', '61', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'GGT', 'GGT', 7, 'femenino', '9', '39', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'GGT', 'GGT', 15, 'masculino', '11', '61', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'GGT', 'GGT', 15, 'femenino', '9', '39', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'GGT', 'GGT', 9, 'masculino', '11', '61', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'GGT', 'GGT', 9, 'femenino', '9', '39', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'GGT', 'GGT', 10, 'masculino', '11', '61', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'GGT', 'GGT', 10, 'femenino', '9', '39', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'GGT', 'GGT', 11, 'masculino', '11', '61', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'GGT', 'GGT', 11, 'femenino', '9', '39', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'GGT', 'GGT', 12, 'ambos', '11', '61', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'GGT', 'GGT', 12, 'femenino', '9', '39', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'GGT', 'GGT', 15, 'ambos', '0', '10', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'F. Alcalina', 'F. Alcalina', 6, 'ambos', '64', '644', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'F. Alcalina', 'F. Alcalina', 7, 'ambos', '64', '644', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'F. Alcalina', 'F. Alcalina', 15, 'ambos', '64', '644', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'F. Alcalina', 'F. Alcalina', 9, 'ambos', '64', '644', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'F. Alcalina', 'F. Alcalina', 10, 'ambos', '64', '644', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'F. Alcalina', 'F. Alcalina', 11, 'masculino', '80', '270', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'F. Alcalina', 'F. Alcalina', 11, 'femenino', '64', '240', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'F. Alcalina', 'F. Alcalina', 12, 'masculino', '80', '270', 'U/L', 3),
  ('simple', 'Perfil Hepatico', 'F. Alcalina', 'F. Alcalina', 12, 'femenino', '64', '240', 'U/L', 3),
  ('simple', 'Otros', 'Amonio', 'Amonio', 15, 'ambos', '10', '47', 'umol/L', 3),
  ('simple', 'Electrolitos', 'Sodio', 'Sodio', 15, 'ambos', '135', '145', 'mEq/L', 3),
  ('simple', 'Electrolitos', 'Potasio', 'Potasio', 15, 'ambos', '3.5', '5.1', 'mEq/L', 3),
  ('simple', 'PERFIL ELECTROLITOS', 'Cloro', 'Cloro', 15, 'ambos', '98', '106', 'mEq/L', 3),
  ('simple', 'Electrolitos', 'Calcio', 'Calcio', 15, 'ambos', '4.8', '5.52', 'mg/dl', 3),
  ('simple', 'Electrolitos', 'Calcio', 'Calcio', 12, 'ambos', '4', '5.4', 'mg/dl', 3),
  ('simple', 'Electrolitos', 'Magnesio', 'Magnesio', 15, 'ambos', '1.6', '2.4', 'mg/dl', 3),
  ('simple', 'Electrolitos', 'Fosforo', 'Fosforo', 15, 'ambos', '2.5', '4.8', 'mg/dl', 3),
  ('simple', 'Perfil Cardiaco', 'Troponina T', 'Troponina T', 15, 'ambos', '0', '14', 'pg/ml', 3),
  ('simple', 'Quimica Sanguinea', 'LDH', 'LDH', 6, 'ambos', '150', '500', 'U/L', 3),
  ('simple', 'Quimica Sanguinea', 'LDH', 'LDH', 7, 'ambos', '150', '500', 'U/L', 3),
  ('simple', 'Quimica Sanguinea', 'LDH', 'LDH', 15, 'ambos', '150', '500', 'U/L', 3),
  ('simple', 'Quimica Sanguinea', 'LDH', 'LDH', 9, 'ambos', '150', '500', 'U/L', 3),
  ('simple', 'Quimica Sanguinea', 'LDH', 'LDH', 10, 'ambos', '207', '414', 'U/L', 3),
  ('simple', 'Quimica Sanguinea', 'LDH', 'LDH', 11, 'ambos', '207', '414', 'U/L', 3),
  ('simple', 'Quimica Sanguinea', 'LDH', 'LDH', 12, 'ambos', '207', '414', 'U/L', 3),
  ('simple', 'Perfil Cardiaco', 'CK-MB', 'CK-MB', 15, 'ambos', '0', '24', 'UI/L', 3),
  ('simple', 'Perfil Cardiaco', 'CK', 'CK', 15, 'masculino', '38', '174', 'UI/L', 3),
  ('simple', 'Perfil Cardiaco', 'CK', 'CK', 15, 'femenino', '25', '192', 'UI/L', 3),
  ('simple', 'Riesgo Cardiovascular', 'Homocisteina', 'Homocisteina', 15, 'ambos', '4', '15', 'Umol/L', 3),
  ('simple', 'Perfil Lipídico', 'Lipidos totales', 'Lipidos totales', 15, 'ambos', '400', '800', 'mg/dl', 3),
  ('simple', 'Marcadores Cardíacos', 'NT-Pro BNP', 'NT-Pro BNP', 12, 'ambos', '0', '610', 'pg/ml', 3),
  ('simple', 'Marcadores Cardíacos', 'Mioglobina', 'Mioglobina', 15, 'ambos', 'NEGATIVO POSITIVO', '', '', 3),
  ('simple', 'PERFIL PROTEICO', 'Troponina I', 'Troponina I', 15, 'ambos', '0', '0.4', 'ng/ml', 3),
  ('compuesto', 'Perfil de hierro', 'Perfil de Hierro', 'HIERRO SERICO', 15, 'masculino', '65', '175', 'ug/dl', 3),
  ('compuesto', 'Perfil de hierro', 'Perfil de Hierro', 'HIERRO SERICO', 15, 'femenino', '50', '170', 'ug/dl', 3),
  ('compuesto', 'Perfil de hierro', 'Perfil de Hierro', 'TRANSFERRINA - TIBC', 6, 'ambos', '100', '400', 'ng/ml', 3),
  ('compuesto', 'Perfil de hierro', 'Perfil de Hierro', 'TRANSFERRINA - TIBC', 7, 'ambos', '100', '400', 'ng/ml', 3),
  ('compuesto', 'Perfil de hierro', 'Perfil de Hierro', 'TRANSFERRINA - TIBC', 15, 'ambos', '100', '400', 'ng/ml', 3),
  ('compuesto', 'Perfil de hierro', 'Perfil de Hierro', 'TRANSFERRINA - TIBC', 9, 'ambos', '100', '400', 'ng/ml', 3),
  ('compuesto', 'Perfil de hierro', 'Perfil de Hierro', 'TRANSFERRINA - TIBC', 10, 'ambos', '250', '425', 'ng/ml', 3),
  ('compuesto', 'Perfil de hierro', 'Perfil de Hierro', 'TRANSFERRINA - TIBC', 11, 'ambos', '250', '425', 'ng/ml', 3),
  ('compuesto', 'Perfil de hierro', 'Perfil de Hierro', 'TRANSFERRINA - TIBC', 12, 'ambos', '250', '425', 'ng/ml', 3),
  ('compuesto', 'Perfil de hierro', 'Perfil de Hierro', '% DE SATURACION', 15, 'masculino', '20', '50', '%', 3),
  ('compuesto', 'Perfil de hierro', 'Perfil de Hierro', '% DE SATURACION', 15, 'femenino', '15', '50', '%', 3),
  ('compuesto', 'Perfil de hierro', 'Perfil de Hierro', 'FERRITINA', 15, 'masculino', '16', '220', 'ng/ml', 3),
  ('compuesto', 'Perfil de hierro', 'Perfil de Hierro', 'FERRITINA', 15, 'femenino', '10', '124', 'ng/mL', 3),
  ('compuesto', 'Perfil de hierro', 'Perfil de Hierro', 'FERRITINA', 15, 'ambos', '20', '200', 'ug/l', 3),
  ('simple', 'Marcadores Tumorales', 'PSA Total', 'PSA Total', 11, 'masculino', '0', '3.1', 'ng/mL', 3),
  ('simple', 'Marcadores Tumorales', 'PSA Total', 'PSA Total', 12, 'masculino', '0', '4.4', 'ng/mL', 3),
  ('simple', 'Marcadores Tumorales', 'PSA Libre', 'PSA Libre', 15, 'ambos', '0', '1.3', 'ng/ml', 3),
  ('simple', 'Marcadores Tumorales', 'CEA', 'CEA', 15, 'ambos', '0', '5', 'ng/ml', 3),
  ('simple', 'Marcadores Tumorales', 'CA 125', 'CA 125', 15, 'ambos', '0', '35', 'U/mL', 3),
  ('simple', 'Marcadores Tumorales', 'CA 19-9', 'CA 19-9', 15, 'ambos', '0', '40', 'U/mL', 3),
  ('simple', 'Marcadores Tumorales', 'CA 15-3', 'CA 15-3', 15, 'ambos', '0', '37', 'U/ml', 3),
  ('simple', 'Marcadores Tumorales', 'Alfa Feto Proteina', 'Alfa Feto Proteina', 15, 'ambos', '0', '8.5', 'ng/ml', 3),
  ('simple', 'Marcadores Tumorales', 'Alfa Feto Proteina', 'Alfa Feto Proteina', 15, 'femenino', 'VALOR DEL PACIENTE: ng/ml VALORES NORMALES MUJER EMBARAZAO RANGO 12 SEMANAS 5 A 26 14 SEMANAS 10 A 48 16 SEMANAS 19 A 93 18 SEMANAS 27 A 137 20 SEMANAS 34 A 171 24 SEMANAS 58 A 288 28 SEMANAS 88 A 32 36 SEMANAS 86 A 426 40 SEMANAS 40 A 201', '', '', 3),
  ('simple', 'Marcadores Tumorales', 'Beta 2-Microglobulina', 'Beta 2-Microglobulina', 15, 'ambos', '0', '1900', 'ug/L', 3),
  ('simple', 'Hormonas', 'Prolactina', 'Prolactina', 15, 'masculino', '2.52', '13.23', 'ng/mL', 3),
  ('simple', 'Hormonas', 'Prolactina', 'Prolactina', 15, 'femenino', '3.27', '26.81', 'ng/mL', 3),
  ('simple', 'Endocrinología', 'Insulina Post. Estimulo 120 min', 'Insulina Post. Estimulo 120 min', 15, 'ambos', '10', '60', 'uUI/ml', 3),
  ('simple', 'Endocrinología', 'Cortisol PM.', 'Cortisol PM.', 15, 'ambos', '3', '13', 'ug/dl', 3),
  ('simple', 'Hormonas', 'Cortisol AM.', 'Cortisol AM.', 6, 'ambos', '3', '21', 'ug/dl', 3),
  ('simple', 'Hormonas', 'Cortisol AM.', 'Cortisol AM.', 12, 'ambos', '5', '23', 'ug/dl', 3),
  ('simple', 'Hormonas', 'Insulina Basal', 'Insulina Basal', 15, 'ambos', '2.7', '24.8', 'uIU/ml', 3),
  ('simple', 'Hormonas', 'Insulina Post. Estimulo 60 min', 'Insulina Post. Estimulo 60 min', 15, 'ambos', '20', '100', 'uUI/ml', 3),
  ('simple', 'Hormonas', 'Testosterona Total', 'Testosterona Total', 6, 'masculino', '0', '0.2', 'ng/dL', 3),
  ('simple', 'Hormonas', 'Testosterona Total', 'Testosterona Total', 6, 'femenino', '0', '0.95', 'ng/dL', 3),
  ('simple', 'Hormonas', 'Testosterona Total', 'Testosterona Total', 7, 'masculino', '0', '0.2', 'ng/dL', 3),
  ('simple', 'Hormonas', 'Testosterona Total', 'Testosterona Total', 7, 'femenino', '0', '0.95', 'ng/dL', 3),
  ('simple', 'Hormonas', 'Testosterona Total', 'Testosterona Total', 15, 'masculino', '0', '0.2', 'ng/dL', 3),
  ('simple', 'Hormonas', 'Testosterona Total', 'Testosterona Total', 15, 'femenino', '0', '0.95', 'ng/dL', 3),
  ('simple', 'Hormonas', 'Testosterona Total', 'Testosterona Total', 9, 'masculino', '0', '0.2', 'ng/dL', 3),
  ('simple', 'Hormonas', 'Testosterona Total', 'Testosterona Total', 9, 'femenino', '0', '0.95', 'ng/dL', 3),
  ('simple', 'Hormonas', 'Testosterona Total', 'Testosterona Total', 10, 'masculino', '2.5', '10', 'ng/dL', 3),
  ('simple', 'Hormonas', 'Testosterona Total', 'Testosterona Total', 10, 'femenino', '0', '0.95', 'ng/dL', 3),
  ('simple', 'Hormonas', 'Testosterona Total', 'Testosterona Total', 11, 'masculino', '2.5', '10', 'ng/dL', 3),
  ('simple', 'Hormonas', 'Testosterona Total', 'Testosterona Total', 11, 'femenino', '0', '0.95', 'ng/dL', 3),
  ('simple', 'Hormonas', 'Testosterona Total', 'Testosterona Total', 12, 'masculino', '2.5', '10', 'ng/dL', 3),
  ('simple', 'Hormonas', 'Testosterona Total', 'Testosterona Total', 12, 'femenino', '0', '0.95', 'ng/dL', 3),
  ('simple', 'Hormonas', 'Testosterona Libre', 'Testosterona Libre', 6, 'masculino', '0', '1', 'pg/ml', 3),
  ('simple', 'Hormonas', 'Testosterona Libre', 'Testosterona Libre', 6, 'femenino', '0', '1', 'pg/ml', 3),
  ('simple', 'Hormonas', 'Testosterona Libre', 'Testosterona Libre', 7, 'masculino', '0', '1', 'pg/ml', 3),
  ('simple', 'Hormonas', 'Testosterona Libre', 'Testosterona Libre', 7, 'femenino', '0', '1', 'pg/ml', 3),
  ('simple', 'Hormonas', 'Testosterona Libre', 'Testosterona Libre', 15, 'masculino', '0', '1', 'pg/ml', 3),
  ('simple', 'Hormonas', 'Testosterona Libre', 'Testosterona Libre', 15, 'femenino', '0', '1', 'pg/ml', 3),
  ('simple', 'Hormonas', 'Testosterona Libre', 'Testosterona Libre', 9, 'masculino', '0', '1', 'pg/ml', 3),
  ('simple', 'Hormonas', 'Testosterona Libre', 'Testosterona Libre', 9, 'femenino', '0', '1', 'pg/ml', 3),
  ('simple', 'Hormonas', 'Testosterona Libre', 'Testosterona Libre', 11, 'masculino', '6.1', '30.3', 'pg/ml', 3),
  ('simple', 'Hormonas', 'Testosterona Libre', 'Testosterona Libre', 11, 'femenino', '0.3', '4.4', 'pg/ml', 3),
  ('simple', 'Hormonas', 'Testosterona Libre', 'Testosterona Libre', 12, 'masculino', '6.1', '27.9', 'pg/ml', 3),
  ('simple', 'Hormonas', 'Estradiol', 'Estradiol', 15, 'masculino', '4', '94', 'pg/ml', 3),
  ('simple', 'Hormonas', 'Estradiol', 'Estradiol', 15, 'femenino', '0', '290', 'pg/ml', 3),
  ('simple', 'Hormonas', 'FSH', 'FSH', 15, 'ambos', '1', '10', 'mUI/ml', 3),
  ('simple', 'Hormonas', 'FSH', 'FSH', 12, 'femenino', '1.4', '135.6', 'mUI/ml', 3),
  ('simple', 'Hormonas', 'FSH', 'FSH', 11, 'masculino', '1.4', '12.6', 'mUI/ml', 3),
  ('simple', 'Hormonas', 'LH', 'LH', 15, 'ambos', '0', '92.53', 'mUI/ml', 3),
  ('simple', 'Hormonas', 'LH', 'LH', 12, 'femenino', '0.4', '82', 'mUI/ml', 3),
  ('simple', 'Hormonas', 'LH', 'LH', 15, 'masculino', '0.7', '7.4', 'mUI/ml', 3),
  ('simple', 'Hormonas', 'Progesterona', 'Progesterona', 15, 'masculino', '0.13', '1.22', 'ng/ml', 3),
  ('simple', 'Hormonas', 'Progesterona', 'Progesterona', 15, 'femenino', '0.15', '25', 'ng/ml', 3),
  ('simple', 'Hormonas', 'antimulleriana', 'antimulleriana', 15, 'femenino', '0.02', '9.77', 'ng/ml', 3),
  ('simple', 'Hormonas', 'H. de Crecimiento', 'H. de Crecimiento', 15, 'ambos', '0.12', '10.5', 'ng/ml', 3),
  ('simple', 'Elisas Inmunologia', 'Androstediona', 'Androstediona', 15, 'femenino', '30', '200', 'ng/ml', 3),
  ('simple', 'Endocrinología', 'DHEA Sulfato', 'DHEA Sulfato', 15, 'femenino', '0.03', '5.88', 'ug/ml', 3),
  ('simple', 'Endocrinología', 'DHEA Sulfato', 'DHEA Sulfato', 15, 'masculino', '0.06', '4.58', 'ug/ml', 3),
  ('simple', 'Biología Molecular', 'S.H.B.G', 'S.H.B.G', 15, 'femenino', '30', '135', 'nmol/l', 3),
  ('simple', 'Biología Molecular', 'S.H.B.G', 'S.H.B.G', 15, 'masculino', '11', '80', 'nmol/l', 3),
  ('simple', 'Endocrinología', 'Peptido C', 'Peptido C', 15, 'ambos', '1.1', '4.4', 'ng/ml', 3),
  ('simple', 'Endocrinología', 'Paratohormona', 'Paratohormona', 15, 'ambos', '10.4', '66.5', 'pg/ml', 3),
  ('simple', 'Otros', '17 OH Progesterona', '17 OH Progesterona', 6, 'masculino', '0', '13.7', 'ng/ml', 3),
  ('simple', 'Otros', '17 OH Progesterona', '17 OH Progesterona', 6, 'femenino', '0.1', '16.8', 'ng/ml', 3),
  ('simple', 'Otros', '17 OH Progesterona', '17 OH Progesterona', 7, 'masculino', '0', '13.7', 'ng/ml', 3),
  ('simple', 'Otros', '17 OH Progesterona', '17 OH Progesterona', 7, 'femenino', '0.1', '16.8', 'ng/ml', 3),
  ('simple', 'Otros', '17 OH Progesterona', '17 OH Progesterona', 15, 'masculino', '0.1', '1.7', 'ng/ml', 3),
  ('simple', 'Otros', '17 OH Progesterona', '17 OH Progesterona', 15, 'femenino', '0.1', '4.5', 'ng/ml', 3),
  ('simple', 'Otros', '17 OH Progesterona', '17 OH Progesterona', 9, 'masculino', '0.1', '1.7', 'ng/ml', 3),
  ('simple', 'Otros', '17 OH Progesterona', '17 OH Progesterona', 9, 'femenino', '0.1', '4.5', 'ng/ml', 3),
  ('simple', 'Otros', '17 OH Progesterona', '17 OH Progesterona', 10, 'masculino', '0.5', '2.1', 'ng/ml', 3),
  ('simple', 'Otros', '17 OH Progesterona', '17 OH Progesterona', 10, 'femenino', '0.1', '12', 'ng/ml', 3),
  ('simple', 'Otros', '17 OH Progesterona', '17 OH Progesterona', 11, 'masculino', '0.5', '2.1', 'ng/ml', 3),
  ('simple', 'Otros', '17 OH Progesterona', '17 OH Progesterona', 11, 'femenino', '0.1', '12', 'ng/ml', 3),
  ('simple', 'Otros', '17 OH Progesterona', '17 OH Progesterona', 12, 'masculino', '0.5', '2.1', 'ng/ml', 3),
  ('simple', 'Otros', '17 OH Progesterona', '17 OH Progesterona', 12, 'femenino', '0.1', '12', 'ng/ml', 3),
  ('simple', 'Endocrinología', 'IGF-1', 'IGF-1', 6, 'ambos', '17', '99', 'ng/ml', 3),
  ('simple', 'Endocrinología', 'IGF-1', 'IGF-1', 15, 'ambos', '75', '850', 'ng/ml', 3),
  ('simple', 'Endocrinología', 'IGF-1', 'IGF-1', 12, 'ambos', '60', '350', 'ng/ml', 3),
  ('simple', 'Perfil Tiroideo', 'T4', 'T4', 15, 'ambos', '5', '13', 'ug/dl', 3),
  ('simple', 'Endocrinología', 'T3 libre', 'T3 libre', 15, 'ambos', '2', '4.2', 'pg/ml', 3),
  ('simple', 'Perfil Tiroideo', 'T4 Libre', 'T4 Libre', 15, 'ambos', '0.9', '1.75', 'ng/dL', 3),
  ('simple', 'Perfil Tiroideo', 'TSH', 'TSH', 15, 'ambos', '0.3', '4.5', 'uUI/ml', 3),
  ('simple', 'Vitaminas', 'TSH Ultra', 'TSH Ultra', 15, 'ambos', '0.3', '4.5', 'uUI/ml', 3),
  ('simple', 'Perfil Tiroideo', 'TSH Neonatal', 'TSH Neonatal', 15, 'ambos', '0.6', '17', 'mUI/ml', 3),
  ('simple', 'Elisa Hepatitis', 'HAV IgM', 'HAV IgM', 15, 'ambos', '0', '1.1', '', 3),
  ('simple', 'Elisa Hepatitis', 'HAV IgM', 'HAV IgM', 12, 'ambos', '0', '1.1', '', 3),
  ('simple', 'Elisa Hepatitis', 'HAV IgG', 'HAV IgG', 15, 'ambos', '0', '1.1', '', 3),
  ('simple', 'Endocrinologia', 'ANTI-TPO (Anticuerpos anti-peroxidasa tiroidea)', 'ANTI-TPO (Anticuerpos anti-peroxidasa tiroidea)', 15, 'ambos', '0', '40', 'UI/ml', 3),
  ('simple', 'Endocrinologia', 'ANTI-TG (Anticuerpos anti-tiroglobulina)', 'ANTI-TG (Anticuerpos anti-tiroglobulina)', 15, 'ambos', '0', '125', 'UI/ml', 3),
  ('simple', 'Endocrinologia', 'ANTI-TRAb (Anticuerpos anti-receptor de TSH)', 'ANTI-TRAb (Anticuerpos anti-receptor de TSH)', 15, 'ambos', '0', '1.5', 'IU/L', 3),
  ('simple', 'Elisas', 'Clamidya Ig M', 'Clamidya Ig M', 15, 'ambos', 'NEGATIVO DEBIL POSITOVO POSITIVO', '', '', 3),
  ('simple', 'Elisas', 'Clamidya Ig G', 'Clamidya Ig G', 15, 'ambos', '0', '11', '', 3),
  ('simple', 'Elisa Hepatitis', 'AgsHB', 'AgsHB', 15, 'ambos', '0', '10', 'U/ml', 3),
  ('simple', 'Elisa Hepatitis', 'HVC Hepatitis C', 'HVC Hepatitis C', 15, 'ambos', 'NEGATIVO POSITIVO', '', '', 3),
  ('simple', 'Elisas', 'Toxos. Ig G', 'Toxos. Ig G', 15, 'ambos', '', '', '', 3),
  ('simple', 'Elisas', 'H. Pilory IgA', 'H. Pilory IgA', 15, 'ambos', '70', '400', 'mg/dl', 3),
  ('simple', 'Elisas', 'CHAGAS ELISA', 'CHAGAS ELISA', 15, 'ambos', 'NEGATIVO POSITIVO > 1/40', '', '', 3),
  ('simple', 'Serología Viral', 'Epstein Barr Virus (EBV) IgM', 'Epstein Barr Virus (EBV) IgM', 15, 'ambos', '0', '1.1', '', 3),
  ('simple', 'Serología Viral', 'Epstein Barr Virus (EBV) IgG', 'Epstein Barr Virus (EBV) IgG', 15, 'ambos', '0', '1.1', '', 3),
  ('simple', 'Elisas Inmunologia', 'ANTI-DNA', 'ANTI-DNA', 15, 'ambos', '0', '46.4', 'UI/ml', 3),
  ('simple', 'Elisas Inmunologia', 'ANTI CCP', 'ANTI CCP', 15, 'ambos', '0', '17', 'U/ml', 3),
  ('simple', 'Elisas Inmunologia', 'ANA', 'ANA', 15, 'ambos', '1/80 Patron Moteado Fino POSITIVO > 1/40', '', '', 3),
  ('simple', 'Autoinmunidad / Enfermedades Reumatológicas', 'ANTI DNP', 'ANTI DNP', 15, 'ambos', 'NEGATIVO POSITIVO DIL 1/2', '', '', 3),
  ('simple', 'Elisas Inmunologia', 'Anti B2 Glicoproteina IgM', 'Anti B2 Glicoproteina IgM', 15, 'ambos', '0', '8', 'UI/ml', 3),
  ('simple', 'Elisas Inmunologia', 'Anti B2 Glicoproteina IgG', 'Anti B2 Glicoproteina IgG', 15, 'ambos', '0', '8', 'UI/ml', 3),
  ('simple', 'Elisas Inmunologia', 'Anti Cardiolipina IgM', 'Anti Cardiolipina IgM', 15, 'ambos', '0', '7', 'UI/ml', 3),
  ('simple', 'Elisas Inmunologia', 'Anti Cardiolipina IgG', 'Anti Cardiolipina IgG', 15, 'ambos', '0', '10', 'UI/ml', 3),
  ('simple', 'HEPATITIS AUTOINMUNE', 'Anticuerpos LE', 'Anticuerpos LE', 15, 'ambos', 'NEGATIVO POSITIVO', '', '', 3),
  ('simple', 'Trombofilia autoinmune', 'Anti coagulante lúpico', 'Anti coagulante lúpico', 15, 'ambos', 'NEGATIVO POSITIVO', '', '', 3),
  ('simple', 'Radioinmunoensayo', 'C3', 'C3', 15, 'ambos', '90', '180', 'mg/dl', 3),
  ('simple', 'Radioinmunoensayo', 'C4', 'C4', 15, 'ambos', '10', '40', 'mg/dl', 3),
  ('simple', 'Elisas Inmunologia', 'ANCA C', 'ANCA C', 15, 'ambos', '0', '0.9', '', 3),
  ('simple', 'Elisas Inmunologia', 'ANCA P', 'ANCA P', 15, 'ambos', '0', '0.9', '', 3),
  ('simple', 'HEPATITIS AUTOINMUNE', 'ASMA', 'ASMA', 15, 'ambos', 'NEGATIVO POSITIVO', '', '', 3),
  ('simple', 'HEPATITIS AUTOINMUNE', 'AMA', 'AMA', 15, 'ambos', 'NEGATIVO POSITIVO', '', '', 3),
  ('simple', 'Elisas Inmunologia', 'ANCA PR3', 'ANCA PR3', 15, 'ambos', '0', '5', 'UI/ml', 3),
  ('simple', 'Elisas Inmunologia', 'ANCA MPO', 'ANCA MPO', 15, 'ambos', '0', '5', 'UI/ml', 3),
  ('compuesto', 'Elisas Inmunologia', 'ENA PROFILE', 'Anti SSA/Ro', 15, 'ambos', '0', '0.9', '', 3),
  ('compuesto', 'Elisas Inmunologia', 'ENA PROFILE', 'Anti SSB/La', 15, 'ambos', '0', '0.9', '', 3),
  ('compuesto', 'Elisas Inmunologia', 'ENA PROFILE', 'Anti Sm', 15, 'ambos', '0', '0.9', '', 3),
  ('compuesto', 'Elisas Inmunologia', 'ENA PROFILE', 'Anti Sm/RNP', 15, 'ambos', '0', '0.9', '', 3),
  ('compuesto', 'Elisas Inmunologia', 'ENA PROFILE', 'Anti Jo-1', 15, 'ambos', '0', '0.9', '', 3),
  ('compuesto', 'Elisas Inmunologia', 'ENA PROFILE', 'Anti  SCL - 70', 15, 'ambos', '0', '0.9', '', 3),
  ('compuesto', 'Elisas Inmunologia', 'ENA PROFILE', 'Células LE Latex', 15, 'ambos', 'NEGATIVO POSITIVO', '', '', 3),
  ('simple', 'Elisas', 'HIV 1 - 2', 'HIV 1 - 2', 15, 'ambos', 'NO REACTIVO POSITIVO', '', '', 3),
  ('simple', 'Inmunología', 'IgE', 'IgE', 6, 'ambos', '0', '146', 'UI/ml', 3),
  ('simple', 'Inmunología', 'IgE', 'IgE', 7, 'ambos', '0', '146', 'UI/ml', 3),
  ('simple', 'Inmunología', 'IgE', 'IgE', 15, 'ambos', '0', '280', 'UI/ml', 3),
  ('simple', 'Inmunología', 'IgE', 'IgE', 9, 'ambos', '0', '280', 'UI/ml', 3),
  ('simple', 'Inmunología', 'IgE', 'IgE', 10, 'ambos', '0', '280', 'UI/ml', 3),
  ('simple', 'Inmunología', 'IgE', 'IgE', 11, 'ambos', '0', '200', 'UI/ml', 3),
  ('simple', 'Elisas', 'Herpes 1 Ig G', 'Herpes 1 Ig G', 6, 'ambos', '630', '1380', 'mg/dL', 3),
  ('simple', 'Elisas', 'Herpes 1 Ig G', 'Herpes 1 Ig G', 7, 'ambos', '630', '1380', 'mg/dL', 3),
  ('simple', 'Elisas', 'Herpes 1 Ig G', 'Herpes 1 Ig G', 15, 'ambos', '630', '1380', 'mg/dL', 3),
  ('simple', 'Elisas', 'Herpes 1 Ig G', 'Herpes 1 Ig G', 9, 'ambos', '630', '1380', 'mg/dL', 3),
  ('simple', 'Elisas', 'Herpes 1 Ig G', 'Herpes 1 Ig G', 10, 'ambos', '700', '1600', 'mg/dL', 3),
  ('simple', 'Elisas', 'Herpes 1 Ig G', 'Herpes 1 Ig G', 11, 'ambos', '700', '1600', 'mg/dL', 3),
  ('simple', 'Elisas', 'Herpes 1 Ig G', 'Herpes 1 Ig G', 12, 'ambos', '700', '1600', 'mg/dL', 3),
  ('simple', 'Elisas', 'Herpes 1 Ig M', 'Herpes 1 Ig M', 6, 'ambos', '20', '134', 'mg/dL', 3),
  ('simple', 'Elisas', 'Herpes 1 Ig M', 'Herpes 1 Ig M', 7, 'ambos', '20', '134', 'mg/dL', 3),
  ('simple', 'Elisas', 'Herpes 1 Ig M', 'Herpes 1 Ig M', 15, 'ambos', '20', '134', 'mg/dL', 3),
  ('simple', 'Elisas', 'Herpes 1 Ig M', 'Herpes 1 Ig M', 9, 'ambos', '20', '134', 'mg/dL', 3),
  ('simple', 'Elisas', 'Herpes 1 Ig M', 'Herpes 1 Ig M', 10, 'ambos', '40', '230', 'mg/dL', 3),
  ('simple', 'Elisas', 'Herpes 1 Ig M', 'Herpes 1 Ig M', 11, 'ambos', '40', '230', 'mg/dL', 3),
  ('simple', 'Elisas', 'Herpes 1 Ig M', 'Herpes 1 Ig M', 12, 'ambos', '40', '230', 'mg/dL', 3),
  ('simple', 'Elisas', 'H. Pilory IgA', 'H. Pilory IgA', 6, 'ambos', '0', '240', 'mg/dL', 3),
  ('simple', 'Elisas', 'H. Pilory IgA', 'H. Pilory IgA', 7, 'ambos', '0', '240', 'mg/dL', 3),
  ('simple', 'Elisas', 'H. Pilory IgA', 'H. Pilory IgA', 9, 'ambos', '0', '240', 'mg/dL', 3),
  ('simple', 'Elisas', 'H. Pilory IgA', 'H. Pilory IgA', 10, 'ambos', '70', '400', 'mg/dL', 3),
  ('simple', 'Elisas', 'H. Pilory IgA', 'H. Pilory IgA', 11, 'ambos', '70', '400', 'mg/dL', 3),
  ('simple', 'Elisas', 'H. Pilory IgA', 'H. Pilory IgA', 12, 'ambos', '70', '400', 'mg/dL', 3),
  ('compuesto', 'HEPATITIS AUTOINMUNE', 'ASMA', 'Tropomiosina', 15, 'ambos', '0', '0.9', '', 3),
  ('compuesto', 'HEPATITIS AUTOINMUNE', 'ASMA', 'Actina', 15, 'ambos', '0', '0.9', '', 3),
  ('compuesto', 'HEPATITIS AUTOINMUNE', 'ASMA', 'F-actina', 15, 'ambos', '0', '0.9', '', 3),
  ('compuesto', 'HEPATITIS AUTOINMUNE', 'ANTI LKM - 1', 'LC1', 15, 'ambos', '0', '0.9', '', 3),
  ('simple', 'HEPATITIS AUTOINMUNE', 'ANTI LKM - 1', 'ANTI LKM - 1', 15, 'ambos', 'NEGATIVO POSITIVO', '', '', 3),
  ('compuesto', 'HEPATITIS AUTOINMUNE', 'AMA', 'AMA-M2', 15, 'ambos', '0', '0.9', '', 3),
  ('simple', 'Pruebas Gastricas', 'Antiendomisio IgA', 'Antiendomisio IgA', 15, 'ambos', 'NEGATIVO POSITIVO > 1/5', '', '', 3),
  ('simple', 'Pruebas Gastricas', 'Antiendomisio IgG', 'Antiendomisio IgG', 15, 'ambos', 'NEGATIVO POSITIVO > 1/5', '', '', 3),
  ('simple', 'Pruebas Gastricas', 'Antitransglutaminasa IgA', 'Antitransglutaminasa IgA', 15, 'ambos', '0', '10', 'UI/ml', 3),
  ('simple', 'Pruebas Gastricas', 'Antitransglutaminasa IgG', 'Antitransglutaminasa IgG', 15, 'ambos', '0', '10', 'UI/ml', 3),
  ('simple', 'Pruebas Gastricas', 'Antigliadina IgA', 'Antigliadina IgA', 15, 'ambos', '0', '12', 'UI/ml', 3),
  ('simple', 'Pruebas Gastricas', 'Antigliadina IgG', 'Antigliadina IgG', 15, 'ambos', '0', '12', 'UI/ml', 3),
  ('simple', 'Gastroenterología / Autoinmunidad', 'Anti DGP IgG', 'Anti DGP IgG', 15, 'ambos', '0', '10', 'U/L', 3),
  ('simple', 'Gastroenterología / Autoinmunidad', 'Anti DGP IgA', 'Anti DGP IgA', 15, 'ambos', '0', '10', 'U/ml', 3),
  ('simple', 'Inmunocromatografia', 'Dengue', 'Dengue', 15, 'ambos', 'NEGATIVO POSITIVO', '', '', 3),
  ('simple', 'Inmunocromatografia', 'Chikunguña', 'Chikunguña', 15, 'ambos', 'NEGATIVO POSITIVO', '', '', 3),
  ('simple', 'Elisas Inmunologia', 'Vitamina D', 'Vitamina D', 15, 'ambos', '30', '100', 'ng/ml', 3),
  ('simple', 'Vitaminas', 'Vitamina B12', 'Vitamina B12', 6, 'ambos', '200', '835', 'pg/ml', 3),
  ('simple', 'Otros', 'Vitamina C', 'Vitamina C', 15, 'ambos', '29 GOTAS 0 - 5 gota: NORMAL 6 - 10 gotas: DEFICIENCIA LEVE 11 - 15 gotas: DEFICIENCIA MODERADA 16 - 50 gotas: DEFICIENCIA SEVERA', '', '', 3),
  ('simple', 'Vitaminas', 'Vitamina B9', 'Vitamina B9', 15, 'ambos', '3', '20', 'ng/ml', 3),
  ('simple', 'Hormonas', 'B-hCG Cuantitativa', 'B-hCG Cuantitativa', 15, 'femenino', 'NEGATIVO POSITIVO', '', '', 3),
  ('simple', 'Inmunología', 'Crioglubulinas', 'Crioglubulinas', 15, 'ambos', '0', '1000', 'mg/dl', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'COLOR', 15, 'ambos', 'AMARRILLO AMARILLO CLARO AMARILLO OSCURO AMARILLO PALIDO AMARILLO VERDOSO AMARILLO NARANJA AMBAR AMBAR OSCURO NARANJA ANARANJADO ROJO ROJIZO ROJO OSCURO ROJO CLARO SANGUINOLENTO CAFE VERDE BLANCO NEGRO', '', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'ASPECTO', 15, 'ambos', 'LIMPIDO LIGERAMENTE OPALESCENTE OPALESCENTE TURBIO', '', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'GLUCOSA', 15, 'ambos', 'NEGATIVO TRAZAS POSITIVO ( + ) POSITIVO ( ++ ) POSITIVO ( +++ )', '', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'BILIRRUBINA', 15, 'ambos', 'NEGATIVO TRAZAS POSITIVO ( + ) POSITIVO ( ++ ) POSITIVO ( +++ )', '', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'CETONA', 15, 'ambos', 'NEGATIVO TRAZAS POSITIVO ( + ) POSITIVO ( ++ ) POSITIVO ( +++ )', '', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'DENSIDAD', 15, 'ambos', '1010', '1025', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'HEMOGLOBINA', 15, 'ambos', 'NEGATIVO TRAZAS POSITIVO ( + ) POSITIVO ( ++ ) POSITIVO ( +++ )', '', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'PH', 15, 'ambos', '5000', '7000', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'PROTEINA', 15, 'ambos', 'NEGATIVO TRAZAS POSITIVO ( + ) POSITIVO ( ++ ) POSITIVO ( +++ )', '', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'UROBILINOGENO', 15, 'ambos', 'NEGATIVO TRAZAS POSITIVO ( + ) POSITIVO ( ++ ) POSITIVO ( +++ )', '', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'NITRITOS', 15, 'ambos', 'NEGATIVO POSITIVO', '', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'ESTERSA LEUCOCITARIA', 15, 'ambos', 'NEGATIVO TRAZAS POSITIVO ( + ) POSITIVO ( ++ ) POSITIVO ( +++ )', '', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'Hematíes eumorficos', 15, 'ambos', 'AUSENTE 1 POR CAMPO 2 POR CAMPO 3 POR CAMPO 4 POR CAMPO 5 POR CAMPO 6 POR CAMPO 8 POR CAMPO 10 POR CAMPO 12 POR CAMPO 14 POR CAMPO 15 POR CAMPOI 16 POR CAMPO 18 POR CAMPO 20 POR CAMPO 25 POR CAMPO 30 POR CAMPO 35 POR CAMPO 50 POR CAMPO ABUNDANTE CANTIDAD O - 1 POR CAMPO 28 POR CAMPO 24 POR CAMPO', '', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'LEUCOCITOS', 15, 'ambos', 'AUSENTE 1 POR CAMPO 2 POR CAMPO 3 POR CAMPO 4 POR CAMPO 5 POR CAMPO 6 POR CAMPO 8 POR CAMPO 10 POR CAMPO 12 POR CAMPO 14 POR CAMPO 15 POR CAMPOI 16 POR CAMPO 18 POR CAMPO 20 POR CAMPO 25 POR CAMPO 30 POR CAMPO 35 POR CAMPO 50 POR CAMPO ABUNDANTE CANTIDAD O - 1 POR CAMPO 28 POR CAMPO 24 POR CAMPO', '', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'ACUMULO LEUCOCITARIO', 15, 'ambos', 'AUSENTE PRESENTE', '', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'PIOCITOS', 15, 'ambos', 'AUSENTE 0 - 1 POR CAMPO 2 - 3 POR CAMPO 3 - 4 POR CAMPO 4 - 5 POR CAMPO 8 - 10 POR CAMPO 1 CADA 3 CAMPOS 1 CADA 5 CAMPOS 1 CADA 10 CAMPOS 1 POR 4 CAMPOS 1 POR CAMPO 2 POR CAMPO 3 POR CAMPO 4 POR CAMPO 5 POR CAMPO 6 POR CAMPO', '', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'CELULAS EPITELIALES', 15, 'ambos', 'AUSENTE ESCASA CANTIDAD REGULAR CANTIDAD ABUNDANTE CANTIDAD CAMPO CUBIERTO', '', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'CELULAS REDONDAS', 15, 'ambos', 'AUSENTE 0 - 1 POR CAMPO 2 - 4 POR CAMPO 4 - 6 POR CAMPO 8 - 10 POR CAMPO 1 CADA 3 CAMPOS 1 CADA 5 CAMPOS 1 CADA 10 CAMPOS 1 POR CAMPO 2 POR CAMPO 3 POR CAMPO 4 POR CAMPO 5 POR CAMPO 6 POR CAMPO 8 POR CAMPO 10 POR CAMPO 15 POR CAMPO ABUDANTE CANTIDAD', '', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'FLORA BACTERIANA', 15, 'ambos', 'AUSENTE ESCASA CANTIDAD REGULAR CANTIDAD ABUNDANTE CANTIDAD CAMPO CUBIERTO', '', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'FILAMENTO MUCOIDE', 15, 'ambos', 'AUSENTE ESCASA CANTIDAD REGULAR CANTIDAD ABUNDANTE CANTIDAD CAMPO CUBIERTO', '', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'CRISTALES', 15, 'ambos', 'AUSENTE URATO AMORFO ESCASA CANTIDAD URATO AMORFO REGULAR CANTIDAD URATO AMORFO ABUNDANTE CANTIDAD URATO AMORFO CAMPO CUBIERTO OXALATO DE CALCIO ESCASA CANTIDAD OXALATO DE CALCIO REGULAR CANTIDAD OXALATO DE CALCIO ABUNDANTE CANTIDAD OXALATO DE CALCIO CAMPO CUBIERTO AC. URICO ESCASA CANTIDAD AC. URICO REGULAR CANTIDAD AC. URICO ABUNDANTE CANTIDAD AC. URICO CAMPO CUBIERTO FOSFATO AMORFO ESCASA CANTIDAD FOSFATO AMORFO REGULAR CANTIDAD FOSFATO AMORFO ABUNDANTE CANTIDAD FOSFATO AMORFO CAMPO CUBIERTO FOSFATO TRIPLE ESCASA CANTIDAD FOSFATO TRIPLE REGULAR CANTIDAD FOSFATO TRIPLE ABUNDANTE CANTIDAD FOSFATO TRIPLE CAMPO CUBIERTO MICRO OXALATO DE CALCIO ESCASA CANTIDAD MICRO OXALATO DE CALCIO REGULAR CANTIDAD MICRO OXALATO DE CALCIO ABUNDANTE CANTIDAD MICRO OXALATO DE CALCIO CAMPO CUBIERTO CISTINA ESCASA CANTIDAD CISTINA REGULAR CANTIDAD CISTINA ABUNDANTE CANTIDAD CISTINA CAMPO CUBIERTO OXALATO DE CALCIO MONOHIDRATADO ESCASA CANTIDAD OXALATO DE CALCIO MONOHIDRATADO REGULAR CANTIDAD OXALATO DE CALCIO MONOHIDRATADO ABUNDANTE CANTI DAD OXALATO DE CALCIO MONOHIDRATADO CAMPO CUBIERTO OXALATO DE CALCIO DIHIDRATADO ESCASA CANTIDAD OXALATO DE CALCIO DIHIDRATADO REGULAR CANTIDAD OXALATO DE CALCIO DIHIDRATADO REGULAR CANTIDAD OXALATO DE CALCIO DIHIDRATADO ABUNDANTE CANTIDA', '', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'CILINDROS HIALINOS', 15, 'ambos', 'AUSENTE 0 - 1 POR CAMPO 2 - 4 POR CAMPO 4 - 6 POR CAMPO 8 - 10 POR CAMPO 1 CADA 3 CAMPOS 1 CADA 5 CAMPOS 1 CADA 10 CAMPOS 1 POR CAMPO 2 POR CAMPO 3 POR CAMPO 4 POR CAMPO 5 POR CAMPO 6 POR CAMPO 8 POR CAMPO 10 POR CAMPO 15 POR CAMPO ABUDANTE CANTIDAD', '', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'CILINDROS GRANULOSOS', 15, 'ambos', 'AUSENTE 0 - 1 POR CAMPO 2 - 4 POR CAMPO 4 - 6 POR CAMPO 8 - 10 POR CAMPO 1 CADA 3 CAMPOS 1 CADA 5 CAMPOS 1 CADA 10 CAMPOS 1 POR CAMPO 2 POR CAMPO 3 POR CAMPO 4 POR CAMPO 5 POR CAMPO 6 POR CAMPO 8 POR CAMPO 10 POR CAMPO 15 POR CAMPO ABUDANTE CANTIDAD', '', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'CILINDROS LEUCOOCITARIO', 15, 'ambos', 'ausente 0 - 1 POR CAMPO 1 - 2 POR CAMPO 3 - 4 POR CAMPO 4 - 5 POR CAMPO 6 - 8 POR CAMPO 8 - 10 POR CAMPO 10 - 12 POR CAMPO 12 - 14 POR CAMPO 1 CADA 2 CAMPOS 1 CADA 4 CAMPOS 1 CADA 5 CAMPOS 1 CADA 6 CAMPOS 1 CADA 8 CAMPOS 1 CADA 10 CAMPOS 1 POR CAMPO 2 POR CAMPO 3 POR CAMPO 4 POR CAMPO 5 POR CAMPO 6 POR CAMPO 8 POR CAMPO 10 POR CAMPO 12 POR CAMPO 14 POR CAMPO 15 POR CAMPOI 16 POR CAMPO 18 POR CAMPO 20 POR CAMPO', '', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'PARACITOS', 15, 'ambos', 'AUSENTE TRICHOMONA VAGINALIS', '', '', 3),
  ('compuesto', 'Orina', 'Parcial de Orina', 'OBSERVACIONES', 15, 'femenino', 'SE OBSERVA CÉLULAS CLAVE. SE RECOMIENDA REALIZAR TINCIÓN DE GRAM DE SECRECIÓN VAGINAL', '', '', 3),
  ('compuesto', 'Orina', 'Clearence de Creatinina', 'CREATININA URINARIA', 15, 'ambos', '40', '120', 'mg/dl', 3),
  ('compuesto', 'Orina', 'Clearence de Creatinina', 'CREATININA SERICA', 15, 'femenino', '0.6', '1.2', 'mg/dl', 3),
  ('compuesto', 'Orina', 'Clearence de Creatinina', 'CREATININA SERICA', 15, 'masculino', '0.8', '1.3', 'mg/dl', 3),
  ('compuesto', 'Orina', 'Clearence de Creatinina', 'VOLUMEN DE ORINA DE 24 HORAS', 15, 'ambos', '800', '2000', 'ml/24h', 3),
  ('compuesto', 'Orina', 'Clearence de Creatinina', 'Depuración de Creatinina Endógena (D.C.E.)', 15, 'ambos', '88', '128', 'ml/min', 3),
  ('compuesto', 'Orina', 'Proteinuria 24Hr', 'VOLUMEN DE MUESTRA DE 24 HRS', 15, 'ambos', '800', '2000', 'ml/24h', 3),
  ('compuesto', 'Orina', 'Proteinuria 24Hr', 'RESULTADO DE PROTEINURIA DE 24 HRS', 15, 'ambos', '24', '141', 'mg/24 hrs', 3),
  ('compuesto', 'Nefrología', 'RELACION DE PROTEINA/CREATININA', 'Rel.Proteina/creatinina', 15, 'ambos', 'PROTEINURIA : 87mg/dl CREATINURIA : 44 mg/dl REL. Prot/ Crea : 1.9', '', '', 3),
  ('compuesto', 'Nefrología', 'RELACION ALBUMINA/ CREATININA', 'Relaciòn A/C', 15, 'ambos', '0', '30', 'mg/g', 3),
  ('simple', 'Orina', 'Urea en orina de 24 horas', 'Urea en orina de 24 horas', 15, 'ambos', '20', '35', 'g/24hrs', 3),
  ('simple', 'Orina', 'Creatinina en orina matinal', 'Creatinina en orina matinal', 11, 'femenino', '50', '130', 'mg/dl', 3),
  ('simple', 'Orina', 'Creatinina en orina matinal', 'Creatinina en orina matinal', 12, 'femenino', '40', '110', 'mg/dl', 3),
  ('simple', 'Orina', 'Creatinina en orina matinal', 'Creatinina en orina matinal', 11, 'masculino', '70', '180', 'mg/dl', 3),
  ('simple', 'Orina', 'Creatinina en orina matinal', 'Creatinina en orina matinal', 12, 'masculino', '50', '150', 'mg/dl', 3),
  ('simple', 'Orina', 'Creatinina en orina de 24 horas', 'Creatinina en orina de 24 horas', 15, 'femenino', '0.8', '2', 'g/24 hrs', 3),
  ('simple', 'Orina', 'Creatinina en orina de 24 horas', 'Creatinina en orina de 24 horas', 15, 'masculino', '0.6', '1.8', 'g/24 hrs', 3),
  ('simple', 'Orina', 'Sodio (Na) en orina matinal', 'Sodio (Na) en orina matinal', 15, 'ambos', '40', '220', 'mEq/L', 3),
  ('simple', 'Orina', 'Potasio (K) en orina matinal', 'Potasio (K) en orina matinal', 15, 'ambos', '25', '120', 'mEq/L', 3),
  ('simple', 'Orina', 'Cloro (Cl) en orina matinal', 'Cloro (Cl) en orina matinal', 15, 'ambos', '110', '250', 'mEq/L', 3),
  ('simple', 'Orina', 'Calcio (Ca) en orina matinal', 'Calcio (Ca) en orina matinal', 15, 'ambos', '4', '12', 'mg/dl', 3),
  ('simple', 'Orina', 'Calcio (Ca) en orina matinal', 'Calcio (Ca) en orina matinal', 9, 'ambos', '4', '10', 'mg/dl', 3),
  ('simple', 'Orina', 'Calcio (Ca) en orina matinal', 'Calcio (Ca) en orina matinal', 12, 'ambos', '5', '35.7', 'mg/dl', 3),
  ('simple', 'Orina', 'Fosforo (p) en orina matinal', 'Fosforo (p) en orina matinal', 15, 'ambos', '400', '1300', 'mg/dl', 3),
  ('simple', 'Orina', 'Microalbuminuria en orina casual', 'Microalbuminuria en orina casual', 15, 'ambos', '0', '15', 'mg/dl', 3),
  ('simple', 'Orina', 'Microalbuminuria en orina de 24 horas', 'Microalbuminuria en orina de 24 horas', 15, 'ambos', '30', '300', 'mg/24 hrs.', 3),
  ('simple', 'Orina', 'Sodio (Na) en orina - 24 horas', 'Sodio (Na) en orina - 24 horas', 15, 'ambos', '40', '220', 'mEq/24h', 3),
  ('simple', 'Orina', 'Potasio (K) en orina  -  24 horas', 'Potasio (K) en orina  -  24 horas', 15, 'ambos', '25', '120', 'mEq/24h', 3),
  ('simple', 'Orina', 'Cloro (Cl) en orina - 24 horas', 'Cloro (Cl) en orina - 24 horas', 15, 'ambos', '110', '250', 'mEq/24h', 3),
  ('simple', 'Orina', 'Calcio (Ca) en orina - 24 horas', 'Calcio (Ca) en orina - 24 horas', 15, 'ambos', '5', '280', 'mg/24 hrs', 3),
  ('compuesto', 'Heces', 'Coproparasitologico', 'COLOR', 15, 'ambos', 'MARRON MARRON OSCURO MARRON CLARO CASTAÑO AMARILLENTO VERDOSO ROJIZO SANGUINOLENTO BLANQUECINO NEGRO AMARILLO PALIDO', '', '', 3),
  ('compuesto', 'Heces', 'Coproparasitologico', 'CONSISTENCIA', 15, 'ambos', 'PASTOSA SEMI PASTOSA LIQUIDA SEMI LIQUIDA MUCOIDE DIARREICA DURA SEMI DURA FLEMOSO SEMI FLEMOSO SEMI DIARREICA SEMI MUCOIDE BLANDA LIQUIDA FLEMOSA', '', '', 3),
  ('compuesto', 'Heces', 'Coproparasitologico', 'MOCO', 15, 'ambos', 'AUSENTE PRESENTE', '', '', 3),
  ('compuesto', 'Heces', 'Coproparasitologico', 'SANGRE', 15, 'ambos', 'AUSENTE PRESENTE', '', '', 3),
  ('compuesto', 'Heces', 'Coproparasitologico', 'LIENTERIA', 15, 'ambos', 'NEGATIVO POSITIVO', '', '', 3),
  ('compuesto', 'Heces', 'Coproparasitologico', 'QUISTES', 15, 'ambos', 'NO SE OBSERVA ENTAMOEBA HISTOLYTICA GIARDIA LAMBLIA ENTAMOEBA COLI CHILOMASTIX MESNILLI ENDOLIMAX NANA IODAMOEBA BUTSCHLII', '', '', 3),
  ('compuesto', 'Heces', 'Coproparasitologico', 'TROFOZOITOS', 15, 'ambos', 'NO SE OBSERVA ENTAMOEBA HISTOLYTICA GIARDIA LAMBLIA ENTAMOEBA COLI CHILOMASTIX MESNILLI', '', '', 3),
  ('compuesto', 'Heces', 'Coproparasitologico', 'LARVAS', 15, 'ambos', 'NO SE OBSERVA STRONGYLOIDES STERCORALIS', '', '', 3),
  ('compuesto', 'Heces', 'Coproparasitologico', 'OOQUISTES', 15, 'ambos', 'NO SE OBSERVA CRYPTOSPORIDIUM CYCLOSPORA CYCLOSPORA', '', '', 3),
  ('compuesto', 'Heces', 'Coproparasitologico', 'HUEVOS', 15, 'ambos', 'NO SE OBSERVA ENTEROBIUS VERMICULARIS HYMENOLEPIS NANA TRICHURIS TRICHIURA ASCARIS LUMBRICOIDES', '', '', 3),
  ('compuesto', 'Heces', 'Coproparasitologico', 'OTROS', 15, 'ambos', 'NO SE OBSERVA BLASTOCYSTIS SPP. ISOSPORA CANI', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'COLOR', 15, 'ambos', 'MARRON MARRON OSCURO MARRON CLARO CASTAÑO AMARILLO VERDOSO BLANQUECINO NEGRO AMARILLO PALIDO VERDUSCO ROJIZO', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'CONSISTENCIA', 15, 'ambos', 'PASTOSA SEMI PASTOSA LIQUIDA SEMI LIQUIDA MUCOIDE DIARREICA DURA SEMI DURA FLEMOSO SEMI FLEMOSO SEMI DIARREICA SEMI MUCOIDE BLANDA LIQUIDA FLEMOSA', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'MOCO', 15, 'ambos', 'AUSENTE PRESENTE', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'SANGRE', 15, 'ambos', 'AUSENTE PRESENTE', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'LIENTERIA', 15, 'ambos', 'NEGATIVO POSITIVO', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'QUISTES', 15, 'ambos', 'NO SE OBSERVA ENTAMOEBA HISTOLYTICA GIARDIA LAMBLIA ENTAMOEBA COLI CHILOMASTIX MESNILLI ENDOLIMAX NANA IODAMOEBA BUTSCHLII', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'TROFOZOITOS', 15, 'ambos', 'NO SE OBSERVA ENTAMOEBA HISTOLYTICA GIARDIA LAMBLIA ENTAMOEBA COLI CHILOMASTIX MESNILLI', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'LARVAS', 15, 'ambos', 'NO SE OBSERVA STRONGYLOIDES STERCORALIS', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'OOQUISTES', 15, 'ambos', 'NO SE OBSERVA CRYPTOSPORIDIUM CYCLOSPORA CYCLOSPORA', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'HUEVOS', 15, 'ambos', 'NO SE OBSERVA ENTEROBIUS VERMICULARIS HYMENOLEPIS NANA TRICHURIS TRICHIURA ASCARIS LUMBRICOIDES', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'OTROS', 15, 'ambos', 'NO SE OBSERVA BLASTOCYSTIS SPP. ISOSPORA CANI', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'FLORA MICROBIANA', 15, 'ambos', 'NORMLA LIGERAMENTE INCREMENTADA INCREMENTADA LIGERAMENTE INCREMENTADA CON PREDOMINIO BACILAR INCREMENTADA CON PREDOMINIO BACILAR ESCASA DISMINUIDA', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'ERITROCITOS', 15, 'ambos', 'AUSENTE ESCASA CANTIDAD REGULAR CANTIDAD ABUNDANTE CANTIDAD 0 - 1 POR CAMPO', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'LEUCOCITOS', 15, 'ambos', 'AUSENTE ESCASA CANTIDAD REGULAR CANTIDAD ABUNDANTE CANTIDAD 0 - 1 POR CAMPO', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'LEVADURAS', 15, 'ambos', 'AUSENTE ESCASA CANTIDAD REGULAR CANTIDAD ABUNDANTE CANTIDAD', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'CORPUSCULOS DE GRASA', 15, 'ambos', 'AUSENTE ESCASA CANTIDAD REGULAR CANTIDAD ABUNDANTE CANTIDAD', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'GRANULOS DE ALMIDON', 15, 'ambos', 'AUSENTE ESCASA CANTIDAD REGULAR CANTIDAD ABUNDANTE CANTIDAD', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'FIBRAS', 15, 'ambos', 'AUSENTE ESCASA CANTIDAD REGULAR CANTIDAD ABUNDANTE CANTIDAD', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'METODO', 15, 'ambos', 'Este estudio fue realizado por mètodo de concentraciòn con soluciòn fisiològica y lugol.', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'ROTAVIRUS', 15, 'ambos', 'NEGATIVO POSITIVO', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'ADENAVIRUS', 15, 'ambos', 'NEGATIVO POSITIVO', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'GIARDIA (ELISA)', 15, 'ambos', '0', '1.1', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'E. HISTOLYTICA (ELISA)', 15, 'ambos', '0', '1.1', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'SANGRE OCULTA', 15, 'ambos', 'NEGATIVO POSITIVO', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'HELICOBACTER PYLORI ANTIGENO', 15, 'ambos', 'NEGATIVO POSITIVO', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'PH FECAL', 15, 'ambos', 'ACIDO ALCALINO', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'AZUCARES REDUCTORES', 15, 'ambos', 'NEGATIVO POSITIVO', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'MOCO FECAL', 15, 'ambos', 'POSITIVO Polimorfonucleares 80 % Mononucleares 20 %', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'Criptosporidium', 15, 'ambos', 'NEGATIVO POSITIVO ACIDO ALCALINO', '', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', '.', 15, 'ambos', '0', '1.1', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'TOXINA A Y B CLOSTRIDIUM', 15, 'ambos', '0', '1.1', '', 3),
  ('compuesto', 'Heces', 'Seriado x 3', 'CALPROTECTINA FECAL', 15, 'ambos', '0', '50', 'Ug/g', 3),
  ('cultivo', 'Bactereologia', 'T. Gram', 'MUESTRA', 15, 'femenino', 'SECRECION VAGINAL', '', '', 3),
  ('compuesto', 'Bactereologia', 'T. Gram', 'EXAMEN EN FRESCO', 15, 'femenino', 'No se observa Trichomonas vaginalis.', '', '', 3),
  ('compuesto', 'Bactereologia', 'T. Gram', 'TINCION DE GRAM', 15, 'femenino', 'Se observa: - Escasa cantidad de Cocos y Diplococos Gram (+). - Escasa cantidad de Bacilos Gram (+). - Escasa cantidad de Células Epiteliales de descamación. - Escasa cantidad de Leucocitos Polimorfonucleares.', '', '', 3),
  ('cultivo', 'Bactereologia', 'Coprocultivo', 'CULTIVO', 15, 'ambos', 'En 24 horas de incubaciòn en medios selectivos y de enriquesimiento desarrollo abundante de colonias lactosa (+) a las 48 horas luego de repique de medios de enriquecimiento a medios selectivos desarrollo de colonias lactosa (+) y colonias lactosa (-).', '', '', 3),
  ('cultivo', 'Bactereologia', 'Coprocultivo', 'DIAGNOSTICO BACTERIOLOGICO', 15, 'ambos', 'Escherichia coli no patógenico. NO AMERITA ANTIBIOGRAMA', '', '', 3),
  ('cultivo', 'Bactereologia', 'cultivo y antibiograma (todos)', 'MUESTRA', 15, 'ambos', 'HISOPADO FARINGEO SECRECION VAGINAL LIQUIDO SINOVIAL Cultivo De Secreciones SECRECION OTICA ESPUTO SECRECION PURULENTA Secreción de Glandula de Bartolino Hilo de sutura SECRECION DE HERIDA DE TERCER FALANGE HILOS DE SUTURA EXUDADO FARINGEO ABSCESO MUSLO IZQUIERSO Quiste vartoline SECRECIÓN DE ABCESO MAMARIO SECRECION DE HERIDA RASPADO DE LENGUA RASPADO DE PIEL EXUDADO DE PIEL LIQUIDO PLEURAL SECRECION DE OIDO DERECHO SECRECION DE OIDO IZQUIERDO SEC. HERIDA DE CATETER DE HEMODIALISIS SEC. HERIDA DE CATETER DE HEMODIALISIS SEMEN SECRECION URETRAL SECRECION DE ULCERA VARICOSA SECRECIÓN BRONQUIAL SECRECION DE VIAS FARINGEAS SECRECION PURULENTA DE ULCERA VARICOSA SECRECION DE HERIDA PIE DERECHO SECRECION DE HERIDA RODILLA IZQUIERDA SECRECION DE HERIDA RODILLA DERECHA HERIDA DE ESPALDA SECRECION DE AMIGDALA DERECHA SECRECION PERIANAL SECRECION DE PUSTULA SEC. DE LESION AL REDEDOR DEL ANO SECRECIÓN OROFARINGEA SECRECION DE PIERNA SEC. OTICA IZQUIERDA Y DERECHA SECRECION DORSO DE LA LENGUA SECRECION DE PLACA DENTAL SECRECION PUSTULOSA DE FARINGE SECRECIÓN PURULENTA VAGINAL SECRECION BUCAL. SECRECIÓN DE COXIS HISOPADO FARINGEO DE AMIGDALA DERECHA ABSCESO DE MAMA DERECHA SECRECIÓN PURULENTA DE MAMA DERECHA SEC. DE PREPUCIO SECRECION PURULENTA DE NARIZ SECRECION DE QUISTE DE BARTOLINO ECRECION DE QUISTE DE BARTOLINO', '', '', 3);

-- -----------------------------------------------------------------------------
-- 1) Marcar pruebas compuestas / cultivo
-- -----------------------------------------------------------------------------
UPDATE `dom_prianacategoria` p
INNER JOIN `dom_anacategoria` a ON a.`anacategoria_id` = p.`anacategoria_id`
INNER JOIN (
  SELECT DISTINCT t.`grupo`, t.`prueba`, MAX(CASE t.`modo` WHEN 'cultivo' THEN 2 ELSE 1 END) AS compleja_obj
  FROM `tmp_biocenter_vref` t
  WHERE t.`modo` IN ('compuesto', 'cultivo')
  GROUP BY t.`grupo`, t.`prueba`
) x ON x.`grupo` = a.`name` AND x.`prueba` = p.`name`
SET p.`compleja` = x.`compleja_obj`
WHERE (p.`deleted` = 0 OR p.`deleted` IS NULL)
  AND (a.`deleted` = 0 OR a.`deleted` IS NULL);

-- Quitar referencias simples placeholder de pruebas que pasan a compuestas/cultivo
DELETE pr
FROM `dom_priresultados` pr
INNER JOIN `dom_prianacategoria` p ON p.`prianacategoria_id` = pr.`prianacategoria_id`
INNER JOIN `dom_anacategoria` a ON a.`anacategoria_id` = p.`anacategoria_id`
INNER JOIN (
  SELECT DISTINCT t.`grupo`, t.`prueba`
  FROM `tmp_biocenter_vref` t
  WHERE t.`modo` IN ('compuesto', 'cultivo')
) x ON x.`grupo` = a.`name` AND x.`prueba` = p.`name`;

-- -----------------------------------------------------------------------------
-- 2) Sub-análisis (compuesto / cultivo) → dom_secanacategoria
-- -----------------------------------------------------------------------------
INSERT INTO `dom_secanacategoria`
  (`prianacategoria_id`, `nombre`, `paciente_id`, `valor_min`, `valor_max`, `umedida`, `formulas_id`, `opcion_id`, `deleted`, `sexo`)
SELECT
  p.`prianacategoria_id`,
  t.`sec_nombre`,
  t.`poblacion_id`,
  t.`valor_min`,
  t.`valor_max`,
  t.`unidad`,
  1,
  t.`opcion_id`,
  0,
  t.`sexo`
FROM `tmp_biocenter_vref` t
INNER JOIN `dom_anacategoria` a ON a.`name` = t.`grupo` AND (a.`deleted` = 0 OR a.`deleted` IS NULL)
INNER JOIN `dom_prianacategoria` p ON p.`anacategoria_id` = a.`anacategoria_id`
  AND p.`name` = t.`prueba`
  AND (p.`deleted` = 0 OR p.`deleted` IS NULL)
WHERE t.`modo` IN ('compuesto', 'cultivo')
  AND NOT EXISTS (
    SELECT 1 FROM `dom_secanacategoria` s
    WHERE s.`prianacategoria_id` = p.`prianacategoria_id`
      AND s.`nombre` = t.`sec_nombre`
      AND s.`paciente_id` = t.`poblacion_id`
      AND (s.`sexo` = t.`sexo` OR s.`sexo` IS NULL OR s.`sexo` = '')
      AND (s.`deleted` = 0 OR s.`deleted` IS NULL)
  );

UPDATE `dom_secanacategoria` s
INNER JOIN `dom_prianacategoria` p ON p.`prianacategoria_id` = s.`prianacategoria_id`
INNER JOIN `dom_anacategoria` a ON a.`anacategoria_id` = p.`anacategoria_id`
INNER JOIN `tmp_biocenter_vref` t ON t.`modo` IN ('compuesto', 'cultivo')
  AND t.`grupo` = a.`name`
  AND t.`prueba` = p.`name`
  AND t.`sec_nombre` = s.`nombre`
  AND t.`poblacion_id` = s.`paciente_id`
  AND t.`sexo` = IF(s.`sexo` IS NULL OR s.`sexo` = '', 'ambos', s.`sexo`)
SET
  s.`valor_min` = t.`valor_min`,
  s.`valor_max` = t.`valor_max`,
  s.`umedida` = t.`unidad`,
  s.`opcion_id` = t.`opcion_id`,
  s.`sexo` = t.`sexo`,
  s.`deleted` = 0
WHERE (s.`deleted` = 0 OR s.`deleted` IS NULL);

-- -----------------------------------------------------------------------------
-- 3) Pruebas simples → dom_priresultados
-- -----------------------------------------------------------------------------
INSERT INTO `dom_priresultados`
  (`prianacategoria_id`, `id_poblacion`, `valor_min`, `valor_max`, `umedida`, `formulas_id`, `opcion_id`, `deleted`, `sexo`)
SELECT
  p.`prianacategoria_id`,
  t.`poblacion_id`,
  t.`valor_min`,
  t.`valor_max`,
  t.`unidad`,
  1,
  t.`opcion_id`,
  0,
  t.`sexo`
FROM `tmp_biocenter_vref` t
INNER JOIN `dom_anacategoria` a ON a.`name` = t.`grupo` AND (a.`deleted` = 0 OR a.`deleted` IS NULL)
INNER JOIN `dom_prianacategoria` p ON p.`anacategoria_id` = a.`anacategoria_id`
  AND p.`name` = t.`prueba`
  AND (p.`deleted` = 0 OR p.`deleted` IS NULL)
WHERE t.`modo` = 'simple'
  AND NOT EXISTS (
    SELECT 1 FROM `dom_priresultados` pr
    WHERE pr.`prianacategoria_id` = p.`prianacategoria_id`
      AND pr.`id_poblacion` = t.`poblacion_id`
      AND (pr.`sexo` = t.`sexo` OR pr.`sexo` IS NULL OR pr.`sexo` = '')
      AND (pr.`deleted` = 0 OR pr.`deleted` IS NULL)
  );

UPDATE `dom_priresultados` pr
INNER JOIN `dom_prianacategoria` p ON p.`prianacategoria_id` = pr.`prianacategoria_id`
INNER JOIN `dom_anacategoria` a ON a.`anacategoria_id` = p.`anacategoria_id`
INNER JOIN `tmp_biocenter_vref` t ON t.`modo` = 'simple'
  AND t.`grupo` = a.`name`
  AND t.`prueba` = p.`name`
  AND t.`poblacion_id` = pr.`id_poblacion`
  AND t.`sexo` = IF(pr.`sexo` IS NULL OR pr.`sexo` = '', 'ambos', pr.`sexo`)
SET
  pr.`valor_min` = t.`valor_min`,
  pr.`valor_max` = t.`valor_max`,
  pr.`umedida` = t.`unidad`,
  pr.`opcion_id` = t.`opcion_id`,
  pr.`sexo` = t.`sexo`,
  pr.`deleted` = 0
WHERE (pr.`deleted` = 0 OR pr.`deleted` IS NULL);

-- -----------------------------------------------------------------------------
-- 4) Verificación
-- -----------------------------------------------------------------------------
SELECT t.`modo`, COUNT(*) AS filas_tmp FROM `tmp_biocenter_vref` t GROUP BY t.`modo`;

SELECT p.`compleja`, COUNT(*) AS pruebas
FROM `dom_prianacategoria` p
WHERE (p.`deleted` = 0 OR p.`deleted` IS NULL)
GROUP BY p.`compleja`;

SELECT a.`name` AS grupo, p.`name` AS prueba, COUNT(s.`secanacategoria_id`) AS subanalisis
FROM `dom_prianacategoria` p
INNER JOIN `dom_anacategoria` a ON a.`anacategoria_id` = p.`anacategoria_id`
LEFT JOIN `dom_secanacategoria` s ON s.`prianacategoria_id` = p.`prianacategoria_id`
  AND (s.`deleted` = 0 OR s.`deleted` IS NULL)
WHERE p.`compleja` IN (1, 2)
GROUP BY a.`name`, p.`name`, p.`compleja`
ORDER BY subanalisis DESC
LIMIT 30;

COMMIT;
