#!/bin/sh

echo "== Limpando config =="
php artisan config:clear

echo "== Rodando migrations =="
php artisan migrate --force

echo "== Cacheando config =="
php artisan config:cache || echo "FALHOU no config:cache"

echo "== Iniciando servidor na porta ${PORT:-8000} =="
exec php artisan serve --host 0.0.0.0 --port ${PORT:-8000}