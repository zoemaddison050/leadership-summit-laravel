#!/bin/bash

# Leadership Summit Laravel - Database Restore Script
# This script restores database from backup

set -e

# Configuration
ENVIRONMENT=${1:-production}
BACKUP_FILE=${2}

# Load environment-specific configuration
case $ENVIRONMENT in
    "production")
        DB_CONTAINER="leadership-summit-db"
        DB_NAME="leadership_summit"
        ;;
    "staging")
        DB_CONTAINER="leadership-summit-staging-db"
        DB_NAME="leadership_summit_staging"
        ;;
    *)
        echo "❌ Invalid environment: $ENVIRONMENT"
        echo "Usage: $0 [production|staging] [backup_file]"
        exit 1
        ;;
esac

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

# Function to list available backups
list_backups() {
    echo "Available backups:"
    find ./backups/database -name "${ENVIRONMENT}_*.sql.gz" -type f | sort -r | head -10
}

# Check if backup file is provided
if [ -z "$BACKUP_FILE" ]; then
    print_error "Backup file not specified"
    echo ""
    list_backups
    echo ""
    echo "Usage: $0 [production|staging] [backup_file]"
    exit 1
fi

# Check if backup file exists
if [ ! -f "$BACKUP_FILE" ]; then
    print_error "Backup file not found: $BACKUP_FILE"
    echo ""
    list_backups
    exit 1
fi

print_warning "⚠️  WARNING: This will replace the current database!"
print_warning "Environment: $ENVIRONMENT"
print_warning "Database: $DB_NAME"
print_warning "Backup file: $BACKUP_FILE"
echo ""

# Confirmation prompt
read -p "Are you sure you want to continue? (yes/no): " -r
if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    print_status "Restore cancelled"
    exit 0
fi

print_step "Starting database restore for $ENVIRONMENT environment..."

# Check if database container is running
if ! docker ps | grep -q "$DB_CONTAINER"; then
    print_error "Database container '$DB_CONTAINER' is not running"
    exit 1
fi

# Create a backup of current database before restore
CURRENT_BACKUP_DIR="./backups/pre-restore"
mkdir -p "$CURRENT_BACKUP_DIR"
CURRENT_BACKUP_FILE="$CURRENT_BACKUP_DIR/${ENVIRONMENT}_pre_restore_$(date +%Y%m%d_%H%M%S).sql"

print_step "Creating backup of current database before restore..."
if docker exec "$DB_CONTAINER" mysqldump \
    -u root \
    -p"${DB_PASSWORD:-secret}" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --add-drop-database \
    --databases "$DB_NAME" > "$CURRENT_BACKUP_FILE"; then
    
    gzip "$CURRENT_BACKUP_FILE"
    print_status "Current database backed up to: ${CURRENT_BACKUP_FILE}.gz"
else
    print_error "Failed to backup current database"
    exit 1
fi

# Verify backup file integrity
print_step "Verifying backup file integrity..."
if [[ "$BACKUP_FILE" == *.gz ]]; then
    if ! gunzip -t "$BACKUP_FILE"; then
        print_error "Backup file is corrupted"
        exit 1
    fi
    print_status "Backup file integrity verified"
    RESTORE_COMMAND="gunzip -c '$BACKUP_FILE'"
else
    RESTORE_COMMAND="cat '$BACKUP_FILE'"
fi

# Stop application containers to prevent database access during restore
print_step "Stopping application containers..."
APP_CONTAINER="${DB_CONTAINER//-db/}"
docker stop "$APP_CONTAINER" 2>/dev/null || true

# Restore database
print_step "Restoring database from backup..."
if eval "$RESTORE_COMMAND" | docker exec -i "$DB_CONTAINER" mysql -u root -p"${DB_PASSWORD:-secret}"; then
    print_status "✅ Database restored successfully"
else
    print_error "❌ Database restore failed"
    
    # Attempt to restore from pre-restore backup
    print_warning "Attempting to restore from pre-restore backup..."
    if gunzip -c "${CURRENT_BACKUP_FILE}.gz" | docker exec -i "$DB_CONTAINER" mysql -u root -p"${DB_PASSWORD:-secret}"; then
        print_status "Pre-restore backup restored successfully"
    else
        print_error "Failed to restore pre-restore backup"
    fi
    
    exit 1
fi

# Start application containers
print_step "Starting application containers..."
docker start "$APP_CONTAINER" 2>/dev/null || true

# Wait for application to be ready
print_step "Waiting for application to be ready..."
sleep 10

# Verify restore
print_step "Verifying database restore..."
if docker exec "$APP_CONTAINER" php artisan tinker --execute="
try {
    \$userCount = DB::table('users')->count();
    \$eventCount = DB::table('events')->count();
    echo 'Users: ' . \$userCount . PHP_EOL;
    echo 'Events: ' . \$eventCount . PHP_EOL;
    echo 'Database verification successful' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Database verification failed: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
" 2>/dev/null; then
    print_status "✅ Database verification successful"
else
    print_error "❌ Database verification failed"
    exit 1
fi

# Run any necessary post-restore commands
print_step "Running post-restore commands..."
docker exec "$APP_CONTAINER" php artisan config:cache
docker exec "$APP_CONTAINER" php artisan route:cache
docker exec "$APP_CONTAINER" php artisan view:cache

# Log restore
echo "$(date): Database restore completed - $BACKUP_FILE" >> restore.log

print_status "🎉 Database restore completed successfully!"
print_status "Restored from: $BACKUP_FILE"
print_status "Pre-restore backup: ${CURRENT_BACKUP_FILE}.gz"