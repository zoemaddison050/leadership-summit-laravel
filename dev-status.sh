#!/bin/bash

echo "🔍 Development Environment Status"
echo "================================="

# Check if local PHP server is running
if pgrep -f "php artisan serve" > /dev/null; then
    echo "📱 Local PHP Server: ✅ RUNNING"
    echo "   URL: http://localhost:8000"
    LOCAL_RUNNING=true
else
    echo "📱 Local PHP Server: ❌ STOPPED"
    LOCAL_RUNNING=false
fi

# Check if Docker containers are running
if docker-compose ps | grep -q "Up"; then
    echo "🐳 Docker Containers: ✅ RUNNING"
    echo "   URL: http://localhost:8080"
    DOCKER_RUNNING=true
else
    echo "🐳 Docker Containers: ❌ STOPPED"
    DOCKER_RUNNING=false
fi

# Check current database configuration
if grep -q "sqlite" .env; then
    echo "🗄️  Database: SQLite (local)"
elif grep -q "DB_HOST=mysql" .env; then
    echo "🗄️  Database: MySQL (Docker)"
elif grep -q "DB_HOST=127.0.0.1" .env; then
    echo "🗄️  Database: MySQL (localhost)"
else
    echo "🗄️  Database: Unknown configuration"
fi

echo ""
echo "🎯 Recommended Actions:"
if [ "$LOCAL_RUNNING" = true ] && [ "$DOCKER_RUNNING" = true ]; then
    echo "⚠️  Both local and Docker are running - choose one:"
    echo "   ./switch-to-local.sh  (for local development)"
    echo "   ./switch-to-docker.sh (for Docker development)"
elif [ "$LOCAL_RUNNING" = true ]; then
    echo "✅ Using local development mode"
    echo "   Admin: http://localhost:8000/login"
elif [ "$DOCKER_RUNNING" = true ]; then
    echo "✅ Using Docker development mode"
    echo "   Admin: http://localhost:8080/login"
else
    echo "❌ No development server running"
    echo "   ./switch-to-local.sh  (start local development)"
    echo "   ./switch-to-docker.sh (start Docker development)"
fi

echo ""
echo "👤 Admin Credentials: admin@leadershipsummit.com / password"