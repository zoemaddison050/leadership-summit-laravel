<?php

namespace App\Services;

use App\Models\UniPaymentSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class WebhookTestingService
{
    private WebhookUrlGenerator $urlGenerator;

    public function __construct(WebhookUrlGenerator $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Test webhook URL accessibility
     */
    public function testWebhookAccessibility(string $webhookUrl = null): array
    {
        $url = $webhookUrl ?: $this->urlGenerator->generateUniPaymentWebhookUrl();

        $result = [
            'url' => $url,
            'accessible' => false,
            'response_time' => null,
            'status_code' => null,
            'error' => null,
            'tested_at' => Carbon::now(),
            'test_method' => 'HEAD'
        ];

        try {
            $startTime = microtime(true);

            // Test with HEAD request first (lighter)
            $response = Http::timeout(10)->head($url);

            $endTime = microtime(true);
            $result['response_time'] = round(($endTime - $startTime) * 1000, 2); // ms
            $result['status_code'] = $response->status();

            // Consider 200, 405 (Method Not Allowed), or 404 as accessible
            // 405 means the endpoint exists but doesn't accept HEAD
            // 404 might be expected if the route only accepts POST
            $result['accessible'] = in_array($response->status(), [200, 404, 405]);

            // If HEAD failed with 405, try OPTIONS
            if ($response->status() === 405) {
                $optionsResponse = Http::timeout(10)->send('OPTIONS', $url);
                $result['test_method'] = 'OPTIONS';
                $result['status_code'] = $optionsResponse->status();
                $result['accessible'] = in_array($optionsResponse->status(), [200, 404]);
            }
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            Log::warning('Webhook accessibility test failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
        }

        // Cache the result for 5 minutes
        Cache::put("webhook_test_{$url}", $result, 300);

        return $result;
    }

    /**
     * Test webhook with a sample payload
     */
    public function testWebhookWithPayload(string $webhookUrl = null): array
    {
        $url = $webhookUrl ?: $this->urlGenerator->generateUniPaymentWebhookUrl();

        $testPayload = [
            'event_type' => 'test',
            'order_id' => 'test_' . time(),
            'status' => 'test',
            'timestamp' => Carbon::now()->toISOString(),
            'test_mode' => true
        ];

        $result = [
            'url' => $url,
            'success' => false,
            'response_time' => null,
            'status_code' => null,
            'response_body' => null,
            'error' => null,
            'tested_at' => Carbon::now(),
            'payload' => $testPayload
        ];

        try {
            $startTime = microtime(true);

            $response = Http::timeout(15)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'UniPayment-Webhook-Test/1.0'
                ])
                ->post($url, $testPayload);

            $endTime = microtime(true);
            $result['response_time'] = round(($endTime - $startTime) * 1000, 2);
            $result['status_code'] = $response->status();
            $result['response_body'] = $response->body();
            $result['success'] = $response->successful();
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            Log::warning('Webhook payload test failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
        }

        return $result;
    }

    /**
     * Get webhook processing statistics
     */
    public function getWebhookStats(int $days = 7): array
    {
        $since = Carbon::now()->subDays($days);

        // Get stats from logs (this would be enhanced with a proper webhook_logs table)
        $stats = [
            'period_days' => $days,
            'since' => $since,
            'total_webhooks' => 0,
            'successful_webhooks' => 0,
            'failed_webhooks' => 0,
            'average_response_time' => 0,
            'last_webhook_received' => null,
            'error_rate' => 0,
            'common_errors' => []
        ];

        // This is a simplified version - in production you'd query a webhook_logs table
        try {
            $logFile = storage_path('logs/laravel.log');
            if (file_exists($logFile)) {
                $logs = file_get_contents($logFile);
                $webhookLogs = preg_match_all('/UniPayment webhook/', $logs);
                $stats['total_webhooks'] = $webhookLogs;
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get webhook stats', ['error' => $e->getMessage()]);
        }

        return $stats;
    }

    /**
     * Run comprehensive webhook diagnostics
     */
    public function runDiagnostics(): array
    {
        $diagnostics = [
            'timestamp' => Carbon::now(),
            'environment' => app()->environment(),
            'app_url' => config('app.url'),
            'webhook_url' => null,
            'url_accessibility' => null,
            'payload_test' => null,
            'configuration' => null,
            'recommendations' => []
        ];

        // Test webhook URL generation
        try {
            $webhookUrl = $this->urlGenerator->generateUniPaymentWebhookUrl();
            $diagnostics['webhook_url'] = $webhookUrl;
        } catch (\Exception $e) {
            $diagnostics['recommendations'][] = 'Failed to generate webhook URL: ' . $e->getMessage();
        }

        // Test URL accessibility
        if ($diagnostics['webhook_url']) {
            $diagnostics['url_accessibility'] = $this->testWebhookAccessibility($diagnostics['webhook_url']);

            if (!$diagnostics['url_accessibility']['accessible']) {
                $diagnostics['recommendations'][] = 'Webhook URL is not accessible from external services';
            }
        }

        // Test with payload
        if ($diagnostics['webhook_url']) {
            $diagnostics['payload_test'] = $this->testWebhookWithPayload($diagnostics['webhook_url']);

            if (!$diagnostics['payload_test']['success']) {
                $diagnostics['recommendations'][] = 'Webhook endpoint does not properly handle POST requests';
            }
        }

        // Check configuration
        $diagnostics['configuration'] = $this->checkWebhookConfiguration();

        // Add environment-specific recommendations
        if (app()->environment('local')) {
            if (!str_contains($diagnostics['webhook_url'] ?? '', 'ngrok')) {
                $diagnostics['recommendations'][] = 'Consider using ngrok for local webhook testing';
            }
        }

        return $diagnostics;
    }

    /**
     * Check webhook configuration
     */
    private function checkWebhookConfiguration(): array
    {
        $config = [
            'unipayment_configured' => false,
            'webhook_enabled' => false,
            'webhook_url_set' => false,
            'app_url_set' => !empty(config('app.url')),
            'environment' => app()->environment()
        ];

        try {
            $setting = UniPaymentSetting::first();
            if ($setting) {
                $config['unipayment_configured'] = !empty($setting->app_id) && !empty($setting->api_key);
                $config['webhook_enabled'] = $setting->webhook_enabled ?? false;
                $config['webhook_url_set'] = !empty($setting->webhook_url);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to check UniPayment configuration', ['error' => $e->getMessage()]);
        }

        return $config;
    }

    /**
     * Get cached test results
     */
    public function getCachedTestResults(string $webhookUrl): ?array
    {
        return Cache::get("webhook_test_{$webhookUrl}");
    }

    /**
     * Clear test cache
     */
    public function clearTestCache(string $webhookUrl = null): void
    {
        if ($webhookUrl) {
            Cache::forget("webhook_test_{$webhookUrl}");
        } else {
            // Clear all webhook test cache
            $pattern = 'webhook_test_*';
            Cache::flush(); // This is a simple approach - in production you'd use a more targeted approach
        }
    }
}
