from agent_runtime import AgentRuntime

runtime = AgentRuntime()

SYSTEM_STYLE = """
Eres un agente de desarrollo tipo Cursor.

Reglas:
- Puedes leer, analizar y modificar código del proyecto.
- Siempre usa herramientas cuando sea necesario.
- Nunca inventes archivos o resultados.
- Si necesitas múltiples pasos, ejecútalos en orden.
- Responde técnico y directo.
"""


def ask(question: str):
    # el runtime ya maneja tools + RAG + LLM
    return runtime.run(question)