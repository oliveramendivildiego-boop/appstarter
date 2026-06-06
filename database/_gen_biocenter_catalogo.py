#!/usr/bin/env python3
"""Genera SQL de borrado e importación del catálogo desde precioscompletosubir.csv"""
import csv
from collections import OrderedDict
from pathlib import Path

CSV_PATH = Path(r"c:\Users\Diego\Downloads\laboratorio biocenter\precioscompletosubir.csv")
OUT_DELETE = Path(__file__).parent / "biocenter_borrar_catalogo_analisis.sql"
OUT_INSERT = Path(__file__).parent / "biocenter_importar_precioscompletosubir.sql"


def parse_price(s: str) -> int:
    s = (s or "0").strip()
    if not s:
        return 0
    if "," in s:
        s = s.replace(".", "").replace(",", ".")
    return int(round(float(s)))


def sql_escape(s: str) -> str:
    return s.replace("\\", "\\\\").replace("'", "''")


def load_rows():
    rows = []
    with CSV_PATH.open("r", encoding="utf-8-sig", newline="") as f:
        reader = csv.DictReader(f, delimiter=";")
        for r in reader:
            grupo = (r.get("GRUPO") or "").strip()
            prueba = (r.get("PRUEBA") or "").strip()
            if not prueba:
                continue
            if not grupo:
                # Vitamina C en el CSV viene sin grupo; va con el bloque Vitaminas
                grupo = "Vitaminas" if prueba.lower().startswith("vitamina") else "Otros"
            precio = parse_price(r.get("PRECIO", ""))
            precio_deriv = parse_price(r.get("PRECIO_DERIVADO", ""))
            rows.append((grupo, prueba, precio, precio_deriv))

    seen = OrderedDict()
    for row in rows:
        seen[(row[0], row[1])] = row
    return list(seen.values())


def write_delete_script():
    content = """-- =============================================================================
-- Biocenter: vaciar catálogo de análisis clínicos (grupos y pruebas)
-- =============================================================================
-- Base: laboratorio | Prefijo: dom_ (app/Config/Database.php)
--
-- ELIMINA todo el catálogo de labotests:
--   grupos (dom_anacategoria), pruebas (dom_prianacategoria), sub-clases,
--   referencias, manuales, perfiles y config reactivo↔análisis.
--
-- NO elimina: órdenes (dom_registro), resultados de pacientes (dom_regvalues),
--             poblaciones, fórmulas ni opciones de resultado.
--
-- IMPORTANTE: haga backup antes de ejecutar.
--   mysqldump -u root -p laboratorio dom_anacategoria dom_prianacategoria ... > backup.sql
--
-- Ejecutar:
--   mysql -u root -p laboratorio < database/biocenter_borrar_catalogo_analisis.sql
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

START TRANSACTION;

-- Comente la siguiente línea si no tiene la tabla dom_labotest_reactivo_config
DELETE FROM `dom_labotest_reactivo_config`;
DELETE FROM `dom_manuals`;
DELETE FROM `dom_perfil_examen`;
DELETE FROM `dom_secanacategoria`;
DELETE FROM `dom_priresultados`;
DELETE FROM `dom_prianacategoria`;
DELETE FROM `dom_anacategoria`;

ALTER TABLE `dom_anacategoria` AUTO_INCREMENT = 1;
ALTER TABLE `dom_prianacategoria` AUTO_INCREMENT = 1;
ALTER TABLE `dom_priresultados` AUTO_INCREMENT = 1;
ALTER TABLE `dom_secanacategoria` AUTO_INCREMENT = 1;
ALTER TABLE `dom_manuals` AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;

SELECT 'dom_anacategoria' AS tabla, COUNT(*) AS filas FROM `dom_anacategoria`
UNION ALL
SELECT 'dom_prianacategoria', COUNT(*) FROM `dom_prianacategoria`;
"""
    OUT_DELETE.write_text(content, encoding="utf-8")


