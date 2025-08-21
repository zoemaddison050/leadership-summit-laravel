#!/bin/bash

# Leadership Summit Laravel - Rollback Script
# This script handles rollback to previous deployment

set -e

# Configuration
ENVIRONMENT=${1:-production}
ROLLBACK_TYPE=${2:-auto}  # auto, manual, or commit

# Load environment-specific configuration
case $ENVIRONMENT in
    "production")
        DOCKER_COMPOSE_FILE="docker-compose.yml"
        APP_CONTAINER="leadership-summit-app"
        DB_CONTAINER="leadership-summit-db"
        ;;
    "staging")
        DOCKER_COMPOSE_FILE="docker-compose.staging.yml"
        APP_CONTAINER="leadership-summit-staging"
        DB_CONTAINER="leadership-summit-staging-db"
        ;;
    *)
        echo "❌ Invalid environment: $ENVIRONMENT"
        echo "Usage: $0 [production|staging] [auto|manual|commit] [commit_hash]"
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

# Function to get last successful deployment info
get_last_deployment() {
    if [ -f "deployment.log" ]; then
        tail -n 10 deployment.log | grep "Successful deployment" | tail -n 1
    fi
}

# Function to rollback to automatic backup
rollback_auto() {
    print_step "Rolling back using automatic backup..."
    
    if [ -f ".last_backup_path" ]; then
        BACKUP_PATH=$(cat .last_backup_path)
        
        if [ -d "$BACKUP_PATH" ]; then
            print_status "Found backup at: $BACKUP_PATH"
            
            # Stop current containers
            print_status "Stopping current containers..."
            docker-compose -f "$DOCKER_COMPOSE_FILE" down --timeout 30
            
            # Restore database
            if [ -f "$BACKUP_PATH/database_backup.sql" ]; then
                print_status "Restoring database..."
                docker-compose -f "$DOCKER_COMPOSE_FILE" up -d "$DB_CONTAINER"
                sleep 15
                
                if docker exec -i "$DB_CONTAINER" mysql -u root -p"${DB_PASSWORD:-secret}" < "$BACKUP_PATH/database_backup.sql"; then
                    print_status "✅ Database restored"
                else
                    print_error "❌ Database restore failed"
                    return 1
                fi
            fi
            
            # Restore storage directory
            if [ -d "$BACKUP_PATH/storage" ]; then
                print_status "Restoring storage directory..."
                rm -rf ./storage
                cp -r "$BACKUP_PATH/storage" ./
                print_status "✅ Storage restored"
            fi
            
            # Restore environment file
            ENV_FILE=".env"
            if [ "$ENVIRONMENT" = "staging" ]; then
                ENV_FILE=".env.staging"
            fi
            
            if [ -f "$BACKUP_PATH/$(basename $ENV_FILE)" ]; then
                cp "$BACKUP_PATH/$(basename $ENV_FILE)" "$ENV_FILE"
                print_status "✅ Environment file restored"
            fi
            
            # Start all containers
            print_status "Starting containers..."
            docker-compose -f "$DOCKER_COMPOSE_FILE" up -d
            
            return 0
        else
            print_error "Backup directory not found: $BACKUP_PATH"
            return 1
        fi
    else
        print_error "No automatic backup path found"
        return 1
    fi
}

# Function to rollback to specific commit
rollback_commit() {
    local commit_hash=${3}
    
    if [ -z "$commit_hash" ]; then
        print_error "Commit hash not provided"
        echo "Usage: $0 $ENVIRONMENT commit [commit_hash]"
        return 1
    fi
    
    print_step "Rolling back to commit: $commit_hash"
    
    # Verify commit exists
    if ! git cat-file -e "$commit_hash^{commit}" 2>/dev/null; then
        print_error "Invalid commit hash: $commit_hash"
        return 1
    fi
    
    # Create backup before rollback
    print_status "Creating backup before rollback..."
    ./scripts/backup-database.sh "$ENVIRONMENT"
    
    # Stash current changes
    git stash push -m "Pre-rollback stash $(date)"
    
    # Checkout specific commit
    git checkout "$commit_hash"
    
    # Rebuild and deploy
    print_status "Rebuilding application..."
    docker-compose -f "$DOCKER_COMPOSE_FILE" down
    docker-compose -f "$DOCKER_COMPOSE_FILE" up -d --build
    
    # Wait for containers to be ready
    sleep 30
    
    # Run necessary commands
    docker exec "$APP_CONTAINER" composer install --no-dev --optimize-autoloader
    docker exec "$APP_CONTAINER" php artisan migrate --force
    docker exec "$APP_CONTAINER" php artisan config:cache
    docker exec "$APP_CONTAINER" php artisan route:cache
    docker exec "$APP_CONTAINER" php artisan view:cache
    
    return 0
}

