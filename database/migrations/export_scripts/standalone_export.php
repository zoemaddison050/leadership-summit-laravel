<?php

/**
 * Standalone WordPress Data Export Script
 * 
 * This script can be run independently of WordPress to export data
 * Place this file in your WordPress root directory and run via command line
 */

// Configuration
$wp_config_path = __DIR__ . '/../../../../wp-config.php';
$export_dir = __DIR__ . '/exported_data/';

// Check if WordPress config exists
if (!file_exists($wp_config_path)) {
    die("WordPress configuration not found at: $wp_config_path\n");
}

// Load WordPress
require_once($wp_config_path);
require_once(ABSPATH . 'wp-load.php');

// Include the main export class
require_once(__DIR__ . '/wordpress_data_export.php');

// Run the export
try {
    echo "Starting standalone WordPress data export...\n";
    echo "Export directory: $export_dir\n\n";

    $exporter = new WordPressDataExporter();
    $exporter->export_all_data();

    echo "\nExport completed successfully!\n";
    echo "Files exported to: $export_dir\n";
} catch (Exception $e) {
    echo "Export failed: " . $e->getMessage() . "\n";
    exit(1);
}
