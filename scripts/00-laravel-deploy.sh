#!/usr/bin/env bash
echo "Instalando dependências do Composer..."
composer install --no-dev --working-dir=/var/www/html --optimize-autoloader

echo "Rodando migrations (conexão direta, sem pooler)..."
DB_HOST="${DB_HOST_DIRECT:-$DB_HOST}" php artisan migrate --force

echo "Gerando cache de configuração e rotas..."
php artisan config:cache
php artisan route:cache