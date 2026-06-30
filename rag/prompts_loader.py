import os

def load_template():
    base = os.path.dirname(__file__)
    path = os.path.join(base, 'prompts', 'cursor_style.md')
    try:
        with open(path, 'r', encoding='utf-8') as f:
            return f.read().strip()
    except FileNotFoundError:
        return ''

def format_query(query: str) -> str:
    template = load_template()
    if not template:
        return query
    return f"{template}\n\nUsuario pregunta: {query}"
