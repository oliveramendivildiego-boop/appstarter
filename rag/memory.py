import json
from pathlib import Path

MEM_FILE = "rag_memory.json"


def load_memory():
    if Path(MEM_FILE).exists():
        return json.loads(Path(MEM_FILE).read_text())
    return {}


def save_memory(data):
    Path(MEM_FILE).write_text(json.dumps(data, indent=2))


def update_memory(key, value):
    mem = load_memory()
    mem[key] = value
    save_memory(mem)
    return mem


def get_memory():
    return load_memory()