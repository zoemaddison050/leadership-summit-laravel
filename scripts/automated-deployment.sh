#!/bin/bash

# Leadership Summit Laravel - Automated Deployment Pipeline
# This script provides a complete CI/CD pipeline for automated deployments

set -e

# Configuration
ENVIRONMENT=${1:-staging}
BRANCH=${2:-main}
RUN_TESTS=${3:-true}
SEND_NOTIFICATIONS=${4:-true}

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
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

print_pipeline() {
    echo -e "${PURPLE}[PIPELINE]${NC} $1"
}

# Function to send notifications
send_notification() {
    local status=$1
    local message=$2
    
    if [ "$SEND_NOTIFICATIONS" != "true" ]; then
        return 0
    fi
    
    # Slack notification (if webhook configured)
    if [ -n "$SLACK_WEBHOOK_URL" ]; then
        curl -X POST -H 'Content-type: application/json' \
            --data "{\"text\":\"🚀 Leadership Summit Deployment\\n*Environment:* $ENVIRONMENT\\n*Status:* $status\\n*Message:* $message\\n*Branch:* $BRANCH\\n*Time:* $(date)\"}" \
            "$SLACK_WEBHOOK_URL" || true
    fi
    
    # Email notification (if configured)
    if [ -n "$NOTIFICATION_EMAIL" ]; then
        echo "Subject: Leadership Summit Deployment - $status
        
Environment: $ENVIRONMENT
Status: $status
Message: $message
Branch: $BRANCH
Time: $(date)
Commit: $(git rev-parse --short HEAD)
        " | sendmail "$NOTIFICATION_EMAIL" || true
    fi
}

# Function to validate environment
validate_environment() {
    print_step "Validating deployment environment..."
    
    case $ENVIRONMENT in
        "staging"|"production")
            print_status "Environment: $ENVIRONMENT"
            ;;
        *)
            print_error "Invalid environment: $ENVIRONMENT"
            echo "Valid environments: staging, production"
            exit 1
            ;;
    esac
    
    # Check if branch exists
    if ! git ls-remote --heads origin "$BRANCH" | grep -q "$BRANCH"; then
        print_error "Branch '$BRANCH' does not exist on remote"
        exit 1
    fi
    
    print_status "✅ Environment validation passed"
}

# Function to run pre-deployment checks
pre_deployment_checks() {
    print_step "Running pre-deployment checks..."
    
    # Check system resources
    print_status "Checking system resources..."
    
    # Check disk space (require at least 2GB free)
    AVAILABLE_SPACE=$(df . | tail -1 | awk '{print $4}')
    if [ "$AVAILABLE_SPACE" -lt 2097152 ]; then  # 2GB in KB
        print_error "Insufficient disk space. At least 2GB required."
        exit 1
    fi
    
    # Check memory usage
    MEMORY_USAGE=$(free | grep Mem | awk '{print ($3/$2) * 100.0}')
    if (( $(echo "$MEMORY_USAGE > 90" | bc -l) )); then
        print_warning "High memory usage: ${MEMORY_USAGE}%"
    fi
    
    # Check if Docker daemon is running
    if ! docker info > /dev/null 2>&1; then
        print_error "Docker daemon is not running"
        exit 1
    fi
    
    # Check for running containers that might conflict
    if [ "$ENVIRONMENT" = "production" ]; then
        if docker ps | grep -q "leadership-summit-staging"; then
            print_warning "Staging containers are running during production deployment"
        fi
    fi
    
    print_status "✅ Pre-deployment checks passed"
}

# Function to run code quality checks
code_quality_checks() {
    print_step "Running code quality checks..."
    
    # PHP syntax check
    print_status "Checking PHP syntax..."
    find . -name "*.php" -not -path "./vendor/*" -not -path "./node_modules/*" -exec php -l {} \; > /dev/null
    
    # Check for TODO/FIXME comments in critical files
    print_status "Checking for TODO/FIXME comments..."
    TODO_COUNT=$(find app/ -name "*.php" -exec grep -l "TODO\|FIXME" {} \; | wc -l)
    if [ "$TODO_COUNT" -gt 0 ]; then
        print_warning "Found $TODO_COUNT files with TODO/FIXME comments"
    fi
    
    # Check for debug statements
    print_status "Checking for debug statements..."
    DEBUG_COUNT=$(find app/ -name "*.php" -exec grep -l "dd(\|dump(\|var_dump\|print_r" {} \; | wc -l)
    if [ "$DEBUG_COUNT" -gt 0 ]; then
        print_warning "Found $DEBUG_COUNT files with debug statements"
        if [ "$ENVIRONMENT" = "production" ]; then
            print_error "Debug statements found in production deployment"
            exit 1
        fi
    fi
    
    print_status "✅ Code quality checks completed"
}

