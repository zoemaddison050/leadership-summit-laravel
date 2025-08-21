<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WebhookUrlGenerator;

class TestWebhookUrl extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'webhook:test-url {--check-accessibility : Test if the webhook URL is accessible}';

    /**
     * The console command description.
     */
    protected $description = 'Test webhook URL generation and validation';

    /**
     * Execute the console command.
     */
    public function handle(WebhookUrlGenerator $generator)
    {
        $this->info('Testing Webhook URL Generation');
        $this->line('');

        // Get environment info
        $environment = $generator->detectEnvironment();
        $this->info("Environment: {$environment}");

        // Generate webhook URL
        $result = $generator->getValidatedWebhookUrl();

        if ($result['url']) {
            $this->info("Generated URL: {$result['url']}");
        } else {
            $this->error('Failed to generate webhook URL');
        }

        // Show validation results
        $validation = $result['validation'];

        if ($validation['valid']) {
            $this->info('✓ URL format is valid');
        } else {
            $this->error('✗ URL format is invalid');
        }

        if ($validation['accessible']) {
            $this->info('✓ URL appears to be accessible');
        } else {
            $this->warn('⚠ URL may not be accessible from external services');
        }

        // Show errors
        if (!empty($validation['errors'])) {
            $this->error('Errors:');
            foreach ($validation['errors'] as $error) {
                $this->line("  - {$error}");
            }
        }

        // Show warnings
        if (!empty($validation['warnings'])) {
            $this->warn('Warnings:');
            foreach ($validation['warnings'] as $warning) {
                $this->line("  - {$warning}");
            }
        }

        // Show recommendations
        $recommendations = $generator->getWebhookRecommendations();
        if (!empty($recommendations)) {
            $this->line('');
            $this->info('Recommendations:');
            foreach ($recommendations as $rec) {
                $this->line("  [{$rec['type']}] {$rec['message']}");
            }
        }

        // Test accessibility if requested
        if ($this->option('check-accessibility') && $result['url']) {
            $this->line('');
            $this->info('Testing URL accessibility...');
            $accessible = $generator->isWebhookAccessible($result['url']);

            if ($accessible) {
                $this->info('✓ URL is accessible');
            } else {
                $this->warn('⚠ URL is not accessible or returned an error');
            }
        }

        return 0;
    }
}
