-- -------------------------------------------------------------
-- Esquema de Base de Datos para el Sistema de Laboratorio (LIS)
-- Versión: 1.0
-- -------------------------------------------------------------

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Estructuras de Personas (Pacientes, Empleados)
--

CREATE TABLE `people` (
  `person_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name_fa` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name_mom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ci` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `gender` int(1) DEFAULT NULL,
  `address_1` text COLLATE utf8mb4_unicode_ci,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`person_id`),
  UNIQUE KEY `ci` (`ci`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla base para personas (pacientes, empleados)';

CREATE TABLE `customers` (
  `person_id` int(11) NOT NULL,
  `account_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'NIT / RUC para facturación',
  `seguro` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `institucion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`person_id`),
  CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`person_id`) REFERENCES `people` (`person_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Datos específicos de Pacientes';

CREATE TABLE `employees` (
  `person_id` int(11) NOT NULL,
  `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol_id` int(11) DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`person_id`),
  UNIQUE KEY `username` (`username`),
  CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`person_id`) REFERENCES `people` (`person_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Datos de Empleados y credenciales de acceso';

CREATE TABLE `doctors` (
  `doctor_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `speciality` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `commission_percent` decimal(5,2) DEFAULT '0.00',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Médicos referentes';

--
-- Módulos de Seguridad y Permisos
--

CREATE TABLE `modules` (
  `module_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_lang_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `desc_lang_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`module_id`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `permissions` (
  `module_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `person_id` int(10) NOT NULL,
  PRIMARY KEY (`module_id`,`person_id`),
  KEY `person_id` (`person_id`),
  CONSTRAINT `permissions_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`) ON DELETE CASCADE,
  CONSTRAINT `permissions_ibfk_2` FOREIGN KEY (`person_id`) REFERENCES `employees` (`person_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Núcleo Operativo: Catálogo de Pruebas y Órdenes
--

CREATE TABLE `anacategoria` (
  `anacategoria_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`anacategoria_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Categorías de pruebas (Grupos)';

CREATE TABLE `prianacategoria` (
  `prianacategoria_id` int(11) NOT NULL AUTO_INCREMENT,
  `anacategoria_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `order` int(11) NOT NULL DEFAULT '0',
  `cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cost_deriv` decimal(10,2) DEFAULT '0.00',
  `compleja` int(1) NOT NULL DEFAULT '0' COMMENT '0:Simple, 1:Compuesta, 2:Cultivo, 3:Personalizado',
  `mostrar_valores` tinyint(1) NOT NULL DEFAULT '1',
  `graficar` tinyint(1) NOT NULL DEFAULT '0',
  `tipo_muestra_id` int(11) DEFAULT NULL,
  `metodo_id` int(11) DEFAULT NULL,
  `recomendaciones_previas` text COLLATE utf8mb4_unicode_ci,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`prianacategoria_id`),
  KEY `anacategoria_id` (`anacategoria_id`),
  CONSTRAINT `prianacategoria_ibfk_1` FOREIGN KEY (`anacategoria_id`) REFERENCES `anacategoria` (`anacategoria_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Definición de cada prueba o análisis';

CREATE TABLE `registro` (
  `registro_id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_orden` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `person_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `pruebas` text COLLATE utf8mb4_unicode_ci,
  `ingreso` datetime NOT NULL,
  `prioridad` tinyint(1) NOT NULL DEFAULT '0',
  `id_session` int(11) DEFAULT NULL COMMENT 'Empleado que creó la orden',
  `comentario_resultado` text COLLATE utf8mb4_unicode_ci,
  `anulado` tinyint(1) NOT NULL DEFAULT '0',
  `motivo_anulacion` text COLLATE utf8mb4_unicode_ci,
  `fecha_anulacion` datetime DEFAULT NULL,
  `person_id_anulo` int(11) DEFAULT NULL,
  PRIMARY KEY (`registro_id`),
  UNIQUE KEY `numero_orden` (`numero_orden`),
  KEY `person_id` (`person_id`),
  KEY `doctor_id` (`doctor_id`),
  CONSTRAINT `registro_ibfk_1` FOREIGN KEY (`person_id`) REFERENCES `people` (`person_id`),
  CONSTRAINT `registro_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE SET NULL,
  CONSTRAINT `registro_ibfk_3` FOREIGN KEY (`id_session`) REFERENCES `employees` (`person_id`) ON DELETE SET NULL,
  CONSTRAINT `registro_ibfk_4` FOREIGN KEY (`person_id_anulo`) REFERENCES `employees` (`person_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Órdenes de laboratorio';

CREATE TABLE `regvalues` (
  `regvalues_id` int(11) NOT NULL AUTO_INCREMENT,
  `registro_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Clave del parámetro (ej. sec_123, pri_456)',
  `regvalues` text COLLATE utf8mb4_unicode_ci COMMENT 'Valor del resultado',
  `id_session` int(11) DEFAULT NULL,
  PRIMARY KEY (`regvalues_id`),
  KEY `registro_id` (`registro_id`),
  CONSTRAINT `regvalues_ibfk_1` FOREIGN KEY (`registro_id`) REFERENCES `registro` (`registro_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Resultados de los parámetros de una orden';

--
-- Módulos Financieros (Pagos, Comisiones, Cotizaciones)
--

CREATE TABLE `pago` (
  `pago_id` int(11) NOT NULL AUTO_INCREMENT,
  `registro_id` int(11) NOT NULL,
  `total_reco` decimal(10,2) DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL,
  `monto_pagar` decimal(10,2) NOT NULL,
  `tipopago` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `saldo` decimal(10,2) NOT NULL,
  `comentarios` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`pago_id`),
  UNIQUE KEY `registro_id` (`registro_id`),
  CONSTRAINT `pago_ibfk_1` FOREIGN KEY (`registro_id`) REFERENCES `registro` (`registro_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Información de pago por orden';

CREATE TABLE `pago_abonos` (
  `pago_abono_id` int(11) NOT NULL AUTO_INCREMENT,
  `registro_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `tipopago` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_abono` datetime NOT NULL,
  `person_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`pago_abono_id`),
  KEY `registro_id` (`registro_id`),
  CONSTRAINT `pago_abonos_ibfk_1` FOREIGN KEY (`registro_id`) REFERENCES `registro` (`registro_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial de abonos a una orden';

CREATE TABLE `doctor_commissions` (
  `commission_id` int(11) NOT NULL AUTO_INCREMENT,
  `doctor_id` int(11) NOT NULL,
  `registro_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `commission_percent` decimal(5,2) NOT NULL,
  `commission_amount` decimal(10,2) NOT NULL,
  `created_date` datetime NOT NULL,
  `paid_date` datetime DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=pendiente, 1=pagada',
  `notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`commission_id`),
  KEY `doctor_id` (`doctor_id`),
  KEY `registro_id` (`registro_id`),
  CONSTRAINT `doctor_commissions_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE,
  CONSTRAINT `doctor_commissions_ibfk_2` FOREIGN KEY (`registro_id`) REFERENCES `registro` (`registro_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `toquotelogs` (
  `toquotelogs_id` int(11) NOT NULL AUTO_INCREMENT,
  `person_id` int(11) DEFAULT NULL COMMENT 'Empleado que cotizó',
  `cotizo` text COLLATE utf8mb4_unicode_ci COMMENT 'Nombres de pruebas (legacy)',
  `costo` decimal(10,2) DEFAULT NULL,
  `refe` decimal(10,2) DEFAULT NULL,
  `items_json` json DEFAULT NULL,
  `fecha` datetime NOT NULL,
  PRIMARY KEY (`toquotelogs_id`),
  KEY `person_id` (`person_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de cotizaciones';

--
-- Módulos de Soporte (Inventario, Equipos, Calidad)
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `inventory_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `reorder_point` int(11) DEFAULT '0',
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`),
  KEY `supplier_id` (`supplier_id`),
  CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `inventory_lots` (
  `lot_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `lot_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT '0',
  `expiry_date` date DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  PRIMARY KEY (`lot_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `inventory_lots_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `inventory_movements` (
  `movement_id` int(11) NOT NULL AUTO_INCREMENT,
  `lot_id` int(11) NOT NULL,
  `quantity_change` int(11) NOT NULL COMMENT 'Positivo para entrada, negativo para salida',
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `movement_date` datetime NOT NULL,
  `person_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`movement_id`),
  KEY `lot_id` (`lot_id`),
  CONSTRAINT `inventory_movements_ibfk_1` FOREIGN KEY (`lot_id`) REFERENCES `inventory_lots` (`lot_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `equipment` (
  `equipment_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `acquisition_date` date DEFAULT NULL,
  `supplier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `maintenance_frequency` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`equipment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `equipment_maintenance` (
  `maintenance_id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_id` int(11) NOT NULL,
  `maintenance_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `maintenance_date` date NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `technician` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`maintenance_id`),
  KEY `equipment_id` (`equipment_id`),
  CONSTRAINT `equipment_maintenance_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `quality_control_lots` (
  `lot_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lot_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiry_date` date DEFAULT NULL,
  PRIMARY KEY (`lot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `quality_control_lot_analytes` (
  `analyte_id` int(11) NOT NULL AUTO_INCREMENT,
  `lot_id` int(11) NOT NULL,
  `analyte_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_mean` decimal(10,4) NOT NULL,
  `std_dev` decimal(10,4) NOT NULL,
  PRIMARY KEY (`analyte_id`),
  KEY `lot_id` (`lot_id`),
  CONSTRAINT `qc_lot_analytes_ibfk_1` FOREIGN KEY (`lot_id`) REFERENCES `quality_control_lots` (`lot_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `quality_control_results` (
  `result_id` int(11) NOT NULL AUTO_INCREMENT,
  `analyte_id` int(11) NOT NULL,
  `result_value` decimal(10,4) NOT NULL,
  `result_date` datetime NOT NULL,
  `person_id` int(11) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`result_id`),
  KEY `analyte_id` (`analyte_id`),
  CONSTRAINT `qc_results_ibfk_1` FOREIGN KEY (`analyte_id`) REFERENCES `quality_control_lot_analytes` (`analyte_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablas de Configuración y Sistema
--

CREATE TABLE `app_config` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_log` (
  `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `person_id` int(11) DEFAULT NULL,
  `module` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `object_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details` json DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  KEY `person_id` (`person_id`),
  KEY `module_action` (`module`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ci_sessions` (
  `id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` int(10) unsigned NOT NULL DEFAULT '0',
  `data` blob NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ci_sessions_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablas de Catálogos Auxiliares
--

CREATE TABLE `poblacion` (
  `id_poblacion` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `edad_min` int(11) DEFAULT NULL,
  `edad_max` int(11) DEFAULT NULL,
  `unidad` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'años',
  `orden` int(11) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_poblacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Grupos poblacionales para valores de referencia';

CREATE TABLE `opciones` (
  `opciones_id` int(11) NOT NULL AUTO_INCREMENT,
  `opciones` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tabla` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`opciones_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tipos de resultado (listas desplegables)';

CREATE TABLE `opcion_valores` (
  `opcion_valor_id` int(11) NOT NULL AUTO_INCREMENT,
  `opciones_id` int(11) NOT NULL,
  `valor` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `orden` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`opcion_valor_id`),
  KEY `opciones_id` (`opciones_id`),
  CONSTRAINT `opcion_valores_ibfk_1` FOREIGN KEY (`opciones_id`) REFERENCES `opciones` (`opciones_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tipo_muestra` (
  `tipo_muestra_id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`tipo_muestra_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `metodo` (
  `metodo_id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`metodo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `genero` (
  `genero_id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `orden` int(11) NOT NULL DEFAULT '0',
  `deleted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`genero_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Datos Iniciales
--

INSERT INTO `people` (`person_id`, `first_name`, `last_name_fa`, `deleted`) VALUES
(1, 'Admin', 'User', 0);

INSERT INTO `employees` (`person_id`, `username`, `password`, `deleted`, `active`) VALUES
(1, 'admin', '5f4dcc3b5aa765d61d8327deb882cf99', 0, 1); -- password: 'password'

INSERT INTO `modules` (`module_id`, `name_lang_key`, `sort`) VALUES
('customers', 'module_customers', 10),
('employees', 'module_employees', 100),
('doctors', 'module_doctors', 20),
('doctor_commissions', 'module_doctor_commissions', 25),
('labotests', 'module_labotests', 30),
('toquotes', 'module_toquotes', 40),
('registers', 'module_registers', 5),
('reports', 'module_reports', 50),
('config', 'module_config', 110),
('quality_control', 'module_quality_control', 60),
('inventory', 'module_inventory', 70),
('equipment', 'module_equipment', 80),
('audit', 'module_audit', 90);

INSERT INTO `permissions` (`module_id`, `person_id`) VALUES
('customers', 1),
('employees', 1),
('doctors', 1),
('doctor_commissions', 1),
('labotests', 1),
('toquotes', 1),
('registers', 1),
('reports', 1),
('config', 1),
('quality_control', 1),
('inventory', 1),
('equipment', 1),
('audit', 1);

COMMIT;
