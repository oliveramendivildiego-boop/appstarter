-- ============================================================================
-- Datos sintéticos para validar rendimiento de los reportes analíticos nuevos
-- Crea la BD laboratorio_perftest (separada; NO toca la BD productiva).
--   ~100.000 órdenes, ~300.000 resultados, ~250.000 eventos de auditoría.
-- Uso:  mysql -u root < tests/perf/analytics_perf_seed.sql
-- ============================================================================

DROP DATABASE IF EXISTS laboratorio_perftest;
CREATE DATABASE laboratorio_perftest CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE laboratorio_perftest;

SET SESSION cte_max_recursion_depth = 400000;

-- Esquema clonado de la BD real (estructura + índices)
CREATE TABLE dom_people            LIKE laboratorio.dom_people;
CREATE TABLE dom_doctors           LIKE laboratorio.dom_doctors;
CREATE TABLE dom_employees         LIKE laboratorio.dom_employees;
CREATE TABLE dom_registro          LIKE laboratorio.dom_registro;
CREATE TABLE dom_pago              LIKE laboratorio.dom_pago;
CREATE TABLE dom_pago_abono        LIKE laboratorio.dom_pago_abono;
CREATE TABLE dom_regvalues         LIKE laboratorio.dom_regvalues;
CREATE TABLE dom_resulanalisis     LIKE laboratorio.dom_resulanalisis;
CREATE TABLE dom_auditoria         LIKE laboratorio.dom_auditoria;
CREATE TABLE dom_anacategoria      LIKE laboratorio.dom_anacategoria;
CREATE TABLE dom_prianacategoria   LIKE laboratorio.dom_prianacategoria;
CREATE TABLE dom_secanacategoria   LIKE laboratorio.dom_secanacategoria;
CREATE TABLE dom_priresultados     LIKE laboratorio.dom_priresultados;
CREATE TABLE dom_reactivo          LIKE laboratorio.dom_reactivo;
CREATE TABLE dom_reactivo_lote     LIKE laboratorio.dom_reactivo_lote;
CREATE TABLE dom_reactivo_movimiento  LIKE laboratorio.dom_reactivo_movimiento;
CREATE TABLE dom_reactivo_consumo_auto LIKE laboratorio.dom_reactivo_consumo_auto;

-- Catálogos reales (pequeños) copiados tal cual
INSERT INTO dom_anacategoria    SELECT * FROM laboratorio.dom_anacategoria;
INSERT INTO dom_prianacategoria SELECT * FROM laboratorio.dom_prianacategoria;
INSERT INTO dom_secanacategoria SELECT * FROM laboratorio.dom_secanacategoria;
INSERT INTO dom_priresultados   SELECT * FROM laboratorio.dom_priresultados;
INSERT INTO dom_doctors         SELECT * FROM laboratorio.dom_doctors;
INSERT INTO dom_employees       SELECT * FROM laboratorio.dom_employees;
INSERT INTO dom_reactivo        SELECT * FROM laboratorio.dom_reactivo;
INSERT INTO dom_reactivo_lote   SELECT * FROM laboratorio.dom_reactivo_lote;

-- 10.000 pacientes sintéticos
INSERT INTO dom_people (ci, first_name, last_name_fa, last_name_mom, phone_number, email, birthday, gender, comments)
WITH RECURSIVE n AS (SELECT 1 i UNION ALL SELECT i + 1 FROM n WHERE i < 10000)
SELECT CONCAT('CI', i), CONCAT('Paciente', i), CONCAT('Apellido', i), '', '', '',
       DATE_ADD('1950-01-01', INTERVAL (i % 25000) DAY), 1 + (i % 2), ''
FROM n;

-- 100.000 órdenes en los últimos 2 años (pruebas = CSV de 3 IDs reales)
INSERT INTO dom_registro (person_id, doctor_id, pruebas, ingreso, id_session, anulado, numero_orden)
WITH RECURSIVE n AS (SELECT 1 i UNION ALL SELECT i + 1 FROM n WHERE i < 100000),
ids AS (SELECT prianacategoria_id, ROW_NUMBER() OVER (ORDER BY prianacategoria_id) rn FROM dom_prianacategoria),
cnt AS (SELECT COUNT(*) c FROM ids)
SELECT
    1 + (i % 10000),
    COALESCE((SELECT MIN(doctor_id) FROM dom_doctors), 1),
    CONCAT(
        (SELECT prianacategoria_id FROM ids WHERE rn = 1 + (i % (SELECT c FROM cnt))),
        ',',
        (SELECT prianacategoria_id FROM ids WHERE rn = 1 + ((i * 7) % (SELECT c FROM cnt))),
        ',',
        (SELECT prianacategoria_id FROM ids WHERE rn = 1 + ((i * 13) % (SELECT c FROM cnt)))
    ),
    DATE_ADD(NOW() - INTERVAL 730 DAY, INTERVAL (i % 63072000) SECOND),
    1 + (i % 5),
    IF(i % 50 = 0, 1, 0),
    CONCAT('PT_', i)
