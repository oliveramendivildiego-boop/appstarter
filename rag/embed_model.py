from llama_index.embeddings.ollama import OllamaEmbedding

def get_embed_model():
    return OllamaEmbedding(
        model_name="nomic-embed-text"
    )