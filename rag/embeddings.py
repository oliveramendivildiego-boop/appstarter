from llama_index.embeddings.ollama import OllamaEmbedding

_embed_model = None


def get_embed_model():
    global _embed_model

    if _embed_model is None:
        _embed_model = OllamaEmbedding(
            model_name="nomic-embed-text",
            base_url="http://localhost:11434"
        )

    return _embed_model