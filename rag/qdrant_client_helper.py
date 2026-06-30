from qdrant_client import QdrantClient
from config import QDRANT_URL


def get_qdrant_client():
    """Devuelve una instancia de QdrantClient configurada con `QDRANT_URL`.

    Nota: requiere instalar `qdrant-client` en el entorno (pip install qdrant-client).
    """
    # QdrantClient acepta el argumento `url` para conectarse vía HTTP
    return QdrantClient(url=QDRANT_URL)
