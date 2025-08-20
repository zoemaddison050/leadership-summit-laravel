<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\WebhookUrlGenerator;
use App\Services\WebhookTestingService;
use Exception;

class TestWebhookConnectivity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook:test-connectivity 
                            {--timeout=30 : Request timeout in seconds}
                            {--retries=3 : Number of retry attempts}
                            {--verbose : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test webhook endpoint connectivity and response';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔗 Testing webhook connectivity...');

        $timeout = (int) $this->option('timeout');
        $retries = (int) $this->option('retries');
        $verbose = $this->option('verbose');

        try {
            $generator = app(WebhookUrlGenerator::class);
            $testingService = app(WebhookTestingService::class);

            $webhookUrl = $generator->generateUniPaymentWebhookUrl();

            if (empty($webhookUrl)) {
                $this->error('❌ Cannot generate webhook URL');
                return 1;
            }

            $this->info("Testing webhook URL: {$webhookUrl}");
            $this->newLine();

            // Test basic connectivity
            $this->testBasicConnectivity($webhookUrl, $timeout, $retries, $verbose);

            // Test with various payloads
            $this->testWebhookPayloads($webhookUrl, $timeout, $verbose);

            // Test signature validation if configured
            $this->testSignatureValidation($webhookUrl, $timeout, $verbose);

            // Test error handling
            $this->testErrorHandling($webhookUrl, $timeout, $verbose);

            $this->newLine();
            $this->info('✅ Webhook connectivity test completed!');

            return 0;
        } catch (Exception $e) {
            $this->error("❌ Webhook connectivity test failed: {$e->getMessage()}");

            if ($verbose) {
                $this->error("Stack trace:");
                $this->line($e->getTraceAsString());
            }

            return 1;
        }
    }

    /**
     * Test basic connectivity to webhook endpoint
     */
    private function testBasicConnectivity($webhookUrl, $timeout, $retries, $verbose)
    {
        $this->info('Testing basic connectivity...');

        $attempt = 1;
        $success = false;

        while ($attempt <= $retries && !$success) {
            try {
                if ($verbose && $attempt > 1) {
                    $this->line("  Attempt {$attempt}/{$retries}...");
                }

                $startTime = microtime(true);

                $response = Http::timeout($timeout)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'User-Agent' => 'Laravel-Webhook-Tester/1.0'
                    ])
                    ->post($webhookUrl, [
                        'test' => 'connectivity',
                        'timestamp' => now()->toISOString(),
                        'attempt' => $attempt
                    ]);

                $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                $statusCode = $response->status();

                if (in_array($statusCode, [200, 422, 401])) {
                    $this->line("  ✅ Connected successfully (HTTP {$statusCode}) - {$responseTime}ms");
                    $success = true;

                    if ($verbose) {
                        $this->line("  Response headers:");
                        foreach ($response->headers() as $header => $values) {
                            $this->line("    {$header}: " . implode(', ', $values));
                        }

                        if ($response->body()) {
                            $this->line("  Response body: " . $response->body());
                        }
                    }
                } else {
                    $this->warn("  ⚠️  Unexpected response: HTTP {$statusCode} - {$responseTime}ms");

                    if ($verbose) {
                        $this->line("  Response body: " . $response->body());
                    }
                }
            } catch (Exception $e) {
                $this->warn("  ❌ Connection failed: {$e->getMessage()}");

                if ($attempt < $retries) {
                    $this->line("  Retrying in 2 seconds...");
                    sleep(2);
                }
            }

            $attempt++;
        }

        if (!$success) {
            throw new Exception("Failed to connect after {$retries} attempts");
        }
    }

    /**
     * Test webhook with various payload formats
     */
    private function testWebhookPayloads($webhookUrl, $timeout, $verbose)
    {
        $this->info('Testing webhook payloads...');

        $testPayloads = [
            'Empty payload' => [],
            'Minimal payload' => [
                'event_type' => 'test'
            ],
            'UniPayment-like payload' => [
                'event_type' => 'invoice_paid',
                'invoice_id' => 'test_invoice_123',
                'order_id' => 'test_order_456',
                'status' => 'Paid',
                'amount' => '100.00',
                'currency' => 'USD',
                'created_at' => now()->toISOString()
            ],
            'Invalid payload' => [
                'invalid_field' => 'invalid_value'
            ]
        ];

        foreach ($testPayloads as $description => $payload) {
            try {
                if ($verbose) {
                    $this->line("  Testing: {$description}");
                }

                $response = Http::timeout($timeout)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'User-Agent' => 'Laravel-Webhook-Tester/1.0'
                    ])
                    ->post($webhookUrl, $payload);

                $statusCode = $response->status();

                if (in_array($statusCode, [200, 422, 400, 401])) {
                    $this->line("  ✅ {$description}: HTTP {$statusCode}");
                } else {
                    $this->warn("  ⚠️  {$description}: HTTP {$statusCode}");
                }

                if ($verbose && $response->body()) {
                    $this->line("    Response: " . $response->body());
                }
            } catch (Exception $e) {
                $this->warn("  ❌ {$description}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Test signature validation if configured
     */
    private function testSignatureValidation($webhookUrl, $timeout, $verbose)
    {
        $webhookSecret = config('unipayment.webhook_secret');

        if (empty($webhookSecret)) {
            $this->warn('Skipping signature validation test - no webhook secret configured');
            return;
        }

        $this->info('Testing signature validation...');

        $payload = [
            'event_type' => 'test_signature',
            'timestamp' => now()->toISOString()
        ];

        $payloadJson = json_encode($payload);

        // Test with valid signature
        try {
            $validSignature = base64_encode(hash_hmac('sha256', $payloadJson, $webhookSecret, true));

            $response = Http::timeout($timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-UniPayment-Signature' => $validSignature,
                    'User-Agent' => 'Laravel-Webhook-Tester/1.0'
                ])
                ->withBody($payloadJson)
                ->post($webhookUrl);

            $statusCode = $response->status();

            if (in_array($statusCode, [200, 422])) {
                $this->line("  ✅ Valid signature accepted: HTTP {$statusCode}");
            } else {
                $this->warn("  ⚠️  Valid signature rejected: HTTP {$statusCode}");
            }
        } catch (Exception $e) {
            $this->warn("  ❌ Valid signature test failed: {$e->getMessage()}");
        }

        // Test with invalid signature
        try {
            $invalidSignature = base64_encode('invalid_signature');

            $response = Http::timeout($timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-UniPayment-Signature' => $invalidSignature,
                    'User-Agent' => 'Laravel-Webhook-Tester/1.0'
                ])
                ->withBody($payloadJson)
                ->post($webhookUrl);

            $statusCode = $response->status();

            if ($statusCode === 401) {
                $this->line("  ✅ Invalid signature rejected: HTTP {$statusCode}");
            } else {
                $this->warn("  ⚠️  Invalid signature not properly rejected: HTTP {$statusCode}");
            }
        } catch (Exception $e) {
            $this->warn("  ❌ Invalid signature test failed: {$e->getMessage()}");
        }
    }

    /**
     * Test error handling scenarios
     */
    private function testErrorHandling($webhookUrl, $timeout, $verbose)
    {
        $this->info('Testing error handling...');

        $errorTests = [
            'Large payload' => str_repeat('x', 10000),
            'Invalid JSON' => 'invalid-json-content',
            'Empty body' => ''
        ];

        foreach ($errorTests as $description => $body) {
            try {
                if ($verbose) {
                    $this->line("  Testing: {$description}");
                }

                $response = Http::timeout($timeout)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'User-Agent' => 'Laravel-Webhook-Tester/1.0'
                    ])
                    ->withBody($body)
                    ->post($webhookUrl);

                $statusCode = $response->status();

                if (in_array($statusCode, [400, 422, 413, 500])) {
                    $this->line("  ✅ {$description}: HTTP {$statusCode} (expected error)");
                } else {
                    $this->warn("  ⚠️  {$description}: HTTP {$statusCode} (unexpected)");
                }
            } catch (Exception $e) {
                // Connection errors are expected for some tests
                $this->line("  ✅ {$description}: Connection error (expected)");
            }
        }
    }
}
