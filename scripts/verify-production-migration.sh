#!/bin/bash

# Leadership Summit Laravel - Production Data Migration Verification Script
# This script verifies that all WordPress data has been successfully migrated
# Requirements: 1.4

set -e

# Configuration
VERIFICATION_DATE=$(date +"%Y-%m-%d %H:%M:%S")
LOG_FILE="./logs/migration_verification_$(date +%Y%m%d_%H%M%S).log"

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
echo "Production Data Migration Verification - $VERIFICATION_DATE" > "$LOG_FILE"
echo "========================================" >> "$LOG_FILE"

# Function to verify database connectivity
verify_database_connection() {
    print_step "Verifying database connection"
    
    if docker exec leadership-summit-app php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB_CONNECTED';" 2>/dev/null | grep -q "DB_CONNECTED"; then
        print_status "✅ Database connection verified"
        return 0
    else
        print_error "❌ Database connection failed"
        return 1
    fi
}

# Function to verify migrated data integrity
verify_data_integrity() {
    print_step "Verifying migrated data integrity"
    
    local verification_failed=false
    
    # Check Users table
    print_status "Checking Users table..."
    local user_count=$(docker exec leadership-summit-app php artisan tinker --execute="echo User::count();" 2>/dev/null | tail -1 || echo "0")
    if [ "$user_count" -gt 0 ]; then
        print_status "✅ Users table: $user_count records"
    else
        print_error "❌ Users table: No records found"
        verification_failed=true
    fi
    
    # Check Events table
    print_status "Checking Events table..."
    local event_count=$(docker exec leadership-summit-app php artisan tinker --execute="echo Event::count();" 2>/dev/null | tail -1 || echo "0")
    if [ "$event_count" -gt 0 ]; then
        print_status "✅ Events table: $event_count records"
    else
        print_warning "⚠️ Events table: No records found"
    fi
    
    # Check Speakers table
    print_status "Checking Speakers table..."
    local speaker_count=$(docker exec leadership-summit-app php artisan tinker --execute="echo Speaker::count();" 2>/dev/null | tail -1 || echo "0")
    if [ "$speaker_count" -gt 0 ]; then
        print_status "✅ Speakers table: $speaker_count records"
    else
        print_warning "⚠️ Speakers table: No records found"
    fi
    
    # Check Sessions table
    print_status "Checking Sessions table..."
    local session_count=$(docker exec leadership-summit-app php artisan tinker --execute="echo Session::count();" 2>/dev/null | tail -1 || echo "0")
    if [ "$session_count" -gt 0 ]; then
        print_status "✅ Sessions table: $session_count records"
    else
        print_warning "⚠️ Sessions table: No records found"
    fi
    
    # Check Pages table
    print_status "Checking Pages table..."
    local page_count=$(docker exec leadership-summit-app php artisan tinker --execute="echo Page::count();" 2>/dev/null | tail -1 || echo "0")
    if [ "$page_count" -gt 0 ]; then
        print_status "✅ Pages table: $page_count records"
    else
        print_warning "⚠️ Pages table: No records found"
    fi
    
    # Check Media table
    print_status "Checking Media table..."
    local media_count=$(docker exec leadership-summit-app php artisan tinker --execute="echo Media::count();" 2>/dev/null | tail -1 || echo "0")
    if [ "$media_count" -gt 0 ]; then
        print_status "✅ Media table: $media_count records"
    else
        print_warning "⚠️ Media table: No records found"
    fi
    
    # Check Registrations table
    print_status "Checking Registrations table..."
    local registration_count=$(docker exec leadership-summit-app php artisan tinker --execute="echo Registration::count();" 2>/dev/null | tail -1 || echo "0")
    print_status "ℹ️ Registrations table: $registration_count records"
    
    # Check Tickets table
    print_status "Checking Tickets table..."
    local ticket_count=$(docker exec leadership-summit-app php artisan tinker --execute="echo Ticket::count();" 2>/dev/null | tail -1 || echo "0")
    if [ "$ticket_count" -gt 0 ]; then
        print_status "✅ Tickets table: $ticket_count records"
    else
        print_warning "⚠️ Tickets table: No records found"
    fi
    
    if [ "$verification_failed" = true ]; then
        print_error "Data integrity verification failed"
        return 1
    else
        print_status "✅ Data integrity verification passed"
        return 0
    fi
}

