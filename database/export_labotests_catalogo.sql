-- =============================================================================
-- Exportar catálogo de LABOTESTS (análisis, sub-clases, referencias, etc.)
-- =============================================================================
-- Base y prefijo según app/Config/Database.php (ej. base `laboratorio`, prefijo dom_)
--
-- CONTENIDO DE ESTE ARCHIVO
--   1) Exportar: mysqldump (recomendado) y phpMyAdmin
--   2) Importar: mysql CLI y phpMyAdmin (ver sección IMPORTAR)
--   3) Consultas de verificación (conteos por tabla)
--
-- TABLAS INCLUIDAS (todo lo que usa /labotests en la app)
--   • dom_anacategoria          Grupos / categorías
--   • dom_prianacategoria       Análisis (pruebas) por grupo
--   • dom_secanacategoria       Filas de pruebas compuestas (referencias, títulos)
--   • dom_priresultados         Valores de referencia (pruebas no compuestas)
--   • dom_poblacion             Grupos poblacionales (edad/sexo en catálogo)
--   • dom_formulas              Fórmulas (incl. personalizadas con expresión)
--   • dom_opciones              Tipos de resultado (numérico, texto, etc.)
--   • dom_tipo_muestra          Tipo de muestra (si existe en tu BD)
--   • dom_metodo                Método de prueba (si existe)
--   • dom_manuals               Manuales / notas por análisis
--   • dom_perfil_examen         Perfiles (listas de prianacategoria_id)
--   • dom_labotest_reactivo_config  Consumo automático reactivo ↔ análisis
--
-- OPCIONAL (inventario; solo si quieres que los reactivo_id del config existan al restaurar)
--   • dom_reactivo
--
-- NO incluye: órdenes de trabajo (dom_registro), resultados de pacientes (dom_regvalues),
--             ni otros módulos ajenos al catálogo de pruebas.
-- =============================================================================


-- -----------------------------------------------------------------------------
-- MÉTODO A — mysqldump (MySQL / MariaDB, línea de comandos)
-- -----------------------------------------------------------------------------
-- Ajusta usuario, ruta al ejecutable y nombre de base si difieren.
--
-- Windows (CMD o PowerShell; ajusta la carpeta de mysql según tu WAMP/XAMPP):
/*
cd C:\wamp64\bin\mysql\mysql8.4.0\bin
mysqldump.exe -u root -p --single-transaction --set-gtid-purged=OFF --default-character-set=utf8mb4 --skip-comments laboratorio dom_anacategoria dom_prianacategoria dom_secanacategoria dom_priresultados dom_poblacion dom_formulas dom_opciones dom_tipo_muestra dom_metodo dom_manuals dom_perfil_examen dom_labotest_reactivo_config > C:\backup\labotests_catalogo.sql
*/
--
-- Linux / macOS / Git Bash:
/*
mysqldump -u root -p \
  --single-transaction \
  --set-gtid-purged=OFF \
  --default-character-set=utf8mb4 \
  --skip-comments \
  laboratorio \
  dom_anacategoria \
  dom_prianacategoria \
  dom_secanacategoria \
  dom_priresultados \
  dom_poblacion \
  dom_formulas \
  dom_opciones \
  dom_tipo_muestra \
  dom_metodo \
  dom_manuals \
  dom_perfil_examen \
  dom_labotest_reactivo_config \
  > export_labotests_catalogo_$(date +%Y%m%d_%H%M%S).sql
*/
--
-- Export EXTENDIDO (añade catálogo de reactivos para coherencia con labotest_reactivo_config):
--   Añade al final de la lista de tablas:  dom_reactivo
--
-- Si alguna tabla no existe en tu instalación (p. ej. dom_tipo_muestra), quítala del comando.
-- -----------------------------------------------------------------------------


-- -----------------------------------------------------------------------------
-- MÉTODO B — phpMyAdmin
-- -----------------------------------------------------------------------------
-- 1. Selecciona la base de datos (ej. laboratorio).
-- 2. Pestaña "Exportar".
-- 3. Método personalizado → elige solo las tablas listadas arriba (marcar check).
-- 4. Formato SQL, codificación utf8mb4, activar "Enviar" y descargar.
-- -----------------------------------------------------------------------------


-- -----------------------------------------------------------------------------
-- MÉTODO C — Solo datos ACTIVOS (sin borrados lógicos), una tabla a la vez
-- -----------------------------------------------------------------------------
-- mysqldump permite --where por tabla; hay que ejecutar un volcado por tabla.
-- Ejemplo (repite cambiando tabla y ajusta la condición si tu columna deleted es distinta):
--
-- mysqldump -u root -p laboratorio dom_prianacategoria --where="(deleted=0 OR deleted IS NULL)"
--
-- Aplica condición similar a: dom_anacategoria, dom_secanacategoria, dom_priresultados,
-- dom_manuals, dom_perfil_examen, dom_labotest_reactivo_config (deleted/enabled según tu esquema).
-- dom_poblacion, dom_formulas, dom_opciones, dom_tipo_muestra, dom_metodo suelen llevar deleted o no;
-- revisa con DESCRIBE nombre_tabla;
-- -----------------------------------------------------------------------------


