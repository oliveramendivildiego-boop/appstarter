-- Script para identificar y limpiar comisiones duplicadas
-- Ejecutar: mysql -u root -p laboratorio < database/clean_duplicate_commissions.sql

-- 1. Identificar duplicados por registro_id y doctor_id
SELECT 
    registro_id, 
    doctor_id, 
    COUNT(*) as duplicados,
    GROUP_CONCAT(commission_id ORDER BY commission_id) as ids_duplicados
FROM dom_doctor_commissions 
GROUP BY registro_id, doctor_id 
HAVING COUNT(*) > 1;

-- 2. Eliminar duplicados manteniendo el más antiguo (menor commission_id)
DELETE c1 FROM dom_doctor_commissions c1
INNER JOIN dom_doctor_commissions c2 
WHERE c1.registro_id = c2.registro_id 
AND c1.doctor_id = c2.doctor_id 
AND c1.commission_id > c2.commission_id;

-- 3. Verificar resultado después de limpieza
SELECT 
    registro_id, 
    doctor_id, 
    COUNT(*) as total
FROM dom_doctor_commissions 
GROUP BY registro_id, doctor_id 
ORDER BY registro_id;

-- 4. Mostrar todas las comisiones restantes
SELECT * FROM dom_doctor_commissions ORDER BY registro_id, doctor_id;
