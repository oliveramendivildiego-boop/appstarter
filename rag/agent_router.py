import json
from query_engine import build_query_engine
from agent_tools import list_files, read_file, write_file, apply_patch
from prompts_loader import load_template

engine = build_query_engine()


BASE_SYSTEM = """
Eres un agente tipo Cursor IDE.

REGLAS:
1. Solo puedes responder en JSON válido.
2. Dos modos:
   - tool: ejecutar herramientas
   - final: respuesta final
3. Nunca escribas texto fuera del JSON.

TOOLS DISPONIBLES:
- list_workspace(path)
- read_file(file_path)
- write_file(file_path, content)
- patch_file(file_path, old, new)
- query_rag(question)
"""

# carga plantilla de estilo Cursor (si existe) y la antepone al system prompt
TEMPLATE = load_template()
if TEMPLATE:
    SYSTEM_PROMPT = TEMPLATE + "\n\n" + BASE_SYSTEM
else:
    SYSTEM_PROMPT = BASE_SYSTEM


TOOLS = {
    "list_workspace": list_files,
    "read_file": read_file,
    "write_file": write_file,
    "patch_file": apply_patch,
}


def run_tool(name, args):
    if name not in TOOLS:
        return f"UNKNOWN_TOOL: {name}"
    return TOOLS[name](**args)


def ask(question: str):
    messages = SYSTEM_PROMPT + "\n\nUSER:\n" + question

    response = engine.query(messages)

    try:
        data = json.loads(str(response))
    except:
        return {"action": "final", "output": str(response)}

    if data.get("action") == "tool":
        result = run_tool(data["tool"], data.get("args", {}))

        followup = engine.query(
            SYSTEM_PROMPT +
            f"\nTOOL_RESULT:\n{result}\n\nCONTINUE"
        )

        try:
            return json.loads(str(followup))
        except:
            return {"action": "final", "output": str(followup)}

    return data