-- Migration para agregar campo de comisión a doctores
-- Ejecutar: ALTER TABLE dom_doctors ADD COLUMN commission_percent DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Porcentaje de comisión del doctor';

ALTER TABLE dom_doctors ADD COLUMN commission_percent DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Porcentaje de comisión del doctor';
ALTER TABLE dom_doctors ADD COLUMN has_commission tinyint(1) DEFAULT 0 COMMENT '1=usa comision, 0=no usa comision';

-- Tabla para registrar las comisiones generadas
CREATE TABLE IF NOT EXISTS dom_doctor_commissions (
  commission_id int(10) NOT NULL AUTO_INCREMENT,
  doctor_id int(10) NOT NULL,
  registro_id int(10) NOT NULL,
  total_amount decimal(10,2) NOT NULL COMMENT 'Monto total de la prueba',
  commission_percent decimal(5,2) NOT NULL COMMENT 'Porcentaje de comisión aplicado',
  commission_amount decimal(10,2) NOT NULL COMMENT 'Monto de comisión calculado',
  created_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  paid_date timestamp NULL DEFAULT NULL COMMENT 'Fecha de pago de comisión',
  status tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=pendiente, 1=pagado',
  notes text,
  PRIMARY KEY (commission_id),
  KEY idx_doctor_id (doctor_id),
  KEY idx_registro_id (registro_id),
  KEY idx_status (status),
  KEY idx_created_date (created_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Comisiones generadas por doctor por pruebas realizadas';
