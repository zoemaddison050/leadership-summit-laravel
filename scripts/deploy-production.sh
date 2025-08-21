#!/bin/bash

# Leadership Summit Laravel - Production Deployment Script
# This script handles secure production deployment with comprehensive checks

set -e

# Configuration
BRANCH=${1:-main}
SKIP_BACKUP=${2:-false}
DOCKER_COMPOSE_FILE="docker-compose.production.yml"
ENV_FILE=".env.production"
APP_CONTAINER="leadership-summit-app"
DB_CONTAINER="leadership-summit-db"
DOMAIN="leadershipsummit.com"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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
    print_step "Checking production deployment prerequisites..."
    
    # Check if running as appropriate user
    if [ "$EUID" -eq 0 ]; then
        print_error "Do not run production deployment as root"
        exit 1
    fi
    
    # Check if Docker is running
    if ! docker info > /dev/null 2>&1; then
        print_error "Docker is not running"
        exit 1
    fi
    
    # Check if required files exist
    REQUIRED_FILES=(
        "$ENV_FILE"
        "$DOCKER_COMPOSE_FILE"
        "docker/nginx/production/app.conf"
        "docker/ssl/leadershipsummit.com.crt"
        "docker/ssl/leadershipsummit.com.key"
        "Dockerfile"
    )
    
    for file in "${REQUIRED_FILES[@]}"; do
        if [ ! -f "$file" ]; then
            print_error "Required file not found: $file"
            exit 1
        fi
    done
    
    # Check environment configuration
    if grep -q "GENERATE_NEW_KEY_FOR_PRODUCTION" "$ENV_FILE"; then
        print_error "Production APP_KEY not configured in $ENV_FILE"
        exit 1
    fi
    
    if grep -q "CHANGE_THIS_PASSWORD" "$ENV_FILE"; then
        print_error "Production passwords not configured in $ENV_FILE"
        exit 1
    fi
    
    # Check webhook configuration
    if ! grep -q "WEBHOOK_BASE_URL" "$ENV_FILE"; then
        print_error "WEBHOOK_BASE_URL not configured in $ENV_FILE"
        exit 1
    fi
    
    if ! grep -q "UNIPAYMENT_WEBHOOK_SECRET" "$ENV_FILE"; then
        print_warning "UNIPAYMENT_WEBHOOK_SECRET not configured - webhook signature validation will be disabled"
    fi
    
    # Check SSL certificates
    if ! openssl x509 -in docker/ssl/leadershipsummit.com.crt -noout -checkend 86400; then
        print_error "SSL certificate is expired or will expire within 24 hours"
        exit 1
    fi
    
    # Check disk space (require at least 5GB free)
    AVAILABLE_SPACE=$(df . | tail -1 | awk '{print $4}')
    if [ "$AVAILABLE_SPACE" -lt 5242880 ]; then  # 5GB in KB
        print_error "Insufficient disk space. At least 5GB required."
        exit 1
    fi
    
    print_status "✅ Prerequisites check passed"
}

# Function to perform security checks
security_checks() {
    print_step "Performing security checks..."
    
    # Check file permissions
    if [ -r "$ENV_FILE" ]; then
        PERMS=$(stat -c "%a" "$ENV_FILE")
        if [ "$PERMS" != "600" ]; then
            print_warning "Environment file permissions should be 600"
            chmod 600 "$ENV_FILE"
        fi
    fi
    
    # Check for sensitive data in git
    if git log --all --full-history -- "$ENV_FILE" | grep -q "commit"; then
        print_warning "Environment file found in git history"
    fi
    
    # Verify SSL certificate matches domain
    CERT_DOMAIN=$(openssl x509 -in docker/ssl/leadershipsummit.com.crt -noout -subject | grep -o "CN=[^,]*" | cut -d= -f2)
    if [ "$CERT_DOMAIN" != "$DOMAIN" ]; then
        print_error "SSL certificate domain mismatch: $CERT_DOMAIN != $DOMAIN"
        exit 1
    fi
    
    print_status "✅ Security checks passed"
}

