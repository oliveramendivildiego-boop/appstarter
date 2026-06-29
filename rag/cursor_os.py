from multi_agent_core import run_multi_agent
from memory import update_memory
from watcher import detect_changes
import threading


class CursorOS:

    def __init__(self):
        self.running = True

    # -------------------------
    # EVENT HANDLER
    # -------------------------
    def on_change(self, changes):
        print("📡 Changes detected:", changes)

        update_memory("last_changes", changes)

        # Auto-react (Cursor OS behavior)
        task = f"""
        Analiza cambios en el workspace:
        {changes}
        Sugiere mejoras o correcciones si es necesario.
        """

        result = run_multi_agent(task)
        print("🧠 Auto-response:", result)

    # -------------------------
    # START SYSTEM
    # -------------------------
    def start(self):
        print("🚀 CURSOR OS STARTED")

        t = threading.Thread(
            target=detect_changes,
            args=(self.on_change,),
            daemon=True
        )

        t.start()

        while self.running:
            cmd = input("\nOS> ")

            if cmd == "exit":
                break

            result = run_multi_agent(cmd)
            print(result)


if __name__ == "__main__":
    CursorOS().start()