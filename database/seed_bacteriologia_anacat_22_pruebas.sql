-- =============================================================================
-- Bacteriología (dom_anacategoria.anacategoria_id = 22): catálogo de pruebas
-- =============================================================================
-- Ejecutar en la base del laboratorio (prefijo dom_ según app/Config/Database.php):
--   mysql -u root -p laboratorio < database/seed_bacteriologia_anacat_22_pruebas.sql
--
-- Configuración aplicada por prueba:
--   • compleja = 0 (prueba NO compuesta)
--   • cost y cost_deriv = mismo precio de la lista
--   • Valor de referencia: población "Todos" (id_poblacion = 15), sexo ambos
--   • valor_min, valor_max y umedida vacíos; opcion_id = 3 (texto/cualitativo)
--
-- Antes de ejecutar, verifique que exista la población "Todos":
--   SELECT id_poblacion, name FROM dom_poblacion WHERE id_poblacion = 15 OR name LIKE '%Todos%';
-- Si su id difiere de 15, cambie @id_poblacion_todos abajo.
--
-- Requisitos opcionales (si no existen, comente las líneas ALTER o la columna sexo):
--   database/migration_priresultados_sexo.sql
-- =============================================================================

SET NAMES utf8mb4;
SET @anacat := 22;
SET @id_poblacion_todos := 15;

-- -----------------------------------------------------------------------------
-- 1) Actualizar nombres/precios de pruebas antiguas (mismo anacategoria_id)
-- -----------------------------------------------------------------------------
UPDATE `dom_prianacategoria` SET
  `name` = 'CITOGRAMA NASAL', `order` = 6, `cost` = 55, `cost_deriv` = 55, `compleja` = 0, `deleted` = 0
WHERE `anacategoria_id` = @anacat AND `deleted` = 0
  AND UPPER(TRIM(`name`)) IN ('CITOLOGIA NASAL', 'CITOGRAMA NASAL');

UPDATE `dom_prianacategoria` SET
  `name` = 'UROCULTIVO', `order` = 42, `cost` = 200, `cost_deriv` = 200, `compleja` = 0, `deleted` = 0
WHERE `anacategoria_id` = @anacat AND `deleted` = 0
  AND UPPER(TRIM(`name`)) = 'UROCULTIVO';

UPDATE `dom_prianacategoria` SET
  `name` = 'COPROCULTIVO', `order` = 8, `cost` = 260, `cost_deriv` = 260, `compleja` = 0, `deleted` = 0
WHERE `anacategoria_id` = @anacat AND `deleted` = 0
  AND UPPER(TRIM(`name`)) = 'COPROCULTIVO';

UPDATE `dom_prianacategoria` SET
  `name` = 'ESPERMOCULTIVO', `order` = 22, `cost` = 140, `cost_deriv` = 140, `compleja` = 0, `deleted` = 0
WHERE `anacategoria_id` = @anacat AND `deleted` = 0
  AND UPPER(TRIM(`name`)) IN ('ESPERMOCULTIVO', 'ESPERMOCULTIVO ');

UPDATE `dom_prianacategoria` SET
  `order` = 1, `cost` = 200, `cost_deriv` = 200, `compleja` = 0, `deleted` = 0
WHERE `anacategoria_id` = @anacat AND `deleted` = 0
  AND UPPER(TRIM(`name`)) = 'ADA';

UPDATE `dom_prianacategoria` SET
  `name` = 'TINCION DE GRAM', `order` = 39, `cost` = 50, `cost_deriv` = 50, `compleja` = 0, `deleted` = 0
WHERE `anacategoria_id` = @anacat AND `deleted` = 0
  AND UPPER(TRIM(`name`)) IN ('T. GRAM', 'TINCION DE GRAM');

