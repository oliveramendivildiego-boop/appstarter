-- Añadir campo sexo a priresultados (pruebas no compuestas)
-- Ejecutar contra la base de datos (con prefijo dom_ si aplica)

ALTER TABLE `dom_priresultados` ADD COLUMN `sexo` VARCHAR(20) DEFAULT 'ambos' AFTER `id_poblacion`;

-- Crear tablas de opciones si no existen (Positivo/Negativo, Reactivo/No reactivo)
CREATE TABLE IF NOT EXISTS `dom_opcion_positivo` (
  `opcion_positivo` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`opcion_positivo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `dom_opcion_positivo` (`opcion_positivo`) VALUES ('Positivo'), ('Negativo');

CREATE TABLE IF NOT EXISTS `dom_opcion_reactivo` (
  `opcion_reactivo` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`opcion_reactivo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `dom_opcion_reactivo` (`opcion_reactivo`) VALUES ('Reactivo'), ('No reactivo');

-- Opcional: si la tabla dom_opciones tiene columna 'tabla', actualizar para Positivo/Reactivo:
-- UPDATE `dom_opciones` SET `tabla` = 'opcion_positivo' WHERE `opciones_id` = 1;
-- UPDATE `dom_opciones` SET `tabla` = 'opcion_reactivo' WHERE `opciones_id` = 2;
