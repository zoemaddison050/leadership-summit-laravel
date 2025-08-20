<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class WebhookMonitoringService
{
    /**
     * Log webhook processing event
     */
    public function logWebhookEvent(string $eventType, array $data, string $status = 'received'): void
    {
        $logData = [
            'event_type' => $eventType,
            'status' => $status,
            'timestamp' => Carbon::now()->toISOString(),
            'data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ];

        Log::channel('single')->info('UniPayment webhook event', $logData);

        // Update monitoring cache
        $this->updateMonitoringCache($eventType, $status);
    }

    /**
     * Log webhook processing success
     */
    public function logWebhookSuccess(string $eventType, array $data, float $processingTime = null): void
    {
        $logData = [
            'event_type' => $eventType,
            'status' => 'success',
            'processing_time_ms' => $processingTime,
            'timestamp' => Carbon::now()->toISOString(),
            'data' => $data
        ];

        Log::channel('single')->info('UniPayment webhook processed successfully', $logData);
        $this->updateMonitoringCache($eventType, 'success', $processingTime);
    }

    /**
     * Log webhook processing error
     */
    public function logWebhookError(string $eventType, array $data, string $error, \Exception $exception = null): void
    {
        $logData = [
            'event_type' => $eventType,
            'status' => 'error',
            'error_message' => $error,
            'timestamp' => Carbon::now()->toISOString(),
            'data' => $data
        ];

        if ($exception) {
            $logData['exception'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ];
        }

        Log::channel('single')->error('UniPayment webhook processing failed', $logData);
        $this->updateMonitoringCache($eventType, 'error');
    }

    /**
     * Get webhook processing metrics
     */
    public function getWebhookMetrics(int $hours = 24): array
    {
        $cacheKey = "webhook_metrics_{$hours}h";

        return Cache::remember($cacheKey, 300, function () use ($hours) {
            $since = Carbon::now()->subHours($hours);

            $metrics = [
                'period_hours' => $hours,
                'since' => $since,
                'total_events' => 0,
                'successful_events' => 0,
                'failed_events' => 0,
                'error_rate' => 0,
                'average_processing_time' => 0,
                'last_event_at' => null,
                'events_by_type' => [],
                'recent_errors' => []
            ];

            // Get metrics from cache counters
            $totalKey = 'webhook_counter_total';
            $successKey = 'webhook_counter_success';
            $errorKey = 'webhook_counter_error';

            $metrics['total_events'] = Cache::get($totalKey, 0);
            $metrics['successful_events'] = Cache::get($successKey, 0);
            $metrics['failed_events'] = Cache::get($errorKey, 0);

            if ($metrics['total_events'] > 0) {
                $metrics['error_rate'] = round(($metrics['failed_events'] / $metrics['total_events']) * 100, 2);
            }

            // Get average processing time
            $processingTimes = Cache::get('webhook_processing_times', []);
            if (!empty($processingTimes)) {
                $metrics['average_processing_time'] = round(array_sum($processingTimes) / count($processingTimes), 2);
            }

            // Get last event timestamp
            $metrics['last_event_at'] = Cache::get('webhook_last_event_at');

            // Get events by type
            $metrics['events_by_type'] = Cache::get('webhook_events_by_type', []);

            // Get recent errors
            $metrics['recent_errors'] = Cache::get('webhook_recent_errors', []);

            return $metrics;
        });
    }

    /**
     * Get webhook health status
     */
    public function getHealthStatus(): array
    {
        $metrics = $this->getWebhookMetrics(1); // Last hour

        $health = [
            'status' => 'healthy',
            'last_event_at' => $metrics['last_event_at'],
            'error_rate' => $metrics['error_rate'],
            'issues' => []
        ];

        // Check for issues
        if ($metrics['error_rate'] > 50) {
            $health['status'] = 'critical';
            $health['issues'][] = 'High error rate: ' . $metrics['error_rate'] . '%';
        } elseif ($metrics['error_rate'] > 20) {
            $health['status'] = 'warning';
            $health['issues'][] = 'Elevated error rate: ' . $metrics['error_rate'] . '%';
        }

        // Check if webhooks are being received
        if ($metrics['last_event_at']) {
            $lastEventTime = Carbon::parse($metrics['last_event_at']);
            $hoursSinceLastEvent = $lastEventTime->diffInHours(Carbon::now());

            if ($hoursSinceLastEvent > 24) {
                $health['status'] = 'warning';
                $health['issues'][] = 'No webhooks received in the last 24 hours';
            }
        } else {
            $health['issues'][] = 'No webhook events recorded';
        }

        return $health;
    }

    /**
     * Update monitoring cache counters
     */
    private function updateMonitoringCache(string $eventType, string $status, float $processingTime = null): void
    {
        // Increment total counter
        Cache::increment('webhook_counter_total');

        // Increment status-specific counter
        Cache::increment("webhook_counter_{$status}");

        // Update events by type
        $eventsByType = Cache::get('webhook_events_by_type', []);
        $eventsByType[$eventType] = ($eventsByType[$eventType] ?? 0) + 1;
        Cache::put('webhook_events_by_type', $eventsByType, 3600);

        // Update last event timestamp
        Cache::put('webhook_last_event_at', Carbon::now()->toISOString(), 3600);

        // Store processing time
        if ($processingTime !== null) {
            $processingTimes = Cache::get('webhook_processing_times', []);
            $processingTimes[] = $processingTime;

            // Keep only last 100 processing times
            if (count($processingTimes) > 100) {
                $processingTimes = array_slice($processingTimes, -100);
            }

            Cache::put('webhook_processing_times', $processingTimes, 3600);
        }

        // Store recent errors (if error status)
        if ($status === 'error') {
            $recentErrors = Cache::get('webhook_recent_errors', []);
            $recentErrors[] = [
                'event_type' => $eventType,
                'timestamp' => Carbon::now()->toISOString()
            ];

            // Keep only last 20 errors
            if (count($recentErrors) > 20) {
                $recentErrors = array_slice($recentErrors, -20);
            }

            Cache::put('webhook_recent_errors', $recentErrors, 3600);
        }
    }

    /**
     * Reset monitoring counters
     */
    public function resetCounters(): void
    {
        $keys = [
            'webhook_counter_total',
            'webhook_counter_success',
            'webhook_counter_error',
            'webhook_events_by_type',
            'webhook_processing_times',
            'webhook_recent_errors',
            'webhook_last_event_at'
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Get webhook processing trends
     */
    public function getProcessingTrends(int $days = 7): array
    {
        // This would typically query a database table
        // For now, we'll return a simplified structure
        $trends = [
            'period_days' => $days,
            'daily_counts' => [],
            'hourly_pattern' => [],
            'error_trends' => []
        ];

        // Generate sample trend data based on current metrics
        $currentMetrics = $this->getWebhookMetrics(24);

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $trends['daily_counts'][$date] = [
                'total' => rand(0, 50),
                'success' => rand(0, 45),
                'errors' => rand(0, 5)
            ];
        }

        return $trends;
    }
}
