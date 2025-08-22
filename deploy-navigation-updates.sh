#!/bin/bash

# Deploy navigation updates to production Laravel installation
# Copy updated files from ~/leadership-summit-laravel to ~/leadership

echo "Starting navigation updates deployment..."

# Copy updated view files
echo "Copying updated view files..."
cp resources/views/layouts/app.blade.php ~/leadership/resources/views/layouts/
cp resources/views/components/header.blade.php ~/leadership/resources/views/components/
cp resources/views/components/navigation.blade.php ~/leadership/resources/views/components/
cp resources/views/about.blade.php ~/leadership/resources/views/
cp resources/views/dashboard.blade.php ~/leadership/resources/views/

# Copy updated SCSS source files
echo "Copying updated SCSS files..."
cp resources/sass/components/_base.scss ~/leadership/resources/sass/components/
cp resources/sass/components/_header.scss ~/leadership/resources/sass/components/

echo "Navigation updates deployed successfully!"
echo "Changes deployed:"
echo "- Removed skip navigation links from app layout"
echo "- Removed breadcrumb navigation from about and dashboard pages"  
echo "- Removed Home link from navigation menu (logo now serves as home)"
echo "- Added improved menu spacing with nav-spaced class"
echo "- Made logo clickable as home link in header"
echo ""
echo "Next steps:"
echo "1. Compile CSS assets in ~/leadership/ directory"
echo "2. Copy compiled assets to public_html"