# Function to create comprehensive backup
create_backup() {
    if [ "$SKIP_BACKUP" = "true" ]; then
        print_warning "Skipping backup as requested"
        return 0
    fi
    
    print_step "Creating comprehensive production backup..."
    
    BACKUP_DIR="./backups/production/$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$BACKUP_DIR"
    
    # Backup database
    if docker ps | grep -q "$DB_CONTAINER"; then
        print_status "Backing up production database..."
        docker exec "$DB_CONTAINER" mysqldump \
            -u root -p"${DB_PASSWORD}" \
            --single-transaction \
            --routines \
            --triggers \
            --events \
            --add-drop-database \
            --databases leadership_summit > "$BACKUP_DIR/database_backup.sql"
        
        gzip "$BACKUP_DIR/database_backup.sql"
        print_status "Database backup created and compressed"
    fi
    
    # Backup storage directory
    if [ -d "./storage" ]; then
        print_status "Backing up storage directory..."
        tar -czf "$BACKUP_DIR/storage_backup.tar.gz" ./storage
        print_status "Storage backup created"
    fi
    
    # Backup environment files
    cp "$ENV_FILE" "$BACKUP_DIR/"
    
    # Create deployment info file
    cat > "$BACKUP_DIR/deployment_info.txt" << EOF
Deployment Date: $(date)
Git Branch: $BRANCH
Git Commit: $(git rev-parse HEAD)
Git Commit Message: $(git log -1 --pretty=%B)
Environment: production
Backup Directory: $BACKUP_DIR
EOF
    
    print_status "Backup created at: $BACKUP_DIR"
    echo "$BACKUP_DIR" > .last_backup_path
}

# Function to update code with safety checks
update_code() {
    print_step "Updating code with safety checks..."
    
    # Verify we're on the correct branch
    CURRENT_BRANCH=$(git branch --show-current)
    if [ "$CURRENT_BRANCH" != "$BRANCH" ]; then
        print_status "Switching from $CURRENT_BRANCH to $BRANCH"
    fi
    
    # Stash any local changes
    if ! git diff-index --quiet HEAD --; then
        print_warning "Local changes detected, stashing..."
        git stash push -m "Pre-deployment stash $(date)"
    fi
    
    # Fetch and verify remote
    git fetch origin
    
    # Check if branch exists on remote
    if ! git ls-remote --heads origin "$BRANCH" | grep -q "$BRANCH"; then
        print_error "Branch '$BRANCH' does not exist on remote"
        exit 1
    fi
    
    # Checkout and pull
    git checkout "$BRANCH"
    git pull origin "$BRANCH"
    
    # Verify commit signature if available
    if git verify-commit HEAD 2>/dev/null; then
        print_status "✅ Commit signature verified"
    else
        print_warning "⚠️  Commit signature not verified"
    fi
    
    print_status "Code updated to latest $BRANCH"
    print_status "Current commit: $(git rev-parse --short HEAD)"
}

# Function to run comprehensive tests
run_tests() {
    print_step "Running comprehensive test suite..."
    
    # Build test environment
    docker-compose -f docker-compose.staging.yml down || true
    docker-compose -f docker-compose.staging.yml up -d --build
    
    # Wait for services to be ready
    sleep 30
    
    # Run unit tests
    print_status "Running unit tests..."
    if ! docker exec leadership-summit-staging php artisan test --testsuite=Unit; then
        print_error "Unit tests failed"
        docker-compose -f docker-compose.staging.yml down
        exit 1
    fi
    
    # Run feature tests
    print_status "Running feature tests..."
    if ! docker exec leadership-summit-staging php artisan test --testsuite=Feature; then
        print_error "Feature tests failed"
        docker-compose -f docker-compose.staging.yml down
        exit 1
    fi
    
    # Run security tests
    print_status "Running security tests..."
    if [ -f "scripts/security-scan.sh" ]; then
        ./scripts/security-scan.sh staging
    fi
    
    # Cleanup test environment
    docker-compose -f docker-compose.staging.yml down
    
    print_status "✅ All tests passed"
}

