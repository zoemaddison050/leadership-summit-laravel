<?php

/**
 * Production Admin User Fix Script v2
 * 
 * This script ensures the admin user exists in production with correct credentials.
 * Handles missing role_id column by running migrations first.
 * 
 * Usage:
 * 1. Upload to server: scp fix-production-admin-v2.php globalea@server:/home/globalea/leadership-summit-laravel/
 * 2. Run via CLI: /usr/local/php83/bin/php fix-production-admin-v2.php
 * 3. Or run via web: https://globaleadershipacademy.com/fix-production-admin-v2.php
 */

// Bootstrap Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<h2>🔧 Production Admin User Fix v2</h2>\n";
echo "<pre>\n";

try {
    // Check database structure first
    echo "0. Checking database structure...\n";
    $hasRoleColumn = \Schema::hasColumn('users', 'role_id');
    echo "   Users table has role_id column: " . ($hasRoleColumn ? "✅ Yes" : "❌ No") . "\n";

    if (!$hasRoleColumn) {
        echo "   🔄 Running migrations to fix database structure...\n";
        \Artisan::call('migrate', ['--force' => true]);
        echo "   ✅ Migrations completed\n";

        // Check again
        $hasRoleColumn = \Schema::hasColumn('users', 'role_id');
        echo "   Users table now has role_id column: " . ($hasRoleColumn ? "✅ Yes" : "❌ No") . "\n";
    }

    // Check if roles exist
    echo "\n1. Checking roles...\n";
    $adminRole = \App\Models\Role::where('name', 'admin')->first();

    if (!$adminRole) {
        echo "   ❌ Admin role not found. Creating roles...\n";

        // Create admin role
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

        // Create other roles
        \App\Models\Role::create([
            'name' => 'speaker',
            'permissions' => [
                'view_events',
                'manage_own_sessions'
            ]
        ]);

        \App\Models\Role::create([
            'name' => 'user',
            'permissions' => [
                'view_events',
                'register_for_events'
            ]
        ]);

        echo "   ✅ Roles created successfully\n";
    } else {
        echo "   ✅ Admin role exists (ID: {$adminRole->id})\n";
    }

    // Check if admin user exists
    echo "\n2. Checking admin user...\n";
    $adminUser = \App\Models\User::where('email', 'admin@leadershipsummit.com')->first();

    if (!$adminUser) {
        echo "   ❌ Admin user not found. Creating admin user...\n";

        // Create user data
        $userData = [
            'name' => 'Admin User',
            'email' => 'admin@leadershipsummit.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ];

        // Add role_id only if column exists
        if ($hasRoleColumn) {
            $userData['role_id'] = $adminRole->id;
        }

        $adminUser = \App\Models\User::create($userData);

        echo "   ✅ Admin user created successfully (ID: {$adminUser->id})\n";
    } else {
        echo "   ✅ Admin user exists (ID: {$adminUser->id})\n";

        // Update password and role to ensure they're correct
        echo "   🔄 Updating admin credentials...\n";
        $adminUser->password = bcrypt('password');
        $adminUser->email_verified_at = now();

        // Update role_id only if column exists
        if ($hasRoleColumn) {
            $adminUser->role_id = $adminRole->id;
        }

        $adminUser->save();

        echo "   ✅ Admin credentials updated\n";
    }

    // Test password
    echo "\n3. Testing admin credentials...\n";
    $passwordTest = \Hash::check('password', $adminUser->password);
    echo "   Password test: " . ($passwordTest ? "✅ PASS" : "❌ FAIL") . "\n";

    // Display user info
    echo "\n4. Admin user details:\n";
    echo "   - ID: {$adminUser->id}\n";
    echo "   - Name: {$adminUser->name}\n";
    echo "   - Email: {$adminUser->email}\n";

    if ($hasRoleColumn && $adminUser->role_id) {
        echo "   - Role ID: {$adminUser->role_id}\n";
        if ($adminUser->role) {
            echo "   - Role Name: {$adminUser->role->name}\n";
        }
    } else {
        echo "   - Role: Not using role system (legacy setup)\n";
    }

    echo "   - Email Verified: " . ($adminUser->email_verified_at ? "Yes" : "No") . "\n";
    echo "   - Created: {$adminUser->created_at}\n";
    echo "   - Updated: {$adminUser->updated_at}\n";

    // Clear caches
    echo "\n5. Clearing application caches...\n";
    \Artisan::call('config:clear');
    echo "   ✅ Config cache cleared\n";

    \Artisan::call('cache:clear');
    echo "   ✅ Application cache cleared\n";

    \Artisan::call('route:clear');
    echo "   ✅ Route cache cleared\n";

    // Test authentication manually
    echo "\n6. Testing authentication system...\n";
    $credentials = [
        'email' => 'admin@leadershipsummit.com',
        'password' => 'password'
    ];

    $authTest = \Auth::attempt($credentials);
    echo "   Authentication test: " . ($authTest ? "✅ PASS" : "❌ FAIL") . "\n";

    if ($authTest) {
        \Auth::logout(); // Clean up
    }

    echo "\n🎉 SUCCESS!\n";
    echo "==================\n";
    echo "Admin user is now ready for production login:\n\n";
    echo "🌐 Login URL: https://globaleadershipacademy.com/login\n";
    echo "📧 Email: admin@leadershipsummit.com\n";
    echo "🔑 Password: password\n\n";
    echo "You can now log in to your admin panel!\n";
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";

    // Try alternative approach without role_id
    echo "\n🔄 Trying alternative approach without roles...\n";
    try {
        $adminUser = \App\Models\User::updateOrCreate(
            ['email' => 'admin@leadershipsummit.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        echo "✅ Admin user created/updated without role system\n";
        echo "📧 Email: admin@leadershipsummit.com\n";
        echo "🔑 Password: password\n";
    } catch (\Exception $e2) {
        echo "❌ Alternative approach also failed: " . $e2->getMessage() . "\n";
    }
}

echo "</pre>\n";

// Auto-redirect to login after 5 seconds if run via web
if (isset($_SERVER['HTTP_HOST'])) {
    echo "<script>
        setTimeout(function() {
            window.location.href = '/login';
        }, 5000);
    </script>";
    echo "<p><a href='/login'>Click here to go to login page</a> (auto-redirecting in 5 seconds)</p>";
}
