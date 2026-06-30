import sys
import json
from query import ask

def main():
    # Lee la pregunta desde stdin o primer arg
    if not sys.stdin.isatty():
        q = sys.stdin.read().strip()
    elif len(sys.argv) > 1:
        q = " ".join(sys.argv[1:])
    else:
        print(json.dumps({"error": "no input"}))
        return

    if not q:
        print(json.dumps({"error": "empty query"}))
        return

    try:
        resp = ask(q)
        out = {"query": q, "answer": resp}
        print(json.dumps(out, ensure_ascii=False))
    except Exception as e:
        print(json.dumps({"error": str(e)}))

if __name__ == '__main__':
    main()
