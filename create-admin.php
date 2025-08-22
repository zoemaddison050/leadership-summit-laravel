<?php
/**
 * Emergency Admin User Creation Script
 * Place this file in your Laravel root directory and access via browser
 * URL: https://globaleadershipacademy.com/create-admin.php
 * 
 * IMPORTANT: Delete this file after use for security!
 */

// Only allow access from specific IP or add basic authentication
$allowed_ips = ['YOUR_IP_HERE']; // Add your IP address
if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips) && !empty($allowed_ips[0])) {
    die('Access denied');
}

// Include Laravel bootstrap
require_once __DIR__.'/vendor/autoload.php';

// Boot Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

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
        echo "✅ Admin role created<br>";
    } else {
        echo "✅ Admin role exists<br>";
    }
    
    // Create or update admin user
    $adminUser = \App\Models\User::updateOrCreate(
        ['email' => 'admin@leadershipsummit.com'],
        [
            'name' => 'Admin User',
            'email' => 'admin@leadershipsummit.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]
    );
    
    echo "✅ Admin user created/updated successfully!<br>";
    echo "📧 Email: admin@leadershipsummit.com<br>";
    echo "🔑 Password: password<br>";
    echo "🔗 Login URL: <a href='/login'>/login</a><br>";
    echo "🏠 Admin Panel: <a href='/admin'>/admin</a><br><br>";
    
    echo "⚠️ <strong>IMPORTANT:</strong> Delete this file immediately for security!<br>";
    echo "📂 File to delete: " . __FILE__ . "<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "📋 Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>