-- -----------------------------------------------------------------------------
-- 2) Insertar pruebas que aún no existen (por nombre exacto en el grupo 22)
-- -----------------------------------------------------------------------------
INSERT INTO `dom_prianacategoria` (`name`, `order`, `cost`, `cost_deriv`, `deleted`, `anacategoria_id`, `compleja`)
SELECT v.nombre, v.orden, v.precio, v.precio, 0, @anacat, 0
FROM (
  SELECT 'ADA' AS nombre, 1 AS orden, 200 AS precio UNION ALL
  SELECT 'ADENOVIRUS ANTÍGENO EN HECES-INMUNOCROMATOGRAFIA', 2, 0 UNION ALL
  SELECT 'ANTIFUNGIGRAMA', 3, 55 UNION ALL
  SELECT 'ASPIRADO TRAQUEAL', 4, 230 UNION ALL
  SELECT 'CHLAMYDIA TRACHOMATIS', 5, 140 UNION ALL
  SELECT 'CITOGRAMA NASAL', 6, 55 UNION ALL
  SELECT 'CONCENTRACION INHIBITORIA MINIMA PARA VANCOMICINA', 7, 290 UNION ALL
  SELECT 'COPROCULTIVO', 8, 260 UNION ALL
  SELECT 'CULTIVO DE ABSCESOS/ SECRECIONES EN GRAL.', 9, 240 UNION ALL
  SELECT 'CULTIVO DE BIOPSIASx3', 10, 300 UNION ALL
  SELECT 'CULTIVO DE ESPUTO', 11, 200 UNION ALL
  SELECT 'CULTIVO DE LIQUIDOS DE PUNCION', 12, 260 UNION ALL
  SELECT 'CULTIVO DE LÍQUIDO PLEURAL', 13, 260 UNION ALL
  SELECT 'CULTIVO DE MATERIALES QUIRURGICOSx3', 14, 300 UNION ALL
  SELECT 'CULTIVO DE MEDULA OSEA', 15, 300 UNION ALL
  SELECT 'CULTIVO DE SECRECION URETRAL', 16, 150 UNION ALL
  SELECT 'CULTIVO MATERIAL DE OIDO', 17, 180 UNION ALL
  SELECT 'CULTIVO MICOLOGICO MUESTRAS PROFUNDAS/SISTEMICAS', 18, 205 UNION ALL
  SELECT 'CULTIVO MICOLOGICO MUESTRAS SUPERFICIALES+ DIRECTO', 19, 150 UNION ALL
  SELECT 'CULTIVO MICOLOGICO MUESTRAS SUPERFICIALES', 20, 100 UNION ALL
  SELECT 'CULTIVO PUNTA DE CATETER', 21, 180 UNION ALL
  SELECT 'ESPERMOCULTIVO', 22, 140 UNION ALL
  SELECT 'HEMOCULTIVO X2 BOTELLAS RESINADAS', 23, 740 UNION ALL
  SELECT 'HISOPADO  ENDO / EXOCERVICAL', 24, 155 UNION ALL
  SELECT 'HISOPADO DE FAUCES', 25, 110 UNION ALL
  SELECT 'HISOPADO DE FAUCES + MICOLÓGICO', 26, 160 UNION ALL
  SELECT 'HISOPADO NASAL PARA  CRIBADO DE  SAMR Y PSEUDOMONAS', 27, 200 UNION ALL
  SELECT 'TINCION DE GRAM Y GIEMSA DE HISOPADO URETRAL', 28, 65 UNION ALL
  SELECT 'LAVADO BRONCOALVEOLAR  (BAL) CUANTITATIVO', 29, 300 UNION ALL
  SELECT 'MICOLOGICO DIRECTO', 30, 50 UNION ALL
  SELECT 'MOCO FECAL', 31, 50 UNION ALL
  SELECT 'ROTAVIRUS/ADENOVIRUS', 32, 155 UNION ALL
  SELECT 'SANGRE OCULTA EN HECES', 33, 85 UNION ALL
  SELECT 'SECRECION OCULAR X2', 34, 180 UNION ALL
  SELECT 'SECRECION VAGINAL EXAMEN DIRECTO', 35, 100 UNION ALL
  SELECT 'SECRECION VAGINAL DIRECTO+ CULTIVO', 36, 180 UNION ALL
  SELECT 'SECRECION VAGINAL +FRESCO+CULTIVO+ ANTIFUNGIGRAMA', 37, 200 UNION ALL
  SELECT 'TINCION DE GIEMSA', 38, 50 UNION ALL
  SELECT 'TINCION DE GRAM', 39, 50 UNION ALL
  SELECT 'UREAPLASMA/MYCOPLASMA CULTIVO', 40, 406 UNION ALL
  SELECT 'CLOSTRIDIUM DIFFICILE TOXINA A/B', 41, 260 UNION ALL
  SELECT 'UROCULTIVO', 42, 200
) AS v
WHERE NOT EXISTS (
  SELECT 1 FROM `dom_prianacategoria` p
  WHERE p.`anacategoria_id` = @anacat
    AND (p.`deleted` = 0 OR p.`deleted` IS NULL)
    AND BINARY TRIM(p.`name`) = BINARY v.nombre
);