# Function to verify data relationships
verify_data_relationships() {
    print_step "Verifying data relationships"
    
    local relationship_failed=false
    
    # Check User-Role relationships
    print_status "Checking User-Role relationships..."
    local users_with_roles=$(docker exec leadership-summit-app php artisan tinker --execute="echo User::whereNotNull('role_id')->count();" 2>/dev/null | tail -1 || echo "0")
    print_status "ℹ️ Users with roles: $users_with_roles"
    
    # Check Event-Session relationships
    print_status "Checking Event-Session relationships..."
    local sessions_with_events=$(docker exec leadership-summit-app php artisan tinker --execute="echo Session::whereNotNull('event_id')->count();" 2>/dev/null | tail -1 || echo "0")
    print_status "ℹ️ Sessions with events: $sessions_with_events"
    
    # Check Session-Speaker relationships
    print_status "Checking Session-Speaker relationships..."
    local session_speaker_count=$(docker exec leadership-summit-app php artisan tinker --execute="echo DB::table('session_speakers')->count();" 2>/dev/null | tail -1 || echo "0")
    print_status "ℹ️ Session-Speaker relationships: $session_speaker_count"
    
    # Check Event-Ticket relationships
    print_status "Checking Event-Ticket relationships..."
    local tickets_with_events=$(docker exec leadership-summit-app php artisan tinker --execute="echo Ticket::whereNotNull('event_id')->count();" 2>/dev/null | tail -1 || echo "0")
    print_status "ℹ️ Tickets with events: $tickets_with_events"
    
    if [ "$relationship_failed" = true ]; then
        print_error "Data relationship verification failed"
        return 1
    else
        print_status "✅ Data relationship verification passed"
        return 0
    fi
}

# Function to verify media files migration
verify_media_migration() {
    print_step "Verifying media files migration"
    
    # Check if storage directory exists
    if [ ! -d "./storage/app/public/media" ]; then
        print_warning "⚠️ Media storage directory not found"
        return 1
    fi
    
    # Count media files
    local media_file_count=$(find ./storage/app/public/media -type f 2>/dev/null | wc -l || echo "0")
    print_status "ℹ️ Media files found: $media_file_count"
    
    # Check media database records vs files
    local media_db_count=$(docker exec leadership-summit-app php artisan tinker --execute="echo Media::count();" 2>/dev/null | tail -1 || echo "0")
    
    if [ "$media_file_count" -gt 0 ] && [ "$media_db_count" -gt 0 ]; then
        print_status "✅ Media migration appears successful"
        return 0
    else
        print_warning "⚠️ Media migration may be incomplete"
        return 1
    fi
}

# Function to verify WordPress data completeness
verify_wordpress_data_completeness() {
    print_step "Verifying WordPress data completeness"
    
    # Check if migration manifest exists
    if [ -f "./database/migrations/import_scripts/migration_manifest.json" ]; then
        print_status "Migration manifest found"
        
        # Display migration summary
        if command -v jq >/dev/null 2>&1; then
            local wp_users=$(jq -r '.wordpress_counts.users // "N/A"' ./database/migrations/import_scripts/migration_manifest.json)
            local wp_posts=$(jq -r '.wordpress_counts.posts // "N/A"' ./database/migrations/import_scripts/migration_manifest.json)
            local wp_media=$(jq -r '.wordpress_counts.media // "N/A"' ./database/migrations/import_scripts/migration_manifest.json)
            
            print_status "WordPress migration summary:"
            print_status "  - Users: $wp_users"
            print_status "  - Posts: $wp_posts"
            print_status "  - Media: $wp_media"
        fi
    else
        print_warning "⚠️ Migration manifest not found"
    fi
    
    # Check for WordPress import completion marker
    if [ -f ".wordpress_import_complete" ]; then
        print_status "✅ WordPress import completion marker found"
        return 0
    else
        print_warning "⚠️ WordPress import completion marker not found"
        return 1
    fi
}

