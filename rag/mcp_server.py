from mcp.server.fastmcp import FastMCP
from agent_runtime import AgentRuntime

mcp = FastMCP("cursor-enterprise-agent")

runtime = AgentRuntime()


@mcp.tool()
def run(task: str):
    """
    Ejecuta el agente completo (planning + tools + reasoning)
    """
    return runtime.run(task)


@mcp.tool()
def ping():
    return "pong"


if __name__ == "__main__":
    print("🚀 CURSOR ENTERPRISE AGENT READY")
    mcp.run()