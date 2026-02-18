-- Migración: Módulos faltantes del laboratorio
-- Ejecutar contra la base de datos laboratorio

-- NOTA: Si una columna ya existe, omita esa línea o ignore el error "Duplicate column"

-- 1. Pacientes: seguro e institución
ALTER TABLE `dom_customers` ADD COLUMN `seguro` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `dom_customers` ADD COLUMN `institucion` VARCHAR(255) DEFAULT NULL;

-- 2. Órdenes: prioridad y perfiles
ALTER TABLE `dom_registro` ADD COLUMN `prioridad` TINYINT DEFAULT 0 COMMENT '0=rutina, 1=urgente';
ALTER TABLE `dom_registro` ADD COLUMN `perfil_id` INT DEFAULT NULL;

-- Tabla perfiles de exámenes
CREATE TABLE IF NOT EXISTS `dom_perfil_examen` (
  `perfil_id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(255) NOT NULL,
  `pruebas` VARCHAR(500) NOT NULL COMMENT 'IDs separados por coma',
  `deleted` TINYINT DEFAULT 0,
  PRIMARY KEY (`perfil_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 3. Gestión de muestras
CREATE TABLE IF NOT EXISTS `dom_tipo_muestra` (
  `tipo_muestra_id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `deleted` TINYINT DEFAULT 0,
  PRIMARY KEY (`tipo_muestra_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `dom_tipo_muestra` (`nombre`) VALUES 
  ('Sangre'), ('Orina'), ('Hisopado'), ('LCR'), ('Heces'), ('Otro');

CREATE TABLE IF NOT EXISTS `dom_muestra` (
  `muestra_id` INT NOT NULL AUTO_INCREMENT,
  `registro_id` INT NOT NULL,
  `codigo_barras` VARCHAR(50) NOT NULL,
  `tipo_muestra_id` INT DEFAULT 1,
  `estado` TINYINT DEFAULT 0 COMMENT '0=tomada, 1=recibida, 2=procesada, 3=validada',
  `fecha_tomada` DATETIME DEFAULT NULL,
  `fecha_recibida` DATETIME DEFAULT NULL,
  `fecha_procesada` DATETIME DEFAULT NULL,
  `usuario_tomo` INT DEFAULT NULL,
  `usuario_recibio` INT DEFAULT NULL,
  `observaciones` TEXT,
  `deleted` TINYINT DEFAULT 0,
  PRIMARY KEY (`muestra_id`),
  UNIQUE KEY `codigo_barras` (`codigo_barras`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 4. Valores críticos
ALTER TABLE `dom_priresultados` ADD COLUMN `critico_min` VARCHAR(20) DEFAULT NULL;
ALTER TABLE `dom_priresultados` ADD COLUMN `critico_max` VARCHAR(20) DEFAULT NULL;
ALTER TABLE `dom_secanacategoria` ADD COLUMN `critico_min` VARCHAR(20) DEFAULT NULL;
ALTER TABLE `dom_secanacategoria` ADD COLUMN `critico_max` VARCHAR(20) DEFAULT NULL;

-- 5. Validación: técnico/médico
ALTER TABLE `dom_resulanalisis` ADD COLUMN `validado_tecnico` TINYINT DEFAULT 0;
ALTER TABLE `dom_resulanalisis` ADD COLUMN `validado_medico` TINYINT DEFAULT 0;
ALTER TABLE `dom_resulanalisis` ADD COLUMN `fecha_validacion` DATETIME DEFAULT NULL;
ALTER TABLE `dom_resulanalisis` ADD COLUMN `observaciones_clinicas` TEXT;

-- 6. Control de calidad
CREATE TABLE IF NOT EXISTS `dom_control_calidad` (
  `control_id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(255) NOT NULL,
  `tipo` TINYINT DEFAULT 1 COMMENT '1=interno, 2=externo',
  `deleted` TINYINT DEFAULT 0,
  PRIMARY KEY (`control_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `dom_control_valor` (
  `valor_id` INT NOT NULL AUTO_INCREMENT,
  `control_id` INT NOT NULL,
  `fecha` DATE NOT NULL,
  `valor` DECIMAL(12,4) NOT NULL,
  `esperado` DECIMAL(12,4) DEFAULT NULL,
  `observaciones` TEXT,
  PRIMARY KEY (`valor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 7. Inventario de reactivos
CREATE TABLE IF NOT EXISTS `dom_reactivo` (
  `reactivo_id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(255) NOT NULL,
  `unidad` VARCHAR(50) DEFAULT NULL,
  `stock_minimo` INT DEFAULT 0,
  `deleted` TINYINT DEFAULT 0,
  PRIMARY KEY (`reactivo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `dom_reactivo_lote` (
  `lote_id` INT NOT NULL AUTO_INCREMENT,
  `reactivo_id` INT NOT NULL,
  `codigo_lote` VARCHAR(100) NOT NULL,
  `cantidad` INT NOT NULL DEFAULT 0,
  `fecha_vencimiento` DATE DEFAULT NULL,
  `fecha_ingreso` DATE DEFAULT NULL,
  `deleted` TINYINT DEFAULT 0,
  PRIMARY KEY (`lote_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 8. Equipos y mantenimiento
CREATE TABLE IF NOT EXISTS `dom_equipo` (
  `equipo_id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(255) NOT NULL,
  `codigo` VARCHAR(50) DEFAULT NULL,
  `ubicacion` VARCHAR(255) DEFAULT NULL,
  `deleted` TINYINT DEFAULT 0,
  PRIMARY KEY (`equipo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `dom_equipo_mantenimiento` (
  `mantenimiento_id` INT NOT NULL AUTO_INCREMENT,
  `equipo_id` INT NOT NULL,
  `tipo` TINYINT DEFAULT 1 COMMENT '1=preventivo, 2=correctivo',
  `fecha` DATE NOT NULL,
  `descripcion` TEXT,
  `realizado_por` INT DEFAULT NULL,
  `deleted` TINYINT DEFAULT 0,
  PRIMARY KEY (`mantenimiento_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 9. Roles y auditoría
CREATE TABLE IF NOT EXISTS `dom_rol` (
  `rol_id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`rol_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `dom_rol` (`nombre`, `descripcion`) VALUES 
  ('Administrador', 'Acceso total al sistema'),
  ('Bioquímico', 'Validación médica de resultados'),
  ('Técnico', 'Procesamiento de análisis'),
  ('Médico', 'Consulta de resultados'),
  ('Recepción', 'Registro de órdenes y pacientes');

ALTER TABLE `dom_employees` ADD COLUMN `rol_id` INT DEFAULT NULL;

CREATE TABLE IF NOT EXISTS `dom_auditoria` (
  `auditoria_id` INT NOT NULL AUTO_INCREMENT,
  `person_id` INT DEFAULT NULL,
  `modulo` VARCHAR(100) NOT NULL,
  `accion` VARCHAR(50) NOT NULL,
  `registro_id` VARCHAR(50) DEFAULT NULL,
  `fecha` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` VARCHAR(45) DEFAULT NULL,
  `datos` TEXT,
  PRIMARY KEY (`auditoria_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Módulos nuevos (ejecutar si no existen)
INSERT IGNORE INTO `dom_modules` (`name_lang_key`, `desc_lang_key`, `sort`, `module_id`) VALUES
('module_controlcalidad', 'module_controlcalidad_desc', 55, 'controlcalidad'),
('module_reactivos', 'module_reactivos_desc', 56, 'reactivos'),
('module_equipos', 'module_equipos_desc', 57, 'equipos'),
('module_auditoria', 'module_auditoria_desc', 58, 'auditoria');

-- Permisos para admin (person_id=1)
INSERT IGNORE INTO `dom_permissions` (`module_id`, `person_id`) VALUES
('controlcalidad', 1), ('reactivos', 1), ('equipos', 1), ('auditoria', 1);
