import os
import hashlib
import json
import chromadb

from pathlib import Path

from llama_index.core import Document
from llama_index.core import VectorStoreIndex
from llama_index.core import StorageContext

from llama_index.vector_stores.chroma import ChromaVectorStore

from llama_index.embeddings.ollama import OllamaEmbedding

from config import *

# ==========================
# EMBEDDINGS
# ==========================

embed_model = OllamaEmbedding(
    model_name=EMBED_MODEL
)

# ==========================
# CHROMA
# ==========================

client = chromadb.PersistentClient(path=DB_PATH)

collection = client.get_or_create_collection("codeigniter")

vector_store = ChromaVectorStore(
    chroma_collection=collection
)

storage = StorageContext.from_defaults(
    vector_store=vector_store
)

# ==========================
# HASHES
# ==========================

if os.path.exists(HASH_FILE):

    with open(HASH_FILE, "r", encoding="utf8") as f:

        hashes = json.load(f)

else:

    hashes = {}

# ==========================
# SHA256
# ==========================

def file_hash(path):

    sha = hashlib.sha256()

    with open(path, "rb") as f:

        while True:

            data = f.read(1024 * 1024)

            if not data:

                break

            sha.update(data)

    return sha.hexdigest()

# ==========================
# INDEX FILE
# ==========================

def index_file(path):

    global hashes

    current = file_hash(path)

    if path in hashes:

        if hashes[path] == current:

            return False

    with open(path, encoding="utf8", errors="ignore") as f:

        text = f.read()

    doc = Document(

        text=text,

        metadata={

            "path": path

        }

    )

    VectorStoreIndex.from_documents(

        [doc],

        storage_context=storage,

        embed_model=embed_model

    )

    hashes[path] = current

    return True

# ==========================
# SAVE HASHES
# ==========================

def save():

    with open(HASH_FILE, "w", encoding="utf8") as f:

        json.dump(

            hashes,

            f,

            indent=2

        )

# ==========================
# REBUILD
# ==========================

def rebuild():

    total = 0

    updated = 0

    exts = {

        ".php",

        ".js",

        ".css",

        ".json",

        ".env",

        ".sql",

        ".md"

    }

    for root, dirs, files in os.walk(PROJECT_PATH):

        dirs[:] = [

            d for d in dirs

            if d not in {

                "vendor",

                "system",

                "writable",

                "cache",

                "logs",

                ".git",

                "node_modules",

                "chroma_db"

            }

        ]

        for file in files:

            ext = Path(file).suffix.lower()

            if ext not in exts:

                continue

            total += 1

            full = os.path.join(root, file)

            if index_file(full):

                updated += 1

                print("✔", file)

    save()

    print()

    print("Archivos:", total)

    print("Actualizados:", updated)

if __name__ == "__main__":

    rebuild()