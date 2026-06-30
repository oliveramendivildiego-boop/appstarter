# config.py
import os

BASE_PATH = r"C:\wamp64\www\laboratorio"

QDRANT_URL = os.getenv("QDRANT_URL", "http://localhost:6333")
COLLECTION_NAME = os.getenv("QDRANT_COLLECTION", "rag_collection")

OLLAMA_BASE_URL = os.getenv("OLLAMA_BASE_URL", "http://localhost:11434")

EMBED_MODEL = "nomic-embed-text"
LLM_MODEL = "qwen2.5-coder:3b"

TOP_K = 6
CHUNK_SIZE = 1024
CHUNK_OVERLAP = 120


# Exclusion lists for ingestion
EXCLUDE_DIRS = [
    # General
    ".git", ".github", ".idea", ".vscode", ".cursor", ".zed",
    "vendor", "node_modules",
    "build", "builds", "dist",
    "chroma_db", "writable", "tests", "__pycache__",
    # Project specific
    "docs", "public/assets"
]
EXCLUDE_FILES = [
    "composer.lock", "package-lock.json",
    "LICENSE", "composer-setup.php", "ci_sessions.sql", "error_log"
]
# Inclusion list for ingestion
INCLUDE_EXTENSIONS = [
    ".py", ".js", ".ts", ".php", ".md", ".txt", ".json", ".yml", ".yaml", ".sql"
]