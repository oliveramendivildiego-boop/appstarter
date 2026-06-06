#!/usr/bin/env python3
"""
Genera biocenter_importar_valores_referencia.sql desde el Excel de valores de referencia.
Empareja por nombre de grupo + prueba + análisis contra el catálogo de precioscompletosubir.csv.
"""
from __future__ import annotations

import csv
import re
import unicodedata
from collections import defaultdict
from pathlib import Path

import pandas as pd

EXCEL_PATH = Path(
    r"c:\Users\Diego\Downloads\laboratorio biocenter\valores_referencia_BIOCENTER_2026-04-21_13-22-10 (2).xlsx"
)
CATALOG_CSV = Path(
    r"c:\Users\Diego\Downloads\laboratorio biocenter\precioscompletosubir.csv"
)
OUT_SQL = Path(__file__).parent / "biocenter_importar_valores_referencia.sql"
OUT_REPORT = Path(__file__).parent / "biocenter_valores_referencia_sin_coincidencia.txt"

POB_DEFAULT = 15
POB_BY_TOKEN = {
    "RECIEN NACIDO": 6,
    "RECIEN NACIDOS": 6,
    "LACTANTE": 7,
    "NINO PEQUEÑO": 8,
    "NIÑO PEQUEÑO": 8,
    "NINO ESCOLAR": 9,
    "NIÑO ESCOLAR": 9,
    "ADOLESCENTE": 10,
    "ADULTO MAYOR": 12,
    "ADULTO": 11,
    "TODOS": POB_DEFAULT,
    "TODO": POB_DEFAULT,
    "PERRO": POB_DEFAULT,
    "PERROS": POB_DEFAULT,
    "GATO": POB_DEFAULT,
    "GATOS": POB_DEFAULT,
    "VARON": POB_DEFAULT,
    "MUJER": POB_DEFAULT,
}

CULTIVO_ANALISIS = {
    "CULTIVO",
    "MUESTRA",
    "DIAGNOSTICO BACTERIOLOGICO",
    "DIAGNOSTICO BACTERIOLÓGICO",
    "ANTIBIOGRAMA",
}

CULTIVO_PRUEBA_TOKENS = (
    "COPROCULTIVO",
    "UROCULTIVO",
    "HEMOCULTIVO",
    "ESPERMOCULTIVO",
    "CULTIVO",
    "ANTIBIOGRAMA",
    "RETROCULTIVO",
)

PRUEBA_ALIASES: dict[str, list[str]] = {
    "LEUCOGRAMA": ["HEMOGRAMA"],
    "PLAQUETARIO": ["HEMOGRAMA"],
    "RETICULOCITOS": ["RECUENTO DE RETICULOCITOS", "HEMOGRAMA"],
    "INDICES HEMATIMETRICOS": ["HEMOGRAMA", "INDICES HEMATIMETRICOS"],
    "INDICES HEMATOMETRICOS": ["HEMOGRAMA", "INDICES HEMATIMETRICOS"],
    "VELOCIDAD DE ERITROSEDIMENTACION": ["ERITROSEDIMENTACION", "HEMOGRAMA"],
    "VSG": ["ERITROSEDIMENTACION", "HEMOGRAMA"],
    "PLAQUETAS": ["HEMOGRAMA", "PLAQUETAS"],
    "PERFIL DE COAGULACION": ["COAGULOGRAMA"],
    "PERFIL DE COAGULACIÓN": ["COAGULOGRAMA"],
    "GRUPO Y FACTOR (RH)": ["GRUPO SANGUINEO"],
    "GRUPO Y FACTOR  (RH)": ["GRUPO SANGUINEO"],
    "GRUPO Y FACTOR RH": ["GRUPO SANGUINEO"],
    "PRUEBA DE TOLERANCIA ORAL A LA GLUCOSA (PTOG)": ["TOLERANCIA DE GLUCOSA"],
    "PRUEBA DE TOLERANCIA ORAL A LA GLUCOSA": ["TOLERANCIA DE GLUCOSA"],
    "TINCION DE GRAM": ["T. GRAM", "T. Gram"],
    "COPROCULTIVO": ["COPROCULTIVO"],
    "CULTIVO Y ANTIBIOGRAMA": ["CULTIVO Y ANTIBIOGRAMA (TODOS)", "CULTIVO Y ANTIBIOGRAMA"],
    "MONONUCLEOSIS": ["MONONUCLEOSIS (LATEX)"],
    "CELULAS LE": ["CELULAS LE", "Celulas LE"],
}

