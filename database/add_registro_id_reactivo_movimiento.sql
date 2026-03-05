-- Añade columna registro_id a dom_reactivo_movimiento para asociar consumo con número de orden
-- Ejecutar si obtienes: Unknown column 'registro_id' in 'field list'

ALTER TABLE `dom_reactivo_movimiento` ADD COLUMN `registro_id` INT DEFAULT NULL COMMENT 'Orden asociada (opcional, null=sin prueba)';
