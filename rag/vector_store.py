from llama_index.vector_stores.qdrant import QdrantVectorStore
from llama_index.core import StorageContext

from qdrant_client.models import VectorParams, Distance

COLLECTION = "codeigniter"
VECTOR_SIZE = 768


def init_vector_store(client):

    # ======================
    # CREAR COLECCIÓN (SOLO UNA VEZ)
    # ======================
    client.recreate_collection(
        collection_name=COLLECTION,
        vectors_config=VectorParams(
            size=VECTOR_SIZE,
            distance=Distance.COSINE
        )
    )

    # ======================
    # VECTOR STORE
    # ======================
    vector_store = QdrantVectorStore(
        client=client,
        collection_name=COLLECTION
    )

    storage_context = StorageContext.from_defaults(
        vector_store=vector_store
    )

    return vector_store, storage_context