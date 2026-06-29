from llama_index.llms.ollama import Ollama
from config import OLLAMA_BASE_URL, LLM_MODEL

def get_llm():
    return Ollama(
        model=LLM_MODEL,
        base_url=OLLAMA_BASE_URL,
        temperature=0.2
    )