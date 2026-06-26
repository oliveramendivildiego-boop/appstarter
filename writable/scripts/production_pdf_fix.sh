#!/usr/bin/env bash
# Arreglo rápido PDF en producción Linux — ejecutar desde la raíz del proyecto:
#   cd /ruta/laboratorio && sudo bash writable/scripts/production_pdf_fix.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

echo "=== Fix PDF Chromium — $ROOT ==="

if [[ "$(id -u)" -ne 0 ]]; then
    echo "Ejecute con sudo: sudo bash writable/scripts/production_pdf_fix.sh"
    exit 1
fi

export DEBIAN_FRONTEND=noninteractive

if command -v apt-get >/dev/null 2>&1; then
    apt-get update -qq
    apt-get install -y chromium-browser 2>/dev/null \
        || apt-get install -y chromium 2>/dev/null \
        || apt-get install -y google-chrome-stable 2>/dev/null \
        || apt-get install -y google-chrome
elif command -v dnf >/dev/null 2>&1; then
    dnf install -y chromium || dnf install -y google-chrome-stable
elif command -v yum >/dev/null 2>&1; then
    yum install -y chromium || yum install -y google-chrome-stable
else
    echo "ERROR: Instale chromium manualmente (apt/dnf/yum no disponible)."
    exit 1
fi

CHROME=""
for p in /usr/bin/chromium /usr/bin/chromium-browser /usr/bin/google-chrome-stable /usr/bin/google-chrome /snap/bin/chromium; do
    if [[ -x "$p" ]]; then CHROME="$p"; break; fi
done
if [[ -z "$CHROME" ]]; then
    CHROME="$(command -v chromium-browser || command -v chromium || command -v google-chrome-stable || true)"
fi
if [[ -z "$CHROME" || ! -x "$CHROME" ]]; then
    echo "ERROR: Chromium instalado pero no se encontró el ejecutable."
    exit 1
fi

echo "Chromium: $CHROME"

ENV_FILE="$ROOT/.env"
LINE="CHROME_EXECUTABLE_PATH=$CHROME"
if [[ ! -f "$ENV_FILE" ]]; then
    printf '%s\n' "$LINE" > "$ENV_FILE"
    echo "Creado .env con $LINE"
else
    if grep -q '^CHROME_EXECUTABLE_PATH' "$ENV_FILE"; then
        sed -i.bak "s|^CHROME_EXECUTABLE_PATH.*|$LINE|" "$ENV_FILE"
    else
        printf '\n# PDF Chromium\n%s\n' "$LINE" >> "$ENV_FILE"
    fi
    echo "Actualizado .env → $LINE"
fi

chown --reference="$ROOT/public/index.php" "$ENV_FILE" 2>/dev/null || true

echo "Prueba headless..."
TMP="$(mktemp /tmp/lab-pdf-XXXXXX.pdf)"
"$CHROME" --headless=new --disable-gpu --no-sandbox --print-to-pdf="$TMP" about:blank
echo "PDF prueba: $(wc -c < "$TMP") bytes"
rm -f "$TMP"

if [[ -f "$ROOT/writable/scripts/pdf_chrome_doctor.php" ]]; then
    sudo -u www-data php "$ROOT/writable/scripts/pdf_chrome_doctor.php" 2>/dev/null \
        || sudo -u apache php "$ROOT/writable/scripts/pdf_chrome_doctor.php" 2>/dev/null \
        || php "$ROOT/writable/scripts/pdf_chrome_doctor.php" || true
fi

echo ""
echo "=== LISTO. Recargue el reporte PDF en el navegador. ==="
