from qdrant_client import QdrantClient
from llama_index.vector_stores.qdrant import QdrantVectorStore
from llama_index.core.storage.storage_context import StorageContext


def init_vector_store():

    client = QdrantClient(
        host="localhost",
        port=6333
    )

    vector_store = QdrantVectorStore(
        client=client,
        collection_name="rag_collection"
    )

    storage_context = StorageContext.from_defaults(
        vector_store=vector_store
    )

    return vector_store, storage_context