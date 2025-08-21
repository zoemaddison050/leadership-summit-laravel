<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\ImportScripts\WordPressDataImporter;

class ImportWordPressData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:wordpress-data 
                            {--dir= : Directory containing exported JSON files}
                            {--dry-run : Run without making changes}
                            {--force : Force import without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import WordPress data from exported JSON files';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('WordPress Data Import Tool');
        $this->info('==========================');

        // Get import directory
        $import_dir = $this->option('dir') ?: database_path('migrations/export_scripts/exported_data/');

        if (!is_dir($import_dir)) {
            $this->error("Import directory not found: {$import_dir}");
            return 1;
        }

        $this->info("Import directory: {$import_dir}");

        // Check for required files
        $required_files = [
            'roles.json',
            'users.json',
            'events.json',
            'tickets.json',
            'speakers.json',
            'sessions.json',
            'session_speakers.json',
            'pages.json',
            'media.json',
            'orders.json',
            'payments.json',
            'registrations.json'
        ];

        $missing_files = [];
        foreach ($required_files as $file) {
            if (!file_exists($import_dir . $file)) {
                $missing_files[] = $file;
            }
        }

        if (!empty($missing_files)) {
            $this->warn('The following files are missing and will be skipped:');
            foreach ($missing_files as $file) {
                $this->warn("  - {$file}");
            }
        }

        // Dry run check
        if ($this->option('dry-run')) {
            $this->info('DRY RUN MODE - No changes will be made');
            return $this->performDryRun($import_dir);
        }

        // Confirmation
        if (!$this->option('force')) {
            if (!$this->confirm('This will import WordPress data into the Laravel database. Continue?')) {
                $this->info('Import cancelled.');
                return 0;
            }
        }

        // Perform import
        try {
            $importer = new WordPressDataImporter($import_dir);

            $this->info('Starting import...');
            $start_time = microtime(true);

            $importer->import_all_data();

            $end_time = microtime(true);
            $duration = round($end_time - $start_time, 2);

            $this->info("Import completed successfully in {$duration} seconds!");

            // Display summary
            $this->displaySummary($importer->get_import_summary());

            return 0;
        } catch (\Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Perform a dry run to check data without importing
     */
    private function performDryRun($import_dir)
    {
        $this->info('Performing dry run...');

        $files_to_check = [
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

        $total_records = 0;

        foreach ($files_to_check as $file => $description) {
            $file_path = $import_dir . $file;

            if (file_exists($file_path)) {
                $content = file_get_contents($file_path);
                $data = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                    $count = count($data);
                    $total_records += $count;
                    $this->info("  {$description}: {$count} records");
                } else {
                    $this->error("  {$description}: Invalid JSON format");
                }
            } else {
                $this->warn("  {$description}: File not found");
            }
        }

        $this->info("Total records to import: {$total_records}");
        $this->info('Dry run completed. Use --force to perform actual import.');

        return 0;
    }

    /**
     * Display import summary
     */
    private function displaySummary($summary)
    {
        $this->info('');
        $this->info('Import Summary:');
        $this->info('===============');

        $total = 0;
        foreach ($summary as $type => $count) {
            $this->info("  " . ucfirst($type) . ": {$count} records");
            $total += $count;
        }

        $this->info("  Total: {$total} records imported");
    }
}