# Clave normalizada (sin espacios/puntuación) → claves de búsqueda en catálogo (ver convert_valores_biocenter_csv.php)
ANALISIS_KEY_ALIASES: dict[str, list[str]] = {
    "GLUCOSA": ["GLICEMIA"],
    "GLUCOSAPOSPRANDIAL": ["GLUC. POST. PRANDIAL"],
    "GLUCOSAPOSTPRANDIAL": ["GLUC. POST. PRANDIAL"],
    "GLUCOSAPOSESTIMULO1H": ["GLUC. POST. PRANDIAL", "GLICEMIA"],
    "GLUCOSAPOSESTIMULO2HRS": ["TOLERANCIA DE GLUCOSA", "GLICEMIA"],
    "GLUCOSABASEAL": ["TOLERANCIA DE GLUCOSA", "GLICEMIA"],
    "HEMOGLOBINAGLICOSILADA": ["HB. GLICOSILADA"],
    "HEMOGLOBINAGLICOSILADAHBA1C": ["HB. GLICOSILADA"],
    "HBA1C": ["HB. GLICOSILADA"],
    "NITROGENOUREICO": ["BUN", "NITROGENO UREICO"],
    "HDLCOLESTEROL": ["C-HDL"],
    "LDLCOLESTEROL": ["C-LDL"],
    "VLDLCOLESTEROL": ["VLDL"],
    "PROTEINASTOTALES": ["PROTEINAS"],
    "RELACIONAG": ["R A/G"],
    "BILIRRUBINATOTAL": ["BILIRRUBINA (D,I,T)"],
    "BILIRRUBINADIRECTA": ["BILIRRUBINA (D,I,T)"],
    "BILIRRUBINAINDIRECTA": ["BILIRRUBINA (D,I,T)"],
    "NILIRRUBINASINDIRECTAS": ["BILIRRUBINA (D,I,T)"],
    "NILIRRUBINAS": ["BILIRRUBINA (D,I,T)"],
    "REIESGOCARDIACO": ["RIESGO CARDIACO"],
    "RIESGOCARDIACO": ["RIESGO CARDIACO"],
    "PROPONINAT": ["Troponina T", "TROPONINA T"],
    "PROPONINAI": ["Troponina I", "TROPONINA I"],
    "MAGNECIO": ["MAGNESIO"],
    "CALCIOTOTAL": ["CALCIO"],
    "CALCIOIONICO": ["CALCIO"],
    "CALCIOIONICOCAI": ["CALCIO"],
    "CPK": ["CK"],
    "CKMB": ["CK-MB"],
    "NTPROBNP": ["NT-Pro BNP"],
    "AMONIOSERICO": ["AMONIO"],
    "FOSFATASAALCALINA": ["F. ALCALINA"],
    "FALCALINA": ["F. ALCALINA"],
    "GOTAST": ["GOT/AST"],
    "GPTALT": ["GPT/ALT"],
    "GPT": ["GPT/ALT"],
    "CLOROCL": ["CLORO", "CLORO ( CL )"],
    "CLORUROS": ["CLORO"],
    "FOSFOROINORGANICO": ["FOSFORO"],
    "HORMONASDECRECIMIENTOHGH": ["H. DE CRECIMIENTO"],
    "HGH": ["H. DE CRECIMIENTO"],
    "INSULINAPOSTPRANDIAL120MIN": ["INSULINA POST. ESTIMULO 120 MIN"],
    "INSULINAPOSTPRANDIAL": ["INSULINA BASAL POST PRANDIAL 120 MIN"],
    "ANDROSTENEDIONA": ["ANDROSTEDIONA"],
    "PSALIBRE": ["PSA LIBRE"],
    "PSATOTAL": ["PSA TOTAL"],
    "PSAPORCENTAJE": ["PSA LIBRE"],
    "ALFAFETOPROTEINA": ["ALFA FETO PROTEINA"],
    "AFP": ["ALFA FETO PROTEINA"],
    "AFPEMBARAZADA": ["ALFA FETO PROTEINA (EMBARAZADA)"],
    "CA125": ["CA 125"],
    "CA199": ["CA 19-9"],
    "CA153": ["CA 15-3"],
    "MICROGLOBULINA": ["BETA 2-MICROGLOBULINA"],
    "B2MICROGLOBULINA": ["BETA 2-MICROGLOBULINA"],
    "ADA": ["ADA LIQ. PLEURAL", "ADA LIQ. ASCITICO", "ADA LIQ. PERICARDICO", "ADA LIQ. SINOVIAL", "ADA LIQ: CEFALORAQUIDEO"],
    "ADALIQUIDOPLEURAL": ["ADA LIQ. PLEURAL"],
    "ADALIQUIDOASCITICO": ["ADA LIQ. ASCITICO"],
    "ADALIQUIDOPERITONAL": ["ADA LIQ. PERICARDICO"],
    "ADALCR": ["ADA LIQ: CEFALORAQUIDEO"],
    "ADALIQUIDOSINOVIAL": ["ADA LIQ. SINOVIAL"],
    "MONONUCLEOSIS": ["MONONUCLEOSIS (LATEX)"],
    "CHAGAS": ["CHAGAS ELISA"],
    "VIH": ["HIV 1 - 2"],
    "HEPATITISC": ["HVC HEPATITIS C"],
    "HEPATITISB": ["AGSHB", "HBSAG IMUNOCROMATOGRAFIA"],
    "T3LIBRE": ["T3 LIBRE", "T3 libre"],
    "T4LIBRE": ["T4 LIBRE", "T4 Libre"],
    "VITAMINAD": ["VITAMINA D"],
    "VITAMINAB12": ["VITAMINA B12"],
    "VITAMINAB9": ["VITAMINA B9"],
    "ACIDOFOLICO": ["VITAMINA B9"],
    "DIMEROD": ["DIMERO D"],
    "DIMERD": ["DIMERO D"],
    "TIEMPODEPROTROMBINA": ["TIEMPO DE PROTROMBINA -INR"],
    "INR": ["TIEMPO DE PROTROMBINA -INR"],
    "FAN": ["ANA", "ANA IFI"],
    "CRIOGLOBULINAS": ["Crioglubulinas"],
    "INDICAN": ["INDICAN"],
    "PROCALCITONINA": ["PROCALCITONINA"],
    "HOMOCISTEINA": ["HOMOCISTEINA"],
    "MIOGLOBINA": ["MIOGLOBINA"],
    "PARCIALDEORINA": ["PARCIAL DE ORINA"],
    "EXAMENGENERALDEORINA": ["PARCIAL DE ORINA"],
    "CLEARENCEDECREATININA": ["CLEARENCE DE CREATININA"],
    "PROTEINURIA24HR": ["PROTEINURIA 24HR"],
    "PROTEINURIAORINACASUAL": ["PROTEINURIA ORINA CASUAL"],
    "RELACIONALBUMINACREATININA": ["RELACION ALBUMINA/ CREATININA"],
    "RELACIONPROTEINACREATININA": ["RELACION DE PROTEINA/CREATININA"],
    "DHEASULFATO": ["DHEA SULFATO"],
    "DHEA": ["DHEA SULFATO"],
    "PARTHORMONA": ["PARATOHORMONA"],
    "PARATOHORMONA": ["PARATOHORMONA"],
    "HERPES1IGM": ["HERPES 1 IG M"],
    "HERPES1IGG": ["HERPES 1 IG G"],
    "HERPES2IGM": ["HERPES LL (2) IGM"],
    "HERPES2IGG": ["HERPES LL(2) IGG"],
    "CHLAMYDIAIGM": ["CLAMIDYA IG M"],
    "CHLAMYDIAIGG": ["CLAMIDYA IG G"],
    "HEPATITISAIGG": ["HAV IGG"],
    "HEPATITISAIM": ["HAV IGM"],
    "TOXOIGM": ["TOXOS. IG M"],
    "TOXOIGG": ["TOXOS. IG G"],
    "HPIGM": ["H. PILORY IGM"],
    "HPIGA": ["H. PILORY IGA"],
    "HPIGG": ["H. PILORY IGG"],
    "CMVIGM": ["CITOMEGALOVIRUS IGM ELISA"],
    "CMVIGG": ["CITOMEGALOVIRUS IGG ELISA"],
    "ANTIMGB": ["ANTI MGB"],
    "LKM1": ["ANTI LKM - 1"],
    "LKM2": ["ANTI LKM - 1"],
    "CHIKUNGUNYAIGM": ["CHIKUNGUÑA"],
    "CHIKUNGUNYAIGG": ["CHIKUNGUÑA"],
    "DENGUEIGM": ["DENGUE"],
    "DENGUEIGG": ["DENGUE"],
    "DENGUEAG": ["DENGUE"],
    "BHCG": ["B-HCG CUANTITATIVA", "HCG"],
    "BHCGORINA": ["HCG ORINA", "HCG"],
    "PSALIBRET": ["PSA LIBRE", "PSA TOTAL"],
}

