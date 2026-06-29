from llama_index.embeddings.ollama import OllamaEmbedding

embed = OllamaEmbedding(model_name="nomic-embed-text")

vec = embed.get_text_embedding("sistema de pacientes")

print(len(vec))
print(vec[:5])