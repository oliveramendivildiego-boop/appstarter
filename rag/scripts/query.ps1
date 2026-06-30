param()

## Detecta y activa rag-env o .venv
$venvPath = $null
if (Test-Path "rag-env") { $venvPath = "rag-env" }
elseif (Test-Path ".venv") { $venvPath = ".venv" }
else {
    Write-Host "No se encontró virtualenv. Ejecuta scripts\ingest.ps1 primero o crea un virtualenv manualmente." -ForegroundColor Yellow
}

if ($venvPath) {
    Write-Host "Activando $venvPath..."
    . .\$venvPath\Scripts\Activate.ps1
}

Write-Host "Ejecutando CLI de consulta (main_query.py). Usa Ctrl+C para salir." -ForegroundColor Green
python ..\main_query.py
