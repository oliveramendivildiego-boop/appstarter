-- Agregar módulo de comisiones de doctores (ejecutar si no se ejecutó add_modulos_nuevos.sql)
-- mysql -u root -p laboratorio < database/add_doctor_commissions_module.sql

INSERT IGNORE INTO `dom_modules` (`name_lang_key`, `desc_lang_key`, `sort`, `module_id`) VALUES
('module_doctor_commissions', 'module_doctor_commissions_desc', 59, 'doctor_commissions');

-- Dar permiso a admin (person_id=1) y otros empleados si es necesario
INSERT IGNORE INTO `dom_permissions` (`module_id`, `person_id`) VALUES
('doctor_commissions', 1);

-- Opcional: Dar permiso a empleados específicos (reemplazar X con el person_id del empleado)
-- INSERT IGNORE INTO `dom_permissions` (`module_id`, `person_id`) VALUES
-- ('doctor_commissions', X);
