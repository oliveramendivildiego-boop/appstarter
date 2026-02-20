-- Tabla para registrar cada pago/abono realizado a una deuda
-- Cada vez que se agrega un pago desde el modal, se inserta aquí
-- Ejecutar en MySQL o desde phpMyAdmin

CREATE TABLE IF NOT EXISTS `dom_pago_abono` (
  `pago_abono_id` int(10) NOT NULL AUTO_INCREMENT,
  `registro_id` int(10) NOT NULL,
  `monto` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tipopago` varchar(10) NOT NULL DEFAULT '1',
  `fecha_abono` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pago_abono_id`),
  KEY `registro_id` (`registro_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
