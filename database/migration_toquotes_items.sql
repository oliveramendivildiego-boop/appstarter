-- Añadir campos para cotizaciones con detalle de items y referencia
ALTER TABLE `dom_toquotelogs`
  ADD COLUMN `refe` INT DEFAULT 0 AFTER `costo`,
  ADD COLUMN `items_json` TEXT DEFAULT NULL AFTER `refe`;
