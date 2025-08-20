<?php

namespace App\Console\Commands;

use App\Services\WebhookTestingService;
use App\Services\WebhookMonitoringService;
use Illuminate\Console\Command;

class TestWebhookMonitoring extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'webhook:test-monitoring {--url=}';

    /**
     * The console command description.
     */
    protected $description = 'Test webhook monitoring and testing functionality';

    private WebhookTestingService $webhookTestingService;
    private WebhookMonitoringService $webhookMonitoringService;

    public function __construct(
        WebhookTestingService $webhookTestingService,
        WebhookMonitoringService $webhookMonitoringService
    ) {
        parent::__construct();
        $this->webhookTestingService = $webhookTestingService;
        $this->webhookMonitoringService = $webhookMonitoringService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Webhook Monitoring and Testing Functionality');
        $this->line('');

        // Test webhook URL accessibility
        $this->info('1. Testing webhook URL accessibility...');
        $webhookUrl = $this->option('url') ?: 'https://httpbin.org/status/200';

        $accessibilityResult = $this->webhookTestingService->testWebhookAccessibility($webhookUrl);

        $this->table(
            ['Property', 'Value'],
            [
                ['URL', $accessibilityResult['url']],
                ['Accessible', $accessibilityResult['accessible'] ? 'Yes' : 'No'],
                ['Response Time', ($accessibilityResult['response_time'] ?? 'N/A') . 'ms'],
                ['Status Code', $accessibilityResult['status_code'] ?? 'N/A'],
                ['Test Method', $accessibilityResult['test_method'] ?? 'N/A'],
                ['Error', $accessibilityResult['error'] ?? 'None']
            ]
        );

        $this->line('');

        // Test webhook with payload
        $this->info('2. Testing webhook with payload...');
        $payloadUrl = $this->option('url') ?: 'https://httpbin.org/post';

        $payloadResult = $this->webhookTestingService->testWebhookWithPayload($payloadUrl);

        $this->table(
            ['Property', 'Value'],
            [
                ['URL', $payloadResult['url']],
                ['Success', $payloadResult['success'] ? 'Yes' : 'No'],
                ['Response Time', ($payloadResult['response_time'] ?? 'N/A') . 'ms'],
                ['Status Code', $payloadResult['status_code'] ?? 'N/A'],
                ['Error', $payloadResult['error'] ?? 'None']
            ]
        );

        $this->line('');

        // Run diagnostics
        $this->info('3. Running comprehensive diagnostics...');
        $diagnostics = $this->webhookTestingService->runDiagnostics();

        $this->table(
            ['Property', 'Value'],
            [
                ['Environment', $diagnostics['environment']],
                ['App URL', $diagnostics['app_url']],
                ['Webhook URL', $diagnostics['webhook_url'] ?? 'Not generated'],
                ['URL Accessible', isset($diagnostics['url_accessibility']) ?
                    ($diagnostics['url_accessibility']['accessible'] ? 'Yes' : 'No') : 'Not tested'],
                ['Recommendations Count', count($diagnostics['recommendations'])]
            ]
        );

        if (!empty($diagnostics['recommendations'])) {
            $this->warn('Recommendations:');
            foreach ($diagnostics['recommendations'] as $recommendation) {
                $this->line('  • ' . $recommendation);
            }
        }

        $this->line('');

        // Test monitoring functionality
        $this->info('4. Testing monitoring functionality...');

        // Log some test events
        $this->webhookMonitoringService->logWebhookEvent('test.event', ['test' => 'data']);
        $this->webhookMonitoringService->logWebhookSuccess('test.success', ['test' => 'data'], 150.5);
        $this->webhookMonitoringService->logWebhookError('test.error', ['test' => 'data'], 'Test error message');

        // Get metrics
        $metrics = $this->webhookMonitoringService->getWebhookMetrics(1);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Events', $metrics['total_events']],
                ['Successful Events', $metrics['successful_events']],
                ['Failed Events', $metrics['failed_events']],
                ['Error Rate', $metrics['error_rate'] . '%'],
                ['Average Processing Time', $metrics['average_processing_time'] . 'ms'],
                ['Last Event At', $metrics['last_event_at'] ?? 'Never']
            ]
        );

        $this->line('');

        // Get health status
        $this->info('5. Checking webhook health status...');
        $health = $this->webhookMonitoringService->getHealthStatus();

        $statusColor = $health['status'] === 'healthy' ? 'green' : ($health['status'] === 'warning' ? 'yellow' : 'red');

        $this->line('<fg=' . $statusColor . '>Health Status: ' . strtoupper($health['status']) . '</>');
        $this->line('Error Rate: ' . $health['error_rate'] . '%');

        if (!empty($health['issues'])) {
            $this->warn('Issues:');
            foreach ($health['issues'] as $issue) {
                $this->line('  • ' . $issue);
            }
        }

        $this->line('');
        $this->info('Webhook monitoring and testing functionality test completed!');

        return 0;
    }
}
