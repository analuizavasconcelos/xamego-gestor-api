#!/usr/bin/env bash
set -e

echo "Rodando migrations..."
DB_HOST="${DB_HOST_DIRECT:-$DB_HOST}" php artisan migrate --force

echo "Limpando caches antigos e gerando cache de configuração..."
php artisan config:clear
php artisan route:clear
php artisan config:cache

echo "Iniciando servidor na porta ${PORT:-10000}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"