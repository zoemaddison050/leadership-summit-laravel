<?php

/**
 * Production Storage Fix Script
 * 
 * This script fixes file upload issues by:
 * 1. Creating missing storage directories
 * 2. Setting proper permissions
 * 3. Creating storage symlink
 * 4. Testing file upload functionality
 * 
 * Usage:
 * 1. Upload to server: scp fix-production-storage.php globalea@server:/home/globalea/leadership-summit-laravel/
 * 2. Run via CLI: /usr/local/php83/bin/php fix-production-storage.php
 * 3. Or run via web: https://globaleadershipacademy.com/fix-production-storage.php
 */

// Bootstrap Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "<h2>🔧 Production Storage Fix</h2>\n";
echo "<pre>\n";

try {
    echo "1. Checking storage directories...\n";

    // Define required directories
    $directories = [
        'storage/app/public',
        'storage/app/public/speakers',
        'storage/app/public/events',
        'storage/app/public/media',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/logs'
    ];

    foreach ($directories as $dir) {
        $fullPath = base_path($dir);
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
            echo "   ✅ Created directory: $dir\n";
        } else {
            echo "   ✅ Directory exists: $dir\n";
        }
    }

    echo "\n2. Setting storage permissions...\n";

    // Set proper permissions
    $storageCommands = [
        'chmod -R 775 storage',
        'chmod -R 775 bootstrap/cache',
        'find storage -type d -exec chmod 775 {} \;',
        'find storage -type f -exec chmod 664 {} \;'
    ];

    foreach ($storageCommands as $command) {
        $output = shell_exec($command . ' 2>&1');
        echo "   ✅ Executed: $command\n";
        if ($output && trim($output) !== '') {
            echo "      Output: " . trim($output) . "\n";
        }
    }

    echo "\n3. Checking storage symlink...\n";

    $publicStoragePath = public_path('storage');
    $storageAppPublicPath = storage_path('app/public');

    if (is_link($publicStoragePath)) {
        echo "   ✅ Storage symlink exists\n";
        echo "   Link target: " . readlink($publicStoragePath) . "\n";
    } else {
        if (is_dir($publicStoragePath)) {
            // Remove existing directory
            shell_exec('rm -rf ' . escapeshellarg($publicStoragePath));
            echo "   🔄 Removed existing storage directory\n";
        }

        // Create symlink
        if (symlink($storageAppPublicPath, $publicStoragePath)) {
            echo "   ✅ Created storage symlink\n";
        } else {
            echo "   ❌ Failed to create storage symlink\n";
            // Try using artisan command
            \Artisan::call('storage:link');
            echo "   🔄 Attempted storage:link via Artisan\n";
        }
    }

    echo "\n4. Testing storage configuration...\n";

    // Test storage disk
    try {
        $testContent = 'Storage test - ' . date('Y-m-d H:i:s');
        $testFile = 'test-' . time() . '.txt';

        \Storage::disk('public')->put($testFile, $testContent);
        echo "   ✅ File write test: SUCCESS\n";

        $retrievedContent = \Storage::disk('public')->get($testFile);
        if ($retrievedContent === $testContent) {
            echo "   ✅ File read test: SUCCESS\n";
        } else {
            echo "   ❌ File read test: FAILED\n";
        }

        // Clean up test file
        \Storage::disk('public')->delete($testFile);
        echo "   ✅ File cleanup: SUCCESS\n";
    } catch (\Exception $e) {
        echo "   ❌ Storage test failed: " . $e->getMessage() . "\n";
    }

    echo "\n5. Checking web accessibility...\n";

    // Test if storage is web accessible
    $testImagePath = 'speakers/test-image.txt';
    $testImageContent = 'Test image accessibility';

    try {
        \Storage::disk('public')->put($testImagePath, $testImageContent);

        $webUrl = asset('storage/' . $testImagePath);
        echo "   Test URL: $webUrl\n";

        // Try to access via HTTP
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'method' => 'GET'
            ]
        ]);

        $response = @file_get_contents($webUrl, false, $context);
        if ($response === $testImageContent) {
            echo "   ✅ Web accessibility test: SUCCESS\n";
        } else {
            echo "   ❌ Web accessibility test: FAILED\n";
            echo "   Response: " . ($response ?: 'No response') . "\n";
        }

        // Clean up
        \Storage::disk('public')->delete($testImagePath);
    } catch (\Exception $e) {
        echo "   ❌ Web accessibility test failed: " . $e->getMessage() . "\n";
    }

    echo "\n6. Checking speaker model and database...\n";

    // Test speaker retrieval
    try {
        $speaker = \App\Models\Speaker::find(1);
        if ($speaker) {
            echo "   ✅ Speaker #1 found: {$speaker->name}\n";
            echo "   Current photo: " . ($speaker->photo ?: 'None') . "\n";

            if ($speaker->photo) {
                $photoExists = \Storage::disk('public')->exists($speaker->photo);
                echo "   Photo file exists: " . ($photoExists ? 'Yes' : 'No') . "\n";

                if ($photoExists) {
                    $photoUrl = asset('storage/' . $speaker->photo);
                    echo "   Photo URL: $photoUrl\n";
                }
            }
        } else {
            echo "   ❌ Speaker #1 not found\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ Speaker test failed: " . $e->getMessage() . "\n";
    }

    echo "\n7. Environment check...\n";

    echo "   PHP Version: " . PHP_VERSION . "\n";
    echo "   Laravel Version: " . app()->version() . "\n";
    echo "   Storage Path: " . storage_path() . "\n";
    echo "   Public Path: " . public_path() . "\n";
    echo "   App URL: " . config('app.url') . "\n";
    echo "   Filesystem Default: " . config('filesystems.default') . "\n";

    // Check if GD extension is loaded (for image processing)
    if (extension_loaded('gd')) {
        echo "   ✅ GD Extension: Loaded\n";
    } else {
        echo "   ❌ GD Extension: Not loaded (required for image processing)\n";
    }

    // Check file upload settings
    echo "   Max Upload Size: " . ini_get('upload_max_filesize') . "\n";
    echo "   Max Post Size: " . ini_get('post_max_size') . "\n";
    echo "   Memory Limit: " . ini_get('memory_limit') . "\n";

    echo "\n🎉 STORAGE FIX COMPLETE!\n";
    echo "========================\n";
    echo "Storage system should now be working properly.\n";
    echo "Try uploading a speaker image again.\n\n";
    echo "If issues persist, check:\n";
    echo "1. Server file permissions (775 for directories, 664 for files)\n";
    echo "2. Web server configuration for /storage path\n";
    echo "3. PHP file upload limits\n";
    echo "4. Available disk space\n";
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";

// Auto-redirect to speaker edit page after 10 seconds if run via web
if (isset($_SERVER['HTTP_HOST'])) {
    echo "<script>
        setTimeout(function() {
            window.location.href = '/admin/speakers/1/edit';
        }, 10000);
    </script>";
    echo "<p><a href='/admin/speakers/1/edit'>Click here to go back to speaker edit</a> (auto-redirecting in 10 seconds)</p>";
}
