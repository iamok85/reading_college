#!/usr/bin/env bash
set -euo pipefail

cd /var/www/reading_college

git pull origin main
composer install --no-dev --optimize-autoloader
php artisan config:clear
php artisan view:clear
php artisan cache:clear
sudo chown -R www-data:www-data storage database
sudo chmod -R 775 storage database
sudo systemctl reload apache2

echo "Deploy complete."
