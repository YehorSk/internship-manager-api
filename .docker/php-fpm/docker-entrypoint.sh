#!/usr/bin/env bash
set -e
for dir in /var/www/*; do
  chown -R www-data:www-data "$dir"
done
chmod -R 775 /var/www/internship-manager-api/storage /var/www/internship-manager-api/bootstrap/cache
exec php-fpm -F
