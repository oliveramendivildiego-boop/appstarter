-- Migración: Inventario de insumos (presentación vs unidad base)
-- Ejecutar: mysql -u root -p laboratorio < database/migration_inventario_insumos.sql
-- Compatible con tablas existentes dom_reactivo y dom_reactivo_lote

-- 1. Agregar columnas a insumos (dom_reactivo)
-- Si alguna columna ya existe, omita esa línea o ignore el error
ALTER TABLE `dom_reactivo` ADD COLUMN `grupo` VARCHAR(100) DEFAULT NULL;
ALTER TABLE `dom_reactivo` ADD COLUMN `subgrupo` VARCHAR(100) DEFAULT NULL;
ALTER TABLE `dom_reactivo` ADD COLUMN `unidad_base` VARCHAR(50) DEFAULT NULL;
ALTER TABLE `dom_reactivo` ADD COLUMN `contenido_por_presentacion` INT DEFAULT 1 COMMENT 'Unidades base por caja/frasco';
ALTER TABLE `dom_reactivo` ADD COLUMN `tipo` TINYINT DEFAULT 1 COMMENT '1=reactivo, 2=kit, 3=insumo';
ALTER TABLE `dom_reactivo` ADD COLUMN `ubicacion` VARCHAR(255) DEFAULT NULL;

-- Migrar unidad → unidad_base si existe
UPDATE `dom_reactivo` SET `unidad_base` = `unidad` WHERE `unidad_base` IS NULL AND `unidad` IS NOT NULL;

-- 2. Tabla de movimientos (entrada/salida con responsable)
CREATE TABLE IF NOT EXISTS `dom_reactivo_movimiento` (
  `movimiento_id` INT NOT NULL AUTO_INCREMENT,
  `reactivo_id` INT NOT NULL,
  `tipo` VARCHAR(20) NOT NULL COMMENT 'entrada|salida',
  `cantidad` DECIMAL(12,4) NOT NULL,
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `person_id` INT DEFAULT NULL COMMENT 'responsable',
  `lote_id` INT DEFAULT NULL,
  `observaciones` TEXT,
  PRIMARY KEY (`movimiento_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Columna para asociar consumo con orden (omitir si ya existe):
ALTER TABLE `dom_reactivo_movimiento` ADD COLUMN `registro_id` INT DEFAULT NULL COMMENT 'Orden asociada (opcional, null=sin prueba)';