-- -----------------------------------------------------------------------------
-- 3) Sincronizar precios y orden en todas las pruebas de la lista (por nombre)
-- -----------------------------------------------------------------------------
UPDATE `dom_prianacategoria` p
INNER JOIN (
  SELECT 'ADA' AS nombre, 1 AS orden, 200 AS precio UNION ALL
  SELECT 'ADENOVIRUS ANTÍGENO EN HECES-INMUNOCROMATOGRAFIA', 2, 0 UNION ALL
  SELECT 'ANTIFUNGIGRAMA', 3, 55 UNION ALL
  SELECT 'ASPIRADO TRAQUEAL', 4, 230 UNION ALL
  SELECT 'CHLAMYDIA TRACHOMATIS', 5, 140 UNION ALL
  SELECT 'CITOGRAMA NASAL', 6, 55 UNION ALL
  SELECT 'CONCENTRACION INHIBITORIA MINIMA PARA VANCOMICINA', 7, 290 UNION ALL
  SELECT 'COPROCULTIVO', 8, 260 UNION ALL
  SELECT 'CULTIVO DE ABSCESOS/ SECRECIONES EN GRAL.', 9, 240 UNION ALL
  SELECT 'CULTIVO DE BIOPSIASx3', 10, 300 UNION ALL
  SELECT 'CULTIVO DE ESPUTO', 11, 200 UNION ALL
  SELECT 'CULTIVO DE LIQUIDOS DE PUNCION', 12, 260 UNION ALL
  SELECT 'CULTIVO DE LÍQUIDO PLEURAL', 13, 260 UNION ALL
  SELECT 'CULTIVO DE MATERIALES QUIRURGICOSx3', 14, 300 UNION ALL
  SELECT 'CULTIVO DE MEDULA OSEA', 15, 300 UNION ALL
  SELECT 'CULTIVO DE SECRECION URETRAL', 16, 150 UNION ALL
  SELECT 'CULTIVO MATERIAL DE OIDO', 17, 180 UNION ALL
  SELECT 'CULTIVO MICOLOGICO MUESTRAS PROFUNDAS/SISTEMICAS', 18, 205 UNION ALL
  SELECT 'CULTIVO MICOLOGICO MUESTRAS SUPERFICIALES+ DIRECTO', 19, 150 UNION ALL
  SELECT 'CULTIVO MICOLOGICO MUESTRAS SUPERFICIALES', 20, 100 UNION ALL
  SELECT 'CULTIVO PUNTA DE CATETER', 21, 180 UNION ALL
  SELECT 'ESPERMOCULTIVO', 22, 140 UNION ALL
  SELECT 'HEMOCULTIVO X2 BOTELLAS RESINADAS', 23, 740 UNION ALL
  SELECT 'HISOPADO  ENDO / EXOCERVICAL', 24, 155 UNION ALL
  SELECT 'HISOPADO DE FAUCES', 25, 110 UNION ALL
  SELECT 'HISOPADO DE FAUCES + MICOLÓGICO', 26, 160 UNION ALL
  SELECT 'HISOPADO NASAL PARA  CRIBADO DE  SAMR Y PSEUDOMONAS', 27, 200 UNION ALL
  SELECT 'TINCION DE GRAM Y GIEMSA DE HISOPADO URETRAL', 28, 65 UNION ALL
  SELECT 'LAVADO BRONCOALVEOLAR  (BAL) CUANTITATIVO', 29, 300 UNION ALL
  SELECT 'MICOLOGICO DIRECTO', 30, 50 UNION ALL
  SELECT 'MOCO FECAL', 31, 50 UNION ALL
  SELECT 'ROTAVIRUS/ADENOVIRUS', 32, 155 UNION ALL
  SELECT 'SANGRE OCULTA EN HECES', 33, 85 UNION ALL
  SELECT 'SECRECION OCULAR X2', 34, 180 UNION ALL
  SELECT 'SECRECION VAGINAL EXAMEN DIRECTO', 35, 100 UNION ALL
  SELECT 'SECRECION VAGINAL DIRECTO+ CULTIVO', 36, 180 UNION ALL
  SELECT 'SECRECION VAGINAL +FRESCO+CULTIVO+ ANTIFUNGIGRAMA', 37, 200 UNION ALL
  SELECT 'TINCION DE GIEMSA', 38, 50 UNION ALL
  SELECT 'TINCION DE GRAM', 39, 50 UNION ALL
  SELECT 'UREAPLASMA/MYCOPLASMA CULTIVO', 40, 406 UNION ALL
  SELECT 'CLOSTRIDIUM DIFFICILE TOXINA A/B', 41, 260 UNION ALL
  SELECT 'UROCULTIVO', 42, 200
) AS v ON BINARY TRIM(p.`name`) = BINARY v.nombre
SET p.`order` = v.orden,
    p.`cost` = v.precio,
    p.`cost_deriv` = v.precio,
    p.`compleja` = 0,
    p.`deleted` = 0
