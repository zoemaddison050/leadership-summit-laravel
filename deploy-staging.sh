#!/bin/bash

# Leadership Summit Laravel - Staging Deployment Script
# This script deploys the application to the staging environment

set -e

echo "🚀 Starting staging deployment..."

# Configuration
STAGING_ENV_FILE=".env.staging"
DOCKER_COMPOSE_FILE="docker-compose.staging.yml"
APP_CONTAINER="leadership-summit-staging"
DB_CONTAINER="leadership-summit-staging-db"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if required files exist
if [ ! -f "$STAGING_ENV_FILE" ]; then
    print_error "Staging environment file not found: $STAGING_ENV_FILE"
    exit 1
fi

if [ ! -f "$DOCKER_COMPOSE_FILE" ]; then
    print_error "Docker compose file not found: $DOCKER_COMPOSE_FILE"
    exit 1
fi

# Stop existing containers
print_status "Stopping existing containers..."
docker-compose -f $DOCKER_COMPOSE_FILE down || true

# Build and start containers
print_status "Building and starting containers..."
docker-compose -f $DOCKER_COMPOSE_FILE up -d --build

# Wait for database to be ready
print_status "Waiting for database to be ready..."
sleep 30

# Copy staging environment file
print_status "Setting up environment configuration..."
docker exec $APP_CONTAINER cp $STAGING_ENV_FILE .env

# Generate application key
print_status "Generating application key..."
docker exec $APP_CONTAINER php artisan key:generate --force

# Install dependencies
print_status "Installing dependencies..."
docker exec $APP_CONTAINER composer install --no-dev --optimize-autoloader

# Run database migrations
print_status "Running database migrations..."
docker exec $APP_CONTAINER php artisan migrate --force

# Seed database if needed
print_status "Seeding database..."
docker exec $APP_CONTAINER php artisan db:seed --force

# Clear and cache configuration
print_status "Optimizing application..."
docker exec $APP_CONTAINER php artisan config:cache
docker exec $APP_CONTAINER php artisan route:cache
docker exec $APP_CONTAINER php artisan view:cache

# Build frontend assets
print_status "Building frontend assets..."
docker exec $APP_CONTAINER npm ci
docker exec $APP_CONTAINER npm run build

# Set proper permissions
print_status "Setting file permissions..."
docker exec $APP_CONTAINER chown -R www-data:www-data /var/www/storage
docker exec $APP_CONTAINER chown -R www-data:www-data /var/www/bootstrap/cache

# Run comprehensive validation
print_status "Running comprehensive validation..."
sleep 10

if [ -f "scripts/validate-staging.sh" ]; then
    print_status "Running staging validation script..."
    if ./scripts/validate-staging.sh; then
        print_status "✅ Staging validation passed!"
    else
        print_warning "⚠️  Staging validation completed with issues"
        print_warning "Review validation results before proceeding"
    fi
else
    # Fallback basic health check
    if curl -f -s http://localhost > /dev/null; then
        print_status "✅ Basic health check passed!"
    else
        print_error "❌ Application is not responding"
        print_warning "Check logs with: docker-compose -f $DOCKER_COMPOSE_FILE logs"
        exit 1
    fi
fi

# Display container status
print_status "Container status:"
docker-compose -f $DOCKER_COMPOSE_FILE ps

echo ""
print_status "🎉 Staging deployment completed!"
print_status "Next steps:"
echo "  1. Run data migration test: ./test-migration.sh"
echo "  2. Test application functionality manually"
echo "  3. Verify all features are working correctly"
echo "  4. Check logs: docker-compose -f $DOCKER_COMPOSE_FILE logs -f"
echo "  5. Run full validation: ./scripts/validate-staging.sh"