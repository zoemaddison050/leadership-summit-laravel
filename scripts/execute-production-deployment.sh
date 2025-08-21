#!/bin/bash

# Leadership Summit Laravel - Production Deployment Execution Script
# This script executes the complete production deployment process
# Requirements: 1.1, 1.4

set -e

# Configuration
DEPLOYMENT_DATE=$(date +"%Y-%m-%d %H:%M:%S")
DEPLOYMENT_ID=$(date +"%Y%m%d_%H%M%S")
LOG_FILE="./logs/production_deployment_${DEPLOYMENT_ID}.log"
BRANCH=${1:-main}
SKIP_BACKUP=${2:-false}

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
log_message() {
    local level=$1
    local message=$2
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "${timestamp} [${level}] ${message}" | tee -a "$LOG_FILE"
}

print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
    log_message "INFO" "$1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
    log_message "WARNING" "$1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
    log_message "ERROR" "$1"
}

print_step() {
    echo -e "${BLUE}[STEP]${NC} $1"
    log_message "STEP" "$1"
}

# Initialize logging
mkdir -p ./logs
echo "Production Deployment Log - $DEPLOYMENT_DATE" > "$LOG_FILE"
echo "Deployment ID: $DEPLOYMENT_ID" >> "$LOG_FILE"
echo "Branch: $BRANCH" >> "$LOG_FILE"
echo "========================================" >> "$LOG_FILE"

# Function to execute deployment plan
execute_deployment_plan() {
    print_step "Executing Production Deployment Plan"
    
    # Step 1: Pre-deployment verification
    print_status "Step 1: Pre-deployment verification"
    
    # Verify staging deployment success
    if [ ! -f ".staging_deployment_success" ]; then
        print_error "Staging deployment not verified. Please complete staging deployment first."
        exit 1
    fi
    
    # Verify all tests passed
    if [ ! -f ".tests_passed" ]; then
        print_error "Tests not verified. Please run and pass all tests first."
        exit 1
    fi
    
    # Check production environment configuration
    if [ ! -f ".env.production" ]; then
        print_error "Production environment file not found"
        exit 1
    fi
    
    # Verify SSL certificates
    if [ ! -f "docker/ssl/leadershipsummit.com.crt" ] || [ ! -f "docker/ssl/leadershipsummit.com.key" ]; then
        print_error "SSL certificates not found"
        exit 1
    fi
    
    # Check SSL certificate validity
    if ! openssl x509 -in docker/ssl/leadershipsummit.com.crt -noout -checkend 86400; then
        print_error "SSL certificate is expired or will expire within 24 hours"
        exit 1
    fi
    
    print_status "✅ Pre-deployment verification completed"
    
    # Step 2: Create comprehensive backup
    print_status "Step 2: Creating comprehensive backup"
    
    if [ "$SKIP_BACKUP" != "true" ]; then
        ./scripts/backup-database.sh production
        
        # Create application backup
        BACKUP_DIR="./backups/production/${DEPLOYMENT_ID}"
        mkdir -p "$BACKUP_DIR"
        
        # Backup current application state
        if [ -d "./storage" ]; then
            tar -czf "$BACKUP_DIR/storage_backup.tar.gz" ./storage
        fi
        
        # Backup environment files
        cp .env.production "$BACKUP_DIR/"
        
        # Create deployment manifest
        cat > "$BACKUP_DIR/deployment_manifest.json" << EOF
{
    "deployment_id": "$DEPLOYMENT_ID",
    "deployment_date": "$DEPLOYMENT_DATE",
    "branch": "$BRANCH",
    "commit_hash": "$(git rev-parse HEAD)",
    "commit_message": "$(git log -1 --pretty=%B | tr '\n' ' ')",
    "environment": "production",
    "backup_location": "$BACKUP_DIR"
}
EOF
        
        print_status "✅ Backup created at: $BACKUP_DIR"
    else
        print_warning "Backup skipped as requested"
    fi
    
    # Step 3: Execute production deployment
    print_status "Step 3: Executing production deployment"
    
    # Run the production deployment script
    ./scripts/deploy-production.sh "$BRANCH" "$SKIP_BACKUP"
    
    print_status "✅ Production deployment executed"
    
    # Step 4: Migrate production data
    print_status "Step 4: Migrating production data"
    
    # Run data migration if needed
    if [ -f "./database/migrations/import_scripts/complete_migration.php" ]; then
        docker exec leadership-summit-app php database/migrations/import_scripts/complete_migration.php
        print_status "✅ Data migration completed"
    else
        print_status "No data migration required"
    fi
    
    # Step 5: Verify functionality
    print_status "Step 5: Verifying functionality"
    
    # Wait for services to be fully ready
    sleep 60
    
    # Run comprehensive functionality verification
    verify_production_functionality
    
    print_status "✅ Functionality verification completed"
    
    # Step 6: Post-deployment tasks
    print_status "Step 6: Post-deployment tasks"
    
    # Clear and warm up caches
    docker exec leadership-summit-app php artisan cache:clear
    docker exec leadership-summit-app php artisan config:cache
    docker exec leadership-summit-app php artisan route:cache
    docker exec leadership-summit-app php artisan view:cache
    
    # Warm up application
    curl -s https://leadershipsummit.com/ > /dev/null || true
    curl -s https://leadershipsummit.com/events > /dev/null || true
    
    # Create deployment success marker
    echo "$DEPLOYMENT_DATE" > ".production_deployment_success"
    
    print_status "✅ Post-deployment tasks completed"
}

