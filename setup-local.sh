#!/bin/bash

# Leadership Summit Laravel - Local Development Setup Script
# This script sets up the local development environment

set -e

echo "🚀 Setting up Leadership Summit Laravel for local development..."

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker and try again."
    exit 1
fi

# Build and start containers
echo "📦 Building and starting Docker containers..."
docker-compose down --remove-orphans
docker-compose build --no-cache
docker-compose up -d

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
sleep 30

# Install Composer dependencies
echo "📚 Installing Composer dependencies..."
docker-compose exec app composer install

# Generate application key if not set
echo "🔑 Generating application key..."
docker-compose exec app php artisan key:generate

# Run database migrations
echo "🗄️ Running database migrations..."
docker-compose exec app php artisan migrate --force

# Seed the database with sample data
echo "🌱 Seeding database with sample data..."
docker-compose exec app php artisan db:seed

# Clear and cache configuration
echo "🧹 Clearing and caching configuration..."
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Set proper permissions
echo "🔒 Setting proper permissions..."
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chown -R www-data:www-data /var/www/html/bootstrap/cache
docker-compose exec app chmod -R 775 /var/www/html/storage
docker-compose exec app chmod -R 775 /var/www/html/bootstrap/cache

# Install Node.js dependencies and build assets
echo "🎨 Installing Node.js dependencies and building assets..."
docker-compose exec app npm install
docker-compose exec app npm run build

echo ""
echo "✅ Local development setup completed successfully!"
echo ""
echo "🌐 Application is available at: http://localhost:8000"
echo "📊 Database: MySQL on localhost:3306"
echo "   - Database: leadership_summit"
echo "   - Username: leadership_summit"
echo "   - Password: leadership_summit_password"
echo ""
echo "🔧 Useful commands:"
echo "   - View logs: docker-compose logs -f"
echo "   - Access app container: docker-compose exec app bash"
echo "   - Access MySQL: docker-compose exec mysql mysql -u leadership_summit -p leadership_summit"
echo "   - Check application health: docker-compose exec app php artisan route:list"
echo "   - Run migrations: docker-compose exec app php artisan migrate"
echo ""
echo "🛠️ To stop the environment: docker-compose down"
echo "🔄 To restart: docker-compose restart"