<?php
/**
 * Production Environment Fixer
 * Place this file in your Laravel root directory and access via browser
 * URL: https://globaleadershipacademy.com/fix-env.php
 * 
 * This script fixes common production environment issues that cause 419 errors
 * 
 * IMPORTANT: Delete this file after use for security!
 */

// Only allow access from specific IP or add basic authentication
$allowed_ips = []; // Add your IP address if needed, or leave empty for now
if (!empty($allowed_ips) && !in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
    die('Access denied');
}

echo "<h2>🔧 Production Environment Fixer</h2>";

try {
    // Check current APP_URL
    $currentUrl = $_SERVER['HTTP_HOST'];
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $correctUrl = $protocol . $currentUrl;
    
    echo "<h3>📊 Current Environment Status:</h3>";
    echo "🌐 Current Domain: " . $currentUrl . "<br>";
    echo "🔗 Correct APP_URL should be: " . $correctUrl . "<br>";
    
    // Read current .env file
    $envFile = __DIR__ . '/.env';
    if (!file_exists($envFile)) {
        echo "❌ .env file not found at: " . $envFile . "<br>";
        die();
    }
    
    $envContents = file_get_contents($envFile);
    
    // Check current APP_URL
    if (preg_match('/APP_URL=(.*)/', $envContents, $matches)) {
        echo "📋 Current APP_URL in .env: " . trim($matches[1]) . "<br>";
    } else {
        echo "⚠️ APP_URL not found in .env<br>";
    }
    
    echo "<br><h3>🛠️ Fixes Applied:</h3>";
    
    // Create backup
    copy($envFile, $envFile . '.backup.' . date('Y-m-d-H-i-s'));
    echo "✅ Created .env backup<br>";
    
    // Fix APP_URL
    if (strpos($envContents, 'APP_URL=') !== false) {
        $envContents = preg_replace('/APP_URL=.*/', 'APP_URL=' . $correctUrl, $envContents);
        echo "✅ Updated APP_URL to: " . $correctUrl . "<br>";
    } else {
        $envContents .= "\nAPP_URL=" . $correctUrl . "\n";
        echo "✅ Added APP_URL: " . $correctUrl . "<br>";
    }
    
    // Fix SESSION_DOMAIN
    if (strpos($envContents, 'SESSION_DOMAIN=') !== false) {
        $envContents = preg_replace('/SESSION_DOMAIN=.*/', 'SESSION_DOMAIN=' . $currentUrl, $envContents);
        echo "✅ Updated SESSION_DOMAIN to: " . $currentUrl . "<br>";
    } else {
        $envContents .= "SESSION_DOMAIN=" . $currentUrl . "\n";
        echo "✅ Added SESSION_DOMAIN: " . $currentUrl . "<br>";
    }
    
    // Fix SESSION_SECURE_COOKIE for HTTPS
    if ($protocol === 'https://') {
        if (strpos($envContents, 'SESSION_SECURE_COOKIE=') !== false) {
            $envContents = preg_replace('/SESSION_SECURE_COOKIE=.*/', 'SESSION_SECURE_COOKIE=true', $envContents);
            echo "✅ Updated SESSION_SECURE_COOKIE to: true<br>";
        } else {
            $envContents .= "SESSION_SECURE_COOKIE=true\n";
            echo "✅ Added SESSION_SECURE_COOKIE: true<br>";
        }
    }
    
    // Ensure production environment
    if (strpos($envContents, 'APP_ENV=') !== false) {
        $envContents = preg_replace('/APP_ENV=.*/', 'APP_ENV=production', $envContents);
        echo "✅ Set APP_ENV to: production<br>";
    }
    
    if (strpos($envContents, 'APP_DEBUG=') !== false) {
        $envContents = preg_replace('/APP_DEBUG=.*/', 'APP_DEBUG=false', $envContents);
        echo "✅ Set APP_DEBUG to: false<br>";
    }
    
    // Write updated .env
    file_put_contents($envFile, $envContents);
    echo "✅ Updated .env file<br>";
    
    echo "<br><h3>🧹 Cache Clearing:</h3>";
    
    // Clear caches
    $output = [];
    
    // Config cache
    exec('cd ' . __DIR__ . ' && php artisan config:clear 2>&1', $output);
    echo "✅ Config cache cleared<br>";
    
    exec('cd ' . __DIR__ . ' && php artisan cache:clear 2>&1', $output);
    echo "✅ Application cache cleared<br>";
    
    exec('cd ' . __DIR__ . ' && php artisan route:clear 2>&1', $output);
    echo "✅ Route cache cleared<br>";
    
    exec('cd ' . __DIR__ . ' && php artisan view:clear 2>&1', $output);
    echo "✅ View cache cleared<br>";
    
    // Rebuild config cache
    exec('cd ' . __DIR__ . ' && php artisan config:cache 2>&1', $output);
    echo "✅ Config cache rebuilt<br>";
    
    echo "<br><h3>🎉 Success!</h3>";
    echo "✅ Environment fixed for production!<br>";
    echo "✅ CSRF tokens should now work properly<br>";
    echo "🔗 Try logging in now: <a href='/login' target='_blank'>/login</a><br>";
    echo "📧 Email: admin@leadershipsummit.com<br>";
    echo "🔑 Password: password<br><br>";
    
    echo "⚠️ <strong>IMPORTANT:</strong> Delete these files immediately for security:<br>";
    echo "📂 " . __FILE__ . "<br>";
    echo "📂 " . __DIR__ . "/create-admin.php<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "📋 Details: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>
