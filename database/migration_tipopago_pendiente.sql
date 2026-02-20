-- Agregar tipo de pago "Pendiente" para pagos diferidos
-- Ejecutar en MySQL o desde phpMyAdmin

INSERT INTO `dom_tipopago` (`tipopago`, `tipopago_id`)
SELECT 'Pendiente', 4
WHERE NOT EXISTS (SELECT 1 FROM `dom_tipopago` WHERE `tipopago_id` = 4);
