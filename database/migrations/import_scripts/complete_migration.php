<?php

/**
 * Complete WordPress to Laravel Migration Script
 * 
 * This script performs a complete migration including:
 * 1. Data import from WordPress
 * 2. Media and assets migration
 * 3. Verification and cleanup
 */

// Bootstrap Laravel
require_once __DIR__ . '/../../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Include required classes
require_once __DIR__ . '/WordPressDataImporter.php';
require_once __DIR__ . '/MediaMigrator.php';

use Database\ImportScripts\WordPressDataImporter;
use Database\ImportScripts\MediaMigrator;

// Configuration
$wordpress_root = dirname(__DIR__, 4) . '/leadership-summit-theme/';
$export_data_dir = __DIR__ . '/../export_scripts/exported_data/';
$dry_run = false;
$force = false;

// Parse command line arguments
$options = getopt('', ['wp-root:', 'data-dir:', 'dry-run', 'force', 'help', 'data-only', 'media-only']);

if (isset($options['help'])) {
    echo "Complete WordPress to Laravel Migration Script\n";
    echo "=============================================\n\n";
    echo "Usage: php complete_migration.php [options]\n\n";
    echo "Options:\n";
    echo "  --wp-root=PATH     WordPress root directory\n";
    echo "  --data-dir=PATH    Directory containing exported JSON files\n";
    echo "  --dry-run          Run without making changes\n";
    echo "  --force            Force migration without confirmation\n";
    echo "  --data-only        Only migrate data (skip media)\n";
    echo "  --media-only       Only migrate media (skip data)\n";
    echo "  --help             Show this help message\n\n";
    exit(0);
}

if (isset($options['wp-root'])) {
    $wordpress_root = rtrim($options['wp-root'], '/') . '/';
}

if (isset($options['data-dir'])) {
    $export_data_dir = rtrim($options['data-dir'], '/') . '/';
}

if (isset($options['dry-run'])) {
    $dry_run = true;
}

if (isset($options['force'])) {
    $force = true;
}

$data_only = isset($options['data-only']);
$media_only = isset($options['media-only']);

// Validate paths
if (!is_dir($wordpress_root)) {
    echo "Error: WordPress root directory not found: $wordpress_root\n";
    exit(1);
}

if (!$media_only && !is_dir($export_data_dir)) {
    echo "Error: Export data directory not found: $export_data_dir\n";
    exit(1);
}

echo "Complete WordPress to Laravel Migration\n";
echo "======================================\n";
echo "WordPress root: $wordpress_root\n";
if (!$media_only) {
    echo "Export data directory: $export_data_dir\n";
}

if ($dry_run) {
    echo "DRY RUN MODE - No changes will be made\n";
}

echo "\n";

// Pre-migration checks
echo "Performing pre-migration checks...\n";

$checks_passed = true;

// Check Laravel environment
try {
    \Illuminate\Support\Facades\DB::connection()->getPdo();
    echo "  ✓ Database connection successful\n";
} catch (Exception $e) {
    echo "  ✗ Database connection failed: " . $e->getMessage() . "\n";
    $checks_passed = false;
}

// Check required directories
$required_dirs = [
    storage_path('app/public/') => 'Laravel storage directory',
    public_path('assets/') => 'Laravel assets directory'
];

foreach ($required_dirs as $dir => $description) {
    if (is_dir($dir) && is_writable($dir)) {
        echo "  ✓ {$description} is writable\n";
    } else {
        echo "  ✗ {$description} is not writable: {$dir}\n";
        $checks_passed = false;
    }
}

// Check WordPress directories
if (!$data_only) {
    $wp_uploads = $wordpress_root . 'wp-content/uploads/';
    $wp_assets = $wordpress_root . 'assets/';

    if (is_dir($wp_uploads)) {
        echo "  ✓ WordPress uploads directory found\n";
    } else {
        echo "  - WordPress uploads directory not found (will be skipped)\n";
    }

    if (is_dir($wp_assets)) {
        echo "  ✓ WordPress assets directory found\n";
    } else {
        echo "  - WordPress assets directory not found (will be skipped)\n";
    }
}

if (!$checks_passed) {
    echo "\nPre-migration checks failed. Please fix the issues above before continuing.\n";
    exit(1);
}

echo "Pre-migration checks passed!\n\n";

// Dry run mode
if ($dry_run) {
    echo "=== DRY RUN RESULTS ===\n\n";

    if (!$media_only) {
        echo "Data Migration Preview:\n";
        echo "-----------------------\n";
        performDataDryRun($export_data_dir);
        echo "\n";
    }

    if (!$data_only) {
        echo "Media Migration Preview:\n";
        echo "------------------------\n";
        performMediaDryRun($wordpress_root);
        echo "\n";
    }

    echo "Dry run completed. Remove --dry-run flag to perform actual migration.\n";
    exit(0);
}

// Confirmation
if (!$force) {
    echo "This will perform a complete WordPress to Laravel migration.\n";
    echo "This process will:\n";

    if (!$media_only) {
        echo "  - Import all WordPress data into Laravel database\n";
    }

    if (!$data_only) {
        echo "  - Copy all media files and theme assets\n";
        echo "  - Update file paths in database\n";
    }

    echo "\nContinue? (y/N): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);

    if (trim(strtolower($line)) !== 'y') {
        echo "Migration cancelled.\n";
        exit(0);
    }
}

// Perform migration
$start_time = microtime(true);
$migration_results = [];

