<?php
/**
 * Emergency Admin User Fix Script for globaleadershipacademy.com
 * 
 * IMPORTANT: Delete this file after use for security!
 */

// Basic security check - only allow local access or add your IP
$allowed_ips = [
    '127.0.0.1', 
    '::1',
    // Add your IP address here for remote access
    // 'YOUR_IP_ADDRESS'
];

// Allow localhost or if you want to test remotely, comment out this check
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowed_ips) && !in_array($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '', $allowed_ips)) {
    // For debugging, you can temporarily comment out this line:
    // die('Access denied. Your IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
}

// Include Laravel bootstrap
require_once __DIR__.'/vendor/autoload.php';

// Boot Laravel application
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "<!DOCTYPE html><html><head><title>Admin User Fix</title></head><body>";
echo "<h1>Global Leadership Academy - Admin User Fix</h1>";

try {
    // Check if admin role exists
    $adminRole = \App\Models\Role::where('name', 'admin')->first();
    
    if (!$adminRole) {
        // Create admin role if it doesn't exist
        $adminRole = \App\Models\Role::create([
            'name' => 'admin',
            'permissions' => [
                'manage_events',
                'manage_users', 
                'manage_speakers',
                'manage_sessions',
                'manage_pages',
                'view_reports'
            ]
        ]);
        echo "<p>✅ Admin role created</p>";
    } else {
        echo "<p>✅ Admin role exists</p>";
    }
    
    // Admin credentials
    $adminEmail = 'admin@globaleadershipacademy.com';
    $adminPassword = 'AdminPassword123!';
    $adminName = 'Global Leadership Admin';
    
    // Create or update admin user
    $adminUser = \App\Models\User::updateOrCreate(
        ['email' => $adminEmail],
        [
            'name' => $adminName,
            'email' => $adminEmail,
            'password' => bcrypt($adminPassword),
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]
    );
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h2>✅ Admin user created/updated successfully!</h2>";
    echo "<p><strong>📧 Email:</strong> " . $adminEmail . "</p>";
    echo "<p><strong>🔑 Password:</strong> " . $adminPassword . "</p>";
    echo "<p><strong>👤 Name:</strong> " . $adminName . "</p>";
    echo "<p><strong>🔗 Login URL:</strong> <a href='/login' target='_blank'>https://globaleadershipacademy.com/login</a></p>";
    echo "<p><strong>🏠 Admin Panel:</strong> <a href='/admin' target='_blank'>https://globaleadershipacademy.com/admin</a></p>";
    echo "</div>";
    
    // Verify password works
    $verifyUser = \App\Models\User::where('email', $adminEmail)->first();
    if ($verifyUser && \Illuminate\Support\Facades\Hash::check($adminPassword, $verifyUser->password)) {
        echo "<p>✅ Password verification successful</p>";
    } else {
        echo "<p>❌ Password verification failed - there may be an issue</p>";
    }
    
    // Show some debugging info
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>Debug Information:</h3>";
    echo "<p><strong>User ID:</strong> " . $adminUser->id . "</p>";
    echo "<p><strong>Role ID:</strong> " . $adminUser->role_id . "</p>";
    echo "<p><strong>Created At:</strong> " . $adminUser->created_at . "</p>";
    echo "<p><strong>Updated At:</strong> " . $adminUser->updated_at . "</p>";
    echo "</div>";
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>⚠️ IMPORTANT SECURITY NOTICE</h3>";
    echo "<p><strong>Delete this file immediately after use!</strong></p>";
    echo "<p>File location: " . __FILE__ . "</p>";
    echo "<p>This script provides administrative access and should not remain on the server.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Error occurred:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<details><summary>Stack trace (click to expand)</summary>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</details>";
    echo "</div>";
}

echo "</body></html>";
?>
