from llama_index.embeddings.ollama import OllamaEmbedding

embed_model = OllamaEmbedding(
    model_name="nomic-embed-text"
)