FROM n;

-- 100.000 pagos (1:1 con la orden)
INSERT INTO dom_pago (registro_id, total_reco, total, monto_pagar, tipopago, saldo, comentarios)
SELECT registro_id, '0', CAST(50 + (registro_id % 450) AS CHAR),
       CAST(50 + (registro_id % 450) AS CHAR), '1', '0', ''
FROM dom_registro;

-- ~300.000 resultados (claves reales c_/noc_ del catálogo, valores numéricos)
INSERT INTO dom_regvalues (regvalues, registro_id, name, id_session)
SELECT ROUND(RAND(r.registro_id * k.kn) * 200, 1),
       CAST(r.registro_id AS CHAR), k.name, 1 + (r.registro_id % 5)
FROM dom_registro r
JOIN (
    (SELECT CONCAT('c_', secanacategoria_id) name, 1 kn FROM dom_secanacategoria WHERE deleted = 0 AND es_separador = 0 ORDER BY secanacategoria_id LIMIT 1)
    UNION ALL
    (SELECT CONCAT('c_', secanacategoria_id), 2 FROM dom_secanacategoria WHERE deleted = 0 AND es_separador = 0 ORDER BY secanacategoria_id DESC LIMIT 1)
    UNION ALL
    (SELECT CONCAT('noc_', priresultados_id), 3 FROM dom_priresultados WHERE deleted = 0 ORDER BY priresultados_id LIMIT 1)
) k;

-- ~250.000 eventos de auditoría: crear + guardar_resultados (+ validar en 50%)
INSERT INTO dom_auditoria (person_id, modulo, accion, registro_id, fecha, ip, datos)
SELECT 1 + (registro_id % 5), 'registers', 'crear', CAST(registro_id AS CHAR), ingreso, '127.0.0.1', NULL
FROM dom_registro;

INSERT INTO dom_auditoria (person_id, modulo, accion, registro_id, fecha, ip, datos)
SELECT 1 + (registro_id % 5), 'registers', 'guardar_resultados', CAST(registro_id AS CHAR),
       DATE_ADD(ingreso, INTERVAL 2 + (registro_id % 70) HOUR), '127.0.0.1',
       IF(registro_id % 10 = 0,
          '{"es_primera_carga":false,"cambios":[{"campo":"Glucosa","valor_anterior":"95","valor_nuevo":"105","prueba":"Glucosa"}],"agregados":[],"eliminados":[]}',
          '{"es_primera_carga":true,"cambios":[],"agregados":[],"eliminados":[]}')
FROM dom_registro;

INSERT INTO dom_auditoria (person_id, modulo, accion, registro_id, fecha, ip, datos)
SELECT 1 + (registro_id % 5), 'registers', 'validar_tecnico', CAST(registro_id AS CHAR),
       DATE_ADD(ingreso, INTERVAL 4 + (registro_id % 90) HOUR), '127.0.0.1', '{"tipo":"tecnico"}'
FROM dom_registro WHERE registro_id % 2 = 0;

-- ~60.000 salidas de inventario en el último año
INSERT INTO dom_reactivo_movimiento (reactivo_id, tipo, cantidad, fecha, person_id, lote_id, registro_id)
WITH RECURSIVE n AS (SELECT 1 i UNION ALL SELECT i + 1 FROM n WHERE i < 60000)
SELECT COALESCE((SELECT MIN(reactivo_id) FROM dom_reactivo), 1) + (i % GREATEST((SELECT COUNT(*) FROM dom_reactivo), 1)),
       'salida', 1 + (i % 3),
       DATE_ADD(NOW() - INTERVAL 365 DAY, INTERVAL (i % 31536000) SECOND),
       1 + (i % 5), NULL, 1 + (i % 100000)
FROM n;

SELECT 'registro' tabla, COUNT(*) filas FROM dom_registro
UNION ALL SELECT 'regvalues', COUNT(*) FROM dom_regvalues
UNION ALL SELECT 'auditoria', COUNT(*) FROM dom_auditoria
UNION ALL SELECT 'pago', COUNT(*) FROM dom_pago
UNION ALL SELECT 'people', COUNT(*) FROM dom_people
UNION ALL SELECT 'reactivo_movimiento', COUNT(*) FROM dom_reactivo_movimiento;
