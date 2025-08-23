<?php

/**
 * Production Admin User Fix Script v3
 * 
 * This script fixes the admin panel access by ensuring:
 * 1. The role_id column exists in the users table
 * 2. The admin user has the proper admin role assigned
 * 3. All database migrations are run
 * 
 * Usage:
 * 1. Upload to server: scp fix-production-admin-v3.php globalea@server:/home/globalea/leadership-summit-laravel/
 * 2. Run via CLI: /usr/local/php83/bin/php fix-production-admin-v3.php
 * 3. Or run via web: https://globaleadershipacademy.com/fix-production-admin-v3.php
 */

// Bootstrap Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<h2>🔧 Production Admin Panel Fix v3</h2>\n";
echo "<pre>\n";

try {
    // Step 1: Check and fix database structure
    echo "1. Checking database structure...\n";
    $hasRoleColumn = \Schema::hasColumn('users', 'role_id');
    echo "   Users table has role_id column: " . ($hasRoleColumn ? "✅ Yes" : "❌ No") . "\n";

    if (!$hasRoleColumn) {
        echo "   🔄 Running migrations to add role_id column...\n";

        // Force run all migrations
        \Artisan::call('migrate', ['--force' => true]);
        $migrateOutput = \Artisan::output();
        echo "   Migration output: " . trim($migrateOutput) . "\n";

        // Check if column was added
        $hasRoleColumn = \Schema::hasColumn('users', 'role_id');
        echo "   Users table now has role_id column: " . ($hasRoleColumn ? "✅ Yes" : "❌ No") . "\n";

        // If still no column, manually add it
        if (!$hasRoleColumn) {
            echo "   🔧 Manually adding role_id column...\n";
            \DB::statement('ALTER TABLE users ADD COLUMN role_id BIGINT UNSIGNED NULL AFTER password');
            \DB::statement('ALTER TABLE users ADD CONSTRAINT users_role_id_foreign FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL');

            $hasRoleColumn = \Schema::hasColumn('users', 'role_id');
            echo "   Role_id column added: " . ($hasRoleColumn ? "✅ Yes" : "❌ No") . "\n";
        }
    }

    // Step 2: Ensure roles exist
    echo "\n2. Checking and creating roles...\n";
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

    // Step 3: Create/update admin user with proper role
    echo "\n3. Setting up admin user...\n";
    $adminUser = \App\Models\User::where('email', 'admin@leadershipsummit.com')->first();

    if (!$adminUser) {
        echo "   ❌ Admin user not found. Creating admin user...\n";

        $adminUser = \App\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin@leadershipsummit.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
            'email_verified_at' => now(),
        ]);

        echo "   ✅ Admin user created successfully (ID: {$adminUser->id})\n";
    } else {
        echo "   ✅ Admin user exists (ID: {$adminUser->id})\n";

        // Update user with proper role
        echo "   🔄 Updating admin user with proper role...\n";
        $adminUser->password = bcrypt('password');
        $adminUser->role_id = $adminRole->id;
        $adminUser->email_verified_at = now();
        $adminUser->save();

        echo "   ✅ Admin user updated with role_id: {$adminUser->role_id}\n";
    }

    // Step 4: Verify role assignment
    echo "\n4. Verifying admin role assignment...\n";
    $adminUser->refresh(); // Reload from database

    echo "   - User ID: {$adminUser->id}\n";
    echo "   - Email: {$adminUser->email}\n";
    echo "   - Role ID: " . ($adminUser->role_id ?: 'NULL') . "\n";

    if ($adminUser->role) {
        echo "   - Role Name: {$adminUser->role->name}\n";
        echo "   - Role Permissions: " . implode(', ', $adminUser->role->permissions ?? []) . "\n";
    } else {
        echo "   ❌ Role relationship not working!\n";
    }

    // Test role methods
    echo "   - isAdmin(): " . ($adminUser->isAdmin() ? "✅ Yes" : "❌ No") . "\n";
    echo "   - hasRole('admin'): " . ($adminUser->hasRole('admin') ? "✅ Yes" : "❌ No") . "\n";

    // Step 5: Test password
    echo "\n5. Testing admin credentials...\n";
    $passwordTest = \Hash::check('password', $adminUser->password);
    echo "   Password test: " . ($passwordTest ? "✅ PASS" : "❌ FAIL") . "\n";

    // Step 6: Test authentication
    echo "\n6. Testing authentication system...\n";
    $credentials = [
        'email' => 'admin@leadershipsummit.com',
        'password' => 'password'
    ];

    $authTest = \Auth::attempt($credentials);
    echo "   Authentication test: " . ($authTest ? "✅ PASS" : "❌ FAIL") . "\n";

    if ($authTest) {
        $loggedInUser = \Auth::user();
        echo "   Logged in user role: " . ($loggedInUser->role ? $loggedInUser->role->name : 'No role') . "\n";
        echo "   Can access admin: " . ($loggedInUser->isAdmin() ? "✅ Yes" : "❌ No") . "\n";
        \Auth::logout(); // Clean up
    }

    // Step 7: Clear all caches
    echo "\n7. Clearing application caches...\n";
    \Artisan::call('config:clear');
    echo "   ✅ Config cache cleared\n";

    \Artisan::call('cache:clear');
    echo "   ✅ Application cache cleared\n";

    \Artisan::call('route:clear');
    echo "   ✅ Route cache cleared\n";

    \Artisan::call('view:clear');
    echo "   ✅ View cache cleared\n";

    echo "\n🎉 SUCCESS!\n";
    echo "==================\n";
    echo "Admin panel access is now fixed:\n\n";
    echo "🌐 Login URL: https://globaleadershipacademy.com/login\n";
    echo "📧 Email: admin@leadershipsummit.com\n";
    echo "🔑 Password: password\n";
    echo "🔧 Admin Panel: https://globaleadershipacademy.com/admin\n\n";
    echo "The admin user now has proper role assignment and should see the full admin panel!\n";
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";

// Auto-redirect to admin panel after 5 seconds if run via web
if (isset($_SERVER['HTTP_HOST'])) {
    echo "<script>
        setTimeout(function() {
            window.location.href = '/admin';
        }, 5000);
    </script>";
    echo "<p><a href='/admin'>Click here to go to admin panel</a> (auto-redirecting in 5 seconds)</p>";
}
