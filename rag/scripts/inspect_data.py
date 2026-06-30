from llama_index.core import SimpleDirectoryReader
from llama_index.core.node_parser import SentenceSplitter
import os

DATA_DIR = os.path.join(os.path.dirname(__file__), '..', 'data')

def main():
    print(f"Leyendo documentos en: {os.path.abspath(DATA_DIR)}")
    reader = SimpleDirectoryReader(DATA_DIR, recursive=True)
    documents = reader.load_data()

    splitter = SentenceSplitter(chunk_size=384, chunk_overlap=80)

    total = 0
    for doc in documents:
        nodes = splitter.get_nodes_from_documents([doc])
        source = getattr(doc, 'extra_info', {}).get('file_path', 'unknown')
        print(f"{source}: {len(nodes)} nodes")
        total += len(nodes)

    print(f"Total nodes: {total}")

if __name__ == '__main__':
    main()
