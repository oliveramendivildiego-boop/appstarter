-- dom_prianacategoria: cost y cost_deriv por prianacategoria_id (lote 124–220)
-- Ejecutar el archivo completo en phpMyAdmin o: mysql -u root -p laboratorio < database/actualizar_precios_por_id_lista_124_220.sql

SET NAMES utf8mb4;

UPDATE dom_prianacategoria
SET
  cost = CASE prianacategoria_id
    WHEN 124 THEN 180
    WHEN 207 THEN 155
    WHEN 213 THEN 178
    WHEN 214 THEN 178
    WHEN 215 THEN 170
    WHEN 216 THEN 170
    WHEN 125 THEN 178
    WHEN 126 THEN 178
    WHEN 127 THEN 178
    WHEN 218 THEN 144
    WHEN 219 THEN 214
    WHEN 220 THEN 178
    WHEN 128 THEN 178
    WHEN 129 THEN 178
    WHEN 130 THEN 300
    WHEN 131 THEN 232
    WHEN 132 THEN 232
    ELSE cost
  END,
  cost_deriv = CASE prianacategoria_id
    WHEN 124 THEN 180
    WHEN 207 THEN 155
    WHEN 213 THEN 178
    WHEN 214 THEN 178
    WHEN 215 THEN 170
    WHEN 216 THEN 170
    WHEN 125 THEN 178
    WHEN 126 THEN 178
    WHEN 127 THEN 178
    WHEN 218 THEN 144
    WHEN 219 THEN 214
    WHEN 220 THEN 178
    WHEN 128 THEN 178
    WHEN 129 THEN 178
    WHEN 130 THEN 300
    WHEN 131 THEN 232
    WHEN 132 THEN 232
    ELSE cost_deriv
  END
WHERE prianacategoria_id IN (
  124, 207, 213, 214, 215, 216, 125, 126, 127, 218, 219, 220,
  128, 129, 130, 131, 132
);

SELECT prianacategoria_id, name, cost, cost_deriv
FROM dom_prianacategoria
WHERE prianacategoria_id IN (
  124, 207, 213, 214, 215, 216, 125, 126, 127, 218, 219, 220,
  128, 129, 130, 131, 132
)
ORDER BY prianacategoria_id;
