#!/usr/bin/env bash
set -Eeuo pipefail

# Repõe o modo online mesmo quando uma migração falha durante a janela de manutenção.
restore_site() {
    php artisan up
}

# O Forge já atualizou o checkout; instala dependências, assets e migrações sem interromper pedidos em curso.
composer install --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan down --retry=60
trap restore_site EXIT
php artisan migrate --force
php artisan optimize
php artisan queue:restart
