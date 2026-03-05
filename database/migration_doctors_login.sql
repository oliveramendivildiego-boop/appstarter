-- Migración: Añadir login (username, password, email) a doctores para que puedan acceder al sistema
-- Ejecutar: mysql -u root nombre_bd < migration_doctors_login.sql
-- O ejecutar el contenido en phpMyAdmin/HeidiSQL

ALTER TABLE `dom_doctors`
  ADD COLUMN `username` VARCHAR(50) NULL DEFAULT NULL UNIQUE,
  ADD COLUMN `password` VARCHAR(255) NULL DEFAULT NULL,
  ADD COLUMN `email` VARCHAR(255) NULL DEFAULT NULL;
