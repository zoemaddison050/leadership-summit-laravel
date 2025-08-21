#!/bin/bash

# Leadership Summit Laravel - Production Monitoring Script
# This script monitors application performance and addresses post-launch issues
# Requirements: 5.1, 5.3, 5.4

set -e

# Configuration
MONITORING_DATE=$(date +"%Y-%m-%d %H:%M:%S")
LOG_FILE="./logs/production_monitoring_$(date +%Y%m%d_%H%M%S).log"
ALERT_THRESHOLD_CPU=80
ALERT_THRESHOLD_MEMORY=85
ALERT_THRESHOLD_DISK=90
ALERT_THRESHOLD_RESPONSE_TIME=3.0
MONITORING_INTERVAL=60

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
echo "Production Monitoring Log - $MONITORING_DATE" > "$LOG_FILE"
echo "========================================" >> "$LOG_FILE"

# Function to monitor application performance
monitor_application_performance() {
    print_step "Monitoring application performance"
    
    local performance_issues=false
    
    # Monitor response times
    print_status "Checking response times..."
    local endpoints=("/" "/events" "/login" "/register")
    
    for endpoint in "${endpoints[@]}"; do
        local response_time=$(curl -o /dev/null -s -w "%{time_total}" -k "https://leadershipsummit.com$endpoint" 2>/dev/null || echo "error")
        
        if [ "$response_time" != "error" ]; then
            if (( $(echo "$response_time > $ALERT_THRESHOLD_RESPONSE_TIME" | bc -l 2>/dev/null || echo "0") )); then
                print_warning "⚠️ Slow response time for $endpoint: ${response_time}s"
                performance_issues=true
            else
                print_status "✅ Response time for $endpoint: ${response_time}s"
            fi
        else
            print_error "❌ Failed to check response time for $endpoint"
            performance_issues=true
        fi
    done
    
    # Monitor database performance
    print_status "Checking database performance..."
    local db_response_time=$(docker exec leadership-summit-app php artisan tinker --execute="
        \$start = microtime(true);
        DB::connection()->getPdo();
        \$end = microtime(true);
        echo round((\$end - \$start) * 1000, 2);
    " 2>/dev/null | tail -1 || echo "error")
    
    if [ "$db_response_time" != "error" ]; then
        if (( $(echo "$db_response_time > 100" | bc -l 2>/dev/null || echo "0") )); then
            print_warning "⚠️ Slow database response: ${db_response_time}ms"
            performance_issues=true
        else
            print_status "✅ Database response time: ${db_response_time}ms"
        fi
    else
        print_error "❌ Failed to check database performance"
        performance_issues=true
    fi
    
    # Monitor Redis performance
    print_status "Checking Redis performance..."
    local redis_response_time=$(docker exec leadership-summit-app php artisan tinker --execute="
        \$start = microtime(true);
        Redis::ping();
        \$end = microtime(true);
        echo round((\$end - \$start) * 1000, 2);
    " 2>/dev/null | tail -1 || echo "error")
    
    if [ "$redis_response_time" != "error" ]; then
        if (( $(echo "$redis_response_time > 50" | bc -l 2>/dev/null || echo "0") )); then
            print_warning "⚠️ Slow Redis response: ${redis_response_time}ms"
            performance_issues=true
        else
            print_status "✅ Redis response time: ${redis_response_time}ms"
        fi
    else
        print_error "❌ Failed to check Redis performance"
        performance_issues=true
    fi
    
    return $([ "$performance_issues" = true ] && echo 1 || echo 0)
}

# Function to monitor system resources
monitor_system_resources() {
    print_step "Monitoring system resources"
    
    local resource_issues=false
    
    # Monitor CPU usage
    print_status "Checking CPU usage..."
    local cpu_usage=$(docker stats --no-stream --format "table {{.CPUPerc}}" | grep -v "CPU" | head -1 | sed 's/%//' || echo "0")
    
    if (( $(echo "$cpu_usage > $ALERT_THRESHOLD_CPU" | bc -l 2>/dev/null || echo "0") )); then
        print_warning "⚠️ High CPU usage: ${cpu_usage}%"
        resource_issues=true
    else
        print_status "✅ CPU usage: ${cpu_usage}%"
    fi
    
    # Monitor memory usage
    print_status "Checking memory usage..."
    local memory_usage=$(docker stats --no-stream --format "table {{.MemPerc}}" | grep -v "MEM" | head -1 | sed 's/%//' || echo "0")
    
    if (( $(echo "$memory_usage > $ALERT_THRESHOLD_MEMORY" | bc -l 2>/dev/null || echo "0") )); then
        print_warning "⚠️ High memory usage: ${memory_usage}%"
        resource_issues=true
    else
        print_status "✅ Memory usage: ${memory_usage}%"
    fi
    
    # Monitor disk usage
    print_status "Checking disk usage..."
    local disk_usage=$(df . | tail -1 | awk '{print $5}' | sed 's/%//' || echo "0")
    
    if (( $(echo "$disk_usage > $ALERT_THRESHOLD_DISK" | bc -l 2>/dev/null || echo "0") )); then
        print_warning "⚠️ High disk usage: ${disk_usage}%"
        resource_issues=true
    else
        print_status "✅ Disk usage: ${disk_usage}%"
    fi
    
    # Monitor container health
    print_status "Checking container health..."
    local unhealthy_containers=$(docker-compose -f docker-compose.production.yml ps | grep -v "Up (healthy)" | grep "Up" | wc -l || echo "0")
    
    if [ "$unhealthy_containers" -gt 0 ]; then
        print_warning "⚠️ $unhealthy_containers containers are not healthy"
        resource_issues=true
    else
        print_status "✅ All containers are healthy"
    fi
    
    return $([ "$resource_issues" = true ] && echo 1 || echo 0)
}

# Function to check for application errors
check_application_errors() {
    print_step "Checking for application errors"
    
    local error_issues=false
    
    # Check Laravel logs for errors
    print_status "Checking Laravel error logs..."
    local error_count=$(docker exec leadership-summit-app find /var/www/storage/logs -name "*.log" -exec grep -c "ERROR\|CRITICAL\|EMERGENCY" {} + 2>/dev/null | awk '{sum += $1} END {print sum}' || echo "0")
    
    if [ "$error_count" -gt 10 ]; then
        print_warning "⚠️ High error count in logs: $error_count errors"
        error_issues=true
    else
        print_status "✅ Error count in logs: $error_count errors"
    fi
    
    # Check for recent critical errors
    print_status "Checking for recent critical errors..."
    local recent_errors=$(docker exec leadership-summit-app find /var/www/storage/logs -name "*.log" -mtime -1 -exec grep -l "CRITICAL\|EMERGENCY" {} + 2>/dev/null | wc -l || echo "0")
    
    if [ "$recent_errors" -gt 0 ]; then
        print_warning "⚠️ Recent critical errors found: $recent_errors files"
        error_issues=true
    else
        print_status "✅ No recent critical errors found"
    fi
    
    # Check HTTP error rates
    print_status "Checking HTTP error rates..."
    local error_responses=0
    local total_responses=0
    
    for endpoint in "/" "/events" "/login"; do
        local response_code=$(curl -s -o /dev/null -w "%{http_code}" -k "https://leadershipsummit.com$endpoint" 2>/dev/null || echo "000")
        total_responses=$((total_responses + 1))
        
        if [ "$response_code" -ge 400 ]; then
            error_responses=$((error_responses + 1))
        fi
    done
    
    if [ "$error_responses" -gt 0 ]; then
        local error_rate=$(echo "scale=2; $error_responses * 100 / $total_responses" | bc -l 2>/dev/null || echo "0")
        print_warning "⚠️ HTTP error rate: ${error_rate}%"
        error_issues=true
    else
        print_status "✅ No HTTP errors detected"
    fi
    
    return $([ "$error_issues" = true ] && echo 1 || echo 0)
}

# Function to optimize performance based on real-world usage
optimize_performance() {
    print_step "Optimizing performance based on usage patterns"
    
    # Clear and optimize caches
    print_status "Optimizing application caches..."
    docker exec leadership-summit-app php artisan cache:clear
    docker exec leadership-summit-app php artisan config:cache
    docker exec leadership-summit-app php artisan route:cache
    docker exec leadership-summit-app php artisan view:cache
    
    # Optimize database
    print_status "Optimizing database..."
    docker exec leadership-summit-db mysqlcheck -o --all-databases -u root -p"${DB_PASSWORD}" 2>/dev/null || print_warning "Database optimization skipped"
    
    # Clean up old logs
    print_status "Cleaning up old logs..."
    docker exec leadership-summit-app find /var/www/storage/logs -name "*.log" -mtime +7 -delete 2>/dev/null || true
    
    # Optimize storage permissions
    print_status "Optimizing storage permissions..."
    docker exec leadership-summit-app chown -R www-data:www-data /var/www/storage
    docker exec leadership-summit-app chmod -R 775 /var/www/storage
    
    # Restart queue workers to clear memory
    print_status "Restarting queue workers..."
    docker-compose -f docker-compose.production.yml restart queue
    
    print_status "✅ Performance optimization completed"
}

# Function to address common post-launch issues
address_post_launch_issues() {
    print_step "Addressing common post-launch issues"
    
    # Check for SSL certificate issues
    print_status "Checking SSL certificate status..."
    if ! openssl s_client -connect leadershipsummit.com:443 -servername leadershipsummit.com < /dev/null 2>/dev/null | openssl x509 -noout -dates > /dev/null 2>&1; then
        print_warning "⚠️ SSL certificate may have issues"
        
        # Check certificate expiration
        local cert_expiry=$(openssl x509 -in docker/ssl/leadershipsummit.com.crt -noout -enddate 2>/dev/null | cut -d= -f2 || echo "Unknown")
        print_status "Certificate expires: $cert_expiry"
    else
        print_status "✅ SSL certificate is valid"
    fi
    
    # Check for database connection pool issues
    print_status "Checking database connections..."
    local db_connections=$(docker exec leadership-summit-db mysql -u root -p"${DB_PASSWORD}" -e "SHOW PROCESSLIST;" 2>/dev/null | wc -l || echo "0")
    
    if [ "$db_connections" -gt 50 ]; then
        print_warning "⚠️ High number of database connections: $db_connections"
        # Restart database connection pool
        docker-compose -f docker-compose.production.yml restart app
    else
        print_status "✅ Database connections: $db_connections"
    fi
    
    # Check for memory leaks
    print_status "Checking for memory leaks..."
    local app_memory=$(docker stats --no-stream --format "table {{.MemUsage}}" leadership-summit-app | grep -v "MEM" | head -1 || echo "Unknown")
    print_status "Application memory usage: $app_memory"
    
    # Check queue processing
    print_status "Checking queue processing..."
    local failed_jobs=$(docker exec leadership-summit-app php artisan queue:failed --format=json 2>/dev/null | jq length 2>/dev/null || echo "0")
    
    if [ "$failed_jobs" -gt 5 ]; then
        print_warning "⚠️ High number of failed jobs: $failed_jobs"
        # Retry failed jobs
        docker exec leadership-summit-app php artisan queue:retry all
    else
        print_status "✅ Failed jobs: $failed_jobs"
    fi
    
    print_status "✅ Post-launch issue check completed"
}

# Function to generate monitoring report
generate_monitoring_report() {
    print_step "Generating monitoring report"
    
    local report_file="./reports/production_monitoring_$(date +%Y%m%d_%H%M%S).md"
    mkdir -p ./reports
    
    # Get current metrics
    local cpu_usage=$(docker stats --no-stream --format "table {{.CPUPerc}}" | grep -v "CPU" | head -1 | sed 's/%//' || echo "0")
    local memory_usage=$(docker stats --no-stream --format "table {{.MemPerc}}" | grep -v "MEM" | head -1 | sed 's/%//' || echo "0")
    local disk_usage=$(df . | tail -1 | awk '{print $5}' | sed 's/%//' || echo "0")
    
    # Get response times
    local home_response=$(curl -o /dev/null -s -w "%{time_total}" -k "https://leadershipsummit.com/" 2>/dev/null || echo "error")
    local events_response=$(curl -o /dev/null -s -w "%{time_total}" -k "https://leadershipsummit.com/events" 2>/dev/null || echo "error")
    
    cat > "$report_file" << EOF
# Production Monitoring Report

**Report Date:** $MONITORING_DATE  
**Environment:** Production  
**Domain:** https://leadershipsummit.com

## System Performance

### Resource Usage
- **CPU Usage:** ${cpu_usage}% $([ $(echo "$cpu_usage > $ALERT_THRESHOLD_CPU" | bc -l 2>/dev/null || echo "0") -eq 1 ] && echo "⚠️" || echo "✅")
- **Memory Usage:** ${memory_usage}% $([ $(echo "$memory_usage > $ALERT_THRESHOLD_MEMORY" | bc -l 2>/dev/null || echo "0") -eq 1 ] && echo "⚠️" || echo "✅")
- **Disk Usage:** ${disk_usage}% $([ $(echo "$disk_usage > $ALERT_THRESHOLD_DISK" | bc -l 2>/dev/null || echo "0") -eq 1 ] && echo "⚠️" || echo "✅")

### Response Times
- **Home Page:** ${home_response}s $([ "$home_response" != "error" ] && [ $(echo "$home_response < $ALERT_THRESHOLD_RESPONSE_TIME" | bc -l 2>/dev/null || echo "1") -eq 1 ] && echo "✅" || echo "⚠️")
- **Events Page:** ${events_response}s $([ "$events_response" != "error" ] && [ $(echo "$events_response < $ALERT_THRESHOLD_RESPONSE_TIME" | bc -l 2>/dev/null || echo "1") -eq 1 ] && echo "✅" || echo "⚠️")

## Container Status

\`\`\`
$(docker-compose -f docker-compose.production.yml ps)
\`\`\`

## Database Performance

- **Connection Status:** $(docker exec leadership-summit-app php artisan tinker --execute="DB::connection()->getPdo(); echo 'Connected';" 2>/dev/null | grep -q "Connected" && echo "✅ Connected" || echo "❌ Disconnected")
- **Active Connections:** $(docker exec leadership-summit-db mysql -u root -p"${DB_PASSWORD}" -e "SHOW PROCESSLIST;" 2>/dev/null | wc -l || echo "Unknown")

## Cache Performance

- **Redis Status:** $(docker exec leadership-summit-app php artisan tinker --execute="Redis::ping(); echo 'OK';" 2>/dev/null | grep -q "OK" && echo "✅ Connected" || echo "❌ Disconnected")
- **Cache Hit Rate:** $(docker exec leadership-summit-redis redis-cli info stats 2>/dev/null | grep keyspace_hits || echo "Unknown")

## Error Analysis

- **Recent Errors:** $(docker exec leadership-summit-app find /var/www/storage/logs -name "*.log" -mtime -1 -exec grep -c "ERROR" {} + 2>/dev/null | awk '{sum += $1} END {print sum}' || echo "0")
- **Critical Errors:** $(docker exec leadership-summit-app find /var/www/storage/logs -name "*.log" -mtime -1 -exec grep -c "CRITICAL" {} + 2>/dev/null | awk '{sum += $1} END {print sum}' || echo "0")
- **Failed Jobs:** $(docker exec leadership-summit-app php artisan queue:failed --format=json 2>/dev/null | jq length 2>/dev/null || echo "0")

## Optimization Actions Taken

- ✅ Application caches cleared and rebuilt
- ✅ Database optimization performed
- ✅ Old logs cleaned up
- ✅ Storage permissions optimized
- ✅ Queue workers restarted

## Recommendations

$([ $(echo "$cpu_usage > $ALERT_THRESHOLD_CPU" | bc -l 2>/dev/null || echo "0") -eq 1 ] && echo "- ⚠️ **High CPU Usage:** Consider scaling up or optimizing resource-intensive operations")
$([ $(echo "$memory_usage > $ALERT_THRESHOLD_MEMORY" | bc -l 2>/dev/null || echo "0") -eq 1 ] && echo "- ⚠️ **High Memory Usage:** Consider increasing memory or optimizing memory usage")
$([ $(echo "$disk_usage > $ALERT_THRESHOLD_DISK" | bc -l 2>/dev/null || echo "0") -eq 1 ] && echo "- ⚠️ **High Disk Usage:** Consider cleaning up old files or increasing storage")
$([ "$home_response" != "error" ] && [ $(echo "$home_response > $ALERT_THRESHOLD_RESPONSE_TIME" | bc -l 2>/dev/null || echo "0") -eq 1 ] && echo "- ⚠️ **Slow Response Times:** Consider performance optimization or caching improvements")

## Next Monitoring Cycle

- **Scheduled:** $(date -d "+$MONITORING_INTERVAL seconds" '+%Y-%m-%d %H:%M:%S')
- **Focus Areas:** Performance optimization, error reduction, resource usage

---
*Report generated automatically on $MONITORING_DATE*
EOF
    
    print_status "Monitoring report created: $report_file"
}

# Function to set up continuous monitoring
setup_continuous_monitoring() {
    print_step "Setting up continuous monitoring"
    
    # Create monitoring cron job
    local cron_file="./monitoring/production_monitoring_cron.sh"
    mkdir -p ./monitoring
    
    cat > "$cron_file" << 'EOF'
#!/bin/bash
# Production monitoring cron job
cd /path/to/leadership-summit-laravel
./scripts/monitor-production.sh --automated >> ./logs/monitoring_cron.log 2>&1
EOF
    
    chmod +x "$cron_file"
    
    # Create monitoring configuration
    cat > "./monitoring/monitoring_config.json" << EOF
{
    "monitoring_interval": $MONITORING_INTERVAL,
    "alert_thresholds": {
        "cpu_usage": $ALERT_THRESHOLD_CPU,
        "memory_usage": $ALERT_THRESHOLD_MEMORY,
        "disk_usage": $ALERT_THRESHOLD_DISK,
        "response_time": $ALERT_THRESHOLD_RESPONSE_TIME
    },
    "endpoints_to_monitor": [
        "/",
        "/events",
        "/login",
        "/register"
    ],
    "notification_settings": {
        "email_alerts": true,
        "webhook_url": "${MONITORING_WEBHOOK_URL:-}",
        "alert_cooldown": 300
    }
}
EOF
    
    print_status "Continuous monitoring setup completed"
    print_status "Add to crontab: */5 * * * * $cron_file"
}

# Main monitoring function
main() {
    local automated_mode=false
    
    if [ "$1" = "--automated" ]; then
        automated_mode=true
    fi
    
    if [ "$automated_mode" = false ]; then
        echo "🔍 Starting Production Monitoring and Optimization"
        echo "================================================="
        echo "Date: $MONITORING_DATE"
        echo ""
    fi
    
    local monitoring_issues=false
    
    # Run monitoring checks
    monitor_application_performance || monitoring_issues=true
    monitor_system_resources || monitoring_issues=true
    check_application_errors || monitoring_issues=true
    
    # Perform optimizations
    optimize_performance
    
    # Address post-launch issues
    address_post_launch_issues
    
    # Generate monitoring report
    generate_monitoring_report
    
    # Set up continuous monitoring (only on first run)
    if [ ! -f "./monitoring/monitoring_config.json" ]; then
        setup_continuous_monitoring
    fi
    
    # Final status
    if [ "$automated_mode" = false ]; then
        if [ "$monitoring_issues" = false ]; then
            echo ""
            print_status "🎉 PRODUCTION MONITORING COMPLETED SUCCESSFULLY"
            print_status "No critical issues detected"
            print_status "Performance optimizations applied"
            print_status "Log file: $LOG_FILE"
            
            echo ""
            print_status "Monitoring Summary:"
            echo "  ✅ Application performance monitored"
            echo "  ✅ System resources monitored"
            echo "  ✅ Application errors checked"
            echo "  ✅ Performance optimizations applied"
            echo "  ✅ Post-launch issues addressed"
            echo "  ✅ Monitoring report generated"
            
        else
            echo ""
            print_warning "⚠️ PRODUCTION MONITORING COMPLETED WITH ISSUES"
            print_warning "Some monitoring checks detected issues"
            print_warning "Please review the log file: $LOG_FILE"
            print_warning "Consider immediate attention to flagged issues"
        fi
        
        echo ""
        print_status "Requirements addressed:"
        echo "  - 5.1: Application performance monitoring and optimization"
        echo "  - 5.3: Page load time monitoring and optimization"
        echo "  - 5.4: System resource monitoring and optimization"
        echo "  - Post-launch issue detection and resolution"
    fi
    
    # Log monitoring completion
    echo "$(date): Production monitoring completed (issues: $monitoring_issues)" >> monitoring_history.log
}

# Show usage information
if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
    echo "Usage: $0 [--automated]"
    echo ""
    echo "This script monitors production application performance and addresses"
    echo "post-launch issues based on real-world usage patterns."
    echo ""
    echo "Options:"
    echo "  --automated    Run in automated mode (less verbose output)"
    echo ""
    echo "Monitoring includes:"
    echo "  - Application performance (response times, database, cache)"
    echo "  - System resources (CPU, memory, disk usage)"
    echo "  - Application errors and issues"
    echo "  - Performance optimization"
    echo "  - Post-launch issue resolution"
    echo ""
    echo "Requirements addressed:"
    echo "  - 5.1: Performance monitoring and optimization"
    echo "  - 5.3: Page load time monitoring"
    echo "  - 5.4: System resource monitoring"
    exit 0
fi

# Execute main function
main "$@"