CAT_ALIASES: dict[str, list[str]] = {
    "HEMATOLOGIA": ["HEMATOLOGIA"],
    "BACTERIOLOGIA": ["BACTEROLOGIA"],
    "QUIMICA SANGUINEA": ["QUIMICA SANGUINEA", "QUÍMICA CLÍNICA", "QUIMICA CLINICA"],
    "INMUNOLOGIA": [
        "ELISAS",
        "ELISAS INMUNOLOGIA",
        "ELISA HEPATITIS",
        "INMUNOLOGIA",
        "INMUNOGLOBULINAS",
        "HORMONAS",
        "ENDOCRINOLOGIA",
        "ENDOCRINOLOGÍA",
        "PERFIL TIROIDEO",
        "MARCADORES TUMORALES",
        "SERIOLOGIA",
        "SERIOLOGIA HEPATITIS",
        "SERIOLOGIA VIRAL",
    ],
    "UROANALISIS": ["ORINA", "UROANALISIS"],
    "COPROANALISIS": ["HECES", "COPROLOGIA", "PARASITOLOGIA", "PARASITOLOGÍA"],
    "COAGULOGRAMA": ["COAGULOGRAMA", "COAGULACION", "COAGULACIÓN"],
    "ELECTROLITOS EN ORINA": ["ORINA"],
    "GOTA GRUESA": ["HEMATOLOGIA"],
    "GRUPO SANGUINEO": ["HEMATOLOGIA"],
    "COOMBS DIRECTO": ["HEMATOLOGIA"],
    "COOMBS INDIRECTO": ["HEMATOLOGIA"],
    "HEPATITIS AUTOINMUNE": ["HEPATITIS AUTOINMUNE", "GASTROENTEROLOGÍA / AUTOINMUNIDAD", "PRUEBAS GASTRICAS"],
}


def strip_accents(text: str) -> str:
    text = unicodedata.normalize("NFKD", text)
    return "".join(c for c in text if not unicodedata.combining(c))


def norm(text: str) -> str:
    s = strip_accents(str(text or "").strip())
    s = re.sub(r"^\*+|\*+$", "", s)
    s = re.sub(r"\s+", " ", s)
    return s.upper()


def norm_key(text: str) -> str:
    s = norm(text)
    s = re.sub(r"\([^)]*\)", " ", s)
    s = re.sub(r"[^A-Z0-9]", "", s)
    return s


def sql_escape(text: str) -> str:
    return str(text or "").replace("\\", "\\\\").replace("'", "''")


def collapse_spaces(text: str) -> str:
    return re.sub(r"\s+", " ", str(text or "").strip())


def sanitize_unidad(value) -> str:
    s = collapse_spaces(str(value or ""))
    return "" if s.lower() in {"nan", "none"} else s


def norm_valor(value) -> str:
    if value is None or (isinstance(value, float) and pd.isna(value)):
        return ""
    s = str(value).strip()
    if s.lower() in ("nan", "none"):
        return ""
    if "," in s:
        s = s.replace(".", "").replace(",", ".")
    elif re.match(r"^\d{1,3}\.\d{3}$", s):
        s = s.replace(".", "")
    try:
        f = float(s)
        if abs(f - round(f)) < 1e-5:
            return str(int(round(f)))
        return str(f).rstrip("0").rstrip(".")
    except ValueError:
        return collapse_spaces(s)


