#!/bin/bash
# Create storage directories with proper permissions

docker exec jelovnik-php mkdir -p /var/www/html/storage/app/temp
docker exec jelovnik-php chmod -R 775 /var/www/html/storage/app/temp
docker exec jelovnik-php chown -R www-data:www-data /var/www/html/storage/app/temp

echo "Storage directories created successfully!"

