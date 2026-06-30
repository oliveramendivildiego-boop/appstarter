# RAG — Uso local con Ollama, Zed y Cursor-style

Este directorio contiene una plantilla RAG (Retrieval-Augmented Generation) preparada para ejecutarse localmente usando Ollama (LLM), Qdrant (vector store) y herramientas de ingestión existentes.

Guía rápida:

- **Entorno**: Python 3.10+ (recomendado). Crear un virtualenv e instalar dependencias:

```powershell
# Si tienes un virtualenv llamado rag-env (creado por pip/venv), úsalo.
if (Test-Path rag-env) {
	.\rag-env\Scripts\Activate.ps1
} else {
	python -m venv .venv
	.\.venv\Scripts\Activate.ps1
}
pip install -r requirements.txt
```

- **Servicios**:
	- Levanta Qdrant en `http://localhost:6333` (o define `QDRANT_URL`).
	- Instala y arranca Ollama en tu máquina y asegúrate que su API esté accesible en `http://localhost:11434` (o define `OLLAMA_BASE_URL`).

- **Configurar modelos**: Edita `config.py` o exporta variables de entorno `EMBED_MODEL` y `LLM_MODEL` para usar los modelos que tengas descargados en Ollama.

- **Ingestar datos** (ejemplo):

```powershell
\.\scripts\ingest.ps1
```

- **Consultar** (CLI interactiva):

```powershell
\.\scripts\query.ps1
```

- **Validar desde Zed**: Usa `zed_validate.py` para ejecutar una consulta y recibir JSON con la respuesta (útil para acciones y pruebas desde el editor).

Archivos añadidos/útiles:

- `scripts/ingest.ps1` — Script PowerShell para preparar entorno e ingestar.
- `scripts/query.ps1` — Script PowerShell para ejecutar la CLI de consulta.
- `zed_validate.py` — Script ligero para validar queries desde Zed (stdin/stdout JSON).
- `prompts/cursor_style.md` — Plantilla de prompt para emular el estilo de Cursor.

Notas y recomendaciones:

- Para que el comportamiento sea similar a Cursor, usa los prompts en `prompts/cursor_style.md` y afina `LLM_MODEL` / `temperature` en `llm.py`.
- Si prefieres exponer Ollama vía HTTP, revisa la documentación de Ollama y ajusta `OLLAMA_BASE_URL`.

Si quieres, automatizo la descarga de un modelo de pruebas y hago un run demo.