try {
    echo "\n=== STARTING MIGRATION ===\n\n";

    // Step 1: Data Migration
    if (!$media_only) {
        echo "Step 1: Migrating WordPress data...\n";
        echo "===================================\n";

        $data_importer = new WordPressDataImporter($export_data_dir);
        $data_importer->import_all_data();

        $migration_results['data'] = $data_importer->get_import_summary();
        echo "Data migration completed!\n\n";
    }

    // Step 2: Media Migration
    if (!$data_only) {
        echo "Step 2: Migrating media and assets...\n";
        echo "=====================================\n";

        $media_migrator = new MediaMigrator($wordpress_root);
        $media_migrator->migrate_all();

        $migration_results['media'] = $media_migrator->get_results();
        echo "Media migration completed!\n\n";
    }

    $end_time = microtime(true);
    $duration = round($end_time - $start_time, 2);

    echo "=== MIGRATION COMPLETED SUCCESSFULLY ===\n";
    echo "Total time: {$duration} seconds\n\n";

    // Display comprehensive summary
    displayMigrationSummary($migration_results);

    // Post-migration recommendations
    echo "\n=== POST-MIGRATION RECOMMENDATIONS ===\n";
    echo "1. Clear application caches: php artisan cache:clear\n";
    echo "2. Rebuild search indexes if applicable\n";
    echo "3. Test key application features\n";
    echo "4. Verify media file accessibility\n";
    echo "5. Update any hardcoded URLs in content\n";
    echo "6. Set up proper backup procedures\n\n";
} catch (Exception $e) {
    echo "\nMigration failed: " . $e->getMessage() . "\n";
    echo "Check the Laravel logs for more details.\n";
    exit(1);
}

echo "Migration process completed successfully!\n";

// Helper functions

function performDataDryRun($export_data_dir)
{
    $files_to_check = [
        'roles.json' => 'Roles',
        'users.json' => 'Users',
        'events.json' => 'Events',
        'tickets.json' => 'Tickets',
        'speakers.json' => 'Speakers',
        'sessions.json' => 'Sessions',
        'pages.json' => 'Pages',
        'media.json' => 'Media Records',
        'orders.json' => 'Orders',
        'payments.json' => 'Payments',
        'registrations.json' => 'Registrations'
    ];

    $total_records = 0;

    foreach ($files_to_check as $file => $description) {
        $file_path = $export_data_dir . $file;

        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);
            $data = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                $count = count($data);
                $total_records += $count;
                echo "  {$description}: {$count} records\n";
            } else {
                echo "  {$description}: Invalid JSON format\n";
            }
        } else {
            echo "  {$description}: File not found (will be skipped)\n";
        }
    }

    echo "Total data records to import: {$total_records}\n";
}

function performMediaDryRun($wordpress_root)
{
    $directories_to_check = [
        'wp-content/uploads/' => 'Uploaded Media',
        'assets/css/' => 'CSS Assets',
        'assets/js/' => 'JavaScript Assets',
        'assets/images/' => 'Image Assets',
        'assets/fonts/' => 'Font Assets'
    ];

    $total_files = 0;
    $total_size = 0;

    foreach ($directories_to_check as $dir => $description) {
        $source_path = $wordpress_root . $dir;

        if (is_dir($source_path)) {
            $file_count = countFilesRecursive($source_path);
            $dir_size = getDirectorySizeRecursive($source_path);
            $total_files += $file_count;
            $total_size += $dir_size;

            echo "  {$description}: {$file_count} files (" . formatBytes($dir_size) . ")\n";
        } else {
            echo "  {$description}: Directory not found (will be skipped)\n";
        }
    }

    echo "Total media files to migrate: {$total_files}\n";
    echo "Total media size to migrate: " . formatBytes($total_size) . "\n";
}

function displayMigrationSummary($results)
{
    echo "=== MIGRATION SUMMARY ===\n\n";

    if (isset($results['data'])) {
        echo "Data Migration Results:\n";
        echo "-----------------------\n";
        $total_data = 0;
        foreach ($results['data'] as $type => $count) {
            echo "  " . ucfirst($type) . ": {$count} records\n";
            $total_data += $count;
        }
        echo "  Total data records: {$total_data}\n\n";
    }

    if (isset($results['media'])) {
        echo "Media Migration Results:\n";
        echo "------------------------\n";
        $migrated = $results['media']['migrated_files'];
        $failed = $results['media']['failed_files'];

        echo "  Successfully migrated: " . count($migrated) . " files\n";
        echo "  Failed migrations: " . count($failed) . " files\n";

        // Calculate total size
        $total_size = 0;
        foreach ($migrated as $file) {
            $total_size += $file['size'];
        }
        echo "  Total size migrated: " . formatBytes($total_size) . "\n";

        // Group by type
        $types = [];
        foreach ($migrated as $file) {
            $type = $file['type'];
            if (!isset($types[$type])) {
                $types[$type] = ['count' => 0, 'size' => 0];
            }
            $types[$type]['count']++;
            $types[$type]['size'] += $file['size'];
        }

        if (!empty($types)) {
            echo "  By type:\n";
            foreach ($types as $type => $stats) {
                echo "    {$type}: {$stats['count']} files (" . formatBytes($stats['size']) . ")\n";
            }
        }
    }
}

function countFilesRecursive($directory)
{
    if (!is_dir($directory)) {
        return 0;
    }

    $count = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $count++;
        }
    }

    return $count;
}

function getDirectorySizeRecursive($directory)
{
    if (!is_dir($directory)) {
        return 0;
    }

    $size = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $size += $file->getSize();
        }
    }

    return $size;
}

function formatBytes($bytes, $precision = 2)
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, $precision) . ' ' . $units[$i];
}
