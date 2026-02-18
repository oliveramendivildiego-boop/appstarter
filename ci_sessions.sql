-- Tabla de sesiones para CodeIgniter 4 (DatabaseHandler)
-- Ejecutar en la base de datos laboratorio

CREATE TABLE IF NOT EXISTS `dom_ci_sessions` (
  `id` varchar(128) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` timestamp DEFAULT CURRENT_TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  `data` blob NOT NULL,
  PRIMARY KEY (`id`),
  KEY `dom_ci_sessions_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
