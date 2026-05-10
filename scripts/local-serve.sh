#!/bin/zsh

set -euo pipefail

cd "$(dirname "$0")/.."

PORT="${PORT:-8080}"
HOST="${HOST:-127.0.0.1}"

exec /Applications/XAMPP/xamppfiles/bin/php artisan serve --host="${HOST}" --port="${PORT}"
