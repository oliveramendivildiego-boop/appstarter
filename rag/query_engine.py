import chromadb

from llama_index.core import (
    VectorStoreIndex,
    StorageContext
)

from llama_index.vector_stores.chroma import ChromaVectorStore

from llama_index.llms.ollama import Ollama

from config import DB_PATH


# ===============================
# MODELO LLM
# ===============================

llm = Ollama(
    model="qwen2.5-coder:7b",
    request_timeout=600.0,
)

# ===============================
# CHROMA
# ===============================

client = chromadb.PersistentClient(path=DB_PATH)

collection = client.get_or_create_collection("codeigniter")

vector_store = ChromaVectorStore(
    chroma_collection=collection
)

storage_context = StorageContext.from_defaults(
    vector_store=vector_store
)

# ===============================
# ÍNDICE
# ===============================

index = VectorStoreIndex.from_vector_store(
    vector_store=vector_store,
    storage_context=storage_context,
)

query_engine = index.as_query_engine(
    llm=llm,
    similarity_top_k=6,
)

# ===============================
# API
# ===============================

def ask(question: str):

    response = query_engine.query(question)

    return str(response)