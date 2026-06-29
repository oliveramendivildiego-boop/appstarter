from vector_store import get_query_engine

_engine = None


def rag_query(question: str):
    global _engine

    if _engine is None:
        _engine = get_query_engine()

    return str(_engine.query(question))