# Function to verify production functionality
verify_production_functionality() {
    print_step "Verifying production functionality"
    
    local verification_failed=false
    
    # Test 1: Container health
    print_status "Testing container health..."
    if ! docker-compose -f docker-compose.production.yml ps | grep -q "Up (healthy)"; then
        print_warning "Some containers may not be healthy"
    fi
    
    # Test 2: Database connectivity
    print_status "Testing database connectivity..."
    if ! docker exec leadership-summit-app php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB_OK';" 2>/dev/null | grep -q "DB_OK"; then
        print_error "Database connectivity test failed"
        verification_failed=true
    fi
    
    # Test 3: Redis connectivity
    print_status "Testing Redis connectivity..."
    if ! docker exec leadership-summit-app php artisan tinker --execute="Redis::ping(); echo 'REDIS_OK';" 2>/dev/null | grep -q "REDIS_OK"; then
        print_error "Redis connectivity test failed"
        verification_failed=true
    fi
    
    # Test 4: Web server response
    print_status "Testing web server response..."
    local max_attempts=10
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        if curl -f -s -k https://leadershipsummit.com/ > /dev/null; then
            print_status "✅ HTTPS endpoint responding"
            break
        elif curl -f -s http://localhost/ > /dev/null; then
            print_status "✅ HTTP endpoint responding"
            break
        fi
        
        print_status "Attempt $attempt/$max_attempts: Waiting for web server..."
        sleep 10
        ((attempt++))
    done
    
    if [ $attempt -gt $max_attempts ]; then
        print_error "Web server not responding after $max_attempts attempts"
        verification_failed=true
    fi
    
    # Test 5: Critical endpoints
    print_status "Testing critical endpoints..."
    local endpoints=("/" "/events" "/login" "/register" "/health")
    
    for endpoint in "${endpoints[@]}"; do
        local response_code=$(curl -s -o /dev/null -w "%{http_code}" -k "https://leadershipsummit.com$endpoint" 2>/dev/null || echo "000")
        if [ "$response_code" = "200" ] || [ "$response_code" = "302" ]; then
            print_status "✅ Endpoint '$endpoint' responding ($response_code)"
        else
            print_error "❌ Endpoint '$endpoint' failed ($response_code)"
            verification_failed=true
        fi
    done
    
    # Test 6: Authentication system
    print_status "Testing authentication system..."
    local login_response=$(curl -s -o /dev/null -w "%{http_code}" -k "https://leadershipsummit.com/login" 2>/dev/null || echo "000")
    if [ "$login_response" = "200" ]; then
        print_status "✅ Authentication system accessible"
    else
        print_error "❌ Authentication system test failed ($login_response)"
        verification_failed=true
    fi
    
    # Test 7: Event management
    print_status "Testing event management..."
    local events_response=$(curl -s -o /dev/null -w "%{http_code}" -k "https://leadershipsummit.com/events" 2>/dev/null || echo "000")
    if [ "$events_response" = "200" ]; then
        print_status "✅ Event management accessible"
    else
        print_error "❌ Event management test failed ($events_response)"
        verification_failed=true
    fi
    
    # Test 8: SSL certificate
    print_status "Testing SSL certificate..."
    if openssl s_client -connect leadershipsummit.com:443 -servername leadershipsummit.com < /dev/null 2>/dev/null | openssl x509 -noout -dates 2>/dev/null; then
        print_status "✅ SSL certificate valid"
    else
        print_warning "⚠️ SSL certificate test inconclusive"
    fi
    
    # Test 9: Performance check
    print_status "Running performance check..."
    local response_time=$(curl -o /dev/null -s -w "%{time_total}" -k "https://leadershipsummit.com/" 2>/dev/null || echo "error")
    if [ "$response_time" != "error" ]; then
        if (( $(echo "$response_time < 3.0" | bc -l 2>/dev/null || echo "0") )); then
            print_status "✅ Response time: ${response_time}s (acceptable)"
        else
            print_warning "⚠️ Response time: ${response_time}s (may need optimization)"
        fi
    fi
    
    # Test 10: Queue system
    print_status "Testing queue system..."
    if docker ps | grep -q "leadership-summit-queue"; then
        print_status "✅ Queue worker running"
    else
        print_warning "⚠️ Queue worker not detected"
    fi
    
    if [ "$verification_failed" = true ]; then
        print_error "Some functionality verification tests failed"
        return 1
    else
        print_status "✅ All functionality verification tests passed"
        return 0
    fi
}

