param()

Write-Host "Preparando entorno virtual y dependencias..."
# Prioriza rag-env si existe, si no usa .venv (crea .venv si ninguno existe)
$venvPath = $null
if (Test-Path "rag-env") { $venvPath = "rag-env" }
elseif (Test-Path ".venv") { $venvPath = ".venv" }
else {
    Write-Host "No se encontró virtualenv; creando .venv..."
    python -m venv .venv
    $venvPath = ".venv"
}

Write-Host "Activando entorno: $venvPath"
. .\$venvPath\Scripts\Activate.ps1
Write-Host "Instalando dependencias..."
pip install -r ..\requirements.txt

Write-Host "Ejecutando ingest (main_ingest.py)"
python ..\main_ingest.py

Write-Host "Ingest completado. Revisa logs/ para detalles." 
