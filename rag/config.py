# config.py
import os

BASE_PATH = r"C:\wamp64\www\laboratorio"

QDRANT_URL = os.getenv("QDRANT_URL", "http://localhost:6333")
COLLECTION_NAME = os.getenv("QDRANT_COLLECTION", "rag_collection")

OLLAMA_BASE_URL = os.getenv("OLLAMA_BASE_URL", "http://localhost:11434")

EMBED_MODEL = "nomic-embed-text"
LLM_MODEL = "qwen2.5-coder:7b"

TOP_K = 6
CHUNK_SIZE = 1024
CHUNK_OVERLAP = 120