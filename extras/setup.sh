#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"

sudo pacman -S --needed --noconfirm php php-sqlite composer
printf 'extension=pdo_sqlite\nextension=sqlite3\n' | sudo tee /etc/php/conf.d/sqlite.ini >/dev/null

if ! command -v npm >/dev/null; then sudo pacman -S --needed --noconfirm nodejs npm; fi

cd laravel
fresh=0; [ -f database/database.sqlite ] || fresh=1
composer setup
if [ "$fresh" = 1 ]; then php artisan db:seed --force; fi

cd ../ionic/playbook-jugador
npm ci
