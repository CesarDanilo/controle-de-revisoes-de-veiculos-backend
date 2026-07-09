#!/bin/sh
set -e

php artisan config:clear
php artisan migrate --force
php artisan config:cache

echo "Iniciando servidor na porta ${PORT:-8000}..."
exec php artisan serve --host 0.0.0.0 --port ${PORT:-8000}