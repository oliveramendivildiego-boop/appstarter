#!/usr/bin/env bash
# Instala Chromium/Chrome para PDF headless del laboratorio.
# Uso (desde la raíz del proyecto):
#   sudo bash writable/scripts/install_chromium_linux.sh
#   sudo bash writable/scripts/install_chromium_linux.sh --write-env
set -euo pipefail

WRITE_ENV=0
for arg in "$@"; do
    if [[ "$arg" == "--write-env" ]]; then
        WRITE_ENV=1
    fi
done

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

echo "==> Proyecto: $ROOT"

find_chrome() {
    local name path
    for name in chromium-browser chromium google-chrome-stable google-chrome; do
        path="$(command -v "$name" 2>/dev/null || true)"
        if [[ -n "$path" && -x "$path" ]]; then
            echo "$path"
            return 0
        fi
    done
    for path in \
        /usr/bin/chromium-browser \
        /usr/bin/chromium \
        /usr/bin/google-chrome-stable \
        /usr/bin/google-chrome \
        /snap/bin/chromium; do
        if [[ -x "$path" ]]; then
            echo "$path"
            return 0
        fi
    done
    return 1
}

EXISTING="$(find_chrome || true)"
if [[ -n "$EXISTING" ]]; then
    echo "==> Ya instalado: $EXISTING"
    CHROME="$EXISTING"
else
    echo "==> Instalando navegador headless..."
    if command -v apt-get >/dev/null 2>&1; then
        export DEBIAN_FRONTEND=noninteractive
        apt-get update -qq
        if apt-get install -y chromium-browser 2>/dev/null; then
            :
        elif apt-get install -y chromium 2>/dev/null; then
            :
        else
            apt-get install -y google-chrome-stable || apt-get install -y google-chrome
        fi
    elif command -v dnf >/dev/null 2>&1; then
        dnf install -y chromium || dnf install -y google-chrome-stable
    elif command -v yum >/dev/null 2>&1; then
        yum install -y chromium || yum install -y google-chrome-stable
    else
        echo "ERROR: No se encontró apt-get, dnf ni yum. Instale Chromium manualmente."
        exit 1
    fi
    CHROME="$(find_chrome)" || {
        echo "ERROR: Paquete instalado pero no se encontró el ejecutable."
        exit 1
    }
    echo "==> Instalado: $CHROME"
fi

echo "==> Prueba headless..."
TMP_PDF="$(mktemp /tmp/lab-chromium-test-XXXXXX.pdf)"
if ! "$CHROME" --headless=new --disable-gpu --no-sandbox --print-to-pdf="$TMP_PDF" about:blank 2>/dev/null; then
    echo "AVISO: La prueba headless falló. Puede faltar libnss3 o permisos. PDF del laboratorio puede fallar igual."
else
    echo "==> Prueba OK ($(wc -c < "$TMP_PDF") bytes)"
    rm -f "$TMP_PDF"
fi

ENV_LINE="CHROME_EXECUTABLE_PATH=$CHROME"
echo ""
echo "Añada al .env del servidor:"
echo "  $ENV_LINE"

if [[ "$WRITE_ENV" -eq 1 ]]; then
    ENV_FILE="$ROOT/.env"
    if [[ ! -f "$ENV_FILE" ]]; then
        echo "$ENV_LINE" >> "$ENV_FILE"
        echo "==> Escrito en .env (archivo nuevo)"
    else
        if grep -q '^CHROME_EXECUTABLE_PATH' "$ENV_FILE"; then
            sed -i.bak "s|^CHROME_EXECUTABLE_PATH.*|$ENV_LINE|" "$ENV_FILE"
            echo "==> Actualizado CHROME_EXECUTABLE_PATH en .env (backup: .env.bak)"
        else
            printf '\n# PDF Chromium headless\n%s\n' "$ENV_LINE" >> "$ENV_FILE"
            echo "==> Añadido CHROME_EXECUTABLE_PATH a .env"
        fi
    fi
fi

echo ""
echo "Verifique con: php writable/scripts/pdf_chrome_doctor.php"
