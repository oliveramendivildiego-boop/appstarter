from ingest import ingest_nodes
from llama_index.core.node_parser import SentenceSplitter
from llama_index.core import SimpleDirectoryReader

documents = SimpleDirectoryReader("./data", recursive=True).load_data()

splitter = SentenceSplitter(chunk_size=384, chunk_overlap=80)
nodes = splitter.get_nodes_from_documents(documents)

print(f"Nodos: {len(nodes)}")

ingest_nodes(nodes)