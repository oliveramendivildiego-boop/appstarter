import argparse
import os
import hashlib
import json
from ingest import ingest_nodes, delete_docs
from llama_index.core.node_parser import SentenceSplitter
from llama_index.core import Document
from config import BASE_PATH, CHUNK_SIZE, CHUNK_OVERLAP, EXCLUDE_DIRS, EXCLUDE_FILES, INCLUDE_EXTENSIONS
from tqdm import tqdm

STATE_FILE = "ingestion_state.json"

def get_file_hash(filepath):
    """Computes SHA256 hash of a file's content."""
    hasher = hashlib.sha256()
    try:
        with open(filepath, 'rb') as f:
            while chunk := f.read(8192):
                hasher.update(chunk)
        return hasher.hexdigest()
    except (IOError, OSError):
        return None

def load_state():
    """Loads the ingestion state from the state file."""
    if os.path.exists(STATE_FILE):
        with open(STATE_FILE, 'r') as f:
            return json.load(f)
    return {}

def save_state(state):
    """Saves the ingestion state to the state file."""
    with open(STATE_FILE, 'w') as f:
        json.dump(state, f, indent=4)

def is_binary(filepath: str) -> bool:
    """Checks if a file is binary by looking for null bytes in the first 8KB."""
    try:
        with open(filepath, 'rb') as f:
            chunk = f.read(8192)
            return b'\0' in chunk
    except (IOError, OSError):
        return True

def collect_files(root_path: str):
    """
    Recursively collects file paths from a directory, respecting inclusion lists
    and skipping binary files.
    """
    file_paths = []
    print("Buscando archivos para procesar...")
    for root, dirs, files in os.walk(root_path, topdown=True, followlinks=False):
        dirs[:] = [d for d in dirs if d not in EXCLUDE_DIRS]
        for filename in files:
            if filename in EXCLUDE_FILES:
                continue
            
            if not any(filename.endswith(ext) for ext in INCLUDE_EXTENSIONS):
                continue
            
            file_path = os.path.join(root, filename)
            if is_binary(file_path):
                continue
            
            file_paths.append(file_path)
    return file_paths

def build_nodes_from_files(file_paths: list[str]):
    """Builds nodes from a list of file paths with tqdm progress indicators."""
    if not file_paths:
        print("No se encontraron archivos para procesar.")
        return []

    documents = []
    print(f"Leyendo {len(file_paths)} documentos...")
    for file_path in tqdm(file_paths, desc="Leyendo archivos"):
        try:
            with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
                content = f.read()
            doc = Document(text=content, id_=file_path, metadata={"file_path": file_path})
            documents.append(doc)
        except Exception as e:
            print(f"
Error leyendo archivo {file_path}: {e}")

    print("Generando nodos...")
    splitter = SentenceSplitter(chunk_size=CHUNK_SIZE, chunk_overlap=CHUNK_OVERLAP)
    
    nodes = []
    for doc in tqdm(documents, desc="Generando nodos"):
        nodes.extend(splitter.get_nodes_from_documents([doc]))
    
    print("Asignando IDs deterministas a los nodos...")
    for node in nodes:
        node_hash = hashlib.sha256(node.get_content().encode('utf-8')).hexdigest()
        node.id_ = f"{node.ref_doc_id}_{node_hash}"

    return nodes

def main():
    parser = argparse.ArgumentParser(description="Ingesta de documentos a vector store")
    parser.add_argument("--path", help="Carpeta a indexar", default=BASE_PATH)
    parser.add_argument("--force", action="store_true", help="Forzar re-ingesta de todos los archivos.")
    args = parser.parse_args()

    print("Iniciando proceso de ingesta incremental...")
    old_state = load_state() if not args.force else {}
    if args.force:
        print("Opción --force detectada. Se re-ingestarán todos los archivos.")

    current_files = set(collect_files(os.path.abspath(args.path)))
    old_files = set(old_state.keys())

    deleted_files = list(old_files - current_files)
    if deleted_files:
        delete_docs(deleted_files)

    files_to_process = []
    new_state = {}
    
    print("Comparando archivos con el estado anterior...")
    for file_path in current_files:
        file_hash = get_file_hash(file_path)
        if not file_hash:
            continue

        if file_path not in old_state or old_state[file_path] != file_hash:
            files_to_process.append(file_path)
        
        new_state[file_path] = file_hash

    if not files_to_process:
        print("No hay archivos nuevos o modificados para procesar.")
    else:
        print(f"Archivos para procesar: {len(files_to_process)}")
        nodes = build_nodes_from_files(files_to_process)
        if nodes:
            print(f"Nodos generados: {len(nodes)}")
            ingest_nodes(nodes)

    print("Guardando estado de la ingesta...")
    save_state(new_state)

    print("Proceso de ingesta incremental completado.")

if __name__ == '__main__':
    main()
