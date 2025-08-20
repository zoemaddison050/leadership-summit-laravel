#!/bin/bash

# Leadership Summit Laravel - Database Backup Script
# This script creates backups of the database with rotation

set -e

# Configuration
ENVIRONMENT=${1:-production}
BACKUP_RETENTION_DAYS=${2:-30}
BACKUP_DIR="./backups/database"

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
        echo "Usage: $0 [production|staging] [retention_days]"
        exit 1
        ;;
esac

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
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

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Generate backup filename with timestamp
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/${ENVIRONMENT}_${DB_NAME}_${TIMESTAMP}.sql"
COMPRESSED_BACKUP="$BACKUP_FILE.gz"

print_status "Starting database backup for $ENVIRONMENT environment..."

# Check if database container is running
if ! docker ps | grep -q "$DB_CONTAINER"; then
    print_error "Database container '$DB_CONTAINER' is not running"
    exit 1
fi

# Create database backup
print_status "Creating database backup..."
if docker exec "$DB_CONTAINER" mysqldump \
    -u root \
    -p"${DB_PASSWORD:-secret}" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --add-drop-database \
    --databases "$DB_NAME" > "$BACKUP_FILE"; then
    
    print_status "Database backup created: $BACKUP_FILE"
    
    # Compress backup
    print_status "Compressing backup..."
    gzip "$BACKUP_FILE"
    print_status "Compressed backup created: $COMPRESSED_BACKUP"
    
    # Verify backup integrity
    print_status "Verifying backup integrity..."
    if gunzip -t "$COMPRESSED_BACKUP"; then
        print_status "✅ Backup integrity verified"
    else
        print_error "❌ Backup integrity check failed"
        exit 1
    fi
    
    # Get backup size
    BACKUP_SIZE=$(du -h "$COMPRESSED_BACKUP" | cut -f1)
    print_status "Backup size: $BACKUP_SIZE"
    
else
    print_error "Failed to create database backup"
    exit 1
fi

# Cleanup old backups
print_status "Cleaning up old backups (older than $BACKUP_RETENTION_DAYS days)..."
find "$BACKUP_DIR" -name "${ENVIRONMENT}_${DB_NAME}_*.sql.gz" -mtime +$BACKUP_RETENTION_DAYS -delete 2>/dev/null || true

# List recent backups
print_status "Recent backups:"
ls -lah "$BACKUP_DIR"/${ENVIRONMENT}_${DB_NAME}_*.sql.gz | tail -5

# Log backup
echo "$(date): Database backup completed - $COMPRESSED_BACKUP ($BACKUP_SIZE)" >> backup.log

print_status "🎉 Database backup completed successfully!"
print_status "Backup location: $COMPRESSED_BACKUP"