-- Agregar formula_expresion a dom_formulas para fórmulas personalizadas guardadas
-- Cuando formula_expresion está llena, es una fórmula calculada (ej: c_5 + c_7 * 100 / c_5)
-- nombre_fun se deja vacío o 'custom' para estas fórmulas

ALTER TABLE `dom_formulas` ADD COLUMN `formula_expresion` VARCHAR(500) DEFAULT NULL AFTER `nombre_fun`;