def norm_sexo(raw: str) -> str:
    k = norm(raw)
    if k in {"M", "MASCULINO", "HOMBRE", "VARON"}:
        return "masculino"
    if k in {"F", "FEMENINO", "MUJER", "FEMENINA"}:
        return "femenino"
    return "ambos"


def resolve_poblacion(name: str) -> int:
    n = norm(name)
    if not n:
        return POB_DEFAULT
    if n in {"VARON", "MUJER", "PERRO", "PERROS", "GATO", "GATOS"}:
        return POB_DEFAULT
    for token, pid in POB_BY_TOKEN.items():
        if token in n:
            return pid
    return POB_DEFAULT


def refine_sexo(poblacion: str, sexo: str) -> str:
    p = norm(poblacion)
    if sexo != "ambos":
        return sexo
    if "VARON" in p or p == "M":
        return "masculino"
    if "MUJER" in p or p == "F":
        return "femenino"
    return sexo


def map_opcion_id(tipo_dato: str) -> int:
    k = norm(tipo_dato)
    if k.startswith("NUMERIC"):
        return 3
    if "ELECC" in k or "PARRAF" in k:
        return 3
    return 3


def load_catalog() -> tuple[dict[tuple[str, str], tuple[str, str]], dict[str, list[tuple[str, str]]]]:
    catalog: dict[tuple[str, str], tuple[str, str]] = {}
    global_by_key: dict[str, list[tuple[str, str]]] = defaultdict(list)
    seen_global: set[tuple[str, str]] = set()
    with CATALOG_CSV.open("r", encoding="utf-8-sig", newline="") as f:
        reader = csv.DictReader(f, delimiter=";")
        for row in reader:
            grupo = (row.get("GRUPO") or "").strip()
            prueba = (row.get("PRUEBA") or "").strip()
            if not grupo:
                grupo = "Otros"
            if not prueba:
                continue
            catalog[(norm(grupo), norm(prueba))] = (grupo, prueba)
            catalog[(norm_key(grupo), norm_key(prueba))] = (grupo, prueba)
            gkey = (grupo, prueba)
            if gkey not in seen_global:
                seen_global.add(gkey)
                global_by_key[norm_key(prueba)].append((grupo, prueba))
                global_by_key[norm(prueba)].append((grupo, prueba))
    return catalog, global_by_key


def cat_candidates(cat: str, prueba: str) -> list[str]:
    c = norm(cat)
    c = re.sub(r"^\*+|\*+$", "", c)
    out = [c]
    if c in CAT_ALIASES:
        out.extend(CAT_ALIASES[c])
    p = norm(prueba)
    if "PERFIL LIPIDICO" in p or "QUIMICA" in c:
        out.append("PERFIL LIPIDICO")
        out.append("QUIMICA SANGUINEA")
    if "PERFIL HEPATICO" in p or "BILIRRUBINA" in p or "GOT" in p or "GPT" in p:
        out.append("PERFIL HEPATICO")
        out.append("QUIMICA SANGUINEA")
    if "PERFIL RENAL" in p or norm_key(prueba) in {"UREA", "CREATININA", "ACIDOURICO"}:
        out.append("QUIMICA SANGUINEA")
    if "PERFIL METABOLICO" in p or "GLUCOSA" in p or "GLICEMIA" in p:
        out.append("QUIMICA SANGUINEA")
    if "PERFIL PROTEICO" in p:
        out.append("QUIMICA SANGUINEA")
    if "PERFIL PANCREATICO" in p:
        out.append("QUIMICA SANGUINEA")
    if "COAGUL" in p or c == "COAGULOGRAMA":
        out.append("COAGULOGRAMA")
    if "ELECTROLITO" in p:
        out.append("ELECTROLITOS")
    if "TIROIDEO" in p:
        out.append("PERFIL TIROIDEO")
    if "HIERRO" in p:
        out.append("PERFIL DE HIERRO")
    if c in {"GOTA GRUESA", "GRUPO SANGUINEO", "COOMBS DIRECTO", "COOMBS INDIRECTO"}:
        out.append("HEMATOLOGIA")
    if p in {"GRUPO SANGUINEO", "COOMBS DIRECTO", "COOMBS INDIRECTO", "GOTA GRUESA", "CELULAS LE"}:
        out.append("HEMATOLOGIA")
    return list(dict.fromkeys(out))


def prueba_candidates(prueba: str, analisis: str) -> list[str]:
    p = norm(prueba)
    out = [p]
    if p in PRUEBA_ALIASES:
        out = PRUEBA_ALIASES[p] + out
    return list(dict.fromkeys(out))


def analisis_candidates(analisis: str) -> list[str]:
    a = norm(analisis)
    ak = norm_key(analisis)
    out = [a, ak, analisis.strip()]
    if ak in ANALISIS_KEY_ALIASES:
        out.extend(ANALISIS_KEY_ALIASES[ak])
    for key, aliases in ANALISIS_KEY_ALIASES.items():
        if key in ak or ak in key:
            out.extend(aliases)
    return list(dict.fromkeys(x for x in out if x))


