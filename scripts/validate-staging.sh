#!/bin/bash

# Leadership Summit Laravel - Staging Environment Validation Script
# This script validates the staging environment configuration and functionality

set -e

echo "🔍 Validating staging environment..."

# Configuration
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

# Counters
PASSED=0
FAILED=0
WARNINGS=0

# Function to print colored output
print_status() {
    echo -e "${GREEN}[PASS]${NC} $1"
    ((PASSED++))
}

print_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
    ((WARNINGS++))
}

print_error() {
    echo -e "${RED}[FAIL]${NC} $1"
    ((FAILED++))
}

print_step() {
    echo -e "${BLUE}[TEST]${NC} $1"
}

# Test 1: Container Status
print_step "Testing container status..."

CONTAINERS=($APP_CONTAINER $DB_CONTAINER $NGINX_CONTAINER $REDIS_CONTAINER)

for container in "${CONTAINERS[@]}"; do
    if docker ps | grep -q "$container"; then
        print_status "Container '$container' is running"
    else
        print_error "Container '$container' is not running"
    fi
done

# Test 2: Application Configuration
print_step "Testing application configuration..."

# Check if .env file exists in container
if docker exec $APP_CONTAINER test -f .env; then
    print_status "Environment file exists in container"
else
    print_error "Environment file missing in container"
fi

# Check APP_ENV
APP_ENV=$(docker exec $APP_CONTAINER php artisan tinker --execute="echo config('app.env');" 2>/dev/null || echo "error")
if [ "$APP_ENV" = "staging" ]; then
    print_status "APP_ENV is correctly set to staging"
else
    print_error "APP_ENV is not set to staging (current: $APP_ENV)"
fi

# Check APP_DEBUG
APP_DEBUG=$(docker exec $APP_CONTAINER php artisan tinker --execute="echo config('app.debug') ? 'true' : 'false';" 2>/dev/null || echo "error")
if [ "$APP_DEBUG" = "false" ]; then
    print_status "APP_DEBUG is correctly set to false"
else
    print_warning "APP_DEBUG should be false in staging (current: $APP_DEBUG)"
fi

# Test 3: Database Connectivity
print_step "Testing database connectivity..."

if docker exec $APP_CONTAINER php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';" > /dev/null 2>&1; then
    print_status "Database connection successful"
else
    print_error "Database connection failed"
fi

# Check database tables
TABLES=("users" "events" "speakers" "sessions" "tickets" "registrations" "pages" "roles" "payments" "orders" "media")

for table in "${TABLES[@]}"; do
    if docker exec $DB_CONTAINER mysql -u root -p${DB_PASSWORD:-secret} -D leadership_summit_staging -e "DESCRIBE $table;" > /dev/null 2>&1; then
        print_status "Table '$table' exists"
    else
        print_error "Table '$table' does not exist"
    fi
done

# Test 4: Redis Connectivity
print_step "Testing Redis connectivity..."

if docker exec $APP_CONTAINER php artisan tinker --execute="Redis::ping(); echo 'OK';" > /dev/null 2>&1; then
    print_status "Redis connection successful"
else
    print_warning "Redis connection failed"
fi

# Test 5: Web Server Response
print_step "Testing web server response..."

# Test HTTP
if curl -f -s http://localhost > /dev/null; then
    print_status "HTTP endpoint responding"
else
    print_error "HTTP endpoint not responding"
fi

# Test HTTPS (may fail with self-signed cert)
if curl -f -s -k https://localhost > /dev/null; then
    print_status "HTTPS endpoint responding"
else
    print_warning "HTTPS endpoint not responding (expected with self-signed cert)"
fi

# Test 6: Application Routes
print_step "Testing application routes..."

ROUTES=(
    "/"
    "/events"
    "/speakers"
    "/sessions"
    "/login"
    "/register"
)

for route in "${ROUTES[@]}"; do
    response_code=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost$route" || echo "000")
    if [ "$response_code" = "200" ]; then
        print_status "Route '$route' returns 200 OK"
    elif [ "$response_code" = "302" ]; then
        print_status "Route '$route' returns 302 (redirect)"
    else
        print_error "Route '$route' returns $response_code"
    fi
done

# Test 7: Laravel Artisan Commands
print_step "Testing Laravel artisan commands..."

# Test config:cache
if docker exec $APP_CONTAINER php artisan config:cache > /dev/null 2>&1; then
    print_status "Config caching works"
else
    print_error "Config caching failed"
fi

