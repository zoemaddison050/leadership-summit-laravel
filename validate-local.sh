#!/bin/bash

# Leadership Summit Laravel - Local Development Validation Script
# This script validates that the local development environment is working correctly

set -e

echo "🔍 Validating Leadership Summit Laravel Local Development Environment"
echo "=================================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

PASSED=0
FAILED=0

# Function to run a test
run_test() {
    local test_name="$1"
    local test_command="$2"
    
    echo -n "Testing $test_name... "
    
    if eval "$test_command" > /dev/null 2>&1; then
        echo -e "${GREEN}✅ PASS${NC}"
        ((PASSED++))
        return 0
    else
        echo -e "${RED}❌ FAIL${NC}"
        ((FAILED++))
        return 1
    fi
}

# Function to run a test with output
run_test_with_output() {
    local test_name="$1"
    local test_command="$2"
    local expected_output="$3"
    
    echo -n "Testing $test_name... "
    
    local output=$(eval "$test_command" 2>/dev/null || echo "")
    
    if [[ "$output" == *"$expected_output"* ]]; then
        echo -e "${GREEN}✅ PASS${NC}"
        ((PASSED++))
        return 0
    else
        echo -e "${RED}❌ FAIL${NC} (Expected: $expected_output, Got: $output)"
        ((FAILED++))
        return 1
    fi
}

echo "🐳 Docker Environment Tests"
echo "=========================="

run_test "Docker is running" "docker info"
run_test "Docker Compose is available" "docker-compose --version"

echo ""
echo "📦 Container Tests"
echo "=================="

run_test "App container is running" "docker-compose ps | grep leadership-summit-app | grep Up"
run_test "Nginx container is running" "docker-compose ps | grep leadership-summit-nginx | grep Up"
run_test "MySQL container is running" "docker-compose ps | grep leadership-summit-mysql | grep Up"

echo ""
echo "🔗 Connectivity Tests"
echo "===================="

run_test "Web server responds" "curl -f -s http://localhost:8000"
run_test "Database connection" "docker-compose exec -T app php artisan tinker --execute='DB::connection()->getPdo(); echo \"OK\";' | grep OK"

echo ""
echo "📁 File System Tests"
echo "==================="

run_test "Storage directory writable" "docker-compose exec -T app test -w /var/www/html/storage"
run_test "Bootstrap cache writable" "docker-compose exec -T app test -w /var/www/html/bootstrap/cache"
run_test "Environment file exists" "test -f .env"
run_test "Application key is set" "grep -q 'APP_KEY=base64:' .env"

echo ""
echo "🗄️ Database Tests"
echo "================="

run_test "Migrations table exists" "docker-compose exec -T app php artisan tinker --execute='DB::table(\"migrations\")->count(); echo \"OK\";' | grep OK"
run_test "Users table exists" "docker-compose exec -T app php artisan tinker --execute='DB::table(\"users\")->count(); echo \"OK\";' | grep OK"
run_test "Events table exists" "docker-compose exec -T app php artisan tinker --execute='DB::table(\"events\")->count(); echo \"OK\";' | grep OK"

echo ""
echo "🎨 Asset Tests"
echo "=============="

run_test "Node modules installed" "docker-compose exec -T app test -d node_modules"
run_test "Assets compiled" "docker-compose exec -T app test -d public/build"

echo ""
echo "🧪 Application Tests"
echo "==================="

run_test "Laravel application responds" "docker-compose exec -T app php artisan --version"
run_test "Cache is working" "docker-compose exec -T app php artisan tinker --execute='Cache::put(\"test\", \"value\", 60); echo Cache::get(\"test\");' | grep value"

echo ""
echo "📊 Test Results"
echo "==============="

TOTAL=$((PASSED + FAILED))

echo "Total tests: $TOTAL"
echo -e "Passed: ${GREEN}$PASSED${NC}"
echo -e "Failed: ${RED}$FAILED${NC}"

if [ $FAILED -eq 0 ]; then
    echo ""
    echo -e "${GREEN}🎉 All tests passed! Your local development environment is working correctly.${NC}"
    echo ""
    echo "🌐 Application URL: http://localhost:8000"
    echo "🗄️ Database: MySQL on localhost:3306"
    echo ""
    echo "You can now start developing! 🚀"
    exit 0
else
    echo ""
    echo -e "${RED}❌ $FAILED test(s) failed. Please check the issues above.${NC}"
    echo ""
    echo "🔧 Try running the troubleshooting script:"
    echo "   ./troubleshoot-local.sh"
    echo ""
    echo "🔄 Or reset the environment:"
    echo "   ./troubleshoot-local.sh reset"
    exit 1
fi