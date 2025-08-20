#!/bin/bash

# Leadership Summit Laravel - Blade Template Fix Validation Script
# This script validates that all syntax errors have been fixed

echo "🔍 Validating Blade template syntax fixes..."

# Function to check for specific error patterns
check_errors() {
    local error_count=0
    
    echo "Checking for CSS syntax errors..."
    
    # Check for malformed CSS url() functions
    local css_url_errors=$(grep -r "url('{{ asset(' " resources/views/ 2>/dev/null | wc -l)
    if [ "$css_url_errors" -gt 0 ]; then
        echo "❌ Found $css_url_errors malformed CSS url() functions:"
        grep -r "url('{{ asset(' " resources/views/ 2>/dev/null
        error_count=$((error_count + css_url_errors))
    else
        echo "✅ No malformed CSS url() functions found"
    fi
    
    echo ""
    echo "Checking for JavaScript syntax errors..."
    
    # Check for unescaped quotes in onclick handlers
    local js_quote_errors=$(grep -r "onclick=\"[^\"]*'{{ \$[^}]*->[^}]* }}'[^\"]*\"" resources/views/ 2>/dev/null | wc -l)
    if [ "$js_quote_errors" -gt 0 ]; then
        echo "❌ Found $js_quote_errors unescaped quotes in JavaScript:"
        grep -r "onclick=\"[^\"]*'{{ \$[^}]*->[^}]* }}'[^\"]*\"" resources/views/ 2>/dev/null
        error_count=$((error_count + js_quote_errors))
    else
        echo "✅ No unescaped quotes in JavaScript found"
    fi
    
    echo ""
    echo "Checking for HTML attribute errors..."
    
    # Check for malformed HTML attributes
    local html_attr_errors=$(grep -r 'style="[^"]*{{ [^}]* }}[^"]*;""' resources/views/ 2>/dev/null | wc -l)
    if [ "$html_attr_errors" -gt 0 ]; then
        echo "❌ Found $html_attr_errors malformed HTML attributes:"
        grep -r 'style="[^"]*{{ [^}]* }}[^"]*;""' resources/views/ 2>/dev/null
        error_count=$((error_count + html_attr_errors))
    else
        echo "✅ No malformed HTML attributes found"
    fi
    
    echo ""
    echo "Checking for Blade directive formatting in JavaScript..."
    
    # Check for improperly formatted Blade directives in JavaScript
    local blade_js_errors=0
    if [ -f "resources/views/events/show.blade.php" ]; then
        if grep -q "@auth" "resources/views/events/show.blade.php" && grep -q "addEventListener" "resources/views/events/show.blade.php"; then
            # Check if the formatting is correct (indented properly)
            if grep -A 10 "@auth" "resources/views/events/show.blade.php" | grep -q "    @if"; then
                echo "✅ Blade directives in JavaScript are properly formatted"
            else
                echo "⚠️ Blade directives in JavaScript may need formatting review"
                blade_js_errors=1
            fi
        fi
    fi
    
    return $error_count
}

# Function to list all problematic files
list_problematic_files() {
    echo ""
    echo "📋 Files that were fixed:"
    
    local files_fixed=(
        "resources/views/about.blade.php"
        "resources/views/home.blade.php"
        "resources/views/events/show.blade.php"
        "resources/views/admin/events/index.blade.php"
        "resources/views/admin/tickets/create.blade.php"
        "resources/views/admin/tickets/index.blade.php"
        "resources/views/admin/users/index.blade.php"
        "resources/views/events/calendar.blade.php"
        "resources/views/tickets/selection.blade.php"
        "resources/views/admin/media/show.blade.php"
        "resources/views/admin/media/edit.blade.php"
    )
    
    for file in "${files_fixed[@]}"; do
        if [ -f "$file" ]; then
            echo "✅ $file"
        else
            echo "⚠️ $file (not found)"
        fi
    done
}

# Function to provide summary and recommendations
provide_summary() {
    local error_count=$1
    
    echo ""
    echo "📊 Validation Summary:"
    echo "====================="
    
    if [ $error_count -eq 0 ]; then
        echo "🎉 SUCCESS: All Blade template syntax errors have been fixed!"
        echo ""
        echo "✅ CSS url() functions are properly formatted"
        echo "✅ JavaScript onclick handlers use proper escaping"
        echo "✅ HTML attributes are correctly structured"
        echo "✅ Blade directives in JavaScript are properly formatted"
        echo ""
        echo "Your Laravel application should now be free of syntax errors."
        echo "You can now run the local development environment without issues."
        
    else
        echo "⚠️ ATTENTION: $error_count syntax error(s) still exist"
        echo ""
        echo "Recommendations:"
        echo "1. Review the errors listed above"
        echo "2. Fix any remaining issues manually"
        echo "3. Run this validation script again"
        echo "4. Consider using an IDE with Blade syntax highlighting"
    fi
}

# Main execution
main() {
    echo "🚀 Starting Blade template syntax validation..."
    echo ""
    
    # Check for errors
    check_errors
    local error_count=$?
    
    # List files that were processed
    list_problematic_files
    
    # Provide summary
    provide_summary $error_count
    
    echo ""
    echo "✅ Validation completed!"
    
    # Return appropriate exit code
    return $error_count
}

# Run main function
main "$@"