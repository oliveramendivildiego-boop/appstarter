-- Insertar grupos de población por edad (orden ascendente)
-- Ejecutar después de migration_poblacion_edad.sql
-- mysql -u root laboratorio < database/insert_poblaciones_edad.sql

-- Lactante: 29 días ≈ 1 mes, 11 meses ≈ 335 días
INSERT INTO `dom_poblacion` (`id_poblacion`, `name`, `edad_min`, `edad_max`, `unidad`, `orden`, `deleted`) VALUES
(6, 'Recién nacido', 0, 28, 'dias', 1, 0),
(7, 'Lactante', 29, 335, 'dias', 2, 0),
(8, 'Niño pequeño', 1, 5, 'años', 3, 0),
(9, 'Niño escolar', 6, 11, 'años', 4, 0),
(10, 'Adolescente', 12, 17, 'años', 5, 0),
(11, 'Adulto', 18, 59, 'años', 6, 0),
(12, 'Adulto mayor', 60, NULL, 'años', 7, 0);
