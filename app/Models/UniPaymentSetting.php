<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class UniPaymentSetting extends Model
{
    use HasFactory;

    protected $table = 'unipayment_settings';

    protected $fillable = [
        'app_id',
        'api_key',
        'environment',
        'webhook_secret',
        'webhook_url',
        'webhook_enabled',
        'last_webhook_test',
        'webhook_test_status',
        'webhook_test_response',
        'webhook_retry_count',
        'last_webhook_received',
        'is_enabled',
        'supported_currencies',
        'processing_fee_percentage',
        'minimum_amount',
        'maximum_amount'
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'webhook_enabled' => 'boolean',
        'last_webhook_test' => 'datetime',
        'last_webhook_received' => 'datetime',
        'supported_currencies' => 'array',
        'processing_fee_percentage' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'maximum_amount' => 'decimal:2'
    ];

    /**
     * Encrypt the API key when storing
     */
    public function setApiKeyAttribute($value)
    {
        if ($value) {
            $this->attributes['api_key'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt the API key when retrieving
     */
    public function getApiKeyAttribute($value)
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Check if UniPayment is properly configured
     */
    public static function isConfigured(): bool
    {
        $settings = self::first();
        return $settings &&
            $settings->app_id &&
            $settings->api_key &&
            $settings->is_enabled;
    }

    /**
     * Get the current UniPayment settings
     */
    public static function current(): ?self
    {
        return self::first();
    }

    /**
     * Validate API credentials
     */
    public function validateCredentials(): bool
    {
        if (!$this->app_id || !$this->api_key) {
            return false;
        }

        // Basic validation - check if credentials are not empty
        // In a real implementation, you might want to make a test API call
        return !empty(trim($this->app_id)) && !empty(trim($this->api_key));
    }

    /**
     * Check if the given currency is supported
     */
    public function supportsCurrency(string $currency): bool
    {
        if (!$this->supported_currencies) {
            return false;
        }

        return in_array(strtoupper($currency), array_map('strtoupper', $this->supported_currencies));
    }

    /**
     * Check if the amount is within allowed limits
     */
    public function isAmountValid(float $amount): bool
    {
        return $amount >= $this->minimum_amount && $amount <= $this->maximum_amount;
    }

    /**
     * Calculate processing fee for given amount
     */
    public function calculateFee(float $amount): float
    {
        return round($amount * ($this->processing_fee_percentage / 100), 2);
    }

    /**
     * Get the webhook URL for this environment
     */
    public function getWebhookUrl(): ?string
    {
        if ($this->webhook_url) {
            return $this->webhook_url;
        }

        // Generate default webhook URL using WebhookUrlGenerator
        $generator = app(\App\Services\WebhookUrlGenerator::class);
        return $generator->generateUniPaymentWebhookUrl();
    }

    /**
     * Check if webhooks are properly configured
     */
    public function isWebhookConfigured(): bool
    {
        return $this->webhook_enabled && !empty($this->getWebhookUrl());
    }

    /**
     * Update webhook test status
     */
    public function updateWebhookTestStatus(string $status, ?string $response = null): void
    {
        $this->update([
            'webhook_test_status' => $status,
            'webhook_test_response' => $response,
            'last_webhook_test' => now(),
        ]);
    }

    /**
     * Record webhook received
     */
    public function recordWebhookReceived(): void
    {
        $this->update([
            'last_webhook_received' => now(),
            'webhook_retry_count' => 0, // Reset retry count on successful webhook
        ]);
    }

    /**
     * Increment webhook retry count
     */
    public function incrementWebhookRetryCount(): void
    {
        $this->increment('webhook_retry_count');
    }

    /**
     * Check if webhook URL is accessible
     */
    public function testWebhookUrl(): array
    {
        $webhookUrl = $this->getWebhookUrl();

        if (!$webhookUrl) {
            return [
                'success' => false,
                'message' => 'No webhook URL configured',
                'status_code' => null
            ];
        }

        try {
            $generator = app(\App\Services\WebhookUrlGenerator::class);
            $isAccessible = $generator->isWebhookAccessible($webhookUrl);

            if ($isAccessible) {
                $this->updateWebhookTestStatus('success', 'Webhook URL is accessible');
                return [
                    'success' => true,
                    'message' => 'Webhook URL is accessible',
                    'status_code' => 200
                ];
            } else {
                $this->updateWebhookTestStatus('failed', 'Webhook URL is not accessible');
                return [
                    'success' => false,
                    'message' => 'Webhook URL is not accessible',
                    'status_code' => null
                ];
            }
        } catch (\Exception $e) {
            $this->updateWebhookTestStatus('failed', $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error testing webhook URL: ' . $e->getMessage(),
                'status_code' => null
            ];
        }
    }

    /**
     * Get webhook status information
     */
    public function getWebhookStatus(): array
    {
        return [
            'enabled' => $this->webhook_enabled,
            'configured' => $this->isWebhookConfigured(),
            'url' => $this->getWebhookUrl(),
            'last_test' => $this->last_webhook_test,
            'test_status' => $this->webhook_test_status,
            'test_response' => $this->webhook_test_response,
            'last_received' => $this->last_webhook_received,
            'retry_count' => $this->webhook_retry_count,
        ];
    }
}
