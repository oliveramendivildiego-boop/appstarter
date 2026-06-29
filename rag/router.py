from file_tools import list_files_grouped
from query_engine import build_query_engine

engine = build_query_engine()


def ask(query: str):

    q = query.lower()

    if "archivos" in q or "estructura" in q or "modulos" in q:
        return format_tree(list_files_grouped())

    return engine.query(query)


def format_tree(grouped):
    output = []

    for category, files in grouped.items():
        output.append(f"\n📁 {category} ({len(files)})")

        for f in files[:15]:  # límite estilo Cursor
            output.append(f" - {f}")

    return "\n".join(output)