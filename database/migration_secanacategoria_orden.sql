-- Orden de sub-clases (secanacategoria) para mostrar en tabla y en registro
-- Ejecutar una sola vez. Si la columna ya existe, omitir.
ALTER TABLE `dom_secanacategoria` ADD COLUMN `orden` INT NOT NULL DEFAULT 0 AFTER `deleted`;
-- Opcional: dar orden inicial por ID para no cambiar el orden actual
UPDATE `dom_secanacategoria` SET `orden` = `secanacategoria_id` WHERE 1=1;
