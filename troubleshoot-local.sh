#!/bin/bash

# Leadership Summit Laravel - Local Development Troubleshooting Script
# This script helps diagnose and fix common local development issues

set -e

echo "🔍 Leadership Summit Laravel - Local Development Troubleshooting"
echo "=============================================================="

# Function to check Docker status
check_docker() {
    echo "🐳 Checking Docker status..."
    if docker info > /dev/null 2>&1; then
        echo "✅ Docker is running"
    else
        echo "❌ Docker is not running. Please start Docker."
        return 1
    fi
}

# Function to check container status
check_containers() {
    echo "📦 Checking container status..."
    
    if docker-compose ps | grep -q "leadership-summit-app"; then
        local app_status=$(docker-compose ps | grep "leadership-summit-app" | awk '{print $4}')
        echo "App container: $app_status"
    else
        echo "❌ App container not found"
    fi
    
    if docker-compose ps | grep -q "leadership-summit-nginx"; then
        local nginx_status=$(docker-compose ps | grep "leadership-summit-nginx" | awk '{print $4}')
        echo "Nginx container: $nginx_status"
    else
        echo "❌ Nginx container not found"
    fi
    
    if docker-compose ps | grep -q "leadership-summit-mysql"; then
        local mysql_status=$(docker-compose ps | grep "leadership-summit-mysql" | awk '{print $4}')
        echo "MySQL container: $mysql_status"
    else
        echo "❌ MySQL container not found"
    fi
}

# Function to check database connectivity
check_database() {
    echo "🗄️ Checking database connectivity..."
    
    if docker-compose exec app php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connected successfully';" 2>/dev/null | grep -q "Database connected successfully"; then
        echo "✅ Database connection successful"
    else
        echo "❌ Database connection failed"
        echo "💡 Try running: docker-compose restart mysql"
        echo "💡 Wait 30 seconds and try again"
        return 1
    fi
}

# Function to check file permissions
check_permissions() {
    echo "🔒 Checking file permissions..."
    
    if docker-compose exec app test -w /var/www/html/storage; then
        echo "✅ Storage directory is writable"
    else
        echo "❌ Storage directory is not writable"
        echo "🔧 Fixing permissions..."
        docker-compose exec app chown -R www-data:www-data /var/www/html/storage
        docker-compose exec app chmod -R 775 /var/www/html/storage
        echo "✅ Storage permissions fixed"
    fi
    
    if docker-compose exec app test -w /var/www/html/bootstrap/cache; then
        echo "✅ Bootstrap cache directory is writable"
    else
        echo "❌ Bootstrap cache directory is not writable"
        echo "🔧 Fixing permissions..."
        docker-compose exec app chown -R www-data:www-data /var/www/html/bootstrap/cache
        docker-compose exec app chmod -R 775 /var/www/html/bootstrap/cache
        echo "✅ Bootstrap cache permissions fixed"
    fi
}

# Function to check application key
check_app_key() {
    echo "🔑 Checking application key..."
    
    if grep -q "APP_KEY=base64:" .env; then
        echo "✅ Application key is set"
    else
        echo "❌ Application key is not set"
        echo "🔧 Generating application key..."
        docker-compose exec app php artisan key:generate
        echo "✅ Application key generated"
    fi
}

# Function to check migrations
check_migrations() {
    echo "📋 Checking database migrations..."
    
    local migration_count=$(docker-compose exec app php artisan migrate:status 2>/dev/null | grep -c "Ran" || echo "0")
    
    if [ "$migration_count" -gt 0 ]; then
        echo "✅ Migrations have been run ($migration_count migrations)"
    else
        echo "❌ No migrations have been run"
        echo "🔧 Running migrations..."
        docker-compose exec app php artisan migrate --force
        echo "✅ Migrations completed"
    fi
}

# Function to check web server response
check_web_server() {
    echo "🌐 Checking web server response..."
    
    local response_code=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000 2>/dev/null || echo "000")
    
    if [ "$response_code" = "200" ]; then
        echo "✅ Web server responding (HTTP $response_code)"
    else
        echo "❌ Web server not responding (HTTP $response_code)"
        echo "💡 Check if containers are running: docker-compose ps"
        echo "💡 Check logs: docker-compose logs nginx"
        return 1
    fi
}

# Function to check composer dependencies
check_composer() {
    echo "📚 Checking Composer dependencies..."
    
    if docker-compose exec app composer check-platform-reqs > /dev/null 2>&1; then
        echo "✅ Composer dependencies are satisfied"
    else
        echo "❌ Composer dependency issues detected"
        echo "🔧 Installing/updating dependencies..."
        docker-compose exec app composer install
        echo "✅ Composer dependencies updated"
    fi
}