WHERE p.`anacategoria_id` = @anacat;

-- tipo_muestra_id / metodo_id: dejar sin especificar (NULL) si existen las columnas
UPDATE `dom_prianacategoria`
SET `tipo_muestra_id` = NULL
WHERE `anacategoria_id` = @anacat
  AND (`deleted` = 0 OR `deleted` IS NULL)
  AND EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'dom_prianacategoria'
      AND COLUMN_NAME = 'tipo_muestra_id'
  );

-- -----------------------------------------------------------------------------
-- 4) Valores de referencia (dom_priresultados) — población Todos, sexo ambos
-- -----------------------------------------------------------------------------
INSERT INTO `dom_priresultados`
  (`prianacategoria_id`, `id_poblacion`, `valor_min`, `valor_max`, `umedida`, `formulas_id`, `opcion_id`, `deleted`, `sexo`)
SELECT p.`prianacategoria_id`, @id_poblacion_todos, '', '', '', 1, 3, 0, 'ambos'
FROM `dom_prianacategoria` p
INNER JOIN (
  SELECT 'ADA' AS nombre UNION ALL
  SELECT 'ADENOVIRUS ANTÍGENO EN HECES-INMUNOCROMATOGRAFIA' UNION ALL
  SELECT 'ANTIFUNGIGRAMA' UNION ALL
  SELECT 'ASPIRADO TRAQUEAL' UNION ALL
  SELECT 'CHLAMYDIA TRACHOMATIS' UNION ALL
  SELECT 'CITOGRAMA NASAL' UNION ALL
  SELECT 'CONCENTRACION INHIBITORIA MINIMA PARA VANCOMICINA' UNION ALL
  SELECT 'COPROCULTIVO' UNION ALL
  SELECT 'CULTIVO DE ABSCESOS/ SECRECIONES EN GRAL.' UNION ALL
  SELECT 'CULTIVO DE BIOPSIASx3' UNION ALL
  SELECT 'CULTIVO DE ESPUTO' UNION ALL
  SELECT 'CULTIVO DE LIQUIDOS DE PUNCION' UNION ALL
  SELECT 'CULTIVO DE LÍQUIDO PLEURAL' UNION ALL
  SELECT 'CULTIVO DE MATERIALES QUIRURGICOSx3' UNION ALL
  SELECT 'CULTIVO DE MEDULA OSEA' UNION ALL
  SELECT 'CULTIVO DE SECRECION URETRAL' UNION ALL
  SELECT 'CULTIVO MATERIAL DE OIDO' UNION ALL
  SELECT 'CULTIVO MICOLOGICO MUESTRAS PROFUNDAS/SISTEMICAS' UNION ALL
  SELECT 'CULTIVO MICOLOGICO MUESTRAS SUPERFICIALES+ DIRECTO' UNION ALL
  SELECT 'CULTIVO MICOLOGICO MUESTRAS SUPERFICIALES' UNION ALL
  SELECT 'CULTIVO PUNTA DE CATETER' UNION ALL
  SELECT 'ESPERMOCULTIVO' UNION ALL
  SELECT 'HEMOCULTIVO X2 BOTELLAS RESINADAS' UNION ALL
  SELECT 'HISOPADO  ENDO / EXOCERVICAL' UNION ALL
  SELECT 'HISOPADO DE FAUCES' UNION ALL
  SELECT 'HISOPADO DE FAUCES + MICOLÓGICO' UNION ALL
  SELECT 'HISOPADO NASAL PARA  CRIBADO DE  SAMR Y PSEUDOMONAS' UNION ALL
  SELECT 'TINCION DE GRAM Y GIEMSA DE HISOPADO URETRAL' UNION ALL
  SELECT 'LAVADO BRONCOALVEOLAR  (BAL) CUANTITATIVO' UNION ALL
  SELECT 'MICOLOGICO DIRECTO' UNION ALL
  SELECT 'MOCO FECAL' UNION ALL
  SELECT 'ROTAVIRUS/ADENOVIRUS' UNION ALL
  SELECT 'SANGRE OCULTA EN HECES' UNION ALL
  SELECT 'SECRECION OCULAR X2' UNION ALL
  SELECT 'SECRECION VAGINAL EXAMEN DIRECTO' UNION ALL
  SELECT 'SECRECION VAGINAL DIRECTO+ CULTIVO' UNION ALL
  SELECT 'SECRECION VAGINAL +FRESCO+CULTIVO+ ANTIFUNGIGRAMA' UNION ALL
  SELECT 'TINCION DE GIEMSA' UNION ALL
  SELECT 'TINCION DE GRAM' UNION ALL
  SELECT 'UREAPLASMA/MYCOPLASMA CULTIVO' UNION ALL
  SELECT 'CLOSTRIDIUM DIFFICILE TOXINA A/B' UNION ALL
  SELECT 'UROCULTIVO'
) AS lista ON BINARY TRIM(p.`name`) = BINARY lista.nombre
WHERE p.`anacategoria_id` = @anacat
  AND p.`compleja` = 0
  AND (p.`deleted` = 0 OR p.`deleted` IS NULL)
  AND NOT EXISTS (
    SELECT 1 FROM `dom_priresultados` pr
    WHERE pr.`prianacategoria_id` = p.`prianacategoria_id`
      AND pr.`id_poblacion` = @id_poblacion_todos
      AND (pr.`deleted` = 0 OR pr.`deleted` IS NULL)
      AND (pr.`sexo` = 'ambos' OR pr.`sexo` IS NULL OR pr.`sexo` = '')
  );

