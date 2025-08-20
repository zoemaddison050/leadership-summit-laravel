#!/bin/bash

echo "Switching to Docker development mode..."

# Stop any local PHP servers
pkill -f "php artisan serve" 2>/dev/null || true

# Restore Docker environment
cp .env.docker .env

# Start Docker containers
docker-compose up -d

# Wait for containers to be ready
sleep 5

# Clear config cache in container
docker-compose exec app php artisan config:clear

echo ""
echo "✅ Application is now running in Docker!"
echo "🌐 Access your app at: http://localhost:8080"
echo "🗄️  Database: MySQL (Docker container)"
echo "👤 Admin login: admin@leadershipsummit.com / password"
echo ""
echo "To stop: docker-compose down"