from llama_index.core import VectorStoreIndex
from qdrant_connection import get_qdrant_client
from vector_store import init_vector_store
from embed_model import get_embed_model
from llm import get_llm

def build_query_engine():
    client = get_qdrant_client()

    vector_store, storage_context = init_vector_store(client)

    embed_model = get_embed_model()
    llm = get_llm()

    index = VectorStoreIndex.from_vector_store(
        vector_store=vector_store,
        embed_model=embed_model
    )

    query_engine = index.as_query_engine(
        llm=llm,
        similarity_top_k=6
    )

    return query_engine