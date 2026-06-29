from mcp.server.fastmcp import FastMCP

from tools.file_tools import list_files, read_file, search_files
from router import ask  # tu RAG ya funcionando

mcp = FastMCP("local-rag-ide")

# -------------------------
# FILE SYSTEM
# -------------------------

@mcp.tool()
def list_fs(path: str = "C:\\wamp64\\www\\laboratorio"):
    return list_files(path)


@mcp.tool()
def read_fs(path: str):
    return read_file(path)


@mcp.tool()
def search_fs(query: str):
    return search_files(query)


# -------------------------
# RAG ENGINE
# -------------------------

@mcp.tool()
def ask_rag(question: str):
    return ask(question)


# -------------------------
# ENTRYPOINT
# -------------------------

if __name__ == "__main__":
    mcp.run()