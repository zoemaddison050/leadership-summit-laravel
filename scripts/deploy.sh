#!/bin/bash

# Leadership Summit Laravel - Production Deployment Script
# This script handles automated deployment to production

set -e

# Configuration
ENVIRONMENT=${1:-production}
BRANCH=${2:-main}
BACKUP_RETENTION_DAYS=30

# Load environment-specific configuration
case $ENVIRONMENT in
    "production")
        ENV_FILE=".env"
        DOCKER_COMPOSE_FILE="docker-compose.yml"
        APP_CONTAINER="leadership-summit-app"
        DB_CONTAINER="leadership-summit-db"
        DOMAIN="leadershipsummit.com"
        ;;
    "staging")
        ENV_FILE=".env.staging"
        DOCKER_COMPOSE_FILE="docker-compose.staging.yml"
        APP_CONTAINER="leadership-summit-staging"
        DB_CONTAINER="leadership-summit-staging-db"
        DOMAIN="staging.leadershipsummit.com"
        ;;
    *)
        echo "❌ Invalid environment: $ENVIRONMENT"
        echo "Usage: $0 [production|staging] [branch]"
        exit 1
        ;;
esac

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

# Function to check prerequisites
check_prerequisites() {
    print_step "Checking prerequisites..."
    
    # Check if Docker is running
    if ! docker info > /dev/null 2>&1; then
        print_error "Docker is not running"
        exit 1
    fi
    
    # Check if required files exist
    if [ ! -f "$ENV_FILE" ]; then
        print_error "Environment file not found: $ENV_FILE"
        exit 1
    fi
    
    if [ ! -f "$DOCKER_COMPOSE_FILE" ]; then
        print_error "Docker compose file not found: $DOCKER_COMPOSE_FILE"
        exit 1
    fi
    
    print_status "Prerequisites check passed"
}

# Function to create backup
create_backup() {
    print_step "Creating backup..."
    
    BACKUP_DIR="./backups/$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$BACKUP_DIR"
    
    # Backup database
    if docker ps | grep -q "$DB_CONTAINER"; then
        print_status "Backing up database..."
        docker exec "$DB_CONTAINER" mysqldump -u root -p"${DB_PASSWORD:-secret}" --all-databases > "$BACKUP_DIR/database_backup.sql"
    fi
    
    # Backup storage directory
    if [ -d "./storage" ]; then
        print_status "Backing up storage directory..."
        cp -r ./storage "$BACKUP_DIR/"
    fi
    
    # Backup environment file
    if [ -f "$ENV_FILE" ]; then
        cp "$ENV_FILE" "$BACKUP_DIR/"
    fi
    
    print_status "Backup created at: $BACKUP_DIR"
    echo "$BACKUP_DIR" > .last_backup_path
}

# Function to update code
update_code() {
    print_step "Updating code from repository..."
    
    # Stash any local changes
    git stash push -m "Pre-deployment stash $(date)"
    
    # Fetch latest changes
    git fetch origin
    
    # Checkout specified branch
    git checkout "$BRANCH"
    
    # Pull latest changes
    git pull origin "$BRANCH"
    
    print_status "Code updated to latest $BRANCH"
}

# Function to build and deploy
build_and_deploy() {
    print_step "Building and deploying application..."
    
    # Stop existing containers gracefully
    print_status "Stopping existing containers..."
    docker-compose -f "$DOCKER_COMPOSE_FILE" down --timeout 30 || true
    
    # Build new images
    print_status "Building Docker images..."
    docker-compose -f "$DOCKER_COMPOSE_FILE" build --no-cache
    
    # Start containers
    print_status "Starting containers..."
    docker-compose -f "$DOCKER_COMPOSE_FILE" up -d
    
    # Wait for containers to be ready
    print_status "Waiting for containers to be ready..."
    sleep 30
    
    # Copy environment file
    docker exec "$APP_CONTAINER" cp "$ENV_FILE" .env
    
    # Install/update dependencies
    print_status "Installing dependencies..."
    docker exec "$APP_CONTAINER" composer install --no-dev --optimize-autoloader --no-interaction
    
    # Generate application key if needed
    if ! docker exec "$APP_CONTAINER" php artisan key:generate --show | grep -q "base64:"; then
        print_status "Generating application key..."
        docker exec "$APP_CONTAINER" php artisan key:generate --force
    fi
    
    # Run database migrations
    print_status "Running database migrations..."
    docker exec "$APP_CONTAINER" php artisan migrate --force
    
    # Clear and optimize caches
    print_status "Optimizing application..."
    docker exec "$APP_CONTAINER" php artisan config:cache
    docker exec "$APP_CONTAINER" php artisan route:cache
    docker exec "$APP_CONTAINER" php artisan view:cache
    docker exec "$APP_CONTAINER" php artisan event:cache
    
    # Build frontend assets
    print_status "Building frontend assets..."
    docker exec "$APP_CONTAINER" npm ci --production
    docker exec "$APP_CONTAINER" npm run build
    
    # Set proper permissions
    print_status "Setting file permissions..."
    docker exec "$APP_CONTAINER" chown -R www-data:www-data /var/www/storage
    docker exec "$APP_CONTAINER" chown -R www-data:www-data /var/www/bootstrap/cache
    docker exec "$APP_CONTAINER" chmod -R 775 /var/www/storage
    docker exec "$APP_CONTAINER" chmod -R 775 /var/www/bootstrap/cache
}

