#!/bin/sh
set -e

echo "Aguardando o banco de dados..."
until php artisan db:show > /dev/null 2>&1; do
  sleep 2
done

echo "Rodando migrations..."
php artisan migrate --force

echo "Limpando e cacheando config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Iniciando servidor..."
php artisan serve --host=0.0.0.0 --port=8000