def lookup_profile_parent(
    catalog: dict,
    cat: str,
    prueba: str,
) -> tuple[str, str, str] | None:
    """Perfiles del Excel que en catálogo son una sola prueba compuesta."""
    c = norm(cat)
    p = norm(prueba)
    mapping: list[tuple[str, str, str, str]] = [
        ("UROANALISIS", "EXAMEN", "ORINA", "PARCIAL DE ORINA"),
        ("COPROANALISIS", "EXAMEN", "HECES", "COPROPARASITOLOGICO"),
        ("COPROLOGICO SERIADO", "EXAMEN", "HECES", "SERIADO X 3"),
        ("COPROLOGICO SERIADO", "OTROS ELEMENTOS", "HECES", "SERIADO X 3"),
        ("CUAGULOGRAMA", "PERFIL DE COAGUL", "COAGULOGRAMA", "COAGULOGRAMA"),
        ("INMUNOLOGIA", "PERFIL ENA", "ELISAS INMUNOLOGIA", "ENA PROFILE"),
        ("BACTERIOLOGIA", "TINCION DE GRAM", "Bactereologia", "T. Gram"),
        ("BACTERIOLOGIA", "COPROCULTIVO", "Bactereologia", "Coprocultivo"),
        ("BACTERIOLOGIA", "CULTIVO Y ANTIBIOGRAMA", "Bactereologia", "cultivo y antibiograma (todos)"),
        ("UROANALISIS", "DEPURACION DE CREATININA", "ORINA", "CLEARENCE DE CREATININA"),
        ("UROANALISIS", "RELACION ALBUMINA", "NEFROLOGÍA", "RELACION ALBUMINA/ CREATININA"),
        ("UROANALISIS", "RELACION DE PROTEINA", "NEFROLOGÍA", "RELACION DE PROTEINA/CREATININA"),
        ("UROANALISIS", "PROTEINURIA DE 24", "ORINA", "PROTEINURIA 24HR"),
        ("UROANALISIS", "PROTEINURIA", "ORINA", "PROTEINURIA ORINA CASUAL"),
    ]
    for cat_token, pru_token, grupo_n, prueba_n in mapping:
        if cat_token in c and pru_token in p:
            hit = catalog.get((norm(grupo_n), norm(prueba_n))) or catalog.get(
                (norm_key(grupo_n), norm_key(prueba_n))
            )
            if hit:
                return hit[0], hit[1], "compuesto"
            # buscar prueba por nombre parcial en catálogo
            for (gk, pk), val in catalog.items():
                if isinstance(gk, str) and gk == norm(grupo_n) and prueba_n in pk:
                    return val[0], val[1], "compuesto"
    return None


def fuzzy_elisa_match(
    global_by_key: dict[str, list[tuple[str, str]]],
    analisis: str,
) -> tuple[str, str] | None:
    ak = norm_key(analisis)
    tokens = {
        "IGG": "IG G",
        "IGM": "IG M",
        "IGA": "IG A",
        "HEPATITIS": "HEPATITIS",
        "HERPES": "HERPES",
        "TOXO": "TOXOS",
        "CHLAM": "CLAMIDYA",
        "CMV": "CITOMEGALOVIRUS",
        "HELICOBACTER": "H. PILORY",
        "HPYLORI": "H. PILORY",
    }
    for tok, needle in tokens.items():
        if tok in ak:
            for key, items in global_by_key.items():
                if needle.replace(" ", "") in key.replace(" ", "") and any(
                    x in key for x in ["IGG", "IGM", "IGA", "IG G", "IG M", "IG A"]
                ):
                    for item in items:
                        if norm(item[0]) in {"ELISAS", "ELISA HEPATITIS", "ELISAS INMUNOLOGIA", "SERIOLOGIA"}:
                            return item
    return None


def lookup_global(
    global_by_key: dict[str, list[tuple[str, str]]],
    analisis: str,
    preferred_cats: list[str],
) -> tuple[str, str] | None:
    preferred = {norm(c) for c in preferred_cats}
    hits: list[tuple[str, str]] = []
    for cand in analisis_candidates(analisis):
        for key in {cand, norm(cand), norm_key(cand)}:
            hits.extend(global_by_key.get(key, []))
    if not hits:
        return None
    uniq: list[tuple[str, str]] = []
    seen: set[tuple[str, str]] = set()
    for h in hits:
        if h not in seen:
            seen.add(h)
            uniq.append(h)
    for h in uniq:
        if norm(h[0]) in preferred:
            return h
    if len(uniq) == 1:
        return uniq[0]
    for h in uniq:
        if norm(h[0]) in {"HORMONAS", "ELISAS", "QUIMICA SANGUINEA", "HEMATOLOGIA", "ORINA"}:
            return h
    return uniq[0]


def lookup_catalog(
    catalog: dict[tuple[str, str], tuple[str, str]], cat: str, prueba: str
) -> tuple[str, str] | None:
    for c in cat_candidates(cat, prueba):
        for p in prueba_candidates(prueba, ""):
            hit = catalog.get((c, p)) or catalog.get((norm_key(c), norm_key(p)))
            if hit:
                return hit
    return None


def is_cultivo(prueba: str, analisis: str) -> bool:
    p = norm(prueba)
    a = norm(analisis)
    if a in CULTIVO_ANALISIS:
        return True
    return any(tok in p for tok in CULTIVO_PRUEBA_TOKENS)


def load_excel_rows() -> list[dict]:
    df = pd.read_excel(EXCEL_PATH, sheet_name=0, header=0)
    df.columns = [
        "categoria",
        "prueba",
        "analisis",
        "tipo_dato",
        "tipo_muestra",
        "poblacion",
        "col6",
        "sexo",
        "valor_min",
        "valor_max",
        "unidad",
        "col11",
    ]
    rows = []
    for _, r in df.iterrows():
        prueba = str(r["prueba"] or "").strip()
        if not prueba:
            continue
        analisis = str(r["analisis"] or "").strip() or prueba
        rows.append(
            {
                "categoria": str(r["categoria"] or "").strip(),
                "prueba": prueba,
                "analisis": analisis,
                "tipo_dato": str(r["tipo_dato"] or "").strip(),
                "poblacion": str(r["poblacion"] or "").strip(),
                "sexo": refine_sexo(
                    str(r["poblacion"] or ""),
                    norm_sexo(str(r["sexo"] or "Ambos")),
                ),
                "valor_min": norm_valor(r["valor_min"]),
                "valor_max": norm_valor(r["valor_max"]),
                "unidad": sanitize_unidad(r["unidad"]),
                "opcion_id": map_opcion_id(str(r["tipo_dato"] or "")),
            }
        )
    return rows


