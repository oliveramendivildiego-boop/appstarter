# -*- coding: utf-8 -*-
"""Vista previa: cuántos nombres del CSV existen en dom_prianacategoria (requiere pymysql o mysql.connector)."""
import re
from pathlib import Path

CSV_PATH = Path(r"c:\Users\Diego\Downloads\LISTA DE PRECIOS PRUEBAS COMPARATIVOS  QUANTUM.csv")


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


def load_csv():
    rows = []
    with open(CSV_PATH, "r", encoding="latin-1") as f:
        for line_no, line in enumerate(f, 1):
            if line_no == 1:
                continue
            parts = line.rstrip("\n\r").split(";")
            if len(parts) < 6:
                continue
            desc = parts[2].strip()
            if not desc or "Sub Grupo" in desc or desc.upper() == "DESCRIPCION":
                continue
            precio = parse_precio(parts[5].strip())
            if precio is not None:
                rows.append((parts[1].strip(), desc, precio))
    return rows


def main():
    try:
        import pymysql
    except ImportError:
        print("Instale pymysql: pip install pymysql")
        return

    rows = load_csv()
    conn = pymysql.connect(
        host="localhost",
        user="root",
        password="",
        database="laboratorio",
        charset="utf8mb4",
    )
    names_db = set()
    with conn.cursor() as cur:
        cur.execute(
            "SELECT TRIM(name) FROM dom_prianacategoria WHERE deleted = 0 OR deleted IS NULL"
        )
        for (n,) in cur.fetchall():
            names_db.add(n)

    no_match = [r for r in rows if r[1] not in names_db]
    match = len(rows) - len(no_match)
    print(f"CSV con precio: {len(rows)}")
    print(f"Coinciden exacto (TRIM name): {match}")
    print(f"No encontrados: {len(no_match)}")
    if no_match:
        print("\n--- NO ENCONTRADOS ---")
        for cod, name, precio in sorted(no_match, key=lambda x: x[1])[:50]:
            print(f"  [{cod}] {name} -> {precio}")
        if len(no_match) > 50:
            print(f"  ... y {len(no_match) - 50} mas")
    conn.close()


if __name__ == "__main__":
    main()
