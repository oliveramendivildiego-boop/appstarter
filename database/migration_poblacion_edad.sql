-- Extender tabla poblacion para rangos de edad configurables
-- Ejecutar en phpMyAdmin o: mysql -u root laboratorio < database/migration_poblacion_edad.sql
--
-- edad_min, edad_max: valores numéricos (ej. 0, 28, 60)
-- unidad: dias, meses o años (aplica a min y max)
-- orden: para ordenar en listados
-- Para "≥ 60 años": edad_min=60, edad_max=NULL, unidad='años'
-- Grupos sin rango (Masculino, Femenino, Todos): edad_min/max/unidad=NULL

ALTER TABLE `dom_poblacion` ADD COLUMN `edad_min` DECIMAL(10,2) DEFAULT NULL AFTER `name`;
ALTER TABLE `dom_poblacion` ADD COLUMN `edad_max` DECIMAL(10,2) DEFAULT NULL AFTER `edad_min`;
ALTER TABLE `dom_poblacion` ADD COLUMN `unidad` ENUM('dias','meses','años') DEFAULT NULL AFTER `edad_max`;
ALTER TABLE `dom_poblacion` ADD COLUMN `orden` INT NOT NULL DEFAULT 0 AFTER `unidad`;
ALTER TABLE `dom_poblacion` ADD COLUMN `deleted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `orden`;

-- Opcional: Insertar grupos por edad (si desea reemplazar/agregar)
-- INSERT INTO dom_poblacion (id_poblacion, name, edad_min, edad_max, unidad, orden, deleted) VALUES
-- (6, 'Recién nacido', 0, 28, 'dias', 1, 0),
-- (7, 'Lactante', 29, 330, 'dias', 2, 0),
-- (8, 'Niño pequeño', 1, 5, 'años', 3, 0),
-- (9, 'Niño escolar', 6, 11, 'años', 4, 0),
-- (10, 'Adolescente', 12, 17, 'años', 5, 0),
-- (11, 'Adulto', 18, 59, 'años', 6, 0),
-- (12, 'Adulto mayor', 60, NULL, 'años', 7, 0);
