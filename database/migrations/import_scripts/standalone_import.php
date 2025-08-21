<?php

/**
 * Standalone WordPress Data Import Script
 * 
 * This script can be run independently to import WordPress data into Laravel
 * Run this script from the Laravel root directory
 */

// Bootstrap Laravel
require_once __DIR__ . '/../../../vendor/autoload.php';

$app = require_once __DIR__ . '/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Include the importer class
require_once __DIR__ . '/WordPressDataImporter.php';

use Database\ImportScripts\WordPressDataImporter;

// Configuration
$import_dir = __DIR__ . '/../export_scripts/exported_data/';
$dry_run = false;

// Parse command line arguments
$options = getopt('', ['dir:', 'dry-run', 'help']);

if (isset($options['help'])) {
    echo "WordPress Data Import Script\n";
    echo "============================\n\n";
    echo "Usage: php standalone_import.php [options]\n\n";
    echo "Options:\n";
    echo "  --dir=PATH     Directory containing exported JSON files\n";
    echo "  --dry-run      Run without making changes\n";
    echo "  --help         Show this help message\n\n";
    exit(0);
}

if (isset($options['dir'])) {
    $import_dir = rtrim($options['dir'], '/') . '/';
}

if (isset($options['dry-run'])) {
    $dry_run = true;
}

// Check if import directory exists
if (!is_dir($import_dir)) {
    echo "Error: Import directory not found: $import_dir\n";
    exit(1);
}

echo "WordPress Data Import\n";
echo "====================\n";
echo "Import directory: $import_dir\n";

if ($dry_run) {
    echo "DRY RUN MODE - No changes will be made\n";
}

echo "\n";

// Check for required files
$required_files = [
    'roles.json' => 'Roles',
    'users.json' => 'Users',
    'events.json' => 'Events',
    'tickets.json' => 'Tickets',
    'speakers.json' => 'Speakers',
    'sessions.json' => 'Sessions',
    'session_speakers.json' => 'Session-Speaker Relationships',
    'pages.json' => 'Pages',
    'media.json' => 'Media Files',
    'orders.json' => 'Orders',
    'payments.json' => 'Payments',
    'registrations.json' => 'Registrations'
];

$missing_files = [];
$total_records = 0;

echo "Checking files...\n";
foreach ($required_files as $file => $description) {
    $file_path = $import_dir . $file;

    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        $data = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            $count = count($data);
            $total_records += $count;
            echo "  ✓ {$description}: {$count} records\n";
        } else {
            echo "  ✗ {$description}: Invalid JSON format\n";
            $missing_files[] = $file;
        }
    } else {
        echo "  - {$description}: File not found (will be skipped)\n";
        $missing_files[] = $file;
    }
}

echo "\nTotal records to process: {$total_records}\n";

if ($dry_run) {
    echo "\nDry run completed. Remove --dry-run flag to perform actual import.\n";
    exit(0);
}

// Confirmation
echo "\nThis will import WordPress data into the Laravel database.\n";
echo "Continue? (y/N): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim(strtolower($line)) !== 'y') {
    echo "Import cancelled.\n";
    exit(0);
}

// Perform import
try {
    echo "\nStarting import...\n";
    $start_time = microtime(true);

    $importer = new WordPressDataImporter($import_dir);
    $importer->import_all_data();

    $end_time = microtime(true);
    $duration = round($end_time - $start_time, 2);

    echo "\nImport completed successfully in {$duration} seconds!\n";

    // Display summary
    echo "\nImport Summary:\n";
    echo "===============\n";

    $summary = $importer->get_import_summary();
    $total_imported = 0;

    foreach ($summary as $type => $count) {
        echo "  " . ucfirst($type) . ": {$count} records\n";
        $total_imported += $count;
    }

    echo "  Total: {$total_imported} records imported\n";
} catch (Exception $e) {
    echo "\nImport failed: " . $e->getMessage() . "\n";
    echo "Check the Laravel logs for more details.\n";
    exit(1);
}

echo "\nImport process completed!\n";
