import os

BASE_PATH = r"C:\wamp64\www\laboratorio"


def list_files(path=""):
    full = os.path.join(BASE_PATH, path)

    files = []
    for root, dirs, fs in os.walk(full):
        for f in fs:
            files.append(os.path.join(root, f))

    return files[:200]  # evitar overflow


def read_file(path):
    with open(path, "r", encoding="utf-8", errors="ignore") as f:
        return f.read()[:8000]