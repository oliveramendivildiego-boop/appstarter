# -*- coding: utf-8 -*-
"""Genera actualizar_precios_quantum.sql desde el CSV de Quantum."""
import re
from pathlib import Path

CSV_PATH = Path(r"c:\Users\Diego\Downloads\LISTA DE PRECIOS PRUEBAS COMPARATIVOS  QUANTUM.csv")
OUT_PATH = Path(__file__).resolve().parent / "actualizar_precios_quantum.sql"


def esc_sql(s: str) -> str:
    return s.replace("\\", "\\\\").replace("'", "''")


def parse_precio(raw):
    if raw is None:
        return None
    s = str(raw).strip()
    if not s or s in ("-", "- ", " -", " - "):
        return None
    if s.startswith("#"):
        return None
    m = re.match(r"^(\d+(?:\.\d+)?)", s.replace(",", "."))
    if m:
        v = float(m.group(1))
        return int(v) if v == int(v) else round(v, 2)
    return None


def main():
    rows = []
    sin_precio_csv = []

    with open(CSV_PATH, "r", encoding="latin-1") as f:
        for line_no, line in enumerate(f, 1):
            line = line.rstrip("\n\r")
            if not line.strip():
                continue
            parts = line.split(";")
            if len(parts) < 3:
                continue
            codigo = parts[1].strip() if len(parts) > 1 else ""
            desc = parts[2].strip() if len(parts) > 2 else ""
            if line_no == 1 or desc.upper() == "DESCRIPCION":
                continue
            precio_raw = parts[5].strip() if len(parts) > 5 else ""
            if not desc or "Sub Grupo" in desc:
                continue
            precio = parse_precio(precio_raw)
            if precio is None:
                if desc:
                    sin_precio_csv.append((line_no, codigo, desc, precio_raw))
                continue
            rows.append((line_no, codigo, desc, precio, precio_raw))

    lines = [
        "-- Actualizacion de precios desde LISTA DE PRECIOS PRUEBAS COMPARATIVOS QUANTUM.csv",
        "-- Tabla: dom_prianacategoria | cost y cost_deriv con PRECIO QUANTUM",
        "-- Coincidencia: UPPER(TRIM(name)) = UPPER(TRIM(descripcion CSV))",
        "-- Ejecutar en MySQL/MariaDB. Revisar SELECT de no actualizados antes de COMMIT.",
        "",
        "SET NAMES utf8mb4;",
        "START TRANSACTION;",
        "",
        "DROP TEMPORARY TABLE IF EXISTS tmp_precios_quantum;",
        "CREATE TEMPORARY TABLE tmp_precios_quantum (",
        "  linea_csv INT NOT NULL,",
        "  codigo_csv VARCHAR(50) NOT NULL,",
        "  nombre_csv VARCHAR(255) NOT NULL,",
        "  precio_csv DECIMAL(12,2) NOT NULL,",
        "  precio_raw VARCHAR(50) NULL,",
        "  PRIMARY KEY (codigo_csv),",
        "  KEY idx_nombre (nombre_csv(191))",
        ");",
        "",
        "INSERT INTO tmp_precios_quantum (linea_csv, codigo_csv, nombre_csv, precio_csv, precio_raw) VALUES",
    ]

    vals = []
    for line_no, codigo, desc, precio, precio_raw in rows:
        p = int(precio) if isinstance(precio, float) and precio == int(precio) else precio
        vals.append(
            f"  ({line_no}, '{esc_sql(codigo)}', '{esc_sql(desc)}', {p}, '{esc_sql(precio_raw)}')"
        )

    chunk = 50
    for i in range(0, len(vals), chunk):
        block = vals[i : i + chunk]
        end = ";\n" if i + chunk >= len(vals) else ",\n"
        lines.append(",\n".join(block) + end)

    lines.extend(
        [
            "",
            "-- Nombres repetidos en CSV (collation ignora tildes: PROTEINAS = PROTEÍNAS)",
            "-- Ej: CELULAS LE en HEMT01=170 e INHR 43=120 -> se usa el MAYOR precio (170)",
            "-- MySQL no permite usar la misma tabla temporal 2 veces en un SELECT (#1137)",
            "DROP TEMPORARY TABLE IF EXISTS tmp_dedup_nombre;",
            "CREATE TEMPORARY TABLE tmp_dedup_nombre (",
            "  nombre_key VARCHAR(255) NOT NULL,",
            "  max_precio DECIMAL(12,2) NOT NULL,",
            "  PRIMARY KEY (nombre_key)",
            ");",
            "INSERT INTO tmp_dedup_nombre (nombre_key, max_precio)",
            "SELECT UPPER(TRIM(nombre_csv)), MAX(precio_csv)",
            "FROM tmp_precios_quantum",
            "GROUP BY UPPER(TRIM(nombre_csv));",
            "",
            "DROP TEMPORARY TABLE IF EXISTS tmp_precios_por_nombre;",
            "CREATE TEMPORARY TABLE tmp_precios_por_nombre (",
            "  linea_csv INT NOT NULL,",
            "  codigo_csv VARCHAR(50) NOT NULL,",
            "  nombre_csv VARCHAR(255) NOT NULL,",
            "  precio_csv DECIMAL(12,2) NOT NULL,",
            "  precio_raw VARCHAR(50) NULL,",
            "  PRIMARY KEY (codigo_csv)",
            ");",
            "DROP TEMPORARY TABLE IF EXISTS tmp_dedup_linea;",
            "CREATE TEMPORARY TABLE tmp_dedup_linea (",
            "  nombre_key VARCHAR(255) NOT NULL,",
            "  pick_linea INT NOT NULL,",
            "  PRIMARY KEY (nombre_key)",
            ");",
            "INSERT INTO tmp_dedup_linea (nombre_key, pick_linea)",
            "SELECT d.nombre_key, MIN(t.linea_csv)",
            "FROM tmp_precios_quantum t",
            "INNER JOIN tmp_dedup_nombre d",
            "  ON UPPER(TRIM(t.nombre_csv)) = d.nombre_key AND t.precio_csv = d.max_precio",
            "GROUP BY d.nombre_key;",
            "",
            "INSERT INTO tmp_precios_por_nombre",
            "SELECT t.linea_csv, t.codigo_csv, t.nombre_csv, t.precio_csv, t.precio_raw",
            "FROM tmp_precios_quantum t",
            "INNER JOIN tmp_dedup_linea dl",
            "  ON UPPER(TRIM(t.nombre_csv)) = dl.nombre_key AND t.linea_csv = dl.pick_linea;",
            "",
            "-- Filas del CSV descartadas por nombre duplicado (otro codigo, otro precio)",
            "SELECT",
            "  'NOMBRE_DUPLICADO_EN_CSV' AS motivo,",
            "  t.linea_csv, t.codigo_csv, t.nombre_csv, t.precio_csv, t.precio_raw",
            "FROM tmp_precios_quantum t",
            "LEFT JOIN tmp_precios_por_nombre u ON t.codigo_csv = u.codigo_csv",
            "WHERE u.codigo_csv IS NULL",
            "ORDER BY t.nombre_csv, t.linea_csv;",
            "",
            "-- Actualizar ambos costos donde el nombre coincide (activos)",
            "UPDATE dom_prianacategoria p",
            "INNER JOIN tmp_precios_por_nombre t",
            "  ON UPPER(TRIM(p.name)) = UPPER(TRIM(t.nombre_csv))",
            "SET p.cost = t.precio_csv, p.cost_deriv = t.precio_csv",
            "WHERE (p.deleted = 0 OR p.deleted IS NULL);",
            "",
            "-- ========== NO ACTUALIZADOS ==========",
            "",
            "-- 1) CSV con precio pero nombre no existe en sistema",
            "SELECT",
            "  'NO_ENCONTRADO_EN_SISTEMA' AS motivo,",
            "  t.linea_csv, t.codigo_csv, t.nombre_csv, t.precio_csv, t.precio_raw",
            "FROM tmp_precios_por_nombre t",
            "LEFT JOIN dom_prianacategoria p",
            "  ON UPPER(TRIM(p.name)) = UPPER(TRIM(t.nombre_csv))",
            "  AND (p.deleted = 0 OR p.deleted IS NULL)",
            "WHERE p.prianacategoria_id IS NULL",
            "ORDER BY t.nombre_csv;",
            "",
        ]
    )

    if sin_precio_csv:
        lines.extend(
            [
                "-- 2) CSV con descripcion pero sin precio valido en PRECIO QUANTUM",
                "DROP TEMPORARY TABLE IF EXISTS tmp_sin_precio_csv;",
                "CREATE TEMPORARY TABLE tmp_sin_precio_csv (",
                "  linea_csv INT, codigo_csv VARCHAR(50), nombre_csv VARCHAR(255), precio_raw VARCHAR(50)",
                ");",
                "INSERT INTO tmp_sin_precio_csv VALUES",
            ]
        )
        sp_vals = [
            f"  ({a}, '{esc_sql(b)}', '{esc_sql(c)}', '{esc_sql(d)}')"
            for a, b, c, d in sin_precio_csv
        ]
        lines.append(",\n".join(sp_vals) + ";")
        lines.append(
            "SELECT\n"
            "  'SIN_PRECIO_VALIDO_EN_CSV' AS motivo,\n"
            "  s.linea_csv, s.codigo_csv, s.nombre_csv, s.precio_raw\n"
            "FROM tmp_sin_precio_csv s\n"
            "ORDER BY s.nombre_csv;"
        )
    else:
        lines.append("-- 2) (ninguna fila del CSV sin precio valido)")

    lines.extend(
        [
            "",
            "-- 3) Cantidad actualizada",
            "SELECT COUNT(*) AS filas_actualizadas",
            "FROM tmp_precios_por_nombre t",
            "INNER JOIN dom_prianacategoria p ON UPPER(TRIM(p.name)) = UPPER(TRIM(t.nombre_csv))",
            "WHERE (p.deleted = 0 OR p.deleted IS NULL);",
            "",
            "-- 4) Nombres duplicados en sistema (varias filas con mismo name)",
            "SELECT TRIM(p.name) AS nombre, COUNT(*) AS cantidad,",
            "  GROUP_CONCAT(p.prianacategoria_id ORDER BY p.prianacategoria_id) AS ids",
            "FROM dom_prianacategoria p",
            "INNER JOIN tmp_precios_por_nombre t ON UPPER(TRIM(p.name)) = UPPER(TRIM(t.nombre_csv))",
            "WHERE (p.deleted = 0 OR p.deleted IS NULL)",
            "GROUP BY TRIM(p.name) HAVING COUNT(*) > 1;",
            "",
            "COMMIT;",
            "",
            "-- Si revisa y no confia: ejecutar ROLLBACK; en lugar de COMMIT;",
        ]
    )

    OUT_PATH.write_text("\n".join(lines), encoding="utf-8")
    print(f"OK: {OUT_PATH}")
    print(f"Con precio: {len(rows)} | Sin precio CSV: {len(sin_precio_csv)}")


if __name__ == "__main__":
    main()
