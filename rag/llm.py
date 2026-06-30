from llama_index.llms.ollama import Ollama
from config import OLLAMA_BASE_URL, LLM_MODEL

def get_llm():
    # Asegura que usamos el modelo configurado de Ollama (por defecto 'qwen2.5-coder:7b')
    print(f"[rag] Using Ollama LLM model: {LLM_MODEL} @ {OLLAMA_BASE_URL}")
    return Ollama(
        model=LLM_MODEL,
        base_url=OLLAMA_BASE_URL,
        temperature=0.2
    )