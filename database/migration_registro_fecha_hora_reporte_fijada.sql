-- Opcional: mismo cambio que la migración de CodeIgniter.
-- Preferido: desde la raíz del proyecto ejecutar:
--   php spark migrate
--
-- Migración: app/Database/Migrations/2026-04-09-140000_RegistroFechaHoraReporteFijada.php
--
-- Si la columna ya existe, omita esta línea.

ALTER TABLE `dom_registro`
    ADD COLUMN `fecha_hora_reporte_fijada` DATETIME NULL DEFAULT NULL
    COMMENT 'Momento en que se imprimió o generó PDF del reporte por primera vez';
