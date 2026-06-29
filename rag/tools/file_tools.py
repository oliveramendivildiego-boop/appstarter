import os

def list_files(path):
    out = []
    for root, _, files in os.walk(path):
        for f in files:
            out.append(os.path.join(root, f))
    return out


def read_file(path):
    with open(path, "r", encoding="utf-8", errors="ignore") as f:
        return f.read()


def search_files(query, base="C:\\wamp64\\www\\laboratorio"):
    results = []
    for root, _, files in os.walk(base):
        for f in files:
            if query.lower() in f.lower():
                results.append(os.path.join(root, f))
    return results