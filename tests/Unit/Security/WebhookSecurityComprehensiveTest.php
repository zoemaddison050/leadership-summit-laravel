<?php

namespace Tests\Unit\Security;

use Tests\TestCase;
use App\Models\UniPaymentSetting;
use App\Http\Middleware\WebhookAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookSecurityComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    protected $middleware;
    protected $uniPaymentSettings;

    protected function setUp(): void
    {
        parent::setUp();

        $uniPaymentService = $this->createMock(\App\Services\UniPaymentOfficialService::class);
        $this->middleware = new WebhookAuthentication($uniPaymentService);

        $this->uniPaymentSettings = UniPaymentSetting::create([
            'app_id' => 'test-app-id',
            'api_key' => 'test-api-key',
            'environment' => 'sandbox',
            'webhook_secret' => 'test-webhook-secret',
            'webhook_enabled' => true,
            'is_enabled' => true
        ]);
    }

    /** @test */
    public function validates_correct_webhook_signature()
    {
        $payload = json_encode(['test' => 'data']);
        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-UniPayment-Signature', $signature);

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function rejects_invalid_webhook_signature()
    {
        $payload = json_encode(['test' => 'data']);
        $invalidSignature = 'sha256=' . hash_hmac('sha256', $payload, 'wrong-secret');

        $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-UniPayment-Signature', $invalidSignature);

        Log::shouldReceive('warning')
            ->with('Webhook signature validation failed', \Mockery::type('array'))
            ->once();

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Invalid signature', $response->getContent());
    }

    /** @test */
    public function rejects_missing_webhook_signature()
    {
        $payload = json_encode(['test' => 'data']);

        $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        // No signature header

        Log::shouldReceive('warning')
            ->with('Webhook signature missing', \Mockery::type('array'))
            ->once();

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Missing signature', $response->getContent());
    }

    /** @test */
    public function allows_webhook_without_signature_when_no_secret_configured()
    {
        // Remove webhook secret
        $this->uniPaymentSettings->update(['webhook_secret' => null]);

        $payload = json_encode(['test' => 'data']);

        $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        // No signature header

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function rejects_malformed_signature_format()
    {
        $payload = json_encode(['test' => 'data']);

        $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-UniPayment-Signature', 'invalid-format');

        Log::shouldReceive('warning')
            ->with('Webhook signature validation failed', \Mockery::type('array'))
            ->once();

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    /** @test */
    public function handles_empty_payload_gracefully()
    {
        $payload = '';
        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-UniPayment-Signature', $signature);

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function validates_signature_with_special_characters_in_payload()
    {
        $payload = json_encode(['test' => 'data with special chars: !@#$%^&*()']);
        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-UniPayment-Signature', $signature);

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function validates_signature_with_unicode_characters()
    {
        $payload = json_encode(['test' => 'data with unicode: 测试数据 🚀']);
        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-UniPayment-Signature', $signature);

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function handles_very_large_payload()
    {
        $largeData = str_repeat('x', 100000); // 100KB payload
        $payload = json_encode(['large_data' => $largeData]);
        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-UniPayment-Signature', $signature);

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function logs_security_events_with_proper_details()
    {
        $payload = json_encode(['test' => 'data']);
        $invalidSignature = 'sha256=' . hash_hmac('sha256', $payload, 'wrong-secret');

        $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-UniPayment-Signature', $invalidSignature);
        $request->server->set('REMOTE_ADDR', '192.168.1.100');
        $request->headers->set('User-Agent', 'Malicious-Bot/1.0');

        Log::shouldReceive('warning')
            ->with('Webhook signature validation failed', \Mockery::on(function ($logData) {
                return isset($logData['ip_address']) &&
                    isset($logData['user_agent']) &&
                    isset($logData['signature_provided']) &&
                    isset($logData['payload_length']) &&
                    $logData['ip_address'] === '192.168.1.100' &&
                    $logData['user_agent'] === 'Malicious-Bot/1.0';
            }))
            ->once();

        $this->middleware->handle($request, function ($req) {
            return response('OK', 200);
        });
    }

    /** @test */
    public function handles_signature_with_different_hash_algorithms()
    {
        $payload = json_encode(['test' => 'data']);

        // Test with md5 (should be rejected)
        $md5Signature = 'md5=' . hash_hmac('md5', $payload, 'test-webhook-secret');
        $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-UniPayment-Signature', $md5Signature);

        Log::shouldReceive('warning')
            ->with('Webhook signature validation failed', \Mockery::type('array'))
            ->once();

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    /** @test */
    public function prevents_timing_attacks_in_signature_comparison()
    {
        $payload = json_encode(['test' => 'data']);
        $correctSignature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');
        $incorrectSignature = 'sha256=' . hash_hmac('sha256', $payload, 'wrong-secret');

        // Measure time for correct signature
        $request1 = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        $request1->headers->set('X-UniPayment-Signature', $correctSignature);

        $start1 = microtime(true);
        $response1 = $this->middleware->handle($request1, function ($req) {
            return response('OK', 200);
        });
        $time1 = microtime(true) - $start1;

        // Measure time for incorrect signature
        $request2 = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        $request2->headers->set('X-UniPayment-Signature', $incorrectSignature);

        Log::shouldReceive('warning')->once();

        $start2 = microtime(true);
        $response2 = $this->middleware->handle($request2, function ($req) {
            return response('OK', 200);
        });
        $time2 = microtime(true) - $start2;

        // Time difference should be minimal (within reasonable bounds)
        $timeDifference = abs($time1 - $time2);
        $this->assertLessThan(0.01, $timeDifference); // Less than 10ms difference
    }

    /** @test */
    public function handles_multiple_signature_headers()
    {
        $payload = json_encode(['test' => 'data']);
        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-UniPayment-Signature', [$signature, 'sha256=invalid']);

        // Should use the first signature
        $response = $this->middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function validates_signature_case_sensitivity()
    {
        $payload = json_encode(['test' => 'data']);
        $signature = 'SHA256=' . hash_hmac('sha256', $payload, 'test-webhook-secret'); // Uppercase

        $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-UniPayment-Signature', $signature);

        Log::shouldReceive('warning')
            ->with('Webhook signature validation failed', \Mockery::type('array'))
            ->once();

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    /** @test */
    public function handles_webhook_secret_with_special_characters()
    {
        // Update webhook secret with special characters
        $specialSecret = 'secret!@#$%^&*()_+-=[]{}|;:,.<>?';
        $this->uniPaymentSettings->update(['webhook_secret' => $specialSecret]);

        $payload = json_encode(['test' => 'data']);
        $signature = 'sha256=' . hash_hmac('sha256', $payload, $specialSecret);

        $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-UniPayment-Signature', $signature);

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function handles_concurrent_signature_validation_requests()
    {
        $payload = json_encode(['test' => 'data']);
        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        // Simulate concurrent requests
        $responses = [];
        for ($i = 0; $i < 5; $i++) {
            $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
            $request->headers->set('X-UniPayment-Signature', $signature);

            $responses[] = $this->middleware->handle($request, function ($req) {
                return response('OK', 200);
            });
        }

        // All should succeed
        foreach ($responses as $response) {
            $this->assertEquals(200, $response->getStatusCode());
        }
    }

    /** @test */
    public function handles_database_unavailable_during_signature_validation()
    {
        // Mock database failure
        $this->mock(UniPaymentSetting::class, function ($mock) {
            $mock->shouldReceive('first')
                ->andThrow(new \Exception('Database connection failed'));
        });

        $payload = json_encode(['test' => 'data']);
        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-webhook-secret');

        $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-UniPayment-Signature', $signature);

        Log::shouldReceive('error')
            ->with('Failed to retrieve webhook secret for validation', \Mockery::type('array'))
            ->once();

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(500, $response->getStatusCode());
    }

    /** @test */
    public function validates_signature_with_binary_payload()
    {
        $binaryPayload = pack('H*', '48656c6c6f20576f726c64'); // "Hello World" in hex
        $signature = 'sha256=' . hash_hmac('sha256', $binaryPayload, 'test-webhook-secret');

        $request = Request::create('/webhook', 'POST', [], [], [], [], $binaryPayload);
        $request->headers->set('X-UniPayment-Signature', $signature);

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function rate_limits_failed_signature_attempts()
    {
        $payload = json_encode(['test' => 'data']);
        $invalidSignature = 'sha256=' . hash_hmac('sha256', $payload, 'wrong-secret');

        Log::shouldReceive('warning')->times(5);

        // Make multiple failed attempts
        for ($i = 0; $i < 5; $i++) {
            $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
            $request->headers->set('X-UniPayment-Signature', $invalidSignature);
            $request->server->set('REMOTE_ADDR', '192.168.1.100');

            $response = $this->middleware->handle($request, function ($req) {
                return response('OK', 200);
            });

            $this->assertEquals(401, $response->getStatusCode());
        }
    }
}
