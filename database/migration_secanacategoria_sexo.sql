-- Añadir campo sexo a sub-clases (secanacategoria)
-- Valores: 'ambos', 'masculino', 'femenino'
ALTER TABLE `dom_secanacategoria` ADD COLUMN `sexo` VARCHAR(20) DEFAULT 'ambos' AFTER `paciente_id`;
