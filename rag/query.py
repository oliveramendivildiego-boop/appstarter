from query_engine import build_query_engine
from prompts_loader import format_query


def ask(query: str):
    engine = build_query_engine()
    prompt = format_query(query)
    response = engine.query(prompt)
    return str(response)