def write_insert_script(rows):
    groups = OrderedDict()
    for g, _, _, _ in rows:
        if g not in groups:
            groups[g] = len(groups) + 1

    group_order = {}
    test_order = {}
    for g, p, _, _ in rows:
        if g not in test_order:
            test_order[g] = 0
        test_order[g] += 1

    lines = [
        """-- =============================================================================
-- Biocenter: importar catálogo desde precioscompletosubir.csv
-- =============================================================================
-- Fuente: precioscompletosubir.csv ({total_pruebas} pruebas, {total_grupos} grupos)
--
-- Ejecutar DESPUÉS de biocenter_borrar_catalogo_analisis.sql (o en BD vacía de catálogo).
--
-- Configuración por prueba:
--   • compleja = 0 (prueba simple)
--   • cost / cost_deriv según CSV
--   • Referencia por defecto: población "Todos" (@id_poblacion_todos)
--   • opcion_id = 3 (texto/cualitativo), sin min/max/unidad
--
-- Verifique la población "Todos" antes de ejecutar:
--   SELECT id_poblacion, name FROM dom_poblacion WHERE name LIKE '%Todos%';
-- Si su id difiere de 15, cambie @id_poblacion_todos abajo.
--
-- Ejecutar:
--   mysql -u root -p laboratorio < database/biocenter_importar_precioscompletosubir.sql
-- =============================================================================

SET NAMES utf8mb4;
SET @id_poblacion_todos := 15;

START TRANSACTION;

-- -----------------------------------------------------------------------------
-- 1) Grupos (dom_anacategoria)
-- -----------------------------------------------------------------------------
""".format(total_pruebas=len(rows), total_grupos=len(groups)),
    ]

    lines.append("INSERT INTO `dom_anacategoria` (`name`, `order`, `deleted`) VALUES")
    group_values = []
    for name, order in groups.items():
        group_values.append(f"  ('{sql_escape(name)}', {order}, 0)")
    lines.append(",\n".join(group_values) + ";\n")

    lines.append("""-- -----------------------------------------------------------------------------
-- 2) Pruebas (dom_prianacategoria)
-- -----------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS `tmp_biocenter_pruebas`;
CREATE TEMPORARY TABLE `tmp_biocenter_pruebas` (
  `grupo` VARCHAR(255) NOT NULL,
  `prueba` VARCHAR(255) NOT NULL,
  `orden` INT NOT NULL,
  `cost` INT NOT NULL,
  `cost_deriv` INT NOT NULL,
  PRIMARY KEY (`grupo`, `prueba`(191))
) ENGINE=Memory DEFAULT CHARSET=utf8mb4;

INSERT INTO `tmp_biocenter_pruebas` (`grupo`, `prueba`, `orden`, `cost`, `cost_deriv`) VALUES""")

    test_values = []
    counters = {}
    for g, p, cost, cost_deriv in rows:
        counters[g] = counters.get(g, 0) + 1
        orden = counters[g]
        test_values.append(
            f"  ('{sql_escape(g)}', '{sql_escape(p)}', {orden}, {cost}, {cost_deriv})"
        )

    lines.append(",\n".join(test_values) + ";\n")

    lines.append("""INSERT INTO `dom_prianacategoria` (`name`, `order`, `cost`, `cost_deriv`, `deleted`, `anacategoria_id`, `compleja`)
SELECT t.`prueba`, t.`orden`, t.`cost`, t.`cost_deriv`, 0, a.`anacategoria_id`, 0
FROM `tmp_biocenter_pruebas` t
INNER JOIN `dom_anacategoria` a ON a.`name` = t.`grupo`
WHERE (a.`deleted` = 0 OR a.`deleted` IS NULL);

DROP TEMPORARY TABLE IF EXISTS `tmp_biocenter_pruebas`;
""")

    lines.append("""-- -----------------------------------------------------------------------------
-- 3) Valores de referencia por defecto (dom_priresultados)
-- -----------------------------------------------------------------------------
INSERT INTO `dom_priresultados`
  (`prianacategoria_id`, `id_poblacion`, `valor_min`, `valor_max`, `umedida`, `formulas_id`, `opcion_id`, `deleted`, `sexo`)
SELECT p.`prianacategoria_id`, @id_poblacion_todos, '', '', '', 1, 3, 0, 'ambos'
FROM `dom_prianacategoria` p
WHERE (p.`deleted` = 0 OR p.`deleted` IS NULL)
  AND p.`compleja` = 0
  AND NOT EXISTS (
    SELECT 1 FROM `dom_priresultados` pr
    WHERE pr.`prianacategoria_id` = p.`prianacategoria_id`
      AND pr.`id_poblacion` = @id_poblacion_todos
      AND (pr.`deleted` = 0 OR pr.`deleted` IS NULL)
  );

-- Si la columna sexo no existe, comente la línea sexo arriba y use:
-- INSERT INTO `dom_priresultados` (..., `deleted`) SELECT ..., 0 FROM ...

-- -----------------------------------------------------------------------------
-- 4) Verificación
-- -----------------------------------------------------------------------------
SELECT a.`name` AS grupo, COUNT(p.`prianacategoria_id`) AS pruebas
FROM `dom_anacategoria` a
LEFT JOIN `dom_prianacategoria` p ON p.`anacategoria_id` = a.`anacategoria_id`
  AND (p.`deleted` = 0 OR p.`deleted` IS NULL)
WHERE (a.`deleted` = 0 OR a.`deleted` IS NULL)
GROUP BY a.`anacategoria_id`, a.`name`, a.`order`
ORDER BY a.`order`, a.`name`;

SELECT COUNT(*) AS total_pruebas FROM `dom_prianacategoria` WHERE `deleted` = 0;

COMMIT;
""")

    OUT_INSERT.write_text("\n".join(lines), encoding="utf-8")


def main():
    rows = load_rows()
    write_delete_script()
    write_insert_script(rows)
    print(f"Generado: {OUT_DELETE}")
    print(f"Generado: {OUT_INSERT}")
    print(f"Pruebas: {len(rows)}, Grupos: {len({r[0] for r in rows})}")


if __name__ == "__main__":
    main()
