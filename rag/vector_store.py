from llama_index.vector_stores.qdrant import QdrantVectorStore
from llama_index.core import StorageContext
from config import COLLECTION_NAME

def init_vector_store(client):
    vector_store = QdrantVectorStore(
        client=client,
        collection_name=COLLECTION_NAME
    )

    storage_context = StorageContext.from_defaults(
        vector_store=vector_store
    )

    return vector_store, storage_context