# Function to check Node.js dependencies
check_node() {
    echo "🎨 Checking Node.js dependencies..."
    
    if docker-compose exec app test -d node_modules; then
        echo "✅ Node.js dependencies are installed"
    else
        echo "❌ Node.js dependencies not found"
        echo "🔧 Installing Node.js dependencies..."
        docker-compose exec app npm install
        echo "✅ Node.js dependencies installed"
    fi
    
    if docker-compose exec app test -d public/build; then
        echo "✅ Assets have been built"
    else
        echo "❌ Assets have not been built"
        echo "🔧 Building assets..."
        docker-compose exec app npm run build
        echo "✅ Assets built successfully"
    fi
}

# Function to clear caches
clear_caches() {
    echo "🧹 Clearing application caches..."
    
    docker-compose exec app php artisan config:clear
    docker-compose exec app php artisan cache:clear
    docker-compose exec app php artisan route:clear
    docker-compose exec app php artisan view:clear
    
    echo "✅ Caches cleared"
}

# Function to show logs
show_logs() {
    echo "📋 Recent application logs:"
    echo "=========================="
    docker-compose logs --tail=20 app
    echo ""
    echo "📋 Recent nginx logs:"
    echo "===================="
    docker-compose logs --tail=20 nginx
    echo ""
    echo "📋 Recent MySQL logs:"
    echo "===================="
    docker-compose logs --tail=20 mysql
}

# Function to run comprehensive health check
health_check() {
    echo "🏥 Running comprehensive health check..."
    echo ""
    
    local issues=0
    
    check_docker || ((issues++))
    echo ""
    
    check_containers || ((issues++))
    echo ""
    
    check_database || ((issues++))
    echo ""
    
    check_permissions || ((issues++))
    echo ""
    
    check_app_key || ((issues++))
    echo ""
    
    check_migrations || ((issues++))
    echo ""
    
    check_composer || ((issues++))
    echo ""
    
    check_node || ((issues++))
    echo ""
    
    check_web_server || ((issues++))
    echo ""
    
    if [ $issues -eq 0 ]; then
        echo "🎉 All health checks passed! Your local development environment is ready."
        echo ""
        echo "🌐 Application URL: http://localhost:8000"
        echo "🗄️ Database: MySQL on localhost:3306"
        echo ""
    else
        echo "⚠️ Found $issues issue(s). Please review the output above."
        echo ""
        echo "🔧 Common fixes:"
        echo "   - Restart containers: docker-compose restart"
        echo "   - Rebuild containers: docker-compose down && docker-compose up -d --build"
        echo "   - Check logs: docker-compose logs -f"
        echo ""
    fi
}

# Function to reset environment
reset_environment() {
    echo "🔄 Resetting local development environment..."
    echo "⚠️ This will destroy all data and rebuild everything from scratch."
    read -p "Are you sure? (yes/no): " -r
    
    if [[ $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
        echo "🗑️ Stopping and removing containers..."
        docker-compose down --volumes --remove-orphans
        
        echo "🧹 Removing Docker images..."
        docker rmi leadership-summit-laravel 2>/dev/null || true
        
        echo "📦 Rebuilding and starting containers..."
        docker-compose up -d --build
        
        echo "⏳ Waiting for services to be ready..."
        sleep 30
        
        echo "🔧 Running setup..."
        ./setup-local.sh
        
        echo "✅ Environment reset completed!"
    else
        echo "❌ Reset cancelled"
    fi
}

# Main menu
case "${1:-help}" in
    "health")
        health_check
        ;;
    "database")
        check_database
        ;;
    "permissions")
        check_permissions
        ;;
    "clear-cache")
        clear_caches
        ;;
    "logs")
        show_logs
        ;;
    "reset")
        reset_environment
        ;;
    "help"|*)
        echo "Usage: $0 [command]"
        echo ""
        echo "Commands:"
        echo "  health       - Run comprehensive health check (default)"
        echo "  database     - Check database connectivity"
        echo "  permissions  - Check and fix file permissions"
        echo "  clear-cache  - Clear all application caches"
        echo "  logs         - Show recent container logs"
        echo "  reset        - Reset entire environment (destructive)"
        echo "  help         - Show this help message"
        echo ""
        echo "Examples:"
        echo "  $0 health      # Run full health check"
        echo "  $0 database    # Check database only"
        echo "  $0 logs        # Show recent logs"
        echo ""
        
        # Run health check by default
        if [ "${1:-help}" != "help" ]; then
            echo "Running health check..."
            echo ""
            health_check
        fi
        ;;
esac