<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\WebhookMonitoringService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WebhookMonitoringServiceTest extends TestCase
{
    protected WebhookMonitoringService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new WebhookMonitoringService();

        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function logs_webhook_event_with_correct_data()
    {
        $eventType = 'payment.completed';
        $data = ['order_id' => 'test_123', 'amount' => 100.00];

        Log::shouldReceive('channel')
            ->with('single')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('UniPayment webhook event', \Mockery::on(function ($logData) use ($eventType, $data) {
                return $logData['event_type'] === $eventType &&
                    $logData['status'] === 'received' &&
                    $logData['data'] === $data &&
                    isset($logData['timestamp']) &&
                    isset($logData['ip_address']) &&
                    isset($logData['user_agent']);
            }));

        $this->service->logWebhookEvent($eventType, $data);

        // Verify cache counters are updated
        $this->assertEquals(1, Cache::get('webhook_counter_total'));
        $this->assertEquals(1, Cache::get('webhook_counter_received'));
    }

    /** @test */
    public function logs_webhook_success_with_processing_time()
    {
        $eventType = 'payment.completed';
        $data = ['order_id' => 'test_123'];
        $processingTime = 150.5;

        Log::shouldReceive('channel')
            ->with('single')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('UniPayment webhook processed successfully', \Mockery::on(function ($logData) use ($eventType, $data, $processingTime) {
                return $logData['event_type'] === $eventType &&
                    $logData['status'] === 'success' &&
                    $logData['processing_time_ms'] === $processingTime &&
                    $logData['data'] === $data;
            }));

        $this->service->logWebhookSuccess($eventType, $data, $processingTime);

        // Verify cache counters and processing times are updated
        $this->assertEquals(1, Cache::get('webhook_counter_total'));
        $this->assertEquals(1, Cache::get('webhook_counter_success'));

        $processingTimes = Cache::get('webhook_processing_times', []);
        $this->assertContains($processingTime, $processingTimes);
    }

    /** @test */
    public function logs_webhook_error_with_exception_details()
    {
        $eventType = 'payment.failed';
        $data = ['order_id' => 'test_123'];
        $error = 'Payment processing failed';
        $exception = new \Exception('Database connection failed', 500);

        Log::shouldReceive('channel')
            ->with('single')
            ->andReturnSelf();

        Log::shouldReceive('error')
            ->once()
            ->with('UniPayment webhook processing failed', \Mockery::on(function ($logData) use ($eventType, $error, $exception) {
                return $logData['event_type'] === $eventType &&
                    $logData['status'] === 'error' &&
                    $logData['error_message'] === $error &&
                    isset($logData['exception']) &&
                    $logData['exception']['message'] === $exception->getMessage();
            }));

        $this->service->logWebhookError($eventType, $data, $error, $exception);

        // Verify error counters are updated
        $this->assertEquals(1, Cache::get('webhook_counter_total'));
        $this->assertEquals(1, Cache::get('webhook_counter_error'));

        $recentErrors = Cache::get('webhook_recent_errors', []);
        $this->assertCount(1, $recentErrors);
        $this->assertEquals($eventType, $recentErrors[0]['event_type']);
    }

    /** @test */
    public function logs_webhook_error_without_exception()
    {
        $eventType = 'payment.failed';
        $data = ['order_id' => 'test_123'];
        $error = 'Validation failed';

        Log::shouldReceive('channel')
            ->with('single')
            ->andReturnSelf();

        Log::shouldReceive('error')
            ->once()
            ->with('UniPayment webhook processing failed', \Mockery::on(function ($logData) use ($eventType, $error) {
                return $logData['event_type'] === $eventType &&
                    $logData['error_message'] === $error &&
                    !isset($logData['exception']);
            }));

        $this->service->logWebhookError($eventType, $data, $error);
    }

    /** @test */
    public function gets_webhook_metrics_with_correct_calculations()
    {
        // Set up test data in cache
        Cache::put('webhook_counter_total', 100);
        Cache::put('webhook_counter_success', 85);
        Cache::put('webhook_counter_error', 15);
        Cache::put('webhook_processing_times', [100.5, 150.2, 200.8, 75.3]);
        Cache::put('webhook_last_event_at', Carbon::now()->subMinutes(30)->toISOString());
        Cache::put('webhook_events_by_type', [
            'payment.completed' => 60,
            'payment.failed' => 15,
            'payment.pending' => 25
        ]);
        Cache::put('webhook_recent_errors', [
            ['event_type' => 'payment.failed', 'timestamp' => Carbon::now()->subMinutes(10)->toISOString()],
            ['event_type' => 'payment.timeout', 'timestamp' => Carbon::now()->subMinutes(5)->toISOString()]
        ]);

        $metrics = $this->service->getWebhookMetrics(24);

        $this->assertEquals(24, $metrics['period_hours']);
        $this->assertEquals(100, $metrics['total_events']);
        $this->assertEquals(85, $metrics['successful_events']);
        $this->assertEquals(15, $metrics['failed_events']);
        $this->assertEquals(15.0, $metrics['error_rate']); // 15/100 * 100
        $this->assertEquals(131.7, $metrics['average_processing_time']); // Average of processing times
        $this->assertNotNull($metrics['last_event_at']);
        $this->assertCount(3, $metrics['events_by_type']);
        $this->assertCount(2, $metrics['recent_errors']);
    }

    /** @test */
    public function calculates_zero_error_rate_when_no_events()
    {
        $metrics = $this->service->getWebhookMetrics(24);

        $this->assertEquals(0, $metrics['total_events']);
        $this->assertEquals(0, $metrics['error_rate']);
    }

    /** @test */
    public function caches_webhook_metrics()
    {
        Cache::put('webhook_counter_total', 50);

        // First call should cache the result
        $metrics1 = $this->service->getWebhookMetrics(24);

        // Change the underlying data
        Cache::put('webhook_counter_total', 100);

        // Second call should return cached result
        $metrics2 = $this->service->getWebhookMetrics(24);

        $this->assertEquals($metrics1['total_events'], $metrics2['total_events']);
        $this->assertEquals(50, $metrics2['total_events']); // Should still be 50 from cache
    }

    /** @test */
    public function gets_healthy_status_with_low_error_rate()
    {
        Cache::put('webhook_counter_total', 100);
        Cache::put('webhook_counter_success', 95);
        Cache::put('webhook_counter_error', 5);
        Cache::put('webhook_last_event_at', Carbon::now()->subMinutes(30)->toISOString());

        $health = $this->service->getHealthStatus();

        $this->assertEquals('healthy', $health['status']);
        $this->assertEquals(5.0, $health['error_rate']);
        $this->assertEmpty($health['issues']);
    }

    /** @test */
    public function gets_warning_status_with_elevated_error_rate()
    {
        Cache::put('webhook_counter_total', 100);
        Cache::put('webhook_counter_success', 70);
        Cache::put('webhook_counter_error', 30);
        Cache::put('webhook_last_event_at', Carbon::now()->subMinutes(30)->toISOString());

        $health = $this->service->getHealthStatus();

        $this->assertEquals('warning', $health['status']);
        $this->assertEquals(30.0, $health['error_rate']);
        $this->assertContains('Elevated error rate: 30%', $health['issues']);
    }

    /** @test */
    public function gets_critical_status_with_high_error_rate()
    {
        Cache::put('webhook_counter_total', 100);
        Cache::put('webhook_counter_success', 40);
        Cache::put('webhook_counter_error', 60);
        Cache::put('webhook_last_event_at', Carbon::now()->subMinutes(30)->toISOString());

        $health = $this->service->getHealthStatus();

        $this->assertEquals('critical', $health['status']);
        $this->assertEquals(60.0, $health['error_rate']);
        $this->assertContains('High error rate: 60%', $health['issues']);
    }

    /** @test */
    public function detects_no_recent_webhooks_issue()
    {
        Cache::put('webhook_counter_total', 10);
        Cache::put('webhook_counter_success', 10);
        Cache::put('webhook_counter_error', 0);
        Cache::put('webhook_last_event_at', Carbon::now()->subHours(25)->toISOString());

        $health = $this->service->getHealthStatus();

        $this->assertEquals('warning', $health['status']);
        $this->assertContains('No webhooks received in the last 24 hours', $health['issues']);
    }

    /** @test */
    public function detects_no_webhook_events_recorded()
    {
        $health = $this->service->getHealthStatus();

        $this->assertContains('No webhook events recorded', $health['issues']);
    }

    /** @test */
    public function updates_monitoring_cache_counters()
    {
        $this->service->logWebhookEvent('payment.completed', []);

        $this->assertEquals(1, Cache::get('webhook_counter_total'));
        $this->assertEquals(1, Cache::get('webhook_counter_received'));

        $eventsByType = Cache::get('webhook_events_by_type', []);
        $this->assertEquals(1, $eventsByType['payment.completed']);

        $this->assertNotNull(Cache::get('webhook_last_event_at'));
    }

    /** @test */
    public function limits_processing_times_to_100_entries()
    {
        // Fill cache with 100 processing times
        $processingTimes = array_fill(0, 100, 100.0);
        Cache::put('webhook_processing_times', $processingTimes);

        // Add one more
        $this->service->logWebhookSuccess('payment.completed', [], 200.0);

        $updatedTimes = Cache::get('webhook_processing_times', []);
        $this->assertCount(100, $updatedTimes);
        $this->assertEquals(200.0, end($updatedTimes)); // New time should be at the end
    }

    /** @test */
    public function limits_recent_errors_to_20_entries()
    {
        // Fill cache with 20 errors
        $errors = [];
        for ($i = 0; $i < 20; $i++) {
            $errors[] = [
                'event_type' => "error_{$i}",
                'timestamp' => Carbon::now()->subMinutes($i)->toISOString()
            ];
        }
        Cache::put('webhook_recent_errors', $errors);

        // Add one more error
        $this->service->logWebhookError('new_error', [], 'Test error');

        $updatedErrors = Cache::get('webhook_recent_errors', []);
        $this->assertCount(20, $updatedErrors);
        $this->assertEquals('new_error', end($updatedErrors)['event_type']);
    }

    /** @test */
    public function resets_all_monitoring_counters()
    {
        // Set up some test data
        Cache::put('webhook_counter_total', 100);
        Cache::put('webhook_counter_success', 85);
        Cache::put('webhook_counter_error', 15);
        Cache::put('webhook_events_by_type', ['payment.completed' => 50]);
        Cache::put('webhook_processing_times', [100.0, 200.0]);
        Cache::put('webhook_recent_errors', [['event_type' => 'test']]);
        Cache::put('webhook_last_event_at', Carbon::now()->toISOString());

        $this->service->resetCounters();

        // Verify all counters are cleared
        $this->assertNull(Cache::get('webhook_counter_total'));
        $this->assertNull(Cache::get('webhook_counter_success'));
        $this->assertNull(Cache::get('webhook_counter_error'));
        $this->assertNull(Cache::get('webhook_events_by_type'));
        $this->assertNull(Cache::get('webhook_processing_times'));
        $this->assertNull(Cache::get('webhook_recent_errors'));
        $this->assertNull(Cache::get('webhook_last_event_at'));
    }

    /** @test */
    public function gets_processing_trends_with_sample_data()
    {
        $trends = $this->service->getProcessingTrends(7);

        $this->assertEquals(7, $trends['period_days']);
        $this->assertCount(7, $trends['daily_counts']);

        // Verify each day has the expected structure
        foreach ($trends['daily_counts'] as $date => $counts) {
            $this->assertArrayHasKey('total', $counts);
            $this->assertArrayHasKey('success', $counts);
            $this->assertArrayHasKey('errors', $counts);
            $this->assertIsInt($counts['total']);
            $this->assertIsInt($counts['success']);
            $this->assertIsInt($counts['errors']);
        }
    }

    /** @test */
    public function includes_request_metadata_in_webhook_event_log()
    {
        $eventType = 'payment.completed';
        $data = ['order_id' => 'test_123'];

        // Mock request data
        request()->merge(['test' => 'data']);
        request()->server->set('REMOTE_ADDR', '192.168.1.1');
        request()->headers->set('User-Agent', 'Test-Agent/1.0');

        Log::shouldReceive('channel')
            ->with('single')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('UniPayment webhook event', \Mockery::on(function ($logData) {
                return isset($logData['ip_address']) &&
                    isset($logData['user_agent']);
            }));

        $this->service->logWebhookEvent($eventType, $data);
    }

    /** @test */
    public function handles_missing_cache_data_gracefully()
    {
        // Don't set any cache data
        $metrics = $this->service->getWebhookMetrics(24);

        $this->assertEquals(0, $metrics['total_events']);
        $this->assertEquals(0, $metrics['successful_events']);
        $this->assertEquals(0, $metrics['failed_events']);
        $this->assertEquals(0, $metrics['error_rate']);
        $this->assertEquals(0, $metrics['average_processing_time']);
        $this->assertNull($metrics['last_event_at']);
        $this->assertEmpty($metrics['events_by_type']);
        $this->assertEmpty($metrics['recent_errors']);
    }
}
