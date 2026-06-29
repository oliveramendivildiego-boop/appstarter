from llama_index.embeddings.ollama import OllamaEmbedding
from config import EMBED_MODEL
embed_model=OllamaEmbedding(model_name=EMBED_MODEL)
