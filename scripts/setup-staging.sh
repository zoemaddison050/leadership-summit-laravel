#!/bin/bash

# Leadership Summit Laravel - Staging Environment Setup Script
# This script sets up and configures the complete staging environment

set -e

echo "🏗️  Setting up staging environment..."

# Configuration
STAGING_ENV_FILE=".env.staging"
DOCKER_COMPOSE_FILE="docker-compose.staging.yml"
APP_CONTAINER="leadership-summit-staging"
DB_CONTAINER="leadership-summit-staging-db"
NGINX_CONTAINER="leadership-summit-staging-nginx"
REDIS_CONTAINER="leadership-summit-staging-redis"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
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

print_step() {
    echo -e "${BLUE}[STEP]${NC} $1"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Step 1: Verify prerequisites
print_step "1. Verifying prerequisites..."

if ! command_exists docker; then
    print_error "Docker is not installed. Please install Docker first."
    exit 1
fi

if ! command_exists docker-compose; then
    print_error "Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

print_status "✅ Docker and Docker Compose are available"

# Step 2: Verify required files
print_step "2. Verifying required files..."

REQUIRED_FILES=(
    "$STAGING_ENV_FILE"
    "$DOCKER_COMPOSE_FILE"
    "docker/nginx/staging/app.conf"
    "Dockerfile"
)

for file in "${REQUIRED_FILES[@]}"; do
    if [ ! -f "$file" ]; then
        print_error "Required file not found: $file"
        exit 1
    fi
    print_status "✅ Found: $file"
done

# Step 3: Create necessary directories
print_step "3. Creating necessary directories..."

DIRECTORIES=(
    "storage/app/public"
    "storage/framework/cache"
    "storage/framework/sessions"
    "storage/framework/views"
    "storage/logs"
    "bootstrap/cache"
    "docker/ssl"
)

for dir in "${DIRECTORIES[@]}"; do
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir"
        print_status "Created directory: $dir"
    fi
done

# Step 4: Set up SSL certificates (self-signed for staging)
print_step "4. Setting up SSL certificates..."

SSL_DIR="docker/ssl"
CERT_FILE="$SSL_DIR/staging.leadershipsummit.com.crt"
KEY_FILE="$SSL_DIR/staging.leadershipsummit.com.key"

if [ ! -f "$CERT_FILE" ] || [ ! -f "$KEY_FILE" ]; then
    print_status "Generating self-signed SSL certificate for staging..."
    
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout "$KEY_FILE" \
        -out "$CERT_FILE" \
        -subj "/C=US/ST=State/L=City/O=Organization/CN=staging.leadershipsummit.com"
    
    print_status "✅ SSL certificates generated"
else
    print_status "✅ SSL certificates already exist"
fi

# Step 5: Validate environment configuration
print_step "5. Validating environment configuration..."

if grep -q "GENERATE_NEW_KEY_FOR_STAGING" "$STAGING_ENV_FILE"; then
    print_warning "⚠️  APP_KEY needs to be generated in $STAGING_ENV_FILE"
fi

if grep -q "CHANGE_THIS_PASSWORD" "$STAGING_ENV_FILE"; then
    print_warning "⚠️  Database password needs to be changed in $STAGING_ENV_FILE"
fi

# Step 6: Stop any existing containers
print_step "6. Stopping existing containers..."
docker-compose -f $DOCKER_COMPOSE_FILE down --remove-orphans || true
print_status "Existing containers stopped"

# Step 7: Build and start containers
print_step "7. Building and starting containers..."
docker-compose -f $DOCKER_COMPOSE_FILE up -d --build

print_status "Waiting for containers to start..."
sleep 30

# Step 8: Verify containers are running
print_step "8. Verifying containers are running..."

CONTAINERS=($APP_CONTAINER $DB_CONTAINER $NGINX_CONTAINER $REDIS_CONTAINER)

for container in "${CONTAINERS[@]}"; do
    if docker ps | grep -q "$container"; then
        print_status "✅ Container '$container' is running"
    else
        print_error "❌ Container '$container' is not running"
        docker-compose -f $DOCKER_COMPOSE_FILE logs "$container"
        exit 1
    fi
done

# Step 9: Configure application
print_step "9. Configuring application..."

# Copy environment file
docker exec $APP_CONTAINER cp $STAGING_ENV_FILE .env

# Generate application key
print_status "Generating application key..."
docker exec $APP_CONTAINER php artisan key:generate --force

# Install dependencies
print_status "Installing PHP dependencies..."
docker exec $APP_CONTAINER composer install --no-dev --optimize-autoloader

# Install Node.js dependencies
print_status "Installing Node.js dependencies..."
docker exec $APP_CONTAINER npm ci

# Step 10: Set up database
print_step "10. Setting up database..."

print_status "Waiting for database to be ready..."
sleep 20

# Run migrations
print_status "Running database migrations..."
docker exec $APP_CONTAINER php artisan migrate --force

# Seed database
print_status "Seeding database..."
docker exec $APP_CONTAINER php artisan db:seed --force

# Step 11: Optimize application
print_step "11. Optimizing application..."

docker exec $APP_CONTAINER php artisan config:cache
docker exec $APP_CONTAINER php artisan route:cache
docker exec $APP_CONTAINER php artisan view:cache

# Build frontend assets
print_status "Building frontend assets..."
docker exec $APP_CONTAINER npm run build

# Step 12: Set permissions
print_step "12. Setting file permissions..."

docker exec $APP_CONTAINER chown -R www-data:www-data /var/www/storage
docker exec $APP_CONTAINER chown -R www-data:www-data /var/www/bootstrap/cache
docker exec $APP_CONTAINER chmod -R 775 /var/www/storage
docker exec $APP_CONTAINER chmod -R 775 /var/www/bootstrap/cache

# Step 13: Health check
print_step "13. Running health check..."

sleep 10

# Check HTTP endpoint
if curl -f -s http://localhost > /dev/null; then
    print_status "✅ HTTP endpoint is responding"
else
    print_warning "⚠️  HTTP endpoint is not responding"
fi

# Check database connection
if docker exec $APP_CONTAINER php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';" > /dev/null 2>&1; then
    print_status "✅ Database connection is working"
else
    print_error "❌ Database connection failed"
fi

# Check Redis connection
if docker exec $APP_CONTAINER php artisan tinker --execute="Redis::ping(); echo 'OK';" > /dev/null 2>&1; then
    print_status "✅ Redis connection is working"
else
    print_warning "⚠️  Redis connection failed"
fi

# Step 14: Display summary
print_step "14. Setup complete!"

echo ""
print_status "🎉 Staging environment setup completed successfully!"
echo ""
print_status "Environment Details:"
echo "  - Application URL: http://localhost"
echo "  - HTTPS URL: https://localhost (self-signed certificate)"
echo "  - Database: MySQL 8.0 (port 3307)"
echo "  - Redis: Available for caching and sessions"
echo "  - Environment: staging"
echo ""
print_status "Container Status:"
docker-compose -f $DOCKER_COMPOSE_FILE ps
echo ""
print_status "Next Steps:"
echo "  1. Update /etc/hosts to point staging.leadershipsummit.com to localhost"
echo "  2. Run data migration test: ./test-migration.sh"
echo "  3. Test application functionality"
echo "  4. Configure monitoring and logging"
echo "  5. Set up automated backups"
echo ""
print_status "Useful Commands:"
echo "  - View logs: docker-compose -f $DOCKER_COMPOSE_FILE logs -f"
echo "  - Access app container: docker exec -it $APP_CONTAINER bash"
echo "  - Access database: docker exec -it $DB_CONTAINER mysql -u root -p"
echo "  - Stop environment: docker-compose -f $DOCKER_COMPOSE_FILE down"
echo "  - Deploy updates: ./deploy-staging.sh"