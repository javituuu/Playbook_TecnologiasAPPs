#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."

trap 'kill 0' EXIT
(cd laravel && php artisan serve) &
(cd ionic/playbook-jugador && npm start) &
wait