# Test route:cache
if docker exec $APP_CONTAINER php artisan route:cache > /dev/null 2>&1; then
    print_status "Route caching works"
else
    print_error "Route caching failed"
fi

# Test view:cache
if docker exec $APP_CONTAINER php artisan view:cache > /dev/null 2>&1; then
    print_status "View caching works"
else
    print_error "View caching failed"
fi

# Test 8: File Permissions
print_step "Testing file permissions..."

# Check storage directory permissions
if docker exec $APP_CONTAINER test -w /var/www/storage; then
    print_status "Storage directory is writable"
else
    print_error "Storage directory is not writable"
fi

# Check bootstrap/cache permissions
if docker exec $APP_CONTAINER test -w /var/www/bootstrap/cache; then
    print_status "Bootstrap cache directory is writable"
else
    print_error "Bootstrap cache directory is not writable"
fi

# Test 9: Log Files
print_step "Testing log functionality..."

# Generate a test log entry
docker exec $APP_CONTAINER php artisan tinker --execute="Log::info('Staging validation test log entry');" > /dev/null 2>&1

# Check if log file exists and is writable
if docker exec $APP_CONTAINER test -f /var/www/storage/logs/laravel.log; then
    print_status "Log file exists and is accessible"
else
    print_error "Log file does not exist or is not accessible"
fi

# Test 10: Performance Check
print_step "Testing basic performance..."

# Measure response time for home page
response_time=$(curl -o /dev/null -s -w "%{time_total}" "http://localhost/" 2>/dev/null || echo "error")
if [ "$response_time" != "error" ]; then
    # Convert to milliseconds for easier reading
    response_ms=$(echo "$response_time * 1000" | bc -l 2>/dev/null || echo "$response_time")
    if (( $(echo "$response_time < 2.0" | bc -l) )); then
        print_status "Home page response time: ${response_time}s (good)"
    else
        print_warning "Home page response time: ${response_time}s (slow)"
    fi
else
    print_error "Could not measure response time"
fi

# Test 11: Security Headers
print_step "Testing security headers..."

SECURITY_HEADERS=(
    "X-Frame-Options"
    "X-XSS-Protection"
    "X-Content-Type-Options"
    "Strict-Transport-Security"
)

for header in "${SECURITY_HEADERS[@]}"; do
    if curl -s -I "http://localhost/" | grep -i "$header" > /dev/null; then
        print_status "Security header '$header' is present"
    else
        print_warning "Security header '$header' is missing"
    fi
done

# Test 12: SSL Configuration
print_step "Testing SSL configuration..."

if [ -f "docker/ssl/staging.leadershipsummit.com.crt" ] && [ -f "docker/ssl/staging.leadershipsummit.com.key" ]; then
    print_status "SSL certificates are present"
else
    print_error "SSL certificates are missing"
fi

# Test 13: Environment-specific Settings
print_step "Testing environment-specific settings..."

# Check cache driver
CACHE_DRIVER=$(docker exec $APP_CONTAINER php artisan tinker --execute="echo config('cache.default');" 2>/dev/null || echo "error")
if [ "$CACHE_DRIVER" = "redis" ]; then
    print_status "Cache driver is set to Redis"
else
    print_warning "Cache driver is not Redis (current: $CACHE_DRIVER)"
fi

# Check session driver
SESSION_DRIVER=$(docker exec $APP_CONTAINER php artisan tinker --execute="echo config('session.driver');" 2>/dev/null || echo "error")
if [ "$SESSION_DRIVER" = "redis" ]; then
    print_status "Session driver is set to Redis"
else
    print_warning "Session driver is not Redis (current: $SESSION_DRIVER)"
fi

# Final Summary
echo ""
echo "=================================="
echo "STAGING VALIDATION SUMMARY"
echo "=================================="
echo -e "${GREEN}Passed: $PASSED${NC}"
echo -e "${YELLOW}Warnings: $WARNINGS${NC}"
echo -e "${RED}Failed: $FAILED${NC}"
echo ""

if [ $FAILED -eq 0 ]; then
    if [ $WARNINGS -eq 0 ]; then
        echo -e "${GREEN}🎉 All tests passed! Staging environment is ready.${NC}"
        exit 0
    else
        echo -e "${YELLOW}⚠️  Tests passed with warnings. Review warnings before proceeding.${NC}"
        exit 0
    fi
else
    echo -e "${RED}❌ Some tests failed. Please fix the issues before proceeding.${NC}"
    exit 1
fi