"""
Configuración central del proyecto RAG
"""

from pathlib import Path

# ==========================================
# PROYECTO
# ==========================================

PROJECT_PATH = Path(r"C:\wamp64\www\laboratorio")

# ==========================================
# DIRECTORIOS RAG
# ==========================================

BASE_DIR = Path(__file__).resolve().parent

DATA_DIR = BASE_DIR / "data"

DB_PATH = DATA_DIR / "chroma_db"

HASH_FILE = DATA_DIR / "hashes.json"

LOG_DIR = BASE_DIR / "logs"

PROMPTS_DIR = BASE_DIR / "prompts"

# ==========================================
# MODELOS
# ==========================================

LLM_MODEL = "qwen2.5-coder:7b"

EMBED_MODEL = "nomic-embed-text"

# ==========================================
# INDEXACIÓN
# ==========================================

CHUNK_SIZE = 512

CHUNK_OVERLAP = 64

TOP_K = 6

BATCH_SIZE = 64

NUM_WORKERS = 6

# ==========================================
# EXTENSIONES
# ==========================================

ALLOWED_EXTENSIONS = {
    ".php",
    ".js",
    ".ts",
    ".css",
    ".scss",
    ".html",
    ".json",
    ".xml",
    ".sql",
    ".env",
    ".md",
    ".txt",
    ".yml",
    ".yaml",
}

# ==========================================
# DIRECTORIOS A IGNORAR
# ==========================================

EXCLUDED_DIRS = {
    ".git",
    ".idea",
    ".vscode",
    "vendor",
    "node_modules",
    "system",
    "writable",
    "cache",
    "logs",
    "build",
    "tests",
    "docs",
    "tools",
    "chroma_db",
    "__pycache__",
}

# ==========================================
# OLLAMA
# ==========================================

OLLAMA_URL = "http://127.0.0.1:11434"

REQUEST_TIMEOUT = 600

# ==========================================
# CREAR DIRECTORIOS
# ==========================================

DATA_DIR.mkdir(exist_ok=True)

DB_PATH.mkdir(exist_ok=True)

LOG_DIR.mkdir(exist_ok=True)

PROMPTS_DIR.mkdir(exist_ok=True)