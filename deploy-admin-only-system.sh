#!/bin/zsh

echo "🔧 Deploying Admin-Only System Changes"
echo "====================================="

# Configuration
SERVER_ALIAS="globaleadershipacademy01_rsa"
SERVER_PATH="/home/globalea/leadership-summit-laravel"

echo "📤 Uploading updated files to production server..."

# Upload the modified navigation file
scp "resources/views/components/navigation.blade.php" "$SERVER_ALIAS:$SERVER_PATH/resources/views/components/"

# Upload the new admin dashboard
scp "resources/views/admin-dashboard.blade.php" "$SERVER_ALIAS:$SERVER_PATH/resources/views/"

# Upload the modified routes file
scp "routes/web.php" "$SERVER_ALIAS:$SERVER_PATH/routes/"

if [ $? -eq 0 ]; then
    echo "✅ Files uploaded successfully"
else
    echo "❌ Failed to upload files"
    exit 1
fi

echo ""
echo "🔧 Clearing caches on production server..."

# Clear all caches on the server
ssh "$SERVER_ALIAS" "cd $SERVER_PATH && /usr/local/php83/bin/php artisan config:clear && /usr/local/php83/bin/php artisan cache:clear && /usr/local/php83/bin/php artisan route:clear && /usr/local/php83/bin/php artisan view:clear"

if [ $? -eq 0 ]; then
    echo "✅ Caches cleared successfully"
else
    echo "❌ Failed to clear caches"
    exit 1
fi

echo ""
echo "🎉 DEPLOYMENT COMPLETE!"
echo "======================"
echo ""
echo "Changes deployed:"
echo "✅ Removed 'Admin' link from navigation"
echo "✅ Enhanced 'Admin User' dropdown with admin functions"
echo "✅ Created admin-focused dashboard"
echo "✅ Restricted dashboard access to admin users only"
echo "✅ Updated quick actions for admin management"
echo ""
echo "🌐 Your admin system is now live:"
echo "• Login: https://globaleadershipacademy.com/login"
echo "• Dashboard: https://globaleadershipacademy.com/dashboard"
echo "• Admin Panel: https://globaleadershipacademy.com/admin"
echo ""
echo "📧 Admin Credentials:"
echo "• Email: admin@leadershipsummit.com"
echo "• Password: password"
echo ""
echo "🔍 Test the new admin interface now!"