-- Migración: Tabla genérica para valores de opciones (tipos de resultado)
-- Permite agregar/editar tipos de resultado con múltiples valores sin crear tablas nuevas

-- Tabla de valores para opciones personalizadas
CREATE TABLE IF NOT EXISTS `dom_opcion_valores` (
  `opcion_valor_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `opciones_id` int(10) unsigned NOT NULL,
  `valor` varchar(255) NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`opcion_valor_id`),
  KEY `idx_opcion_valores_opciones` (`opciones_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
