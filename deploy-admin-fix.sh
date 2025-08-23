#!/bin/zsh

echo "🚀 Deploying Admin Fix to Production Server"
echo "==========================================="

# Configuration
SERVER_ALIAS="globaleadershipacademy01_rsa"
SERVER_PATH="/home/globalea/leadership-summit-laravel"
FIX_SCRIPT="fix-production-admin-v3.php"

echo "📤 Uploading fix script to production server..."

# Upload the fix script to the server
scp "$FIX_SCRIPT" "$SERVER_ALIAS:$SERVER_PATH/"

if [ $? -eq 0 ]; then
    echo "✅ Fix script uploaded successfully"
else
    echo "❌ Failed to upload fix script"
    exit 1
fi

echo ""
echo "🔧 Running admin fix on production server..."

# Run the fix script on the server
ssh "$SERVER_ALIAS" "cd $SERVER_PATH && /usr/local/php83/bin/php $FIX_SCRIPT"

if [ $? -eq 0 ]; then
    echo "✅ Admin fix completed successfully"
else
    echo "❌ Admin fix failed"
    exit 1
fi

echo ""
echo "🧹 Cleaning up fix script from server..."

# Remove the fix script from the server
ssh "$SERVER_ALIAS" "rm $SERVER_PATH/$FIX_SCRIPT"

echo ""
echo "🎉 DEPLOYMENT COMPLETE!"
echo "======================"
echo ""
echo "Your production admin login should now work:"
echo "🌐 URL: https://globaleadershipacademy.com/login"
echo "📧 Email: admin@leadershipsummit.com"
echo "🔑 Password: password"
echo ""
echo "If you still have issues, you can also run the fix via web browser:"
echo "1. Upload fix-production-admin.php to your server"
echo "2. Visit: https://globaleadershipacademy.com/fix-production-admin.php"
echo ""
echo "🔍 To verify the fix worked, try logging in now!"