# Function to manual rollback with user selection
rollback_manual() {
    print_step "Manual rollback - selecting backup..."
    
    # List available backups
    echo "Available database backups:"
    find ./backups/database -name "${ENVIRONMENT}_*.sql.gz" -type f | sort -r | head -10 | nl
    
    echo ""
    read -p "Enter backup number to restore (or 'q' to quit): " -r backup_choice
    
    if [[ "$backup_choice" == "q" ]]; then
        print_status "Rollback cancelled"
        return 0
    fi
    
    # Get selected backup file
    BACKUP_FILE=$(find ./backups/database -name "${ENVIRONMENT}_*.sql.gz" -type f | sort -r | head -10 | sed -n "${backup_choice}p")
    
    if [ -z "$BACKUP_FILE" ]; then
        print_error "Invalid backup selection"
        return 1
    fi
    
    print_status "Selected backup: $BACKUP_FILE"
    
    # Restore using the restore script
    ./scripts/restore-database.sh "$ENVIRONMENT" "$BACKUP_FILE"
    
    return 0
}

# Function to verify rollback
verify_rollback() {
    print_step "Verifying rollback..."
    
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
    local max_attempts=5
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

# Main rollback process
main() {
    echo "🔄 Starting rollback for $ENVIRONMENT environment..."
    echo "Rollback type: $ROLLBACK_TYPE"
    echo "Time: $(date)"
    echo ""
    
    # Show last deployment info
    LAST_DEPLOYMENT=$(get_last_deployment)
    if [ -n "$LAST_DEPLOYMENT" ]; then
        print_status "Last successful deployment: $LAST_DEPLOYMENT"
    fi
    
    print_warning "⚠️  WARNING: This will rollback the current deployment!"
    echo ""
    
    # Confirmation prompt for production
    if [ "$ENVIRONMENT" = "production" ]; then
        read -p "Are you sure you want to rollback PRODUCTION? (yes/no): " -r
        if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
            print_status "Rollback cancelled"
            exit 0
        fi
    fi
    
    # Execute rollback based on type
    case $ROLLBACK_TYPE in
        "auto")
            if rollback_auto; then
                print_status "✅ Automatic rollback completed"
            else
                print_error "❌ Automatic rollback failed"
                exit 1
            fi
            ;;
        "manual")
            if rollback_manual; then
                print_status "✅ Manual rollback completed"
            else
                print_error "❌ Manual rollback failed"
                exit 1
            fi
            ;;
        "commit")
            if rollback_commit "$@"; then
                print_status "✅ Commit rollback completed"
            else
                print_error "❌ Commit rollback failed"
                exit 1
            fi
            ;;
        *)
            print_error "Invalid rollback type: $ROLLBACK_TYPE"
            echo "Valid types: auto, manual, commit"
            exit 1
            ;;
    esac
    
    # Verify rollback
    if verify_rollback; then
        # Log rollback
        echo "$(date): Rollback completed - $ENVIRONMENT ($ROLLBACK_TYPE)" >> rollback.log
        
        echo ""
        print_status "🎉 Rollback completed successfully!"
        
        # Display container status
        echo ""
        print_status "Container status:"
        docker-compose -f "$DOCKER_COMPOSE_FILE" ps
    else
        print_error "Rollback verification failed"
        exit 1
    fi
}

# Show usage if no arguments
if [ $# -eq 0 ]; then
    echo "Usage: $0 [production|staging] [auto|manual|commit] [commit_hash]"
    echo ""
    echo "Examples:"
    echo "  $0 staging auto                    # Rollback staging using automatic backup"
    echo "  $0 production manual               # Rollback production with manual backup selection"
    echo "  $0 production commit abc123        # Rollback production to specific commit"
    exit 1
fi

# Run main function
main "$@"