import time
from pathlib import Path

BASE = r"C:\wamp64\www\laboratorio"

_last_state = {}


def scan():
    state = {}
    for p in Path(BASE).rglob("*"):
        if p.is_file():
            try:
                state[str(p)] = p.stat().st_mtime
            except:
                pass
    return state


def detect_changes(callback):
    global _last_state

    _last_state = scan()

    while True:
        time.sleep(2)

        new_state = scan()

        added = set(new_state) - set(_last_state)
        modified = {
            k for k in new_state
            if k in _last_state and new_state[k] != _last_state[k]
        }

        if added or modified:
            callback({
                "added": list(added),
                "modified": list(modified)
            })

        _last_state = new_state