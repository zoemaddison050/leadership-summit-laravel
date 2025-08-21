<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\ImportScripts\MediaMigrator;

class MigrateWordPressMedia extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:wordpress-media 
                            {--wp-root= : WordPress root directory path}
                            {--dry-run : Run without making changes}
                            {--force : Force migration without confirmation}
                            {--verify-only : Only verify existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate WordPress media files and theme assets to Laravel';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('WordPress Media Migration Tool');
        $this->info('==============================');

        // Get WordPress root directory
        $wp_root = $this->option('wp-root') ?: dirname(base_path()) . '/leadership-summit-theme/';

        if (!is_dir($wp_root)) {
            $this->error("WordPress root directory not found: {$wp_root}");
            return 1;
        }

        $this->info("WordPress root: {$wp_root}");

        // Check source directories
        $uploads_path = $wp_root . 'wp-content/uploads/';
        $assets_path = $wp_root . 'assets/';

        $this->info("Checking source directories...");

        if (is_dir($uploads_path)) {
            $upload_count = $this->countFiles($uploads_path);
            $this->info("  ✓ Uploads directory: {$upload_count} files");
        } else {
            $this->warn("  - Uploads directory not found: {$uploads_path}");
        }

        if (is_dir($assets_path)) {
            $asset_count = $this->countFiles($assets_path);
            $this->info("  ✓ Assets directory: {$asset_count} files");
        } else {
            $this->warn("  - Assets directory not found: {$assets_path}");
        }

        // Verify-only mode
        if ($this->option('verify-only')) {
            return $this->verifyExistingFiles();
        }

        // Dry run check
        if ($this->option('dry-run')) {
            $this->info('DRY RUN MODE - No files will be copied');
            return $this->performDryRun($wp_root);
        }

        // Confirmation
        if (!$this->option('force')) {
            if (!$this->confirm('This will copy WordPress media files to Laravel storage. Continue?')) {
                $this->info('Migration cancelled.');
                return 0;
            }
        }

        // Perform migration
        try {
            $migrator = new MediaMigrator($wp_root);

            $this->info('Starting media migration...');
            $start_time = microtime(true);

            $migrator->migrate_all();

            $end_time = microtime(true);
            $duration = round($end_time - $start_time, 2);

            $this->info("Migration completed successfully in {$duration} seconds!");

            // Display results
            $this->displayResults($migrator->get_results());

            return 0;
        } catch (\Exception $e) {
            $this->error('Migration failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Perform a dry run to check what would be migrated
     */
    private function performDryRun($wp_root)
    {
        $this->info('Performing dry run...');

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
            $source_path = $wp_root . $dir;

            if (is_dir($source_path)) {
                $file_count = $this->countFiles($source_path);
                $dir_size = $this->getDirectorySize($source_path);
                $total_files += $file_count;
                $total_size += $dir_size;

                $this->info("  {$description}: {$file_count} files (" . $this->formatBytes($dir_size) . ")");
            } else {
                $this->warn("  {$description}: Directory not found");
            }
        }

        $this->info("Total files to migrate: {$total_files}");
        $this->info("Total size to migrate: " . $this->formatBytes($total_size));
        $this->info('Dry run completed. Use --force to perform actual migration.');

        return 0;
    }

    /**
     * Verify existing migrated files
     */
    private function verifyExistingFiles()
    {
        $this->info('Verifying existing migrated files...');

        $storage_path = storage_path('app/public/');
        $assets_path = public_path('assets/');

        $verified_count = 0;
        $missing_count = 0;

        // Check storage files
        if (is_dir($storage_path . 'uploads/')) {
            $upload_files = $this->countFiles($storage_path . 'uploads/');
            $this->info("  Storage uploads: {$upload_files} files");
            $verified_count += $upload_files;
        } else {
            $this->warn("  Storage uploads directory not found");
        }

        // Check asset files
        $asset_dirs = ['css', 'js', 'images', 'fonts'];
        foreach ($asset_dirs as $dir) {
            $dir_path = $assets_path . $dir . '/';
            if (is_dir($dir_path)) {
                $file_count = $this->countFiles($dir_path);
                $this->info("  Assets {$dir}: {$file_count} files");
                $verified_count += $file_count;
            } else {
                $this->warn("  Assets {$dir} directory not found");
                $missing_count++;
            }
        }

        $this->info("Verification completed: {$verified_count} files found");

        if ($missing_count > 0) {
            $this->warn("{$missing_count} expected directories are missing");
        }

        return 0;
    }

    /**
     * Display migration results
     */
    private function displayResults($results)
    {
        $migrated = $results['migrated_files'];
        $failed = $results['failed_files'];

        $this->info('');
        $this->info('Migration Results:');
        $this->info('==================');

        $this->info("Successfully migrated: " . count($migrated) . " files");

        if (!empty($failed)) {
            $this->warn("Failed migrations: " . count($failed) . " files");

            if (count($failed) <= 10) {
                $this->warn("Failed files:");
                foreach ($failed as $file) {
                    $this->warn("  - {$file['source']}: {$file['error']}");
                }
            } else {
                $this->warn("Too many failed files to display. Check logs for details.");
            }
        }

        // Group by type
        $types = [];
        $total_size = 0;

        foreach ($migrated as $file) {
            $type = $file['type'];
            if (!isset($types[$type])) {
                $types[$type] = ['count' => 0, 'size' => 0];
            }
            $types[$type]['count']++;
            $types[$type]['size'] += $file['size'];
            $total_size += $file['size'];
        }

        $this->info("Total size migrated: " . $this->formatBytes($total_size));

        if (!empty($types)) {
            $this->info('');
            $this->info('By type:');
            foreach ($types as $type => $stats) {
                $this->info("  {$type}: {$stats['count']} files (" . $this->formatBytes($stats['size']) . ")");
            }
        }
    }

    /**
     * Count files in directory recursively
     */
    private function countFiles($directory)
    {
        if (!is_dir($directory)) {
            return 0;
        }

        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get directory size recursively
     */
    private function getDirectorySize($directory)
    {
        if (!is_dir($directory)) {
            return 0;
        }

        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
