#!/bin/zsh

echo "🔧 Deploying Storage Fix to Production Server"
echo "============================================="

# Configuration
SERVER_ALIAS="globaleadershipacademy01_rsa"
SERVER_PATH="/home/globalea/leadership-summit-laravel"

echo "📤 Uploading storage fix files to production server..."

# Upload the filesystem config
scp "config/filesystems.php" "$SERVER_ALIAS:$SERVER_PATH/config/"

# Upload the storage fix script
scp "fix-production-storage.php" "$SERVER_ALIAS:$SERVER_PATH/"

if [ $? -eq 0 ]; then
    echo "✅ Files uploaded successfully"
else
    echo "❌ Failed to upload files"
    exit 1
fi

echo ""
echo "🔧 Running storage fix on production server..."

# Run the storage fix script
ssh "$SERVER_ALIAS" "cd $SERVER_PATH && /usr/local/php83/bin/php fix-production-storage.php"

if [ $? -eq 0 ]; then
    echo "✅ Storage fix completed"
else
    echo "❌ Storage fix failed"
    exit 1
fi

echo ""
echo "🧹 Cleaning up fix script from server..."

# Remove the fix script from the server
ssh "$SERVER_ALIAS" "rm $SERVER_PATH/fix-production-storage.php"

echo ""
echo "🔄 Clearing application caches..."

# Clear caches to ensure new config is loaded
ssh "$SERVER_ALIAS" "cd $SERVER_PATH && /usr/local/php83/bin/php artisan config:clear && /usr/local/php83/bin/php artisan cache:clear"

echo ""
echo "🎉 STORAGE FIX DEPLOYMENT COMPLETE!"
echo "==================================="
echo ""
echo "Storage system has been fixed:"
echo "✅ Added missing filesystems.php configuration"
echo "✅ Created required storage directories"
echo "✅ Set proper file permissions (775/664)"
echo "✅ Created/verified storage symlink"
echo "✅ Tested file upload functionality"
echo ""
echo "🔍 Try uploading a speaker image again:"
echo "https://globaleadershipacademy.com/admin/speakers/1/edit"
echo ""
echo "If you still encounter issues, the fix script provided detailed diagnostics."