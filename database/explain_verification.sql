-- Verificación post-migración: DbOptimizeIndexesLaboratorio
-- Ajusta USE si tu base no se llama `laboratorio`.
USE laboratorio;

-- Prefijo de tablas (dom_) según app/Config/Database.php

-- Registros por paciente y rango de fechas (índice: idx_registro_person_ingreso)
EXPLAIN SELECT r.registro_id, r.ingreso
FROM dom_registro r
WHERE r.person_id = 1
  AND r.ingreso >= '2025-01-01'
  AND r.ingreso < '2026-01-01';

-- JOIN pago → registro (índice: idx_pago_registro_id)
EXPLAIN SELECT p.pago_id, r.registro_id
FROM dom_pago p
INNER JOIN dom_registro r ON r.registro_id = p.registro_id
WHERE p.registro_id = 30;

-- Valores de registro ordenados (índice: idx_regvalues_registro_ord)
EXPLAIN SELECT rv.regvalues_id, rv.name
FROM dom_regvalues rv
WHERE rv.registro_id = '30'
ORDER BY rv.regvalues_id;

-- Resultados por orden (índice: idx_resulanalisis_registro_id)
EXPLAIN SELECT ra.resulanalisis_id
FROM dom_resulanalisis ra
WHERE ra.registro_id = '36';

-- Catálogo anidado (índices compuestos en prianacategoria / secanacategoria / priresultados)
EXPLAIN SELECT pr.priresultados_id
FROM dom_priresultados pr
WHERE pr.prianacategoria_id = 1
  AND pr.id_poblacion = 1
  AND (pr.deleted = 0 OR pr.deleted IS NULL);

-- Listado de personas por apellido (índice: idx_people_last_name_fa)
EXPLAIN SELECT person_id, first_name, last_name_fa
FROM dom_people
ORDER BY last_name_fa
LIMIT 50;

-- Auditoría reciente (índices: idx_auditoria_fecha, idx_auditoria_modulo_fecha)
EXPLAIN SELECT auditoria_id, modulo, fecha
FROM dom_auditoria
WHERE fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY fecha DESC
LIMIT 100;

-- Búsqueda FULLTEXT (solo si existe ft_people_nombres; si no, omitir)
-- EXPLAIN SELECT person_id FROM dom_people
-- WHERE MATCH(first_name, last_name_fa, last_name_mom) AGAINST('García' IN BOOLEAN MODE);
