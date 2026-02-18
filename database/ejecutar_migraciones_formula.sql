-- Ejecutar en phpMyAdmin o MySQL para corregir el error "Unknown column 'formula_expresion'"
-- Si alguna columna ya existe, ese ALTER fallará; ejecuta los que falten individualmente.

-- 1. dom_secanacategoria (sub-clases calculadas en pruebas compuestas)
ALTER TABLE `dom_secanacategoria` ADD COLUMN `formula_expresion` VARCHAR(500) DEFAULT NULL;

-- 2. dom_formulas (fórmulas personalizadas guardadas)
ALTER TABLE `dom_formulas` ADD COLUMN `formula_expresion` VARCHAR(500) DEFAULT NULL;
