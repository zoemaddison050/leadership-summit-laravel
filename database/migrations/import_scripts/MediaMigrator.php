<?php

namespace Database\ImportScripts;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Media;

/**
 * Media and Assets Migrator for WordPress to Laravel Migration
 * 
 * This class handles the migration of media files and theme assets
 */
class MediaMigrator
{
    private $wordpress_uploads_path;
    private $laravel_storage_path;
    private $wordpress_theme_path;
    private $laravel_assets_path;
    private $migrated_files = [];
    private $failed_files = [];
    private $file_mappings = [];

    public function __construct($wordpress_root = null)
    {
        // WordPress paths
        $wp_root = $wordpress_root ?: dirname(__DIR__, 4) . '/leadership-summit-theme/';
        $this->wordpress_uploads_path = $wp_root . 'wp-content/uploads/';
        $this->wordpress_theme_path = $wp_root;

        // Laravel paths
        $this->laravel_storage_path = storage_path('app/public/');
        $this->laravel_assets_path = public_path('assets/');

        // Ensure directories exist
        $this->ensureDirectoriesExist();
    }

    /**
     * Migrate all media and assets
     */
    public function migrate_all()
    {
        echo "Starting media and assets migration...\n";

        try {
            $this->migrate_uploaded_media();
            $this->migrate_theme_assets();
            $this->update_media_database_records();
            $this->verify_file_integrity();

            echo "Media migration completed successfully!\n";
            $this->display_summary();
        } catch (\Exception $e) {
            echo "Media migration failed: " . $e->getMessage() . "\n";
            Log::error('Media migration failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Migrate WordPress uploaded media files
     */
    public function migrate_uploaded_media()
    {
        echo "Migrating uploaded media files...\n";

        if (!is_dir($this->wordpress_uploads_path)) {
            echo "WordPress uploads directory not found: {$this->wordpress_uploads_path}\n";
            return;
        }

        $this->copyDirectoryRecursive(
            $this->wordpress_uploads_path,
            $this->laravel_storage_path . 'uploads/',
            'uploads'
        );

        echo "Uploaded media files migrated\n";
    }

    /**
     * Migrate theme assets (CSS, JS, images)
     */
    public function migrate_theme_assets()
    {
        echo "Migrating theme assets...\n";

        $asset_directories = [
            'assets/css' => 'css',
            'assets/js' => 'js',
            'assets/images' => 'images',
            'assets/fonts' => 'fonts'
        ];

        foreach ($asset_directories as $wp_dir => $laravel_dir) {
            $source_path = $this->wordpress_theme_path . $wp_dir;
            $dest_path = $this->laravel_assets_path . $laravel_dir;

            if (is_dir($source_path)) {
                $this->copyDirectoryRecursive($source_path, $dest_path, $laravel_dir);
                echo "  Migrated {$wp_dir} to assets/{$laravel_dir}\n";
            } else {
                echo "  Skipped {$wp_dir} (not found)\n";
            }
        }

        echo "Theme assets migrated\n";
    }

    /**
     * Update media database records with new file paths
     */
    public function update_media_database_records()
    {
        echo "Updating media database records...\n";

        $media_records = Media::all();
        $updated_count = 0;

        foreach ($media_records as $media) {
            try {
                $old_path = $media->file_path;
                $old_url = $media->file_url;

                // Update file path
                if ($old_path && isset($this->file_mappings[$old_path])) {
                    $media->file_path = $this->file_mappings[$old_path];
                }

                // Update file URL
                if ($old_url) {
                    $new_url = $this->convert_wordpress_url_to_laravel($old_url);
                    if ($new_url !== $old_url) {
                        $media->file_url = $new_url;
                    }
                }

                if ($media->isDirty()) {
                    $media->save();
                    $updated_count++;
                }
            } catch (\Exception $e) {
                echo "Failed to update media record {$media->id}: " . $e->getMessage() . "\n";
                Log::warning('Failed to update media record', [
                    'media_id' => $media->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        echo "Updated {$updated_count} media database records\n";
    }

    /**
     * Verify file integrity after migration
     */
    public function verify_file_integrity()
    {
        echo "Verifying file integrity...\n";

        $verification_errors = [];
        $verified_count = 0;

        foreach ($this->migrated_files as $file_info) {
            $source_path = $file_info['source'];
            $dest_path = $file_info['destination'];

            if (!file_exists($dest_path)) {
                $verification_errors[] = "Missing destination file: {$dest_path}";
                continue;
            }

            // Compare file sizes
            $source_size = filesize($source_path);
            $dest_size = filesize($dest_path);

            if ($source_size !== $dest_size) {
                $verification_errors[] = "Size mismatch for {$dest_path}: expected {$source_size}, got {$dest_size}";
                continue;
            }

            // For critical files, compare checksums
            if ($this->isCriticalFile($source_path)) {
                $source_hash = md5_file($source_path);
                $dest_hash = md5_file($dest_path);

                if ($source_hash !== $dest_hash) {
                    $verification_errors[] = "Checksum mismatch for {$dest_path}";
                    continue;
                }
            }

            $verified_count++;
        }

        if (!empty($verification_errors)) {
            echo "File integrity verification found " . count($verification_errors) . " errors:\n";
            foreach ($verification_errors as $error) {
                echo "  - {$error}\n";
            }
        } else {
            echo "File integrity verification passed for {$verified_count} files\n";
        }

        // Set proper permissions
        $this->setFilePermissions();
    }

    /**
     * Copy directory recursively
     */
    private function copyDirectoryRecursive($source, $destination, $type)
    {
        if (!is_dir($source)) {
            return;
        }

        // Create destination directory
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $dest_path = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();

            if ($item->isDir()) {
                if (!is_dir($dest_path)) {
                    mkdir($dest_path, 0755, true);
                }
            } else {
                try {
                    if (copy($item->getPathname(), $dest_path)) {
                        $this->migrated_files[] = [
                            'source' => $item->getPathname(),
                            'destination' => $dest_path,
                            'type' => $type,
                            'size' => $item->getSize()
                        ];

                        // Store file mapping
                        $this->file_mappings[$item->getPathname()] = $dest_path;
                    } else {
                        $this->failed_files[] = [
                            'source' => $item->getPathname(),
                            'destination' => $dest_path,
                            'error' => 'Copy failed'
                        ];
                    }
                } catch (\Exception $e) {
                    $this->failed_files[] = [
                        'source' => $item->getPathname(),
                        'destination' => $dest_path,
                        'error' => $e->getMessage()
                    ];
                }
            }
        }
    }

    /**
     * Convert WordPress URL to Laravel URL
     */
    private function convert_wordpress_url_to_laravel($wp_url)
    {
        // Extract the file path from WordPress URL
        if (strpos($wp_url, 'wp-content/uploads/') !== false) {
            $path_part = substr($wp_url, strpos($wp_url, 'wp-content/uploads/') + strlen('wp-content/uploads/'));
            return asset('storage/uploads/' . $path_part);
        }

        if (strpos($wp_url, 'wp-content/themes/') !== false) {
            $path_part = substr($wp_url, strpos($wp_url, 'assets/'));
            if ($path_part) {
                return asset($path_part);
            }
        }

        return $wp_url; // Return original if no conversion needed
    }

    /**
     * Check if file is critical and needs checksum verification
     */
    private function isCriticalFile($file_path)
    {
        $critical_extensions = ['php', 'js', 'css', 'json', 'xml'];
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

        return in_array($extension, $critical_extensions);
    }

    /**
     * Set proper file permissions
     */
    private function setFilePermissions()
    {
        echo "Setting file permissions...\n";

        // Set permissions for storage directory
        if (is_dir($this->laravel_storage_path)) {
            chmod($this->laravel_storage_path, 0755);
            $this->setPermissionsRecursive($this->laravel_storage_path, 0644, 0755);
        }

        // Set permissions for assets directory
        if (is_dir($this->laravel_assets_path)) {
            chmod($this->laravel_assets_path, 0755);
            $this->setPermissionsRecursive($this->laravel_assets_path, 0644, 0755);
        }

        echo "File permissions set\n";
    }

    /**
     * Set permissions recursively
     */
    private function setPermissionsRecursive($path, $file_perm, $dir_perm)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                chmod($item->getPathname(), $dir_perm);
            } else {
                chmod($item->getPathname(), $file_perm);
            }
        }
    }

    /**
     * Ensure required directories exist
     */
    private function ensureDirectoriesExist()
    {
        $directories = [
            $this->laravel_storage_path,
            $this->laravel_storage_path . 'uploads/',
            $this->laravel_assets_path,
            $this->laravel_assets_path . 'css/',
            $this->laravel_assets_path . 'js/',
            $this->laravel_assets_path . 'images/',
            $this->laravel_assets_path . 'fonts/'
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * Display migration summary
     */
    private function display_summary()
    {
        echo "\nMedia Migration Summary:\n";
        echo "========================\n";

        $total_files = count($this->migrated_files);
        $failed_files = count($this->failed_files);
        $total_size = 0;

        // Calculate total size
        foreach ($this->migrated_files as $file) {
            $total_size += $file['size'];
        }

        echo "Successfully migrated: {$total_files} files\n";
        echo "Failed migrations: {$failed_files} files\n";
        echo "Total size migrated: " . $this->formatBytes($total_size) . "\n";

        // Group by type
        $types = [];
        foreach ($this->migrated_files as $file) {
            $type = $file['type'];
            if (!isset($types[$type])) {
                $types[$type] = ['count' => 0, 'size' => 0];
            }
            $types[$type]['count']++;
            $types[$type]['size'] += $file['size'];
        }

        echo "\nBy type:\n";
        foreach ($types as $type => $stats) {
            echo "  {$type}: {$stats['count']} files (" . $this->formatBytes($stats['size']) . ")\n";
        }

        if (!empty($this->failed_files)) {
            echo "\nFailed files:\n";
            foreach ($this->failed_files as $failed) {
                echo "  - {$failed['source']}: {$failed['error']}\n";
            }
        }
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

    /**
     * Get migration results
     */
    public function get_results()
    {
        return [
            'migrated_files' => $this->migrated_files,
            'failed_files' => $this->failed_files,
            'file_mappings' => $this->file_mappings
        ];
    }
}