# Function to deploy to production
deploy_production() {
    print_step "Deploying to production..."
    
    # Stop existing containers gracefully
    print_status "Stopping existing containers..."
    docker-compose -f "$DOCKER_COMPOSE_FILE" down --timeout 60 || true
    
    # Clean up unused Docker resources
    docker system prune -f
    
    # Build new images
    print_status "Building production Docker images..."
    docker-compose -f "$DOCKER_COMPOSE_FILE" build --no-cache --parallel
    
    # Start database first
    print_status "Starting database..."
    docker-compose -f "$DOCKER_COMPOSE_FILE" up -d db redis
    sleep 20
    
    # Start application services
    print_status "Starting application services..."
    docker-compose -f "$DOCKER_COMPOSE_FILE" up -d
    
    # Wait for all services to be ready
    print_status "Waiting for services to be ready..."
    sleep 45
    
    # Copy production environment file
    docker exec "$APP_CONTAINER" cp "$ENV_FILE" .env
    
    # Install production dependencies
    print_status "Installing production dependencies..."
    docker exec "$APP_CONTAINER" composer install \
        --no-dev \
        --optimize-autoloader \
        --no-interaction \
        --prefer-dist
    
    # Run database migrations
    print_status "Running database migrations..."
    docker exec "$APP_CONTAINER" php artisan migrate --force
    
    # Optimize application
    print_status "Optimizing application..."
    docker exec "$APP_CONTAINER" php artisan config:cache
    docker exec "$APP_CONTAINER" php artisan route:cache
    docker exec "$APP_CONTAINER" php artisan view:cache
    docker exec "$APP_CONTAINER" php artisan event:cache
    
    # Build and optimize frontend assets
    print_status "Building frontend assets..."
    docker exec "$APP_CONTAINER" npm ci --production
    docker exec "$APP_CONTAINER" npm run build
    
    # Set proper permissions
    print_status "Setting file permissions..."
    docker exec "$APP_CONTAINER" chown -R www-data:www-data /var/www/storage
    docker exec "$APP_CONTAINER" chown -R www-data:www-data /var/www/bootstrap/cache
    docker exec "$APP_CONTAINER" chmod -R 775 /var/www/storage
    docker exec "$APP_CONTAINER" chmod -R 775 /var/www/bootstrap/cache
    
    # Validate webhook configuration
    print_status "Validating webhook configuration..."
    docker exec "$APP_CONTAINER" php artisan webhook:validate-config || print_warning "Webhook validation failed"
    
    # Start queue workers and scheduler
    print_status "Starting background services..."
    docker-compose -f "$DOCKER_COMPOSE_FILE" up -d queue scheduler
}

# Function to run comprehensive health checks
run_health_checks() {
    print_step "Running comprehensive health checks..."
    
    # Wait for application to be fully ready
    sleep 30
    
    # Check container health
    print_status "Checking container health..."
    UNHEALTHY_CONTAINERS=$(docker-compose -f "$DOCKER_COMPOSE_FILE" ps | grep -v "Up (healthy)" | grep "Up" || true)
    if [ -n "$UNHEALTHY_CONTAINERS" ]; then
        print_warning "Some containers are not healthy:"
        echo "$UNHEALTHY_CONTAINERS"
    fi
    
    # Check database connection
    print_status "Testing database connection..."
    if ! docker exec "$APP_CONTAINER" php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database OK';" | grep -q "Database OK"; then
        print_error "Database connection failed"
        return 1
    fi
    
    # Check Redis connection
    print_status "Testing Redis connection..."
    if ! docker exec "$APP_CONTAINER" php artisan tinker --execute="Redis::ping(); echo 'Redis OK';" | grep -q "Redis OK"; then
        print_error "Redis connection failed"
        return 1
    fi
    
    # Check web server response
    print_status "Testing web server response..."
    local max_attempts=10
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        if curl -f -s -H "Host: $DOMAIN" "https://localhost" > /dev/null; then
            print_status "✅ HTTPS endpoint responding"
            break
        elif curl -f -s "http://localhost" > /dev/null; then
            print_status "✅ HTTP endpoint responding"
            break
        fi
        
        print_status "Attempt $attempt/$max_attempts: Waiting for web server..."
        sleep 10
        ((attempt++))
    done
    
    if [ $attempt -gt $max_attempts ]; then
        print_error "Web server not responding after $max_attempts attempts"
        return 1
    fi
    
    # Test critical endpoints
    print_status "Testing critical endpoints..."
    CRITICAL_ENDPOINTS=("/" "/events" "/login" "/health")
    
    for endpoint in "${CRITICAL_ENDPOINTS[@]}"; do
        response_code=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost$endpoint" || echo "000")
        if [ "$response_code" = "200" ] || [ "$response_code" = "302" ]; then
            print_status "✅ Endpoint '$endpoint' responding ($response_code)"
        else
            print_error "❌ Endpoint '$endpoint' failed ($response_code)"
            return 1
        fi
    done
    
    # Test webhook endpoint specifically
    print_status "Testing webhook endpoint..."
    webhook_response=$(curl -s -o /dev/null -w "%{http_code}" -X POST "http://localhost/payment/unipayment/webhook" \
        -H "Content-Type: application/json" \
        -d '{"test": "deployment"}' || echo "000")
    
    if [ "$webhook_response" = "200" ] || [ "$webhook_response" = "422" ]; then
        print_status "✅ Webhook endpoint responding ($webhook_response)"
    else
        print_warning "⚠️  Webhook endpoint returned $webhook_response - check configuration"
    fi
    
    # Performance check
    print_status "Running performance check..."
    response_time=$(curl -o /dev/null -s -w "%{time_total}" "http://localhost/" || echo "error")
    if [ "$response_time" != "error" ]; then
        if (( $(echo "$response_time < 2.0" | bc -l) )); then
            print_status "✅ Response time: ${response_time}s (good)"
        else
            print_warning "⚠️  Response time: ${response_time}s (slow)"
        fi
    fi
    
    print_status "✅ All health checks passed"
}

