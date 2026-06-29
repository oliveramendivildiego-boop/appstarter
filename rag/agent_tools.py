from pathlib import Path

BASE = r"C:\wamp64\www\laboratorio"


def list_files(path=""):
    root = Path(BASE) / path
    return [str(p) for p in root.rglob("*") if p.is_file()]


def read_file(file_path):
    return Path(file_path).read_text(encoding="utf-8", errors="ignore")


def write_file(file_path, content):
    p = Path(file_path)
    p.write_text(content, encoding="utf-8")
    return "OK"


def apply_patch(file_path, old, new):
    content = read_file(file_path)
    updated = content.replace(old, new)
    write_file(file_path, updated)
    return "PATCH_APPLIED"