# Function to run health checks
run_health_checks() {
    print_step "Running health checks..."
    
    # Wait for application to be ready
    sleep 10
    
    # Check if containers are running
    if ! docker-compose -f "$DOCKER_COMPOSE_FILE" ps | grep -q "Up"; then
        print_error "Some containers are not running"
        return 1
    fi
    
    # Check database connection
    if ! docker exec "$APP_CONTAINER" php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';" | grep -q "OK"; then
        print_error "Database connection failed"
        return 1
    fi
    
    # Check web server response
    local max_attempts=10
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        if curl -f -s "http://localhost" > /dev/null; then
            print_status "✅ Application is responding"
            return 0
        fi
        
        print_status "Attempt $attempt/$max_attempts: Waiting for application..."
        sleep 5
        ((attempt++))
    done
    
    print_error "Application is not responding after $max_attempts attempts"
    return 1
}

# Function to cleanup old backups
cleanup_old_backups() {
    print_step "Cleaning up old backups..."
    
    if [ -d "./backups" ]; then
        find ./backups -type d -mtime +$BACKUP_RETENTION_DAYS -exec rm -rf {} + 2>/dev/null || true
        print_status "Old backups cleaned up (older than $BACKUP_RETENTION_DAYS days)"
    fi
}

# Function to rollback deployment
rollback() {
    print_error "Deployment failed, initiating rollback..."
    
    if [ -f ".last_backup_path" ]; then
        BACKUP_PATH=$(cat .last_backup_path)
        
        if [ -d "$BACKUP_PATH" ]; then
            print_status "Rolling back to backup: $BACKUP_PATH"
            
            # Stop current containers
            docker-compose -f "$DOCKER_COMPOSE_FILE" down || true
            
            # Restore database
            if [ -f "$BACKUP_PATH/database_backup.sql" ]; then
                print_status "Restoring database..."
                docker-compose -f "$DOCKER_COMPOSE_FILE" up -d "$DB_CONTAINER"
                sleep 10
                docker exec -i "$DB_CONTAINER" mysql -u root -p"${DB_PASSWORD:-secret}" < "$BACKUP_PATH/database_backup.sql"
            fi
            
            # Restore storage
            if [ -d "$BACKUP_PATH/storage" ]; then
                print_status "Restoring storage..."
                rm -rf ./storage
                cp -r "$BACKUP_PATH/storage" ./
            fi
            
            # Restore environment file
            if [ -f "$BACKUP_PATH/$(basename $ENV_FILE)" ]; then
                cp "$BACKUP_PATH/$(basename $ENV_FILE)" "$ENV_FILE"
            fi
            
            # Start containers with previous version
            docker-compose -f "$DOCKER_COMPOSE_FILE" up -d
            
            print_status "Rollback completed"
        else
            print_error "Backup directory not found: $BACKUP_PATH"
        fi
    else
        print_error "No backup path found for rollback"
    fi
}

# Main deployment process
main() {
    echo "🚀 Starting deployment to $ENVIRONMENT environment..."
    echo "Branch: $BRANCH"
    echo "Time: $(date)"
    echo ""
    
    # Trap errors and rollback
    trap 'rollback; exit 1' ERR
    
    check_prerequisites
    create_backup
    update_code
    build_and_deploy
    
    if run_health_checks; then
        cleanup_old_backups
        
        echo ""
        print_status "🎉 Deployment completed successfully!"
        print_status "Application is available at: https://$DOMAIN"
        
        # Display container status
        echo ""
        print_status "Container status:"
        docker-compose -f "$DOCKER_COMPOSE_FILE" ps
        
        # Log deployment
        echo "$(date): Successful deployment to $ENVIRONMENT (branch: $BRANCH)" >> deployment.log
    else
        print_error "Health checks failed"
        exit 1
    fi
}

# Run main function
main "$@"