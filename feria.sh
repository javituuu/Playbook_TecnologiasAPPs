#!/usr/bin/env bash
# ==============================================================================
# Playbook · Lanzador Todo-en-Uno para Feria de Ciencias (Cloudflare Tunnel + QR)
# ==============================================================================
set -e

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LARAVEL_DIR="$ROOT_DIR/laravel"
IONIC_DIR="$ROOT_DIR/ionic/playbook-jugador"
PORT="${PORT:-8000}"

echo "================================================================"
echo " 🏆 Playbook · Modo Feria de Ciencias"
echo "================================================================"

# 1. Compilar la Web App del Jugador (Ionic) dentro de laravel/public/jugador
if [ "$1" = "--skip-build" ] && [ -f "$LARAVEL_DIR/public/jugador/index.html" ]; then
    echo "⏭️  Omitiendo compilación de Ionic (--skip-build)..."
else
    echo "📦 [1/4] Compilando Web App del Jugador (Ionic -> laravel/public/jugador)..."
    cd "$IONIC_DIR"
    if [ ! -d "node_modules" ]; then
        echo "   Instalando dependencias de Ionic (npm install)..."
        npm install
    fi
    ./node_modules/.bin/ng build --configuration production --base-href /jugador/ --output-path "$LARAVEL_DIR/public/jugador"

    # En versiones modernas de Angular, los archivos se generan dentro de un subdirectorio "browser/"
    if [ -d "$LARAVEL_DIR/public/jugador/browser" ]; then
        cp -r "$LARAVEL_DIR/public/jugador/browser/"* "$LARAVEL_DIR/public/jugador/"
    fi

    # Generar index.php para que el servidor integrado de PHP (artisan serve) sirva el index.html en /jugador/
    cat << 'EOF' > "$LARAVEL_DIR/public/jugador/index.php"
<?php
// Fallback para el servidor de desarrollo de PHP
readfile(__DIR__ . '/index.html');
EOF
fi

# 2. Preparar base de datos y estado inicial de la demo en Laravel
echo "🗄️  [2/4] Preparando base de datos y sembrando estado de demostración..."
cd "$LARAVEL_DIR"
if [ ! -f ".env" ]; then
    cp .env.example .env
    php artisan key:generate
fi

# Intentar demo:reset rápido; si las tablas aún no existen, correr migrate:fresh --seed
if ! php artisan demo:reset 2>/dev/null; then
    php artisan migrate:fresh --seed
fi

# 3. Iniciar servidor de Laravel en segundo plano
echo "🚀 [3/4] Iniciando servidor unificado (Panel Liga + App Jugador + API) en puerto $PORT..."
php artisan serve --host=0.0.0.0 --port="$PORT" > /tmp/playbook-laravel.log 2>&1 &
LARAVEL_PID=$!

cleanup() {
    echo ""
    echo "🛑 Cerrando servidores de Playbook..."
    kill "$LARAVEL_PID" 2>/dev/null || true
    [ -n "${CF_PID:-}" ] && kill "$CF_PID" 2>/dev/null || true
    exit 0
}
trap cleanup INT TERM

sleep 1

# 4. Iniciar Cloudflare Tunnel si está instalado
if command -v cloudflared >/dev/null 2>&1; then
    echo "☁️  [4/4] Abriendo túnel público HTTPS con Cloudflare..."
    CF_LOG="/tmp/playbook-cloudflared.log"
    rm -f "$CF_LOG"
    cloudflared tunnel --url "http://localhost:$PORT" > "$CF_LOG" 2>&1 &
    CF_PID=$!

    PUBLIC_URL=""
    for _ in $(seq 1 30); do
        if grep -qE "https://[a-zA-Z0-9-]+\.trycloudflare\.com" "$CF_LOG" 2>/dev/null; then
            PUBLIC_URL=$(grep -oE "https://[a-zA-Z0-9-]+\.trycloudflare\.com" "$CF_LOG" | head -n 1)
            break
        fi
        sleep 1
    done

    if [ -n "$PUBLIC_URL" ]; then
        # Exportar para que el modal QR de Laravel lo tome por defecto si se reinicia o lee env
        export PLAYBOOK_PUBLIC_URL="$PUBLIC_URL"
        JUGADOR_URL="${PUBLIC_URL}/jugador/"

        echo ""
        echo "================================================================"
        echo " ✅ ¡LISTO PARA LA FERIA!"
        echo "================================================================"
        echo " 🖥️  Panel Administrador de Liga (tu PC):"
        echo "     -> http://localhost:$PORT"
        echo "     -> $PUBLIC_URL"
        echo ""
        echo " 📱 Web App del Jugador (para celulares / QR):"
        echo "     -> $JUGADOR_URL"
        echo ""
        echo " 🔄 Para reiniciar la BD entre visitantes:"
        echo "     1) Haz clic en 'Reiniciar Demo' en la barra lateral del panel web"
        echo "     2) O ejecuta en otra terminal: cd laravel && php artisan demo:reset"
        echo "================================================================"

        if command -v qrencode >/dev/null 2>&1; then
            echo ""
            echo " Escanea este QR con cualquier celular:"
            qrencode -t ansiutf8 "$JUGADOR_URL"
        else
            echo " 💡 Tip: Haz clic en 'QR App Jugador' en la barra lateral del Panel Web"
            echo "    y pega '$JUGADOR_URL' (o abre el panel directamente desde '$PUBLIC_URL')."
        fi
    else
        echo "⚠️  No se pudo detectar la URL de Cloudflare a tiempo. Revisa $CF_LOG"
    fi

    wait "$CF_PID"
else
    echo ""
    echo "⚠️  'cloudflared' no está instalado todavía."
    echo "   • Panel Administrador de Liga: http://localhost:$PORT"
    echo "   • Web App del Jugador:         http://localhost:$PORT/jugador/"
    echo "   (Instala cloudflared siguiendo README_FERIA.md para exponer por internet con QR)"
    wait "$LARAVEL_PID"
fi