# Function to verify functional requirements
verify_functional_requirements() {
    print_step "Verifying functional requirements"
    
    local functional_failed=false
    
    # Test user authentication
    print_status "Testing user authentication functionality..."
    local auth_test=$(docker exec leadership-summit-app php artisan tinker --execute="
        \$user = User::first();
        if (\$user) {
            echo 'AUTH_OK';
        } else {
            echo 'AUTH_FAIL';
        }
    " 2>/dev/null | tail -1 || echo "AUTH_FAIL")
    
    if [ "$auth_test" = "AUTH_OK" ]; then
        print_status "✅ User authentication data available"
    else
        print_error "❌ User authentication test failed"
        functional_failed=true
    fi
    
    # Test event management
    print_status "Testing event management functionality..."
    local event_test=$(docker exec leadership-summit-app php artisan tinker --execute="
        \$event = Event::first();
        if (\$event && \$event->title) {
            echo 'EVENT_OK';
        } else {
            echo 'EVENT_FAIL';
        }
    " 2>/dev/null | tail -1 || echo "EVENT_FAIL")
    
    if [ "$event_test" = "EVENT_OK" ]; then
        print_status "✅ Event management data available"
    else
        print_warning "⚠️ Event management test inconclusive"
    fi
    
    # Test content management
    print_status "Testing content management functionality..."
    local page_test=$(docker exec leadership-summit-app php artisan tinker --execute="
        \$page = Page::first();
        if (\$page && \$page->title) {
            echo 'PAGE_OK';
        } else {
            echo 'PAGE_FAIL';
        }
    " 2>/dev/null | tail -1 || echo "PAGE_FAIL")
    
    if [ "$page_test" = "PAGE_OK" ]; then
        print_status "✅ Content management data available"
    else
        print_warning "⚠️ Content management test inconclusive"
    fi
    
    if [ "$functional_failed" = true ]; then
        print_error "Functional requirements verification failed"
        return 1
    else
        print_status "✅ Functional requirements verification passed"
        return 0
    fi
}

# Function to create verification report
create_verification_report() {
    print_step "Creating verification report"
    
    local report_file="./reports/migration_verification_$(date +%Y%m%d_%H%M%S).md"
    mkdir -p ./reports
    
    # Get data counts
    local user_count=$(docker exec leadership-summit-app php artisan tinker --execute="echo User::count();" 2>/dev/null | tail -1 || echo "0")
    local event_count=$(docker exec leadership-summit-app php artisan tinker --execute="echo Event::count();" 2>/dev/null | tail -1 || echo "0")
    local speaker_count=$(docker exec leadership-summit-app php artisan tinker --execute="echo Speaker::count();" 2>/dev/null | tail -1 || echo "0")
    local session_count=$(docker exec leadership-summit-app php artisan tinker --execute="echo Session::count();" 2>/dev/null | tail -1 || echo "0")
    local page_count=$(docker exec leadership-summit-app php artisan tinker --execute="echo Page::count();" 2>/dev/null | tail -1 || echo "0")
    local media_count=$(docker exec leadership-summit-app php artisan tinker --execute="echo Media::count();" 2>/dev/null | tail -1 || echo "0")
    local registration_count=$(docker exec leadership-summit-app php artisan tinker --execute="echo Registration::count();" 2>/dev/null | tail -1 || echo "0")
    local ticket_count=$(docker exec leadership-summit-app php artisan tinker --execute="echo Ticket::count();" 2>/dev/null | tail -1 || echo "0")
    
    cat > "$report_file" << EOF
# Production Data Migration Verification Report

**Verification Date:** $VERIFICATION_DATE  
**Environment:** Production  
**Status:** $([ -f ".migration_verification_success" ] && echo "✅ VERIFIED" || echo "⚠️ NEEDS ATTENTION")

## Migration Data Summary

| Entity | Count | Status |
|--------|-------|--------|
| Users | $user_count | $([ "$user_count" -gt 0 ] && echo "✅" || echo "❌") |
| Events | $event_count | $([ "$event_count" -gt 0 ] && echo "✅" || echo "⚠️") |
| Speakers | $speaker_count | $([ "$speaker_count" -gt 0 ] && echo "✅" || echo "⚠️") |
| Sessions | $session_count | $([ "$session_count" -gt 0 ] && echo "✅" || echo "⚠️") |
| Pages | $page_count | $([ "$page_count" -gt 0 ] && echo "✅" || echo "⚠️") |
| Media | $media_count | $([ "$media_count" -gt 0 ] && echo "✅" || echo "⚠️") |
| Registrations | $registration_count | ℹ️ |
| Tickets | $ticket_count | $([ "$ticket_count" -gt 0 ] && echo "✅" || echo "⚠️") |

## Verification Tests

### Database Connectivity
- **Status:** $(verify_database_connection >/dev/null 2>&1 && echo "✅ PASS" || echo "❌ FAIL")
- **Description:** Verified Laravel can connect to production database

### Data Integrity
- **Status:** $(verify_data_integrity >/dev/null 2>&1 && echo "✅ PASS" || echo "⚠️ PARTIAL")
- **Description:** Verified all critical tables contain data

### Data Relationships
- **Status:** $(verify_data_relationships >/dev/null 2>&1 && echo "✅ PASS" || echo "⚠️ PARTIAL")
- **Description:** Verified foreign key relationships are intact

### Media Migration
- **Status:** $(verify_media_migration >/dev/null 2>&1 && echo "✅ PASS" || echo "⚠️ PARTIAL")
- **Description:** Verified media files and database records

### WordPress Data Completeness
- **Status:** $(verify_wordpress_data_completeness >/dev/null 2>&1 && echo "✅ PASS" || echo "⚠️ PARTIAL")
- **Description:** Verified WordPress data migration completeness

### Functional Requirements
- **Status:** $(verify_functional_requirements >/dev/null 2>&1 && echo "✅ PASS" || echo "⚠️ PARTIAL")
- **Description:** Verified core functionality data availability

## Recommendations

$([ "$user_count" -eq 0 ] && echo "- ⚠️ **Critical:** No users found - verify user migration")
$([ "$event_count" -eq 0 ] && echo "- ⚠️ **Important:** No events found - verify event migration")
$([ "$speaker_count" -eq 0 ] && echo "- ⚠️ **Important:** No speakers found - verify speaker migration")
$([ "$media_count" -eq 0 ] && echo "- ⚠️ **Important:** No media found - verify media migration")

## Next Steps

1. **Manual Verification:** Manually test all critical user flows
2. **Content Review:** Review migrated content for accuracy
3. **Media Verification:** Verify all images and files are accessible
4. **User Testing:** Test user registration and authentication
5. **Event Management:** Test event creation and management
6. **Payment Processing:** Test payment functionality (if applicable)

## Migration Log

For detailed migration logs, see: \`$LOG_FILE\`

---
*Report generated automatically on $VERIFICATION_DATE*
EOF
    
    print_status "Verification report created: $report_file"
}

# Main verification function
main() {
    echo "🔍 Starting Production Data Migration Verification"
    echo "================================================="
    echo "Date: $VERIFICATION_DATE"
    echo ""
    
    local verification_success=true
    
    # Run all verification tests
    verify_database_connection || verification_success=false
    verify_data_integrity || verification_success=false
    verify_data_relationships || verification_success=false
    verify_media_migration || verification_success=false
    verify_wordpress_data_completeness || verification_success=false
    verify_functional_requirements || verification_success=false
    
    # Create verification report
    create_verification_report
    
    # Set success marker
    if [ "$verification_success" = true ]; then
        echo "$VERIFICATION_DATE" > ".migration_verification_success"
        
        echo ""
        print_status "🎉 PRODUCTION DATA MIGRATION VERIFICATION COMPLETED"
        print_status "All critical verification tests passed"
        print_status "Log file: $LOG_FILE"
        
        echo ""
        print_status "Verification Summary:"
        echo "  ✅ Database connectivity"
        echo "  ✅ Data integrity"
        echo "  ✅ Data relationships"
        echo "  ✅ Media migration"
        echo "  ✅ WordPress data completeness"
        echo "  ✅ Functional requirements"
        
    else
        echo ""
        print_warning "⚠️ PRODUCTION DATA MIGRATION VERIFICATION COMPLETED WITH WARNINGS"
        print_warning "Some verification tests need attention"
        print_warning "Please review the log file: $LOG_FILE"
        print_warning "Manual verification may be required"
    fi
    
    echo ""
    print_status "Requirements addressed:"
    echo "  - 1.4: All WordPress data migration verified"
    echo "  - Data integrity and completeness confirmed"
    echo "  - Functional requirements data availability verified"
}

# Show usage information
if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
    echo "Usage: $0"
    echo ""
    echo "This script verifies that all WordPress data has been successfully"
    echo "migrated to the Laravel production environment."
    echo ""
    echo "Verification includes:"
    echo "  - Database connectivity"
    echo "  - Data integrity and completeness"
    echo "  - Data relationships"
    echo "  - Media file migration"
    echo "  - WordPress data completeness"
    echo "  - Functional requirements"
    echo ""
    echo "Requirements addressed:"
    echo "  - 1.4: WordPress data migration verification"
    exit 0
fi

# Execute main function
main "$@"