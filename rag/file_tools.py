import os
from collections import defaultdict

PROJECT_ROOT = r"C:\wamp64\www\laboratorio"

EXT_MAP = {
    ".py": "Python",
    ".php": "PHP",
    ".js": "JavaScript",
    ".ts": "TypeScript",
    ".md": "Docs",
    ".json": "Config",
    ".env": "Config"
}


def list_files_grouped():
    grouped = defaultdict(list)

    for root, dirs, files in os.walk(PROJECT_ROOT):

        dirs[:] = [d for d in dirs if d not in {
            "vendor", "node_modules", ".git", "cache", "logs", "chroma_db"
        }]

        for f in files:
            ext = os.path.splitext(f)[1]
            full_path = os.path.join(root, f)

            category = EXT_MAP.get(ext, "Other")

            grouped[category].append(full_path)

    return grouped