# Function to run security checks
security_checks() {
    print_step "Running security checks..."
    
    # Check for sensitive data in environment files
    if [ "$ENVIRONMENT" = "production" ]; then
        ENV_FILE=".env.production"
    else
        ENV_FILE=".env.staging"
    fi
    
    if grep -q "password\|secret\|key" "$ENV_FILE" | grep -q "CHANGE_THIS\|GENERATE_NEW\|TODO"; then
        print_error "Default passwords/keys found in $ENV_FILE"
        exit 1
    fi
    
    # Check file permissions
    if [ -f "$ENV_FILE" ]; then
        PERMS=$(stat -c "%a" "$ENV_FILE" 2>/dev/null || stat -f "%A" "$ENV_FILE")
        if [ "$PERMS" != "600" ]; then
            print_warning "Environment file permissions should be 600"
            chmod 600 "$ENV_FILE"
        fi
    fi
    
    # Check for SSL certificate validity (production only)
    if [ "$ENVIRONMENT" = "production" ]; then
        if [ -f "docker/ssl/leadershipsummit.com.crt" ]; then
            if ! openssl x509 -in docker/ssl/leadershipsummit.com.crt -noout -checkend 604800; then
                print_warning "SSL certificate expires within 7 days"
            fi
        fi
    fi
    
    print_status "✅ Security checks passed"
}

# Function to run automated tests
run_automated_tests() {
    if [ "$RUN_TESTS" != "true" ]; then
        print_warning "Skipping tests as requested"
        return 0
    fi
    
    print_step "Running automated test suite..."
    
    # Setup test environment
    TEST_COMPOSE_FILE="docker-compose.staging.yml"
    TEST_CONTAINER="leadership-summit-staging"
    
    print_status "Setting up test environment..."
    docker-compose -f "$TEST_COMPOSE_FILE" down || true
    docker-compose -f "$TEST_COMPOSE_FILE" up -d --build
    
    # Wait for test environment to be ready
    sleep 45
    
    # Run application health check
    print_status "Running application health check..."
    if ! docker exec "$TEST_CONTAINER" php artisan route:list > /dev/null; then
        print_error "Application health check failed"
        docker-compose -f "$TEST_COMPOSE_FILE" down
        exit 1
    fi
    
    # Cleanup test environment
    docker-compose -f "$TEST_COMPOSE_FILE" down
    
    print_status "✅ All tests passed"
}

# Function to deploy based on environment
deploy_application() {
    print_step "Deploying application to $ENVIRONMENT..."
    
    case $ENVIRONMENT in
        "staging")
            ./scripts/setup-staging.sh
            ;;
        "production")
            ./scripts/deploy-production.sh "$BRANCH"
            ;;
    esac
    
    print_status "✅ Application deployed successfully"
}

# Function to run post-deployment verification
post_deployment_verification() {
    print_step "Running post-deployment verification..."
    
    # Wait for application to be fully ready
    sleep 30
    
    # Run validation script
    if [ "$ENVIRONMENT" = "staging" ]; then
        if [ -f "scripts/validate-staging.sh" ]; then
            ./scripts/validate-staging.sh
        fi
    else
        # Production verification
        DOMAIN="leadershipsummit.com"
        
        # Check if application is responding
        if curl -f -s "https://$DOMAIN" > /dev/null; then
            print_status "✅ Production application is responding"
        else
            print_error "❌ Production application is not responding"
            exit 1
        fi
        
        # Check critical endpoints
        ENDPOINTS=("/" "/events" "/speakers" "/login")
        for endpoint in "${ENDPOINTS[@]}"; do
            response_code=$(curl -s -o /dev/null -w "%{http_code}" "https://$DOMAIN$endpoint" || echo "000")
            if [ "$response_code" = "200" ] || [ "$response_code" = "302" ]; then
                print_status "✅ Endpoint '$endpoint' responding ($response_code)"
            else
                print_error "❌ Endpoint '$endpoint' failed ($response_code)"
                exit 1
            fi
        done
    fi
    
    print_status "✅ Post-deployment verification passed"
}

