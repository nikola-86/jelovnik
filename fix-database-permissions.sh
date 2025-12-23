#!/bin/bash
# Fix database file permissions for SQLite

echo "Fixing database permissions..."

# Fix permissions on host (WSL)
chmod 664 database/database.sqlite 2>/dev/null || true
chmod 775 database 2>/dev/null || true

# Fix permissions in Docker container
docker exec jelovnik-php chmod 664 /var/www/html/database/database.sqlite 2>/dev/null || true
docker exec jelovnik-php chmod 775 /var/www/html/database 2>/dev/null || true
docker exec jelovnik-php chown www-data:www-data /var/www/html/database/database.sqlite 2>/dev/null || true
docker exec jelovnik-php chown www-data:www-data /var/www/html/database 2>/dev/null || true

echo "Database permissions fixed!"
echo ""
echo "If the issue persists, you may need to rebuild the Docker container:"
echo "  docker compose down"
echo "  docker compose build --no-cache"
echo "  docker compose up -d"

