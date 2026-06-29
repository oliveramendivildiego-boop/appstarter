import subprocess
from pathlib import Path

BASE = r"C:\wamp64\www\laboratorio"


def run_git(cmd):
    return subprocess.getoutput(f"cd {BASE} && git {cmd}")


def list_files():
    return [str(p) for p in Path(BASE).rglob("*") if p.is_file()]


def read_file(path):
    return Path(path).read_text(encoding="utf-8", errors="ignore")


def write_file(path, content):
    Path(path).write_text(content, encoding="utf-8")
    return "OK"