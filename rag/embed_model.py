from llama_index.embeddings.ollama import OllamaEmbedding
from config import OLLAMA_BASE_URL, EMBED_MODEL

def get_embed_model():
    return OllamaEmbedding(
        model_name=EMBED_MODEL,
        base_url=OLLAMA_BASE_URL
    )