import json
from agent_tools import list_files, read_file, write_file, apply_patch
from query_engine import build_query_engine

engine = build_query_engine()


TOOLS = {
    "list_files": list_files,
    "read_file": read_file,
    "write_file": write_file,
    "apply_patch": apply_patch
}


SYSTEM_PROMPT = """
Eres un agente autónomo tipo Cursor Pro.

REGLAS:
- Debes responder SOLO en JSON válido.
- Puedes ejecutar múltiples pasos.
- Si el trabajo no está completo, continúas iterando.
- No inventes resultados.

FORMATO:

1) Tool:
{
  "action": "tool",
  "tool": "...",
  "args": {...}
}

2) Final:
{
  "action": "final",
  "output": "resultado"
}

OBJETIVO:
Resolver tareas de programación, análisis y edición de código.
"""


def run_tool(tool, args):
    if tool not in TOOLS:
        return f"ERROR: tool {tool} not found"
    return TOOLS[tool](**args)


def step(user_input, memory=""):
    prompt = SYSTEM_PROMPT + "\n\nMEMORY:\n" + memory + "\n\nUSER:\n" + user_input

    response = engine.query(prompt)

    try:
        return json.loads(str(response))
    except:
        return {
            "action": "final",
            "output": str(response)
        }


def run_agent(task, max_steps=5):
    memory = ""
    last_output = None

    for i in range(max_steps):
        result = step(task, memory)

        if result["action"] == "final":
            return result["output"]

        if result["action"] == "tool":
            tool = result["tool"]
            args = result.get("args", {})

            tool_result = run_tool(tool, args)

            memory += f"\nSTEP {i+1}:\nTOOL={tool}\nRESULT={tool_result}\n"

            last_output = tool_result

    return f"MAX_STEPS_REACHED\nLast output: {last_output}"