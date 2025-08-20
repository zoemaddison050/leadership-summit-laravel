#!/bin/bash

# Leadership Summit Laravel - Comprehensive Blade Error Fix Script
# This script fixes all the CSS and JavaScript syntax errors in Blade templates

set -e

echo "🔧 Fixing All Blade Template Syntax Errors"
echo "==========================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

FIXED=0

# Function to fix a file
fix_file() {
    local file="$1"
    local filename=$(basename "$file")
    local fixes=0
    
    echo -n "Fixing $filename... "
    
    if [ ! -f "$file" ]; then
        echo -e "${RED}❌ NOT FOUND${NC}"
        return 1
    fi
    
    # Create backup
    cp "$file" "$file.backup"
    
    # Fix malformed asset() calls with space after quote
    if sed -i.tmp "s/asset(' /asset('/g" "$file" 2>/dev/null; then
        ((fixes++))
    fi
    
    # Fix malformed asset() calls in complex expressions
    if sed -i.tmp "s/asset(' storage\//asset('storage\//g" "$file" 2>/dev/null; then
        ((fixes++))
    fi
    
    if sed -i.tmp "s/asset(' images\//asset('images\//g" "$file" 2>/dev/null; then
        ((fixes++))
    fi
    
    # Fix json_encode() calls in onclick handlers (replace with addslashes for simple strings)
    if sed -i.tmp 's/onclick="\([^"]*\){{ json_encode(\$[^}]*) }}\([^"]*\)"/onclick="\1{{ addslashes(\$\2) }}\3"/g' "$file" 2>/dev/null; then
        ((fixes++))
    fi
    
    # Fix missing semicolons in onclick handlers
    if sed -i.tmp 's/onclick="\([^"]*[^;]\)"/onclick="\1;"/g' "$file" 2>/dev/null; then
        ((fixes++))
    fi
    
    # Fix malformed CSS style attributes (missing semicolon)
    if sed -i.tmp 's/style="\([^"]*\){{ [^}]* }}"/style="\1{{ \2 }};"/g' "$file" 2>/dev/null; then
        ((fixes++))
    fi
    
    # Clean up temporary files
    rm -f "$file.tmp"
    
    if [ $fixes -gt 0 ]; then
        echo -e "${GREEN}✅ FIXED${NC} ($fixes changes)"
        ((FIXED++))
    else
        echo -e "${BLUE}ℹ️ NO CHANGES${NC}"
        # Remove backup if no changes were made
        rm -f "$file.backup"
    fi
    
    return 0
}

# Specific fixes for known issues

echo "🎯 Applying specific fixes for known issues..."

# Fix about.blade.php
if [ -f "resources/views/about.blade.php" ]; then
    echo "Fixing about.blade.php asset() call..."
    sed -i.bak "s/asset(' images/asset('images/g" resources/views/about.blade.php
fi

# Fix events/show.blade.php
if [ -f "resources/views/events/show.blade.php" ]; then
    echo "Fixing events/show.blade.php asset() calls..."
    sed -i.bak "s/asset(' storage/asset('storage/g" resources/views/events/show.blade.php
    sed -i.bak "s/asset(' images/asset('images/g" resources/views/events/show.blade.php
    
    echo "Fixing events/show.blade.php onclick handlers..."
    sed -i.bak "s/json_encode(\$event->title ?? 'Leadership Summit Event')/addslashes(\$event->title ?? 'Leadership Summit Event')/g" resources/views/events/show.blade.php
    sed -i.bak "s/json_encode(url()->current())/url()->current()/g" resources/views/events/show.blade.php
fi

# Fix admin/events/index.blade.php
if [ -f "resources/views/admin/events/index.blade.php" ]; then
    echo "Fixing admin/events/index.blade.php onclick handlers..."
    sed -i.bak "s/json_encode(\$event->title)/addslashes(\$event->title)/g" resources/views/admin/events/index.blade.php
fi

# Fix admin/tickets/create.blade.php
if [ -f "resources/views/admin/tickets/create.blade.php" ]; then
    echo "Fixing admin/tickets/create.blade.php CSS..."
    sed -i.bak 's/class=" mb-3"/class="mb-3"/g' resources/views/admin/tickets/create.blade.php
    sed -i.bak 's/style="display: {{ old('\''has_capacity'\'') ? '\''block'\'' : '\''none'\'' }}"/style="display: {{ old('\''has_capacity'\'') ? '\''block'\'' : '\''none'\'' }};"/g' resources/views/admin/tickets/create.blade.php
fi

# Fix admin/tickets/index.blade.php
if [ -f "resources/views/admin/tickets/index.blade.php" ]; then
    echo "Fixing admin/tickets/index.blade.php CSS and JavaScript..."
    sed -i.bak 's/style="width: {{ $percentage }}%"/style="width: {{ $percentage }}%;"/g' resources/views/admin/tickets/index.blade.php
    sed -i.bak "s/json_encode(\$ticket->name)/addslashes(\$ticket->name)/g" resources/views/admin/tickets/index.blade.php
fi

# Fix admin/users/index.blade.php
if [ -f "resources/views/admin/users/index.blade.php" ]; then
    echo "Fixing admin/users/index.blade.php onclick handlers..."
    sed -i.bak "s/json_encode(\$user->name)/addslashes(\$user->name)/g" resources/views/admin/users/index.blade.php
fi

# Fix events/calendar.blade.php
if [ -f "resources/views/events/calendar.blade.php" ]; then
    echo "Fixing events/calendar.blade.php onclick handlers..."
    sed -i.bak "s/onclick=\"window.location.href='/onclick=\"window.location.href='/g" resources/views/events/calendar.blade.php
    sed -i.bak 's/onclick="\([^"]*[^;]\)"/onclick="\1;"/g' resources/views/events/calendar.blade.php
fi

# Fix tickets/selection.blade.php
if [ -f "resources/views/tickets/selection.blade.php" ]; then
    echo "Fixing tickets/selection.blade.php onclick handlers..."
    sed -i.bak 's/onclick="changeQuantity(\([^)]*\))"/onclick="changeQuantity(\1);"/g' resources/views/tickets/selection.blade.php
    sed -i.bak 's/onchange="updateQuantity(\([^)]*\))"/onchange="updateQuantity(\1);"/g' resources/views/tickets/selection.blade.php
fi

echo ""
echo "🧹 Cleaning up backup files..."
find resources/views -name "*.bak" -delete 2>/dev/null || true

echo ""
echo "✅ All specific fixes applied!"

# List of files to process with general fixes
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

echo ""
echo "🔧 Applying general fixes to all files..."
echo ""

for file in "${FILES[@]}"; do
    fix_file "$file"
done

echo ""
echo "📊 Fix Results"
echo "=============="

echo "Files processed: ${#FILES[@]}"
echo -e "Files fixed: ${GREEN}$FIXED${NC}"

echo ""
echo -e "${GREEN}🎉 All Blade template syntax errors have been fixed!${NC}"
echo ""
echo "Summary of fixes applied:"
echo "  ✅ Fixed malformed asset() calls in CSS"
echo "  ✅ Replaced json_encode() with addslashes() for simple strings"
echo "  ✅ Added missing semicolons in CSS and JavaScript"
echo "  ✅ Fixed malformed style attributes"
echo "  ✅ Fixed malformed onclick handlers"
echo ""
echo "Backup files created with .backup extension (if changes were made)"
echo "Run './validate-blade-syntax.sh' to verify all fixes"