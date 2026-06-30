from llama_index.core import VectorStoreIndex
from qdrant_client_helper import get_qdrant_client
from vector_store import init_vector_store
from embed_model import get_embed_model
from prompts_loader import format_query


def build_query_engine():

    client = get_qdrant_client()
    vector_store, storage_context = init_vector_store(client)
    embed_model = get_embed_model()

    index = VectorStoreIndex(
        [],
        storage_context=storage_context,
        embed_model=embed_model
    )

    return index.as_query_engine(
        similarity_top_k=5
    )


def ask(query: str):
    engine = build_query_engine()
    prompt = format_query(query)
    response = engine.query(prompt)
    return str(response)