def match_rows(
    catalog: dict,
    global_by_key: dict[str, list[tuple[str, str]]],
    excel_rows: list[dict],
) -> tuple[list[dict], list[str]]:
    # cuántos análisis distintos tiene cada (cat, prueba) en el Excel
    group_analisis: dict[tuple[str, str], set[str]] = defaultdict(set)
    for row in excel_rows:
        group_analisis[(norm(row["categoria"]), norm(row["prueba"]))].add(norm(row["analisis"]))

    matched: list[dict] = []
    unmatched: list[str] = []

    for row in excel_rows:
        cat = row["categoria"]
        prueba = row["prueba"]
        analisis = row["analisis"]
        prueba_n = norm(prueba)
        analisis_n = norm(analisis)
        group_key = (norm(cat), prueba_n)
        multi_analisis = len(group_analisis[group_key]) > 1 or prueba_n != analisis_n

        hit = lookup_catalog(catalog, cat, prueba)
        modo = None
        grupo = prueba_cat = sec_nombre = None

        profile = lookup_profile_parent(catalog, cat, prueba)
        if profile and multi_analisis:
            grupo, prueba_cat, modo = profile[0], profile[1], profile[2]
            sec_nombre = analisis
            if is_cultivo(prueba, analisis):
                modo = "cultivo"
        elif hit:
            grupo, prueba_cat = hit
            if is_cultivo(prueba, analisis):
                modo = "cultivo"
                sec_nombre = analisis
            elif multi_analisis:
                modo = "compuesto"
                sec_nombre = analisis
            else:
                modo = "simple"
                sec_nombre = prueba_cat
        else:
            cats = cat_candidates(cat, prueba)
            for c in cats:
                for a in analisis_candidates(analisis):
                    alt = catalog.get((c, norm(a))) or catalog.get((norm_key(c), norm_key(a)))
                    if alt:
                        grupo, prueba_cat = alt
                        modo = "simple"
                        sec_nombre = prueba_cat
                        break
                if modo:
                    break
            if not modo:
                alt = lookup_global(global_by_key, analisis, cats)
                if alt:
                    grupo, prueba_cat = alt
                    modo = "simple"
                    sec_nombre = prueba_cat
            if not modo:
                alt = fuzzy_elisa_match(global_by_key, analisis)
                if alt:
                    grupo, prueba_cat = alt
                    modo = "simple"
                    sec_nombre = prueba_cat

        if not modo:
            unmatched.append(f"{cat} | {prueba} | {analisis} | {row['poblacion']} | {row['sexo']}")
            continue

        matched.append(
            {
                **row,
                "grupo": grupo,
                "prueba_cat": prueba_cat,
                "sec_nombre": sec_nombre if modo in {"compuesto", "cultivo"} else prueba_cat,
                "modo": modo,
                "poblacion_id": resolve_poblacion(row["poblacion"]),
            }
        )

    return matched, unmatched


def dedupe_matched(rows: list[dict]) -> list[dict]:
    seen = {}
    for row in rows:
        key = (
            row["modo"],
            row["grupo"],
            row["prueba_cat"],
            row.get("sec_nombre", ""),
            row["poblacion_id"],
            row["sexo"],
        )
        seen[key] = row
    return list(seen.values())


