import chromadb
import hashlib
import os

from llama_index.core import (
    SimpleDirectoryReader,
    VectorStoreIndex,
    StorageContext,
    Settings,
    Document
)
from llama_index.vector_stores.chroma import ChromaVectorStore
from llama_index.embeddings.ollama import OllamaEmbedding
from llama_index.core.node_parser import SentenceSplitter

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
Settings.num_workers = 4

# ======================
# EMBEDDINGS
# ======================
embed_model = OllamaEmbedding(model_name="nomic-embed-text")

# ======================
# SPLITTER
# ======================
splitter = SentenceSplitter(
    chunk_size=384,
    chunk_overlap=80
)

# ======================
# CHROMA
# ======================
client = chromadb.PersistentClient(path=DB_PATH)
collection = client.get_or_create_collection("codeigniter")

vector_store = ChromaVectorStore(chroma_collection=collection)
storage_context = StorageContext.from_defaults(vector_store=vector_store)

# ======================
# HASH STORAGE (INCREMENTAL INDEX)
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
    with open(path, "rb") as f:
        return hashlib.md5(f.read()).hexdigest()

# ======================
# LOAD EXISTING HASHES
# ======================
old_hashes = load_hashes()
new_hashes = {}

# ======================
# LOAD FILES
# ======================
documents = SimpleDirectoryReader(
    PROJECT_PATH,
    recursive=True,
    exclude=EXCLUDE_DIRS
).load_data()

print(f"📦 Documentos cargados: {len(documents)}")

# ======================
# FILTRADO INCREMENTAL (CLAVE)
# ======================
filtered_docs = []

for doc in documents:
    source = doc.metadata.get("file_path", "")

    if not source:
        continue

    h = file_hash(source)
    new_hashes[source] = h

    # si no cambió → ignorar
    if old_hashes.get(source) == h:
        continue

    filtered_docs.append(doc)

print(f"🟢 Documentos nuevos/modificados: {len(filtered_docs)}")

# ======================
# SI NO HAY CAMBIOS
# ======================
if len(filtered_docs) == 0:
    print("✔ No hay cambios. Índice actualizado.")
    exit()

# ======================
# SPLIT
# ======================
nodes = splitter.get_nodes_from_documents(filtered_docs)

print(f"🧩 Chunks nuevos: {len(nodes)}")

# ======================
# INDEX
# ======================
BATCH_SIZE = 200

def process_batch(batch, batch_id):
    print(f"⚙️ Batch {batch_id} ({len(batch)} chunks)")

    VectorStoreIndex(
        batch,
        storage_context=storage_context,
        embed_model=embed_model
    )

for i in range(0, len(nodes), BATCH_SIZE):
    batch = nodes[i:i + BATCH_SIZE]
    process_batch(batch, f"{i}-{i+len(batch)}")

# ======================
# SAVE HASHES
# ======================
save_hashes(new_hashes)

print("✅ RAG INDEX INCREMENTAL COMPLETADO")