-- =============================================================================
-- IMPORTAR el archivo .sql generado (restaurar catálogo en otra BD o servidor)
-- =============================================================================
--
-- Antes de importar en una base que YA TIENE datos de labotests:
--   • Haz una copia de seguridad completa de esa base (no solo este catálogo).
--   • Si el dump trae CREATE TABLE + INSERT, puede chocar con datos existentes
--     (claves duplicadas). En ese caso o bien vacías esas tablas antes, o importas
--     en una base nueva y luego migras a mano.
--
-- Los dumps típicos de mysqldump incluyen al inicio/final algo como:
--   SET FOREIGN_KEY_CHECKS=0;  ...  SET FOREIGN_KEY_CHECKS=1;
-- así el orden de las tablas no rompe por FK durante la importación.
--
-- -----------------------------------------------------------------------------
-- IMPORTAR — línea de comandos (Windows, ejemplo WAMP)
-- -----------------------------------------------------------------------------
/*
cd C:\wamp64\bin\mysql\mysql8.4.0\bin
mysql.exe -u root -p --default-character-set=utf8mb4 laboratorio < C:\backup\labotests_catalogo.sql
*/
--
-- Si la base de destino es otra (ej. laboratorio_nuevo), créala antes en phpMyAdmin
-- o con: CREATE DATABASE laboratorio_nuevo CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- y sustituye el nombre en el comando:
--   mysql.exe -u root -p --default-character-set=utf8mb4 laboratorio_nuevo < C:\backup\labotests_catalogo.sql
--
-- Linux / macOS:
--   mysql -u root -p --default-character-set=utf8mb4 laboratorio < export_labotests_catalogo.sql
-- -----------------------------------------------------------------------------
--
-- IMPORTAR — phpMyAdmin
-- -----------------------------------------------------------------------------
-- 1. Selecciona la base de datos destino (la misma app debe apuntar a ella en .env / Database.php).
-- 2. Pestaña "Importar".
-- 3. Elige el archivo .sql (límite de tamaño según upload_max_filesize en PHP).
-- 4. Juego de caracteres: utf8mb4. Ejecutar.
-- 5. Si falla por tamaño, sube el límite en php.ini o usa la línea de comandos mysql.
-- -----------------------------------------------------------------------------
--
-- IMPORTAR — solo en BD vacía o de prueba
-- -----------------------------------------------------------------------------
-- Si tu objetivo es clonar el catálogo en un servidor limpio, lo habitual es:
--   1) Crear la base y ejecutar antes todos los scripts de esquema del proyecto
--      (migraciones / database/*.sql) para que existan las tablas.
--   2) Importar este dump de DATOS (si el dump es solo INSERT) o el dump completo
--      (estructura + datos) si lo generaste así desde mysqldump.
-- El dump del MÉTODO A suele incluir estructura y datos de las tablas listadas;
-- en un servidor nuevo necesitas que el resto de tablas de la app existan o la app fallará.
-- =============================================================================


-- =============================================================================
-- VERIFICACIÓN: conteos (ejecutar en la misma base antes o después del backup)
-- =============================================================================
SELECT 'dom_anacategoria' AS tabla,
       SUM(CASE WHEN deleted = 0 OR deleted IS NULL THEN 1 ELSE 0 END) AS filas_activas,
       COUNT(*) AS filas_total
FROM dom_anacategoria
UNION ALL
SELECT 'dom_prianacategoria',
       SUM(CASE WHEN deleted = 0 OR deleted IS NULL THEN 1 ELSE 0 END),
       COUNT(*)
FROM dom_prianacategoria
UNION ALL
SELECT 'dom_secanacategoria',
       SUM(CASE WHEN deleted = 0 OR deleted IS NULL THEN 1 ELSE 0 END),
       COUNT(*)
FROM dom_secanacategoria
UNION ALL
SELECT 'dom_priresultados',
       SUM(CASE WHEN deleted = 0 OR deleted IS NULL THEN 1 ELSE 0 END),
       COUNT(*)
FROM dom_priresultados
UNION ALL
SELECT 'dom_poblacion',
       SUM(CASE WHEN deleted = 0 OR deleted IS NULL THEN 1 ELSE 0 END),
       COUNT(*)
FROM dom_poblacion
UNION ALL
SELECT 'dom_formulas', COUNT(*), COUNT(*) FROM dom_formulas
UNION ALL
SELECT 'dom_opciones', COUNT(*), COUNT(*) FROM dom_opciones
UNION ALL
SELECT 'dom_manuals',
       SUM(CASE WHEN deleted = 0 OR deleted IS NULL THEN 1 ELSE 0 END),
       COUNT(*)
FROM dom_manuals
UNION ALL
SELECT 'dom_perfil_examen',
       SUM(CASE WHEN deleted = 0 OR deleted IS NULL THEN 1 ELSE 0 END),
       COUNT(*)
FROM dom_perfil_examen;

-- Si existen estas tablas, descomenta y ejecuta por separado (o quita las que fallen):
-- SELECT 'dom_tipo_muestra' AS tabla, SUM(CASE WHEN deleted = 0 OR deleted IS NULL THEN 1 ELSE 0 END), COUNT(*) FROM dom_tipo_muestra;
-- SELECT 'dom_metodo', SUM(CASE WHEN deleted = 0 OR deleted IS NULL THEN 1 ELSE 0 END), COUNT(*) FROM dom_metodo;
-- SELECT 'dom_labotest_reactivo_config', SUM(CASE WHEN deleted = 0 AND enabled = 1 THEN 1 ELSE 0 END), COUNT(*) FROM dom_labotest_reactivo_config;
