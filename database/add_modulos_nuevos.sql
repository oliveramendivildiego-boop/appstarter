-- Agregar módulos nuevos (ejecutar después de migrations_modulos_2025.sql)
-- mysql -u root -p laboratorio < database/add_modulos_nuevos.sql

INSERT IGNORE INTO `dom_modules` (`name_lang_key`, `desc_lang_key`, `sort`, `module_id`) VALUES
('module_controlcalidad', 'module_controlcalidad_desc', 55, 'controlcalidad'),
('module_reactivos', 'module_reactivos_desc', 56, 'reactivos'),
('module_equipos', 'module_equipos_desc', 57, 'equipos'),
('module_auditoria', 'module_auditoria_desc', 58, 'auditoria'),
('module_doctor_commissions', 'module_doctor_commissions_desc', 59, 'doctor_commissions');

-- Dar permiso a admin (person_id=1)
INSERT IGNORE INTO `dom_permissions` (`module_id`, `person_id`) VALUES
('controlcalidad', 1),
('reactivos', 1),
('equipos', 1),
('auditoria', 1),
('doctor_commissions', 1);