# Function to setup monitoring and alerts
setup_monitoring() {
    print_step "Setting up monitoring and alerts..."
    
    # Create monitoring directory
    mkdir -p ./monitoring
    
    # Setup log rotation
    cat > ./monitoring/logrotate.conf << 'EOF'
/var/www/storage/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        docker exec leadership-summit-app php artisan cache:clear
    endscript
}
EOF
    
    # Create health check endpoint
    docker exec "$APP_CONTAINER" php artisan make:controller HealthController
    
    print_status "Monitoring setup completed"
}

# Function to cleanup old deployments
cleanup_old_deployments() {
    print_step "Cleaning up old deployments..."
    
    # Remove old Docker images
    docker image prune -f
    
    # Clean up old backups (keep last 10)
    if [ -d "./backups/production" ]; then
        find ./backups/production -maxdepth 1 -type d | sort -r | tail -n +11 | xargs rm -rf 2>/dev/null || true
    fi
    
    # Clean up old logs
    find ./storage/logs -name "*.log" -mtime +30 -delete 2>/dev/null || true
    
    print_status "Cleanup completed"
}

# Main deployment process
main() {
    echo "🚀 Starting PRODUCTION deployment..."
    echo "Branch: $BRANCH"
    echo "Time: $(date)"
    echo "Domain: $DOMAIN"
    echo ""
    
    # Final confirmation for production
    print_warning "⚠️  WARNING: This will deploy to PRODUCTION!"
    read -p "Are you absolutely sure you want to continue? (yes/no): " -r
    if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
        print_status "Deployment cancelled"
        exit 0
    fi
    
    # Trap errors and provide rollback option
    trap 'print_error "Deployment failed! Run ./scripts/rollback.sh production auto to rollback"; exit 1' ERR
    
    check_prerequisites
    security_checks
    create_backup
    update_code
    run_tests
    deploy_production
    
    if run_health_checks; then
        setup_monitoring
        cleanup_old_deployments
        
        echo ""
        print_status "🎉 PRODUCTION deployment completed successfully!"
        print_status "Application is live at: https://$DOMAIN"
        
        # Display final status
        echo ""
        print_status "Container status:"
        docker-compose -f "$DOCKER_COMPOSE_FILE" ps
        
        echo ""
        print_status "Deployment summary:"
        echo "  - Branch: $BRANCH"
        echo "  - Commit: $(git rev-parse --short HEAD)"
        echo "  - Backup: $(cat .last_backup_path 2>/dev/null || echo 'None')"
        echo "  - SSL: Valid until $(openssl x509 -in docker/ssl/leadershipsummit.com.crt -noout -enddate | cut -d= -f2)"
        
        # Log successful deployment
        echo "$(date): Successful PRODUCTION deployment (branch: $BRANCH, commit: $(git rev-parse --short HEAD))" >> deployment.log
        
        echo ""
        print_status "Next steps:"
        echo "  1. Monitor application logs: docker-compose -f $DOCKER_COMPOSE_FILE logs -f"
        echo "  2. Monitor system resources"
        echo "  3. Verify all functionality manually"
        echo "  4. Update DNS if needed"
        echo "  5. Notify stakeholders of successful deployment"
        
    else
        print_error "Health checks failed"
        exit 1
    fi
}

# Show usage if invalid arguments
if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
    echo "Usage: $0 [branch] [skip_backup]"
    echo ""
    echo "Arguments:"
    echo "  branch        Git branch to deploy (default: main)"
    echo "  skip_backup   Skip backup creation (true/false, default: false)"
    echo ""
    echo "Examples:"
    echo "  $0                    # Deploy main branch with backup"
    echo "  $0 release/v2.0       # Deploy specific branch"
    echo "  $0 main true          # Deploy without backup (not recommended)"
    exit 0
fi

# Run main function
main "$@"