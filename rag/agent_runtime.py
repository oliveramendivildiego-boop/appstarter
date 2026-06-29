from router import ask
from tools.file_tool import list_files, read_file
from tools.rag_tool import rag_query


class AgentRuntime:

    def __init__(self):
        self.tools = {
            "list_files": list_files,
            "read_file": read_file,
            "rag": rag_query,
        }

    def run(self, user_input: str):

        # 1. LLM decide acción (NO ejecuta aún)
        decision = ask(f"""
Eres un router de herramientas.

Devuelve SOLO JSON:

{{
  "tool": "list_files | read_file | rag | answer",
  "args": {{}}
}}

Usuario: {user_input}
""")

        try:
            import json
            plan = json.loads(decision)
        except:
            return decision

        tool = plan.get("tool")
        args = plan.get("args", {})

        # 2. Ejecuta tool real
        if tool in self.tools:
            result = self.tools[tool](**args)

            # 3. Re-pregunta al LLM con contexto
            final = ask(f"""
Responde al usuario usando este contexto:

{result}

Pregunta: {user_input}
""")
            return final

        # fallback directo
        return ask(user_input)