-- Si la columna sexo no existe, ejecute esta variante (comente el bloque anterior):
/*
INSERT INTO `dom_priresultados`
  (`prianacategoria_id`, `id_poblacion`, `valor_min`, `valor_max`, `umedida`, `formulas_id`, `opcion_id`, `deleted`)
SELECT p.`prianacategoria_id`, @id_poblacion_todos, '', '', '', 1, 3, 0
FROM `dom_prianacategoria` p
-- ... mismo JOIN lista ...
WHERE NOT EXISTS (
  SELECT 1 FROM `dom_priresultados` pr
  WHERE pr.`prianacategoria_id` = p.`prianacategoria_id`
    AND pr.`id_poblacion` = @id_poblacion_todos
    AND (pr.`deleted` = 0 OR pr.`deleted` IS NULL)
);
*/

-- Normalizar referencias existentes (sin min/max/umedida)
UPDATE `dom_priresultados` pr
INNER JOIN `dom_prianacategoria` p ON p.`prianacategoria_id` = pr.`prianacategoria_id`
INNER JOIN (
  SELECT 'ADA' AS nombre UNION ALL
  SELECT 'ADENOVIRUS ANTÍGENO EN HECES-INMUNOCROMATOGRAFIA' UNION ALL
  SELECT 'ANTIFUNGIGRAMA' UNION ALL
  SELECT 'ASPIRADO TRAQUEAL' UNION ALL
  SELECT 'CHLAMYDIA TRACHOMATIS' UNION ALL
  SELECT 'CITOGRAMA NASAL' UNION ALL
  SELECT 'CONCENTRACION INHIBITORIA MINIMA PARA VANCOMICINA' UNION ALL
  SELECT 'COPROCULTIVO' UNION ALL
  SELECT 'CULTIVO DE ABSCESOS/ SECRECIONES EN GRAL.' UNION ALL
  SELECT 'CULTIVO DE BIOPSIASx3' UNION ALL
  SELECT 'CULTIVO DE ESPUTO' UNION ALL
  SELECT 'CULTIVO DE LIQUIDOS DE PUNCION' UNION ALL
  SELECT 'CULTIVO DE LÍQUIDO PLEURAL' UNION ALL
  SELECT 'CULTIVO DE MATERIALES QUIRURGICOSx3' UNION ALL
  SELECT 'CULTIVO DE MEDULA OSEA' UNION ALL
  SELECT 'CULTIVO DE SECRECION URETRAL' UNION ALL
  SELECT 'CULTIVO MATERIAL DE OIDO' UNION ALL
  SELECT 'CULTIVO MICOLOGICO MUESTRAS PROFUNDAS/SISTEMICAS' UNION ALL
  SELECT 'CULTIVO MICOLOGICO MUESTRAS SUPERFICIALES+ DIRECTO' UNION ALL
  SELECT 'CULTIVO MICOLOGICO MUESTRAS SUPERFICIALES' UNION ALL
  SELECT 'CULTIVO PUNTA DE CATETER' UNION ALL
  SELECT 'ESPERMOCULTIVO' UNION ALL
  SELECT 'HEMOCULTIVO X2 BOTELLAS RESINADAS' UNION ALL
  SELECT 'HISOPADO  ENDO / EXOCERVICAL' UNION ALL
  SELECT 'HISOPADO DE FAUCES' UNION ALL
  SELECT 'HISOPADO DE FAUCES + MICOLÓGICO' UNION ALL
  SELECT 'HISOPADO NASAL PARA  CRIBADO DE  SAMR Y PSEUDOMONAS' UNION ALL
  SELECT 'TINCION DE GRAM Y GIEMSA DE HISOPADO URETRAL' UNION ALL
  SELECT 'LAVADO BRONCOALVEOLAR  (BAL) CUANTITATIVO' UNION ALL
  SELECT 'MICOLOGICO DIRECTO' UNION ALL
  SELECT 'MOCO FECAL' UNION ALL
  SELECT 'ROTAVIRUS/ADENOVIRUS' UNION ALL
  SELECT 'SANGRE OCULTA EN HECES' UNION ALL
  SELECT 'SECRECION OCULAR X2' UNION ALL
  SELECT 'SECRECION VAGINAL EXAMEN DIRECTO' UNION ALL
  SELECT 'SECRECION VAGINAL DIRECTO+ CULTIVO' UNION ALL
  SELECT 'SECRECION VAGINAL +FRESCO+CULTIVO+ ANTIFUNGIGRAMA' UNION ALL
  SELECT 'TINCION DE GIEMSA' UNION ALL
  SELECT 'TINCION DE GRAM' UNION ALL
  SELECT 'UREAPLASMA/MYCOPLASMA CULTIVO' UNION ALL
  SELECT 'CLOSTRIDIUM DIFFICILE TOXINA A/B' UNION ALL
  SELECT 'UROCULTIVO'
) AS lista ON BINARY TRIM(p.`name`) = BINARY lista.nombre
SET pr.`id_poblacion` = @id_poblacion_todos,
    pr.`valor_min` = '',
    pr.`valor_max` = '',
    pr.`umedida` = '',
    pr.`formulas_id` = 1,
    pr.`opcion_id` = 3,
    pr.`deleted` = 0,
    pr.`sexo` = 'ambos'
WHERE p.`anacategoria_id` = @anacat
  AND p.`compleja` = 0
  AND (pr.`deleted` = 0 OR pr.`deleted` IS NULL);

-- -----------------------------------------------------------------------------
-- 5) Verificación
-- -----------------------------------------------------------------------------
SELECT p.`prianacategoria_id`, p.`name`, p.`order`, p.`cost`, p.`cost_deriv`, p.`compleja`,
       COUNT(pr.`priresultados_id`) AS refs
FROM `dom_prianacategoria` p
LEFT JOIN `dom_priresultados` pr
  ON pr.`prianacategoria_id` = p.`prianacategoria_id`
 AND (pr.`deleted` = 0 OR pr.`deleted` IS NULL)
 AND pr.`id_poblacion` = @id_poblacion_todos
WHERE p.`anacategoria_id` = @anacat
  AND (p.`deleted` = 0 OR p.`deleted` IS NULL)
GROUP BY p.`prianacategoria_id`, p.`name`, p.`order`, p.`cost`, p.`cost_deriv`, p.`compleja`
ORDER BY p.`order`, p.`name`;
