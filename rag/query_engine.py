from llama_index.llms.ollama import Ollama
from llama_index.core.chat_engine import ContextChatEngine

from vector_store import init_vector_store
from llama_index.core import VectorStoreIndex
from llama_index.embeddings.ollama import OllamaEmbedding


SYSTEM_PROMPT = """
Eres un asistente tipo Cursor dentro de un IDE local.

Tienes acceso a un sistema de archivos indexado.

Reglas:
- Usa el contexto del código para responder
- Si necesitas más archivos, indícalo
- Razona sobre múltiples archivos
- Ayuda a programar, refactorizar y entender sistemas
- No inventes información fuera del repo
"""


def build_query_engine():

    llm = Ollama(
        model="qwen2.5-coder:7b",
        request_timeout=120.0
    )

    embed_model = OllamaEmbedding(
        model_name="nomic-embed-text"
    )

    vector_store, storage_context = init_vector_store()

    index = VectorStoreIndex.from_vector_store(
        vector_store=vector_store,
        storage_context=storage_context,
        embed_model=embed_model
    )

    retriever = index.as_retriever(similarity_top_k=8)

    chat_engine = ContextChatEngine.from_defaults(
        retriever=retriever,
        llm=llm,
        system_prompt=SYSTEM_PROMPT
    )

    return chat_engine