def write_sql(matched: list[dict], unmatched: list[str]) -> None:
    matched = dedupe_matched(matched)
    simple_count = sum(1 for r in matched if r["modo"] == "simple")
    comp_count = sum(1 for r in matched if r["modo"] == "compuesto")
    cult_count = sum(1 for r in matched if r["modo"] == "cultivo")

    lines = [
        """-- =============================================================================
-- Biocenter: importar valores de referencia desde Excel
-- =============================================================================
-- Fuente: valores_referencia_BIOCENTER_2026-04-21_13-22-10 (2).xlsx
-- Catálogo destino: precioscompletosubir.csv (grupos/pruebas biocenter_importar_precioscompletosubir.sql)
--
-- Ejecutar DESPUÉS de:
--   1) biocenter_borrar_catalogo_analisis.sql
--   2) biocenter_importar_precioscompletosubir.sql
--
-- Filas emparejadas: {total} (simple: {simple}, compuesto: {comp}, cultivo: {cult})
-- Sin coincidencia: {unmatched} (ver biocenter_valores_referencia_sin_coincidencia.txt)
--
-- Población por defecto si no hay banda etaria: 15 (Todos)
-- Bandas etarias usadas: 6-12 (requiere database/insert_poblaciones_edad.sql)
--
-- Requisito opcional: columna sexo en dom_priresultados y dom_secanacategoria
--   database/migration_priresultados_sexo.sql
-- Si no existe sexo, comente esa columna en los INSERT/UPDATE de abajo.
-- =============================================================================

SET NAMES utf8mb4;

-- dom_priresultados usa varchar(11) en instalaciones antiguas; el Excel trae textos
-- largos (NEGATIVO/POSITIVO, listas de opciones, etc.). Ampliar antes de importar.
ALTER TABLE `dom_priresultados`
  MODIFY COLUMN `valor_min` TEXT NOT NULL,
  MODIFY COLUMN `valor_max` TEXT NOT NULL;

ALTER TABLE `dom_secanacategoria`
  MODIFY COLUMN `valor_min` TEXT NOT NULL,
  MODIFY COLUMN `valor_max` TEXT NOT NULL;

START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS `tmp_biocenter_vref`;
CREATE TEMPORARY TABLE `tmp_biocenter_vref` (
  `modo` ENUM('simple','compuesto','cultivo') NOT NULL,
  `grupo` VARCHAR(255) NOT NULL,
  `prueba` VARCHAR(255) NOT NULL,
  `sec_nombre` VARCHAR(255) NOT NULL,
  `poblacion_id` INT NOT NULL,
  `sexo` VARCHAR(20) NOT NULL,
  `valor_min` VARCHAR(255) NOT NULL,
  `valor_max` VARCHAR(255) NOT NULL,
  `unidad` VARCHAR(255) NOT NULL,
  `opcion_id` INT NOT NULL,
  KEY `idx_simple` (`modo`,`grupo`(100),`prueba`(100),`poblacion_id`,`sexo`),
  KEY `idx_sec` (`modo`,`grupo`(100),`prueba`(100),`sec_nombre`(100),`poblacion_id`,`sexo`)
) ENGINE=Memory DEFAULT CHARSET=utf8mb4;

INSERT INTO `tmp_biocenter_vref`
  (`modo`, `grupo`, `prueba`, `sec_nombre`, `poblacion_id`, `sexo`, `valor_min`, `valor_max`, `unidad`, `opcion_id`)
VALUES
""".format(
            total=len(matched),
            simple=simple_count,
            comp=comp_count,
            cult=cult_count,
            unmatched=len(unmatched),
        )
    ]

    values = []
    for r in matched:
        values.append(
            "  ('{modo}', '{grupo}', '{prueba}', '{sec}', {pob}, '{sexo}', '{vmin}', '{vmax}', '{uni}', {opc})".format(
                modo=r["modo"],
                grupo=sql_escape(r["grupo"]),
                prueba=sql_escape(r["prueba_cat"]),
                sec=sql_escape(r["sec_nombre"]),
                pob=int(r["poblacion_id"]),
                sexo=sql_escape(r["sexo"]),
                vmin=sql_escape(r["valor_min"]),
                vmax=sql_escape(r["valor_max"]),
                uni=sql_escape(r["unidad"]),
                opc=int(r["opcion_id"]),
            )
        )
    lines.append(",\n".join(values) + ";\n")

    lines.append(
        """-- -----------------------------------------------------------------------------
-- 1) Marcar pruebas compuestas / cultivo
-- -----------------------------------------------------------------------------
UPDATE `dom_prianacategoria` p
INNER JOIN `dom_anacategoria` a ON a.`anacategoria_id` = p.`anacategoria_id`
INNER JOIN (
  SELECT DISTINCT t.`grupo`, t.`prueba`, MAX(CASE t.`modo` WHEN 'cultivo' THEN 2 ELSE 1 END) AS compleja_obj
  FROM `tmp_biocenter_vref` t
  WHERE t.`modo` IN ('compuesto', 'cultivo')
  GROUP BY t.`grupo`, t.`prueba`
) x ON x.`grupo` = a.`name` AND x.`prueba` = p.`name`
SET p.`compleja` = x.`compleja_obj`
WHERE (p.`deleted` = 0 OR p.`deleted` IS NULL)
  AND (a.`deleted` = 0 OR a.`deleted` IS NULL);

-- Quitar referencias simples placeholder de pruebas que pasan a compuestas/cultivo
DELETE pr
FROM `dom_priresultados` pr
INNER JOIN `dom_prianacategoria` p ON p.`prianacategoria_id` = pr.`prianacategoria_id`
INNER JOIN `dom_anacategoria` a ON a.`anacategoria_id` = p.`anacategoria_id`
INNER JOIN (
  SELECT DISTINCT t.`grupo`, t.`prueba`
  FROM `tmp_biocenter_vref` t
  WHERE t.`modo` IN ('compuesto', 'cultivo')
) x ON x.`grupo` = a.`name` AND x.`prueba` = p.`name`;

-- -----------------------------------------------------------------------------
-- 2) Sub-análisis (compuesto / cultivo) → dom_secanacategoria
-- -----------------------------------------------------------------------------
INSERT INTO `dom_secanacategoria`
  (`prianacategoria_id`, `nombre`, `paciente_id`, `valor_min`, `valor_max`, `umedida`, `formulas_id`, `opcion_id`, `deleted`, `sexo`)
SELECT
  p.`prianacategoria_id`,
  t.`sec_nombre`,
  t.`poblacion_id`,
  t.`valor_min`,
  t.`valor_max`,
  t.`unidad`,
  1,
  t.`opcion_id`,
  0,
  t.`sexo`
FROM `tmp_biocenter_vref` t
INNER JOIN `dom_anacategoria` a ON a.`name` = t.`grupo` AND (a.`deleted` = 0 OR a.`deleted` IS NULL)
INNER JOIN `dom_prianacategoria` p ON p.`anacategoria_id` = a.`anacategoria_id`
  AND p.`name` = t.`prueba`
  AND (p.`deleted` = 0 OR p.`deleted` IS NULL)
WHERE t.`modo` IN ('compuesto', 'cultivo')
  AND NOT EXISTS (
    SELECT 1 FROM `dom_secanacategoria` s
    WHERE s.`prianacategoria_id` = p.`prianacategoria_id`
      AND s.`nombre` = t.`sec_nombre`
      AND s.`paciente_id` = t.`poblacion_id`
      AND (s.`sexo` = t.`sexo` OR s.`sexo` IS NULL OR s.`sexo` = '')
      AND (s.`deleted` = 0 OR s.`deleted` IS NULL)
  );

UPDATE `dom_secanacategoria` s
INNER JOIN `dom_prianacategoria` p ON p.`prianacategoria_id` = s.`prianacategoria_id`
INNER JOIN `dom_anacategoria` a ON a.`anacategoria_id` = p.`anacategoria_id`
INNER JOIN `tmp_biocenter_vref` t ON t.`modo` IN ('compuesto', 'cultivo')
  AND t.`grupo` = a.`name`
  AND t.`prueba` = p.`name`
  AND t.`sec_nombre` = s.`nombre`
  AND t.`poblacion_id` = s.`paciente_id`
  AND t.`sexo` = IF(s.`sexo` IS NULL OR s.`sexo` = '', 'ambos', s.`sexo`)
SET
  s.`valor_min` = t.`valor_min`,
  s.`valor_max` = t.`valor_max`,
  s.`umedida` = t.`unidad`,
  s.`opcion_id` = t.`opcion_id`,
  s.`sexo` = t.`sexo`,
  s.`deleted` = 0
WHERE (s.`deleted` = 0 OR s.`deleted` IS NULL);

-- -----------------------------------------------------------------------------
-- 3) Pruebas simples → dom_priresultados
-- -----------------------------------------------------------------------------
INSERT INTO `dom_priresultados`
  (`prianacategoria_id`, `id_poblacion`, `valor_min`, `valor_max`, `umedida`, `formulas_id`, `opcion_id`, `deleted`, `sexo`)
SELECT
  p.`prianacategoria_id`,
  t.`poblacion_id`,
  t.`valor_min`,
  t.`valor_max`,
  t.`unidad`,
  1,
  t.`opcion_id`,
  0,
  t.`sexo`
FROM `tmp_biocenter_vref` t
INNER JOIN `dom_anacategoria` a ON a.`name` = t.`grupo` AND (a.`deleted` = 0 OR a.`deleted` IS NULL)
INNER JOIN `dom_prianacategoria` p ON p.`anacategoria_id` = a.`anacategoria_id`
  AND p.`name` = t.`prueba`
  AND (p.`deleted` = 0 OR p.`deleted` IS NULL)
WHERE t.`modo` = 'simple'
  AND NOT EXISTS (
    SELECT 1 FROM `dom_priresultados` pr
    WHERE pr.`prianacategoria_id` = p.`prianacategoria_id`
      AND pr.`id_poblacion` = t.`poblacion_id`
      AND (pr.`sexo` = t.`sexo` OR pr.`sexo` IS NULL OR pr.`sexo` = '')
      AND (pr.`deleted` = 0 OR pr.`deleted` IS NULL)
  );

UPDATE `dom_priresultados` pr
INNER JOIN `dom_prianacategoria` p ON p.`prianacategoria_id` = pr.`prianacategoria_id`
INNER JOIN `dom_anacategoria` a ON a.`anacategoria_id` = p.`anacategoria_id`
INNER JOIN `tmp_biocenter_vref` t ON t.`modo` = 'simple'
  AND t.`grupo` = a.`name`
  AND t.`prueba` = p.`name`
  AND t.`poblacion_id` = pr.`id_poblacion`
  AND t.`sexo` = IF(pr.`sexo` IS NULL OR pr.`sexo` = '', 'ambos', pr.`sexo`)
SET
  pr.`valor_min` = t.`valor_min`,
  pr.`valor_max` = t.`valor_max`,
  pr.`umedida` = t.`unidad`,
  pr.`opcion_id` = t.`opcion_id`,
  pr.`sexo` = t.`sexo`,
  pr.`deleted` = 0
WHERE (pr.`deleted` = 0 OR pr.`deleted` IS NULL);

-- -----------------------------------------------------------------------------
-- 4) Verificación
-- -----------------------------------------------------------------------------
SELECT t.`modo`, COUNT(*) AS filas_tmp FROM `tmp_biocenter_vref` t GROUP BY t.`modo`;

SELECT p.`compleja`, COUNT(*) AS pruebas
FROM `dom_prianacategoria` p
WHERE (p.`deleted` = 0 OR p.`deleted` IS NULL)
GROUP BY p.`compleja`;

SELECT a.`name` AS grupo, p.`name` AS prueba, COUNT(s.`secanacategoria_id`) AS subanalisis
FROM `dom_prianacategoria` p
INNER JOIN `dom_anacategoria` a ON a.`anacategoria_id` = p.`anacategoria_id`
LEFT JOIN `dom_secanacategoria` s ON s.`prianacategoria_id` = p.`prianacategoria_id`
  AND (s.`deleted` = 0 OR s.`deleted` IS NULL)
WHERE p.`compleja` IN (1, 2)
GROUP BY a.`name`, p.`name`, p.`compleja`
ORDER BY subanalisis DESC
LIMIT 30;

COMMIT;
"""
    )

    OUT_SQL.write_text("\n".join(lines), encoding="utf-8")
    OUT_REPORT.write_text(
        f"Filas emparejadas: {len(matched)}\nSin coincidencia: {len(unmatched)}\n\n"
        + "\n".join(unmatched[:500]),
        encoding="utf-8",
    )


def main() -> None:
    catalog, global_by_key = load_catalog()
    excel_rows = load_excel_rows()
    matched, unmatched = match_rows(catalog, global_by_key, excel_rows)
    write_sql(matched, unmatched)
    print(f"Excel: {len(excel_rows)} filas")
    print(f"Emparejadas: {len(dedupe_matched(matched))}")
    print(f"Sin coincidencia: {len(unmatched)}")
    print(f"SQL: {OUT_SQL}")
    print(f"Reporte: {OUT_REPORT}")


if __name__ == "__main__":
    main()