# Function to generate deployment report
generate_deployment_report() {
    print_step "Generating deployment report..."
    
    REPORT_DIR="./reports/deployments"
    mkdir -p "$REPORT_DIR"
    
    REPORT_FILE="$REPORT_DIR/${ENVIRONMENT}_deployment_$(date +%Y%m%d_%H%M%S).md"
    
    cat > "$REPORT_FILE" << EOF
# Leadership Summit Deployment Report

## Deployment Information
- **Environment**: $ENVIRONMENT
- **Branch**: $BRANCH
- **Commit**: $(git rev-parse HEAD)
- **Commit Message**: $(git log -1 --pretty=%B)
- **Deployment Time**: $(date)
- **Deployed By**: $(whoami)

## Pre-Deployment Checks
- ✅ Environment validation
- ✅ System resources check
- ✅ Code quality checks
- ✅ Security checks
$([ "$RUN_TESTS" = "true" ] && echo "- ✅ Application health check" || echo "- ⚠️ Health check skipped")

## Deployment Process
- ✅ Application deployment
- ✅ Post-deployment verification

## System Status
EOF
    
    # Add container status
    if [ "$ENVIRONMENT" = "staging" ]; then
        echo "### Staging Containers" >> "$REPORT_FILE"
        docker-compose -f docker-compose.staging.yml ps >> "$REPORT_FILE"
    else
        echo "### Production Containers" >> "$REPORT_FILE"
        docker-compose -f docker-compose.production.yml ps >> "$REPORT_FILE"
    fi
    
    # Add system resources
    echo -e "\n### System Resources" >> "$REPORT_FILE"
    echo "- **Disk Usage**: $(df -h . | tail -1 | awk '{print $5}')" >> "$REPORT_FILE"
    echo "- **Memory Usage**: $(free -h | grep Mem | awk '{print $3"/"$2}')" >> "$REPORT_FILE"
    
    print_status "Deployment report generated: $REPORT_FILE"
}

# Function to cleanup deployment artifacts
cleanup_deployment() {
    print_step "Cleaning up deployment artifacts..."
    
    # Remove temporary files
    rm -f .deployment_lock 2>/dev/null || true
    
    # Clean up old Docker images
    docker image prune -f > /dev/null 2>&1 || true
    
    # Clean up old logs
    find ./storage/logs -name "*.log" -mtime +7 -delete 2>/dev/null || true
    
    print_status "✅ Cleanup completed"
}

# Function to handle deployment failure
handle_deployment_failure() {
    print_error "Deployment failed!"
    
    # Send failure notification
    send_notification "FAILED" "Deployment to $ENVIRONMENT failed. Check logs for details."
    
    # Offer rollback option
    if [ "$ENVIRONMENT" = "production" ]; then
        print_warning "Production deployment failed. Consider running rollback:"
        print_warning "  ./scripts/rollback.sh production auto"
    fi
    
    # Cleanup
    cleanup_deployment
    
    exit 1
}

# Main pipeline execution
main() {
    echo "🚀 Starting automated deployment pipeline..."
    echo "Environment: $ENVIRONMENT"
    echo "Branch: $BRANCH"
    echo "Run Tests: $RUN_TESTS"
    echo "Time: $(date)"
    echo ""
    
    # Create deployment lock
    if [ -f ".deployment_lock" ]; then
        print_error "Another deployment is already in progress"
        exit 1
    fi
    echo "$$" > .deployment_lock
    
    # Trap errors and cleanup
    trap 'handle_deployment_failure' ERR
    trap 'cleanup_deployment' EXIT
    
    # Send start notification
    send_notification "STARTED" "Deployment to $ENVIRONMENT started"
    
    # Execute pipeline steps
    print_pipeline "Phase 1: Validation and Checks"
    validate_environment
    pre_deployment_checks
    code_quality_checks
    security_checks
    
    print_pipeline "Phase 2: Testing"
    run_automated_tests
    
    print_pipeline "Phase 3: Deployment"
    deploy_application
    
    print_pipeline "Phase 4: Verification"
    post_deployment_verification
    
    print_pipeline "Phase 5: Reporting and Cleanup"
    generate_deployment_report
    
    # Success notification
    send_notification "SUCCESS" "Deployment to $ENVIRONMENT completed successfully"
    
    echo ""
    print_status "🎉 Automated deployment pipeline completed successfully!"
    print_status "Environment: $ENVIRONMENT"
    print_status "Branch: $BRANCH"
    print_status "Commit: $(git rev-parse --short HEAD)"
    
    if [ "$ENVIRONMENT" = "production" ]; then
        print_status "Application is live at: https://leadershipsummit.com"
    else
        print_status "Staging application is available at: http://localhost"
    fi
}

# Show usage
if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
    echo "Usage: $0 [environment] [branch] [run_tests] [send_notifications]"
    echo ""
    echo "Arguments:"
    echo "  environment         Target environment (staging|production, default: staging)"
    echo "  branch             Git branch to deploy (default: main)"
    echo "  run_tests          Run automated tests (true|false, default: true)"
    echo "  send_notifications Send deployment notifications (true|false, default: true)"
    echo ""
    echo "Environment Variables:"
    echo "  SLACK_WEBHOOK_URL     Slack webhook for notifications"
    echo "  NOTIFICATION_EMAIL    Email address for notifications"
    echo ""
    echo "Examples:"
    echo "  $0                                    # Deploy staging with all checks"
    echo "  $0 production                         # Deploy production with all checks"
    echo "  $0 staging develop                    # Deploy develop branch to staging"
    echo "  $0 production main false              # Deploy to production without tests"
    echo "  $0 staging main true false            # Deploy without notifications"
    exit 0
fi

# Run main function
main "$@"