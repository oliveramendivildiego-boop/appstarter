"""
Carga todos los modelos utilizados por el proyecto.

Este archivo debe importar Ollama UNA SOLA VEZ.
Todo el resto del proyecto reutiliza estas instancias.
"""

from llama_index.core import Settings

from llama_index.llms.ollama import Ollama

from llama_index.embeddings.ollama import OllamaEmbedding

from config import (
    LLM_MODEL,
    EMBED_MODEL,
    REQUEST_TIMEOUT,
)

# ==========================================
# LLM
# ==========================================

llm = Ollama(
    model=LLM_MODEL,
    request_timeout=REQUEST_TIMEOUT,
)

# ==========================================
# EMBEDDINGS
# ==========================================

embed_model = OllamaEmbedding(
    model_name=EMBED_MODEL,
)

# ==========================================
# LLAMAINDEX SETTINGS
# ==========================================

Settings.llm = llm
Settings.embed_model = embed_model

# ==========================================
# API
# ==========================================

def get_llm():
    """
    Devuelve el modelo LLM.
    """
    return llm


def get_embed_model():
    """
    Devuelve el modelo de embeddings.
    """
    return embed_model