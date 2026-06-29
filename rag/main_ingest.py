import os
import hashlib

from llama_index.core import SimpleDirectoryReader
from llama_index.core.node_parser import SentenceSplitter

from ingest import ingest_nodes

PROJECT_PATH = "C:/wamp64/www/laboratorio"

EXCLUDE_DIRS = [
    "vendor", "system", "cache", "logs", "writable",
    "tests", "tools", "docs", "build", ".git"
]


def load_docs():

    docs = SimpleDirectoryReader(
        PROJECT_PATH,
        recursive=True,
        exclude=EXCLUDE_DIRS
    ).load_data()

    return docs


def main():

    print("📦 Cargando documentos...")
    docs = load_docs()

    print(f"📄 Total docs: {len(docs)}")

    splitter = SentenceSplitter(
        chunk_size=384,
        chunk_overlap=80
    )

    nodes = splitter.get_nodes_from_documents(docs)

    print(f"🧩 Chunks: {len(nodes)}")

    ingest_nodes(nodes)


if __name__ == "__main__":
    main()