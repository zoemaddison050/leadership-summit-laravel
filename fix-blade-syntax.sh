#!/bin/bash

# Leadership Summit Laravel - Blade Template Syntax Fix Script
# This script fixes common syntax errors in Blade templates

echo "🔧 Fixing Blade template syntax errors..."

# Fix CSS url() functions with malformed Blade syntax
echo "Fixing CSS url() functions..."

# Fix any remaining malformed asset() calls in CSS
find resources/views -name "*.blade.php" -exec sed -i.bak "s/url('{{ asset(' /url('{{ asset('/g" {} \;
find resources/views -name "*.blade.php" -exec sed -i.bak "s/') }}') /') }}') /g" {} \;

# Fix JavaScript onclick handlers with unescaped quotes
echo "Fixing JavaScript onclick handlers..."

# Replace single quotes with json_encode for dynamic content in onclick handlers
find resources/views -name "*.blade.php" -exec sed -i.bak "s/onclick=\"\([^\"]*\){{ \$[^}]*->title }}\([^\"]*\)\"/onclick=\"\1{{ json_encode(\$event->title ?? '') }}\2\"/g" {} \;
find resources/views -name "*.blade.php" -exec sed -i.bak "s/onclick=\"\([^\"]*\){{ \$[^}]*->name }}\([^\"]*\)\"/onclick=\"\1{{ json_encode(\$ticket->name ?? '') }}\2\"/g" {} \;

# Fix malformed HTML attributes
echo "Fixing HTML attributes..."

# Fix style attributes with malformed syntax
find resources/views -name "*.blade.php" -exec sed -i.bak 's/style="[^"]*{{ [^}]* }}[^"]*;""/style="{{ old('\''has_capacity'\'') ? '\''display: block'\'' : '\''display: none'\'' }}"/g' {} \;

# Fix title attributes with potential quote issues
find resources/views -name "*.blade.php" -exec sed -i.bak 's/title="{{ \$[^}]*->title }}/title="{{ e(\$event->title ?? '\''Event'\'') }}/g' {} \;

# Clean up backup files
echo "Cleaning up backup files..."
find resources/views -name "*.bak" -delete

echo "✅ Blade template syntax fixes completed!"

# Validate the fixes
echo "🔍 Validating fixes..."

# Check for common syntax issues
echo "Checking for remaining issues..."

# Check for malformed url() functions
malformed_urls=$(grep -r "url('{{ asset(' resources/views/ || true)
if [ -n "$malformed_urls" ]; then
    echo "⚠️ Found remaining malformed URL functions:"
    echo "$malformed_urls"
else
    echo "✅ No malformed URL functions found"
fi

# Check for unescaped quotes in onclick handlers
unescaped_quotes=$(grep -r "onclick=\"[^\"]*'[^']*{{ \$[^}]*->[^}]* }}[^']*'[^\"]*\"" resources/views/ || true)
if [ -n "$unescaped_quotes" ]; then
    echo "⚠️ Found remaining unescaped quotes in onclick handlers:"
    echo "$unescaped_quotes"
else
    echo "✅ No unescaped quotes in onclick handlers found"
fi

echo "🎉 Syntax fix validation completed!"