-- Corregir entidades HTML en nombres de población (Ni&ntilde;os -> Niños)
-- Ejecutar en phpMyAdmin o: mysql -u root laboratorio < database/fix_poblacion_encoding.sql

UPDATE `dom_poblacion` SET `name` = 'Niños' WHERE `name` = 'Ni&ntilde;os';