# Function to create deployment report
create_deployment_report() {
    print_step "Creating deployment report"
    
    local report_file="./reports/production_deployment_${DEPLOYMENT_ID}.md"
    mkdir -p ./reports
    
    cat > "$report_file" << EOF
# Production Deployment Report

**Deployment ID:** $DEPLOYMENT_ID  
**Date:** $DEPLOYMENT_DATE  
**Branch:** $BRANCH  
**Environment:** Production  

## Deployment Summary

- **Status:** $([ -f ".production_deployment_success" ] && echo "✅ SUCCESS" || echo "❌ FAILED")
- **Duration:** $(date -d "$DEPLOYMENT_DATE" +%s 2>/dev/null || echo "N/A") seconds
- **Commit:** $(git rev-parse --short HEAD)
- **Commit Message:** $(git log -1 --pretty=%B | head -1)

## Services Status

\`\`\`
$(docker-compose -f docker-compose.production.yml ps)
\`\`\`

## Container Health

$(docker-compose -f docker-compose.production.yml ps --format "table {{.Name}}\t{{.Status}}")

## Verification Results

- Database Connectivity: $(docker exec leadership-summit-app php artisan tinker --execute="DB::connection()->getPdo(); echo 'OK';" 2>/dev/null | grep -q "OK" && echo "✅ PASS" || echo "❌ FAIL")
- Redis Connectivity: $(docker exec leadership-summit-app php artisan tinker --execute="Redis::ping(); echo 'OK';" 2>/dev/null | grep -q "OK" && echo "✅ PASS" || echo "❌ FAIL")
- Web Server: $(curl -f -s -k https://leadershipsummit.com/ > /dev/null && echo "✅ PASS" || echo "❌ FAIL")
- SSL Certificate: $(openssl s_client -connect leadershipsummit.com:443 -servername leadershipsummit.com < /dev/null 2>/dev/null | openssl x509 -noout -dates > /dev/null 2>&1 && echo "✅ PASS" || echo "⚠️ CHECK")

## Next Steps

1. Monitor application performance and logs
2. Verify all user-facing functionality manually
3. Monitor error rates and response times
4. Update monitoring dashboards
5. Notify stakeholders of successful deployment

## Backup Information

- Backup Location: ./backups/production/${DEPLOYMENT_ID}
- Database Backup: $([ -f "./backups/production/${DEPLOYMENT_ID}/database_backup.sql.gz" ] && echo "✅ Created" || echo "❌ Not Found")
- Storage Backup: $([ -f "./backups/production/${DEPLOYMENT_ID}/storage_backup.tar.gz" ] && echo "✅ Created" || echo "❌ Not Found")

## Rollback Information

If rollback is needed, run:
\`\`\`bash
./scripts/rollback.sh production auto
\`\`\`

---
*Report generated automatically on $DEPLOYMENT_DATE*
EOF
    
    print_status "Deployment report created: $report_file"
}

# Function to send deployment notifications
send_deployment_notifications() {
    print_step "Sending deployment notifications"
    
    # Create notification payload
    local notification_data=$(cat << EOF
{
    "deployment_id": "$DEPLOYMENT_ID",
    "environment": "production",
    "status": "$([ -f ".production_deployment_success" ] && echo "success" || echo "failed")",
    "branch": "$BRANCH",
    "commit": "$(git rev-parse --short HEAD)",
    "timestamp": "$DEPLOYMENT_DATE",
    "url": "https://leadershipsummit.com"
}
EOF
)
    
    # Save notification data for external systems
    echo "$notification_data" > "./logs/deployment_notification_${DEPLOYMENT_ID}.json"
    
    print_status "Deployment notification data saved"
    
    # If webhook URL is configured, send notification
    if [ -n "${DEPLOYMENT_WEBHOOK_URL:-}" ]; then
        curl -X POST \
            -H "Content-Type: application/json" \
            -d "$notification_data" \
            "$DEPLOYMENT_WEBHOOK_URL" || print_warning "Failed to send webhook notification"
    fi
}

# Main execution function
main() {
    echo "🚀 Starting Production Deployment Execution"
    echo "=========================================="
    echo "Deployment ID: $DEPLOYMENT_ID"
    echo "Date: $DEPLOYMENT_DATE"
    echo "Branch: $BRANCH"
    echo "Skip Backup: $SKIP_BACKUP"
    echo ""
    
    # Final confirmation
    print_warning "⚠️ WARNING: This will execute PRODUCTION deployment!"
    read -p "Are you absolutely sure you want to continue? (yes/no): " -r
    if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
        print_status "Deployment execution cancelled"
        exit 0
    fi
    
    # Set up error handling
    trap 'print_error "Deployment execution failed! Check logs: $LOG_FILE"; exit 1' ERR
    
    # Execute deployment plan
    execute_deployment_plan
    
    # Create deployment report
    create_deployment_report
    
    # Send notifications
    send_deployment_notifications
    
    # Final status
    if [ -f ".production_deployment_success" ]; then
        echo ""
        print_status "🎉 PRODUCTION DEPLOYMENT EXECUTION COMPLETED SUCCESSFULLY!"
        print_status "Application is live at: https://leadershipsummit.com"
        print_status "Deployment ID: $DEPLOYMENT_ID"
        print_status "Log file: $LOG_FILE"
        
        echo ""
        print_status "Post-deployment checklist:"
        echo "  ✅ Execute deployment plan"
        echo "  ✅ Migrate production data"
        echo "  ✅ Verify functionality"
        echo "  ✅ Create deployment report"
        echo "  ✅ Send notifications"
        
        echo ""
        print_status "Monitoring recommendations:"
        echo "  1. Monitor application logs: docker-compose -f docker-compose.production.yml logs -f"
        echo "  2. Monitor system resources and performance"
        echo "  3. Verify all critical user flows manually"
        echo "  4. Check error rates and response times"
        echo "  5. Update monitoring dashboards"
        
        # Log successful deployment
        echo "$(date): SUCCESSFUL production deployment execution (ID: $DEPLOYMENT_ID, branch: $BRANCH)" >> deployment_history.log
        
    else
        print_error "Production deployment execution failed"
        exit 1
    fi
}

# Show usage information
if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
    echo "Usage: $0 [branch] [skip_backup]"
    echo ""
    echo "This script executes the complete production deployment process including:"
    echo "  - Pre-deployment verification"
    echo "  - Comprehensive backup creation"
    echo "  - Production deployment execution"
    echo "  - Data migration"
    echo "  - Functionality verification"
    echo "  - Post-deployment tasks"
    echo "  - Deployment reporting"
    echo ""
    echo "Arguments:"
    echo "  branch        Git branch to deploy (default: main)"
    echo "  skip_backup   Skip backup creation (true/false, default: false)"
    echo ""
    echo "Examples:"
    echo "  $0                    # Deploy main branch with backup"
    echo "  $0 release/v2.0       # Deploy specific branch"
    echo "  $0 main true          # Deploy without backup (not recommended)"
    echo ""
    echo "Requirements addressed:"
    echo "  - 1.1: Complete migration functionality preservation"
    echo "  - 1.4: Production data migration"
    exit 0
fi

# Execute main function
main "$@"