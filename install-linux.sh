#!/usr/bin/env bash
set -e
cp -n .env.example .env || true
composer install
php artisan key:generate
php artisan migrate:fresh --seed
npm install
npm run build
echo "Installation complete. Run: php artisan serve"
