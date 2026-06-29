from llama_index.core import VectorStoreIndex, StorageContext
from llama_index.vector_stores.chroma import ChromaVectorStore
from llama_index.embeddings.ollama import OllamaEmbedding
from llama_index.llms.ollama import Ollama

import chromadb

DB_PATH = "./rag/chroma_db"

# EMBEDDINGS
embed_model = OllamaEmbedding(model_name="nomic-embed-text")

# LLM
llm = Ollama(
    model="qwen2.5-coder:7b",
    request_timeout=120.0
)

# CHROMA PERSISTENTE
client = chromadb.PersistentClient(path=DB_PATH)
collection = client.get_or_create_collection("codeigniter")

vector_store = ChromaVectorStore(chroma_collection=collection)
storage_context = StorageContext.from_defaults(vector_store=vector_store)

# CARGAR ÍNDICE
index = VectorStoreIndex.from_vector_store(
    vector_store=vector_store,
    embed_model=embed_model
)

query_engine = index.as_query_engine(llm=llm)

print("💬 RAG listo. Escribe tu pregunta:\n")

while True:
    q = input(">> ")
    if q.lower() in ["exit", "quit"]:
        break

    response = query_engine.query(q)
    print("\n", response, "\n")