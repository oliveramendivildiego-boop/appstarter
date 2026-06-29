import gc
import time

from llama_index.core import VectorStoreIndex

from qdrant_connection import get_qdrant_client
from vector_store import init_vector_store
from embed_model import get_embed_model


def ingest_nodes(nodes):

    # ======================
    # CLIENTE QDRANT
    # ======================
    client = get_qdrant_client()

    vector_store, storage_context = init_vector_store(client)

    embed_model = get_embed_model()

    # ======================
    # ÍNDICE (UNA SOLA VEZ)
    # ======================
    index = VectorStoreIndex(
        [],
        storage_context=storage_context,
        embed_model=embed_model
    )

    # ======================
    # INGESTA POR LOTES
    # ======================
    batch_size = 200

    for i in range(0, len(nodes), batch_size):

        batch = nodes[i:i + batch_size]

        print(f"⚙️ Ingest batch {i}-{i+len(batch)}")

        index.insert_nodes(batch)

        # limpieza de memoria (importante para 1M+ chunks)
        gc.collect()

        # pequeño throttle para evitar presión en Qdrant
        time.sleep(0.05)

    print("✅ INGESTA COMPLETADA")