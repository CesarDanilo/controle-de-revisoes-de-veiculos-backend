#!/bin/sh

echo "== Rodando migrations =="
php artisan migrate --force

echo "== Cacheando config =="
php artisan config:cache

echo "== Iniciando servidor na porta ${PORT:-8000} =="
exec php -S 0.0.0.0:${PORT:-8000} -t public public/index.php