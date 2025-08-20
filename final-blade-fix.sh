#!/bin/bash

# Final fix for the remaining Blade syntax errors

echo "🔧 Applying final fixes to Blade templates..."

# Fix about.blade.php
echo "Fixing about.blade.php..."
sed -i '' "s/asset(' /asset('/g" resources/views/about.blade.php

# Fix home.blade.php  
echo "Fixing home.blade.php..."
sed -i '' "s/asset(' /asset('/g" resources/views/home.blade.php

# Verify fixes
echo "🔍 Verifying fixes..."

if grep -q "asset(' " resources/views/about.blade.php; then
    echo "❌ about.blade.php still has issues"
else
    echo "✅ about.blade.php fixed"
fi

if grep -q "asset(' " resources/views/home.blade.php; then
    echo "❌ home.blade.php still has issues"
else
    echo "✅ home.blade.php fixed"
fi

echo "✅ Final fixes completed!"