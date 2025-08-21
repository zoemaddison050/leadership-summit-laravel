#!/bin/bash

echo "Switching to local development mode..."

# Stop any existing local servers
pkill -f "php artisan serve" 2>/dev/null || true

# Stop Docker containers (but keep MySQL for data)
docker-compose stop app nginx

# Copy local environment (uses SQLite)
cp .env.local .env

# Clear config cache
php artisan config:clear

# Ensure SQLite database exists
touch database/database.sqlite

# Start local development server
echo "Starting local development server on http://localhost:8000"
echo "Using SQLite database for local development"
php artisan serve --host=127.0.0.1 --port=8000 &

sleep 2
echo ""
echo "✅ Application is now running locally!"
echo "🌐 Access your app at: http://localhost:8000"
echo "🗄️  Database: SQLite (database/database.sqlite)"
echo "👤 Admin login: admin@leadershipsummit.com / password"
echo ""
echo "To stop: pkill -f 'php artisan serve'"