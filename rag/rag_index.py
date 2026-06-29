import chromadb
import hashlib
import os
import gc

from llama_index.core import (
    SimpleDirectoryReader,
    StorageContext,
    Settings
)
from llama_index.core.ingestion import IngestionPipeline
from llama_index.core.node_parser import SentenceSplitter
from llama_index.vector_stores.chroma import ChromaVectorStore
from llama_index.embeddings.ollama import OllamaEmbedding

# ======================
# CONFIG
# ======================
PROJECT_PATH = "C:/wamp64/www/laboratorio"
DB_PATH = "./chroma_db"
HASH_FILE = "./rag_file_hashes.txt"

EXCLUDE_DIRS = [
    "vendor", "system", "cache", "logs", "writable",
    "tests", "tools", "docs", "build", "chroma_db",
    "node_modules", ".git"
]

# ======================
# SETTINGS
# ======================
Settings.chunk_size = 384
Settings.chunk_overlap = 80
Settings.num_workers = 2

# ======================
# EMBEDDINGS
# ======================
embed_model = OllamaEmbedding(model_name="nomic-embed-text")

# ======================
# CHROMA
# ======================
client = chromadb.PersistentClient(path=DB_PATH)

collection = client.get_or_create_collection(
    "codeigniter",
    metadata={"hnsw:space": "cosine"}
)

vector_store = ChromaVectorStore(chroma_collection=collection)

storage_context = StorageContext.from_defaults(
    vector_store=vector_store
)

# ======================
# PIPELINE (NUEVO ENFOQUE)
# ======================
pipeline = IngestionPipeline(
    transformations=[
        SentenceSplitter(chunk_size=384, chunk_overlap=80),
    ],
    vector_store=vector_store,
    embed_model=embed_model,
)

# ======================
# HASHES
# ======================
def load_hashes():
    if not os.path.exists(HASH_FILE):
        return {}
    with open(HASH_FILE, "r", encoding="utf-8") as f:
        return dict(line.strip().split("||") for line in f.readlines())

def save_hashes(hashes):
    with open(HASH_FILE, "w", encoding="utf-8") as f:
        for k, v in hashes.items():
            f.write(f"{k}||{v}\n")

def file_hash(path):
    h = hashlib.md5()
    with open(path, "rb") as f:
        for chunk in iter(lambda: f.read(8192), b""):
            h.update(chunk)
    return h.hexdigest()

# ======================
# LOAD FILES
# ======================
old_hashes = load_hashes()
new_hashes = {}

documents = SimpleDirectoryReader(
    PROJECT_PATH,
    recursive=True,
    exclude=EXCLUDE_DIRS
).load_data()

print(f"📦 Documentos cargados: {len(documents)}")

# ======================
# FILTRO INCREMENTAL
# ======================
filtered_docs = []

for doc in documents:
    source = doc.metadata.get("file_path", "")
    if not source:
        continue

    h = file_hash(source)
    new_hashes[source] = h

    if old_hashes.get(source) == h:
        continue

    filtered_docs.append(doc)

print(f"🟢 Documentos nuevos/modificados: {len(filtered_docs)}")

if not filtered_docs:
    print("✔ No hay cambios. Índice actualizado.")
    exit()

# ======================
# INGESTA (OPTIMIZADA)
# ======================
print("⚙️ Iniciando ingestion pipeline...")

nodes = pipeline.run(documents=filtered_docs)

print(f"🧩 Chunks procesados: {len(nodes)}")

# limpieza ligera
gc.collect()

# ======================
# GUARDAR HASHES
# ======================
save_hashes(new_hashes)

print("✅ RAG INDEX INCREMENTAL COMPLETADO")