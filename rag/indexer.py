from llama_index.core import VectorStoreIndex, SimpleDirectoryReader

BASE = r"C:\wamp64\www\laboratorio"


def build_index():
    docs = SimpleDirectoryReader(BASE, recursive=True).load_data()
    index = VectorStoreIndex.from_documents(docs)
    return index