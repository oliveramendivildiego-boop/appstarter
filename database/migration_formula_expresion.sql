-- Agregar columna formula_expresion para sub-clases calculadas en pruebas compuestas
-- Permite definir expresiones como: c_5 + c_10, o [Glucosa] * 0.5 + [Colesterol]
-- Referencias: c_{secanacategoria_id} para cada sub-clase existente

ALTER TABLE `dom_secanacategoria` ADD COLUMN `formula_expresion` VARCHAR(500) DEFAULT NULL AFTER `formulas_id`;
