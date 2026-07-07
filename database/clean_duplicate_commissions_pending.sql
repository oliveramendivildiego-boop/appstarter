-- Elimina comisiones pendientes duplicadas (deja la más reciente por orden).
-- NOTA: En instalaciones con CI4 use: php spark migrate
-- (migración: 2026-07-07-160000_CleanDuplicateDoctorCommissions.php)
-- Este archivo queda como respaldo manual en phpMyAdmin.

DELETE c1 FROM dom_doctor_commissions c1
INNER JOIN dom_doctor_commissions c2
    ON c1.registro_id = c2.registro_id
    AND c1.status = 0
    AND c2.status = 0
    AND c1.commission_id < c2.commission_id;

DELETE c1 FROM dom_doctor_commissions c1
INNER JOIN dom_doctor_commissions c2
    ON c1.registro_id = c2.registro_id
    AND c1.commission_id < c2.commission_id;

ALTER TABLE dom_doctor_commissions
    ADD UNIQUE KEY uk_dc_registro_id (registro_id);

-- Verificar: no debe haber duplicados por registro_id
SELECT registro_id, COUNT(*) AS duplicados
FROM dom_doctor_commissions
GROUP BY registro_id
HAVING COUNT(*) > 1;
