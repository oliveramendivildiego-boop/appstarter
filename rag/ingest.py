import gc
import sys
from llama_index.core import VectorStoreIndex
from tqdm import tqdm
from requests.exceptions import ConnectionError

from qdrant_connection import get_qdrant_client
from vector_store import init_vector_store
from embed_model import get_embed_model

def check_ollama_server(embed_model):
    """
    Verifies the Ollama server is running and the model is available.
    """
    print("Verificando conexión con el servidor Ollama...")
    try:
        # Try to embed a small piece of text to check the connection and model
        embed_model.get_text_embedding("test")
        print("✅ Conexión con Ollama y modelo de embedding verificados.")
    except ConnectionError:
        print("\n❌ Error de conexión con el servidor de Ollama.", file=sys.stderr)
        print("Por favor, asegúrate de que Ollama esté en ejecución.", file=sys.stderr)
        sys.exit(1)
    except Exception as e:
        # Catch other potential exceptions, e.g., model not found
        if "model not found" in str(e).lower():
            print(f"\n❌ Error: Modelo de embedding '{embed_model.model_name}' no encontrado en Ollama.", file=sys.stderr)
            print(f"Ejecuta `ollama pull {embed_model.model_name}` para descargarlo.", file=sys.stderr)
        else:
            print(f"\n❌ Ocurrió un error inesperado al contactar a Ollama: {e}", file=sys.stderr)
        sys.exit(1)


def ingest_nodes(nodes):
    client = get_qdrant_client()
    vector_store, storage_context = init_vector_store(client)
    embed_model = get_embed_model()

    # --- Pre-flight check ---
    check_ollama_server(embed_model)

    index = VectorStoreIndex(
        [],
        storage_context=storage_context,
        embed_model=embed_model
    )

    batch_size = 200

    # Create batches to process
    batches = [nodes[i:i + batch_size] for i in range(0, len(nodes), batch_size)]

    # Ingest nodes with a progress bar
    for batch in tqdm(batches, desc="Ingestando nodos en lotes"):
        index.insert_nodes(batch)
        gc.collect()

    print("✅ INGESTA COMPLETADA")


def delete_docs(doc_ids: list[str]):
    """
    Deletes documents from the vector store by their doc_ids.
    """
    if not doc_ids:
        return

    print(f"🗑️ Deleting {len(doc_ids)} documents...")

    client = get_qdrant_client()
    vector_store, storage_context = init_vector_store(client)
    embed_model = get_embed_model()

    index = VectorStoreIndex.from_vector_store(
        vector_store,
        storage_context=storage_context,
        embed_model=embed_model
    )

    for doc_id in doc_ids:
        print(f"  - Deleting {doc_id}")
        index.delete_ref_doc(doc_id, delete_from_docstore=True)

    print("✅ BORRADO COMPLETADO")