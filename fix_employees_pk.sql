-- Corrige tablas para que person_id sea PRIMARY KEY (requerido por las FKs)
-- Ejecutar en phpMyAdmin sobre la base 'laboratorio' si obtienes "Missing unique key"
-- Si DROP KEY da error (índice no existe), ejecuta solo los ADD PRIMARY KEY

SET FOREIGN_KEY_CHECKS = 0;

-- dom_employees
ALTER TABLE `dom_employees` DROP KEY `person_id`;
ALTER TABLE `dom_employees` ADD PRIMARY KEY (`person_id`);

-- dom_suppliers  
ALTER TABLE `dom_suppliers` DROP KEY `person_id`;
ALTER TABLE `dom_suppliers` ADD PRIMARY KEY (`person_id`);

-- dom_customers
ALTER TABLE `dom_customers` DROP KEY `person_id`;
ALTER TABLE `dom_customers` ADD PRIMARY KEY (`person_id`);

SET FOREIGN_KEY_CHECKS = 1;
