<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\WebhookUrlGenerator;
use Exception;

class ValidateWebhookConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook:validate-config {--timeout=10 : Request timeout in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate webhook configuration and connectivity';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔗 Validating webhook configuration...');

        $errors = [];
        $warnings = [];

        // Check environment variables
        $this->checkEnvironmentVariables($errors, $warnings);

        // Check webhook URL generation
        $this->checkWebhookUrlGeneration($errors, $warnings);

        // Check webhook endpoint accessibility
        $this->checkWebhookAccessibility($errors, $warnings);

        // Check route registration
        $this->checkRouteRegistration($errors, $warnings);

        // Display results
        $this->displayResults($errors, $warnings);

        return empty($errors) ? 0 : 1;
    }

    /**
     * Check required environment variables
     */
    private function checkEnvironmentVariables(&$errors, &$warnings)
    {
        $this->info('Checking environment variables...');

        $requiredVars = [
            'APP_URL' => 'Application URL',
            'WEBHOOK_BASE_URL' => 'Webhook base URL'
        ];

        $optionalVars = [
            'UNIPAYMENT_WEBHOOK_SECRET' => 'UniPayment webhook secret',
            'WEBHOOK_ENABLED' => 'Webhook enabled flag',
            'WEBHOOK_TIMEOUT' => 'Webhook timeout'
        ];

        foreach ($requiredVars as $var => $description) {
            $value = config(strtolower(str_replace('_', '.', $var))) ?? env($var);

            if (empty($value)) {
                $errors[] = "Missing required environment variable: {$var} ({$description})";
            } else {
                $this->line("  ✅ {$var}: {$value}");
            }
        }

        foreach ($optionalVars as $var => $description) {
            $value = config(strtolower(str_replace('_', '.', $var))) ?? env($var);

            if (empty($value)) {
                $warnings[] = "Optional environment variable not set: {$var} ({$description})";
            } else {
                $this->line("  ✅ {$var}: " . (str_contains($var, 'SECRET') ? '***' : $value));
            }
        }
    }

    /**
     * Check webhook URL generation
     */
    private function checkWebhookUrlGeneration(&$errors, &$warnings)
    {
        $this->info('Checking webhook URL generation...');

        try {
            $generator = app(WebhookUrlGenerator::class);
            $webhookUrl = $generator->generateUniPaymentWebhookUrl();

            if (empty($webhookUrl)) {
                $errors[] = 'Webhook URL generator returned empty URL';
                return;
            }

            $this->line("  ✅ Generated webhook URL: {$webhookUrl}");

            // Validate URL format
            if (!filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
                $errors[] = "Generated webhook URL is not valid: {$webhookUrl}";
                return;
            }

            // Check if URL uses HTTPS in production
            if (app()->environment('production') && !str_starts_with($webhookUrl, 'https://')) {
                $warnings[] = 'Webhook URL should use HTTPS in production environment';
            }
        } catch (Exception $e) {
            $errors[] = "Failed to generate webhook URL: {$e->getMessage()}";
        }
    }

    /**
     * Check webhook endpoint accessibility
     */
    private function checkWebhookAccessibility(&$errors, &$warnings)
    {
        $this->info('Checking webhook endpoint accessibility...');

        try {
            $generator = app(WebhookUrlGenerator::class);
            $webhookUrl = $generator->generateUniPaymentWebhookUrl();

            if (empty($webhookUrl)) {
                $errors[] = 'Cannot test accessibility - webhook URL generation failed';
                return;
            }

            $timeout = $this->option('timeout');

            // Test POST request to webhook endpoint
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Laravel-Webhook-Validator/1.0'
                ])
                ->post($webhookUrl, [
                    'test' => 'validation',
                    'timestamp' => now()->toISOString()
                ]);

            $statusCode = $response->status();

            // Accept 200 (success), 422 (validation error), or 401 (auth required)
            if (in_array($statusCode, [200, 422, 401])) {
                $this->line("  ✅ Webhook endpoint accessible (HTTP {$statusCode})");
            } else {
                $warnings[] = "Webhook endpoint returned unexpected status: HTTP {$statusCode}";
            }
        } catch (Exception $e) {
            $warnings[] = "Could not test webhook accessibility: {$e->getMessage()}";
        }
    }

    /**
     * Check route registration
     */
    private function checkRouteRegistration(&$errors, &$warnings)
    {
        $this->info('Checking route registration...');

        $routes = app('router')->getRoutes();
        $webhookRouteFound = false;

        foreach ($routes as $route) {
            if (str_contains($route->uri(), 'payment/unipayment/webhook')) {
                $webhookRouteFound = true;
                $methods = implode('|', $route->methods());
                $this->line("  ✅ Webhook route found: {$methods} {$route->uri()}");

                // Check if POST method is supported
                if (!in_array('POST', $route->methods())) {
                    $errors[] = 'Webhook route does not support POST method';
                }

                break;
            }
        }

        if (!$webhookRouteFound) {
            $errors[] = 'Webhook route not found in registered routes';
        }
    }

    /**
     * Display validation results
     */
    private function displayResults($errors, $warnings)
    {
        $this->newLine();

        if (!empty($warnings)) {
            $this->warn('⚠️  Warnings:');
            foreach ($warnings as $warning) {
                $this->line("  • {$warning}");
            }
            $this->newLine();
        }

        if (!empty($errors)) {
            $this->error('❌ Errors:');
            foreach ($errors as $error) {
                $this->line("  • {$error}");
            }
            $this->newLine();
            $this->error('Webhook configuration validation failed!');
        } else {
            $this->info('✅ Webhook configuration validation passed!');

            if (!empty($warnings)) {
                $this->warn('Note: There are warnings that should be addressed.');
            }
        }
    }
}
