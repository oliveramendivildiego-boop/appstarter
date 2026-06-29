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


# ----------------------------
# AGENT PROMPTS
# ----------------------------

PLANNER_PROMPT = """
Eres Planner.
Divide tareas en pasos claros.
Responde SOLO JSON:
{"steps": ["..."]}
"""

CODER_PROMPT = """
Eres Coder Agent.
Genera código o cambios.
Responde SOLO JSON:
{"action":"tool","tool":"apply_patch","args":{...}}
"""

DEBUGGER_PROMPT = """
Eres Debugger Agent.
Detecta errores en código o lógica.
Responde JSON:
{"issues":[...], "fix": "..."}
"""

REVIEWER_PROMPT = """
Eres Reviewer Agent.
Evalúa calidad del código.
Responde JSON:
{"score": 0-10, "notes": "..."}
"""


# ----------------------------
# TOOL EXECUTOR
# ----------------------------

def run_tool(tool, args):
    if tool not in TOOLS:
        return f"UNKNOWN_TOOL: {tool}"
    return TOOLS[tool](**args)


# ----------------------------
# PLANNER
# ----------------------------

def plan(task):
    res = engine.query(PLANNER_PROMPT + "\nTASK:\n" + task)
    return json.loads(str(res))


# ----------------------------
# CODER
# ----------------------------

def coder(step, memory):
    res = engine.query(CODER_PROMPT + "\nTASK:\n" + step + "\nMEMORY:\n" + memory)
    return json.loads(str(res))


# ----------------------------
# DEBUGGER
# ----------------------------

def debugger(code_context):
    res = engine.query(DEBUGGER_PROMPT + "\nCODE:\n" + code_context)
    return json.loads(str(res))


# ----------------------------
# REVIEWER
# ----------------------------

def reviewer(code_context):
    res = engine.query(REVIEWER_PROMPT + "\nCODE:\n" + code_context)
    return json.loads(str(res))


# ----------------------------
# ORCHESTRATOR
# ----------------------------

def run_multi_agent(task):
    memory = ""

    # 1. PLAN
    plan_result = plan(task)
    steps = plan_result.get("steps", [])

    outputs = []

    for step in steps:

        # 2. CODE
        code_result = coder(step, memory)

        if code_result["action"] == "tool":
            tool_result = run_tool(
                code_result["tool"],
                code_result.get("args", {})
            )
            memory += f"\nTOOL RESULT:\n{tool_result}\n"
            outputs.append(tool_result)

        # 3. DEBUG
        debug = debugger(memory)

        # 4. REVIEW
        review = reviewer(memory)

        memory += f"\nDEBUG:\n{debug}\nREVIEW:\n{review}\n"

    return {
        "task": task,
        "steps": steps,
        "outputs": outputs,
        "final_memory": memory
    }