from llama_index.llms.ollama import Ollama

llm = Ollama(
    model="qwen2.5-coder:7b",
    request_timeout=120.0
)

resp = llm.complete("Explica qué es una API REST")

print(resp)