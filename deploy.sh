#!/bin/sh
set -eu

cd /var/www/reading_college
sudo chown -R ubuntu:ubuntu .

git reset --hard
git clean -fd
git pull origin main

sudo chown -R www-data:www-data storage bootstrap/cache database
sudo chmod -R ug+rw storage bootstrap/cache database
sudo -u www-data php artisan migrate --force
