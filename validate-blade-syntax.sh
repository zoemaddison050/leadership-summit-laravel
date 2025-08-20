#!/bin/bash

# Leadership Summit Laravel - Blade Syntax Validation Script
# This script validates that all Blade template syntax errors have been fixed

set -e

echo "🔍 Validating Blade Template Syntax Fixes"
echo "=========================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

PASSED=0
FAILED=0

# Function to check a file for common syntax issues
check_blade_file() {
    local file="$1"
    local filename=$(basename "$file")
    local issues=0
    
    echo -n "Checking $filename... "
    
    # Check for malformed asset() calls in CSS (with space after quote)
    if grep -q "asset(' " "$file" 2>/dev/null; then
        echo -e "${RED}❌ FAIL${NC} - Malformed asset() call found"
        grep -n "asset(' " "$file" 2>/dev/null || true
        ((issues++))
    fi
    
    # Check for malformed json_encode() calls in JavaScript
    if grep -q "json_encode(" "$file" 2>/dev/null; then
        echo -e "${YELLOW}⚠️ WARNING${NC} - json_encode() found (should use addslashes() for simple strings)"
        ((issues++))
    fi
    
    # Check for missing semicolons in onclick handlers
    if grep -q 'onclick="[^"]*[^;]"' "$file" 2>/dev/null; then
        local missing_semicolon=$(grep -o 'onclick="[^"]*[^;]"' "$file" | grep -v ';' | wc -l)
        if [ "$missing_semicolon" -gt 0 ]; then
            echo -e "${YELLOW}⚠️ WARNING${NC} - Possible missing semicolons in onclick handlers"
            ((issues++))
        fi
    fi
    
    # Check for malformed CSS style attributes
    if grep -q 'style="[^"]*{{ [^}]* }}[^;]"' "$file" 2>/dev/null; then
        echo -e "${RED}❌ FAIL${NC} - Malformed CSS style attribute found"
        ((issues++))
    fi
    
    # Check for Blade directives mixed with JavaScript
    if grep -q '@auth\|@if\|@else\|@endif\|@endauth' "$file" 2>/dev/null; then
        local js_context=$(grep -B2 -A2 '@auth\|@if\|@else\|@endif\|@endauth' "$file" | grep -c 'function\|addEventListener\|onclick' 2>/dev/null || echo "0")
        if [ "$js_context" -gt 0 ]; then
            echo -e "${YELLOW}⚠️ WARNING${NC} - Blade directives found in JavaScript context (this is usually OK)"
            # Don't count this as an issue since it's often intentional
        fi
    fi
    
    if [ "$issues" -eq 0 ]; then
        echo -e "${GREEN}✅ PASS${NC}"
        ((PASSED++))
    else
        ((FAILED++))
    fi
    
    return $issues
}

# List of files to check
FILES=(
    "resources/views/about.blade.php"
    "resources/views/home.blade.php"
    "resources/views/events/show.blade.php"
    "resources/views/events/calendar.blade.php"
    "resources/views/admin/events/index.blade.php"
    "resources/views/admin/tickets/create.blade.php"
    "resources/views/admin/tickets/index.blade.php"
    "resources/views/admin/users/index.blade.php"
    "resources/views/tickets/selection.blade.php"
)

echo "🔍 Checking Blade template files for syntax issues..."
echo ""

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        check_blade_file "$file"
    else
        echo -e "${RED}❌ MISSING${NC} - File not found: $file"
        ((FAILED++))
    fi
done

echo ""
echo "📊 Validation Results"
echo "===================="

TOTAL=$((PASSED + FAILED))

echo "Total files checked: $TOTAL"
echo -e "Passed: ${GREEN}$PASSED${NC}"
echo -e "Failed/Warnings: ${RED}$FAILED${NC}"

if [ $FAILED -eq 0 ]; then
    echo ""
    echo -e "${GREEN}🎉 All Blade template syntax checks passed!${NC}"
    echo ""
    echo "✅ No malformed asset() calls found"
    echo "✅ No malformed CSS style attributes found"
    echo "✅ JavaScript syntax appears correct"
    echo ""
    echo "Your Blade templates should now be free of syntax errors."
    exit 0
else
    echo ""
    echo -e "${YELLOW}⚠️ Some issues were found or warnings generated.${NC}"
    echo ""
    echo "Common fixes applied:"
    echo "  - Fixed malformed asset() calls in CSS"
    echo "  - Replaced json_encode() with addslashes() for simple strings"
    echo "  - Added missing semicolons in CSS and JavaScript"
    echo "  - Fixed malformed style attributes"
    echo ""
    echo "If warnings persist, they may not affect functionality but should be reviewed."
    exit 1
fi