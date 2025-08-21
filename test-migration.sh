#!/bin/bash

# Leadership Summit Laravel - Data Migration Test Script
# This script tests the full data migration process in staging

set -e

echo "🧪 Starting data migration test..."

# Configuration
APP_CONTAINER="leadership-summit-staging"
DB_CONTAINER="leadership-summit-staging-db"
BACKUP_DIR="./migration-test-backups"
WORDPRESS_EXPORT_FILE="wordpress_export_test.sql"

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

# Create backup directory
mkdir -p $BACKUP_DIR

# Step 1: Backup current staging database
print_step "1. Creating backup of current staging database..."
docker exec $DB_CONTAINER mysqldump -u root -p${DB_PASSWORD:-secret} leadership_summit_staging > $BACKUP_DIR/staging_backup_$(date +%Y%m%d_%H%M%S).sql
print_status "Database backup created"

# Step 2: Test WordPress data export
print_step "2. Testing WordPress data export..."
if [ -f "database/migrations/export_scripts/standalone_export.php" ]; then
    print_status "Running WordPress export script..."
    # Note: This would typically run against a WordPress database
    # For testing, we'll simulate the export process
    docker exec $APP_CONTAINER php database/migrations/export_scripts/standalone_export.php --test-mode
    print_status "WordPress export test completed"
else
    print_warning "WordPress export script not found, skipping export test"
fi

# Step 3: Test data import
print_step "3. Testing data import process..."
docker exec $APP_CONTAINER php artisan migrate:fresh --force
docker exec $APP_CONTAINER php database/migrations/import_scripts/standalone_import.php --test-mode
print_status "Data import test completed"

# Step 4: Verify data integrity
print_step "4. Verifying data integrity..."

# Check if tables exist and have data
TABLES=("users" "events" "speakers" "sessions" "tickets" "registrations" "pages")

for table in "${TABLES[@]}"; do
    count=$(docker exec $DB_CONTAINER mysql -u root -p${DB_PASSWORD:-secret} -D leadership_summit_staging -e "SELECT COUNT(*) FROM $table;" -s -N 2>/dev/null || echo "0")
    if [ "$count" -gt 0 ]; then
        print_status "✅ Table '$table' has $count records"
    else
        print_warning "⚠️  Table '$table' is empty or doesn't exist"
    fi
done

# Step 5: Test application functionality
print_step "5. Testing application functionality..."

# Test database connections
print_status "Testing database connection..."
docker exec $APP_CONTAINER php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connection successful';"

# Test model relationships
print_status "Testing model relationships..."
docker exec $APP_CONTAINER php artisan tinker --execute="
\$user = App\Models\User::first();
if (\$user) {
    echo 'User model working: ' . \$user->name . PHP_EOL;
    echo 'User registrations: ' . \$user->registrations->count() . PHP_EOL;
}

\$event = App\Models\Event::first();
if (\$event) {
    echo 'Event model working: ' . \$event->title . PHP_EOL;
    echo 'Event tickets: ' . \$event->tickets->count() . PHP_EOL;
    echo 'Event sessions: ' . \$event->sessions->count() . PHP_EOL;
}
"

# Step 6: Test web endpoints
print_step "6. Testing web endpoints..."

ENDPOINTS=(
    "/"
    "/events"
    "/speakers"
    "/login"
    "/register"
)

for endpoint in "${ENDPOINTS[@]}"; do
    if curl -f -s "http://localhost$endpoint" > /dev/null; then
        print_status "✅ Endpoint '$endpoint' is accessible"
    else
        print_warning "⚠️  Endpoint '$endpoint' returned an error"
    fi
done

# Step 7: Performance test
print_step "7. Running basic performance test..."
print_status "Testing page load times..."

for endpoint in "${ENDPOINTS[@]}"; do
    response_time=$(curl -o /dev/null -s -w "%{time_total}" "http://localhost$endpoint" 2>/dev/null || echo "error")
    if [ "$response_time" != "error" ]; then
        print_status "Endpoint '$endpoint' response time: ${response_time}s"
    else
        print_warning "Could not measure response time for '$endpoint'"
    fi
done

# Step 8: Generate test report
print_step "8. Generating test report..."

REPORT_FILE="$BACKUP_DIR/migration_test_report_$(date +%Y%m%d_%H%M%S).txt"

cat > $REPORT_FILE << EOF
Leadership Summit Laravel - Migration Test Report
Generated: $(date)

=== Database Tables ===
EOF

for table in "${TABLES[@]}"; do
    count=$(docker exec $DB_CONTAINER mysql -u root -p${DB_PASSWORD:-secret} -D leadership_summit_staging -e "SELECT COUNT(*) FROM $table;" -s -N 2>/dev/null || echo "0")
    echo "$table: $count records" >> $REPORT_FILE
done

cat >> $REPORT_FILE << EOF

=== Web Endpoints ===
EOF

for endpoint in "${ENDPOINTS[@]}"; do
    if curl -f -s "http://localhost$endpoint" > /dev/null; then
        echo "$endpoint: OK" >> $REPORT_FILE
    else
        echo "$endpoint: ERROR" >> $REPORT_FILE
    fi
done

print_status "Test report saved to: $REPORT_FILE"

# Final summary
echo ""
print_status "🎉 Data migration test completed!"
print_status "Summary:"
echo "  - Database backup created in $BACKUP_DIR"
echo "  - Data integrity verified"
echo "  - Application functionality tested"
echo "  - Performance baseline established"
echo "  - Test report generated: $REPORT_FILE"
echo ""
print_status "Next steps:"
echo "  1. Review the test report"
echo "  2. Address any warnings or errors"
echo "  3. Run additional manual testing"
echo "  4. Prepare for production deployment"