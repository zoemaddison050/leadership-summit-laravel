<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class UniPaymentOfficialService
{
    protected array $config = [];

    public function __construct()
    {
        $this->loadConfiguration();
    }

    protected function loadConfiguration(): void
    {
        $dbSettings = \App\Models\UniPaymentSetting::first();

        if ($dbSettings && $dbSettings->is_enabled) {
            $this->config = [
                'app_id' => $dbSettings->app_id, // Payment App ID: 0ff18d3d-eea1-47c0-a9a3-e8f5493d9ead
                'client_id' => '5ce7507b-5afc-4c14-a8dc-b1a28a9ac99a', // Authentication Client ID
                'client_secret' => $dbSettings->api_key, // Client Secret: A3gm8g7hVow3eLvmogvEBgdAsCtkKjTpg
                'environment' => $dbSettings->environment ?? 'sandbox',
            ];
        } else {
            // Initialize with default/fallback configuration
            $this->config = [
                'app_id' => config('unipayment.app_id', ''),
                'client_id' => config('unipayment.client_id', ''),
                'client_secret' => config('unipayment.client_secret', ''),
                'environment' => config('unipayment.environment', 'sandbox'),
            ];
        }
    }

    /**
     * Check if the service is properly configured
     */
    protected function isConfigured(): bool
    {
        return !empty($this->config['app_id']) &&
            !empty($this->config['client_id']) &&
            !empty($this->config['client_secret']);
    }

    /**
     * Validate configuration and throw exception if not properly configured
     */
    protected function validateConfiguration(): void
    {
        if (!$this->isConfigured()) {
            throw new Exception(
                'UniPayment service is not properly configured. Please check your UniPayment settings in the admin panel or environment configuration.'
            );
        }
    }

    /**
     * Get access token following official documentation
     */
    protected function getAccessToken(): string
    {
        $this->validateConfiguration();
        $baseUrl = $this->config['environment'] === 'production'
            ? 'https://api.unipayment.io'
            : 'https://sandbox-api.unipayment.io';

        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post($baseUrl . '/connect/token', [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->config['client_id'],
                    'client_secret' => $this->config['client_secret'],
                ]);

            if (!$response->successful()) {
                throw new Exception('Token request failed: ' . $response->body());
            }

            $data = $response->json();

            if (!isset($data['access_token'])) {
                throw new Exception('No access token in response: ' . $response->body());
            }

            return $data['access_token'];
        } catch (Exception $e) {
            Log::error('UniPayment token error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create invoice following official API documentation
     * Based on: https://help.unipayment.io/en/articles/7092746-create-invoice
     */
    public function createPayment(
        float $amount,
        string $currency,
        string $orderId,
        string $title,
        string $description,
        string $notifyUrl,
        string $redirectUrl,
        array $extArgs = []
    ): array {
        try {
            $this->validateConfiguration();
            $accessToken = $this->getAccessToken();

            $baseUrl = $this->config['environment'] === 'production'
                ? 'https://api.unipayment.io'
                : 'https://sandbox-api.unipayment.io';

            // Following official documentation format with correct app_id
            $payload = [
                'app_id' => $this->config['app_id'],
                'price_amount' => $amount,
                'price_currency' => $currency,
                'payment_method_type' => 'CARD', // Specify card payment method
                'order_id' => $orderId,
                'title' => $title,
                'description' => $description,
                'lang' => 'en',
                'notify_url' => $notifyUrl,
                'redirect_url' => $redirectUrl,
            ];

            // Add optional parameters if provided
            if (!empty($extArgs)) {
                $payload['ext_args'] = json_encode($extArgs);
            }

            Log::info('UniPayment Official API Request', [
                'url' => $baseUrl . '/v1.0/invoices',
                'payload' => $payload,
                'environment' => $this->config['environment']
            ]);

            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->post($baseUrl . '/v1.0/invoices', $payload);

            Log::info('UniPayment Official API Response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if (!$response->successful()) {
                throw new Exception('API Error (' . $response->status() . '): ' . $response->body());
            }

            $data = $response->json();

            if (!isset($data['data'])) {
                throw new Exception('Invalid response format: ' . $response->body());
            }

            return [
                'success' => true,
                'invoice_id' => $data['data']['invoice_id'],
                'checkout_url' => $data['data']['invoice_url'], // Correct field name
                'amount' => $data['data']['price_amount'],
                'currency' => $data['data']['price_currency'],
                'status' => $data['data']['status'],
                'order_id' => $data['data']['order_id'],
                'raw_response' => $data
            ];
        } catch (Exception $e) {
            Log::error('UniPayment Official Service Error: ' . $e->getMessage());

            // If we get "App with this id does not exist", provide helpful guidance
            if (strpos($e->getMessage(), 'App with this id does not exist') !== false) {
                throw new Exception(
                    'UniPayment App ID not found. Please: ' .
                        '1) Login to https://sandbox.unipayment.io, ' .
                        '2) Verify your app exists and get the correct App ID, ' .
                        '3) Update your database with the correct credentials. ' .
                        'Original error: ' . $e->getMessage()
                );
            }

            throw $e;
        }
    }

    /**
     * Get invoice status following official documentation
     * Based on: https://help.unipayment.io/en/articles/7092747-get-invoice
     */
    public function getPaymentStatus(string $invoiceId): array
    {
        try {
            $this->validateConfiguration();
            $accessToken = $this->getAccessToken();

            $baseUrl = $this->config['environment'] === 'production'
                ? 'https://api.unipayment.io'
                : 'https://sandbox-api.unipayment.io';

            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get($baseUrl . '/v1.0/invoices/' . $invoiceId);

            if (!$response->successful()) {
                throw new Exception('Status API Error (' . $response->status() . '): ' . $response->body());
            }

            $data = $response->json();

            if (!isset($data['data'])) {
                throw new Exception('Invalid status response format: ' . $response->body());
            }

            return $data['data'];
        } catch (Exception $e) {
            Log::error('UniPayment Status Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test connection with minimal request
     */
    public function testConnection(): array
    {
        try {
            // Check if service is configured
            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'message' => 'UniPayment service is not configured. Please set up your credentials in the admin panel.',
                    'environment' => $this->config['environment'] ?? 'unknown',
                    'app_id' => $this->config['app_id'] ?? 'not_set'
                ];
            }

            // First test token generation
            $accessToken = $this->getAccessToken();

            return [
                'success' => true,
                'message' => 'Connection successful! Token obtained.',
                'token_preview' => substr($accessToken, 0, 20) . '...',
                'environment' => $this->config['environment'],
                'app_id' => $this->config['app_id']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'environment' => $this->config['environment'] ?? 'unknown',
                'app_id' => $this->config['app_id'] ?? 'not_set'
            ];
        }
    }

    /**
     * Create a minimal test payment to verify API parameters
     */
    public function createTestPayment(): array
    {
        try {
            return $this->createPayment(
                1.00, // Minimal amount
                'USD',
                'TEST_' . time(),
                'Test Payment',
                'API Test Payment',
                'https://httpbin.org/post', // Test webhook URL
                'https://httpbin.org/get',  // Test redirect URL
                ['test' => true]
            );
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if UniPayment is available for card payments
     */
    public function isAvailableForCard(): bool
    {
        try {
            // First check if service is configured
            if (!$this->isConfigured()) {
                return false;
            }

            $connectionTest = $this->testConnection();
            return $connectionTest['success'];
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get processing fee percentage
     */
    public function getProcessingFeePercentage(): float
    {
        return 2.9; // Default UniPayment processing fee
    }

    /**
     * Get minimum payment amount
     */
    public function getMinimumAmount(): float
    {
        return 1.00;
    }

    /**
     * Get maximum payment amount
     */
    public function getMaximumAmount(): float
    {
        return 10000.00;
    }

    /**
     * Get default currency
     */
    public function getDefaultCurrency(): string
    {
        return 'USD';
    }

    /**
     * Handle payment callback from UniPayment
     */
    public function handlePaymentCallback(array $callbackData): array
    {
        try {
            Log::info('UniPayment callback received', $callbackData);

            $invoiceId = $callbackData['invoice_id'] ?? null;
            $status = $callbackData['status'] ?? null;
            $orderId = $callbackData['order_id'] ?? null;

            if (!$invoiceId || !$status || !$orderId) {
                throw new Exception('Missing required callback data: invoice_id, status, or order_id');
            }

            // Get the latest payment status from API
            $paymentStatus = $this->getPaymentStatus($invoiceId);

            return [
                'success' => $this->isPaymentSuccessful($paymentStatus['status']),
                'invoice_id' => $invoiceId,
                'status' => $paymentStatus['status'],
                'order_id' => $orderId,
                'amount' => $paymentStatus['price_amount'] ?? null,
                'currency' => $paymentStatus['price_currency'] ?? null,
                'verified' => true,
                'raw_callback_data' => $callbackData,
                'payment_data' => $paymentStatus
            ];
        } catch (Exception $e) {
            Log::error('UniPayment callback error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'verified' => false,
                'raw_callback_data' => $callbackData
            ];
        }
    }

    /**
     * Handle webhook notification from UniPayment
     */
    public function handleWebhookNotification(string $payload, string $signature): array
    {
        try {
            // First validate the payload format
            $webhookData = json_decode($payload, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('UniPayment webhook invalid JSON payload', [
                    'json_error' => json_last_error_msg(),
                    'payload_length' => strlen($payload)
                ]);

                return [
                    'success' => false,
                    'error' => 'Invalid JSON payload: ' . json_last_error_msg(),
                    'verified' => false,
                    'http_status' => 400
                ];
            }

            // Validate webhook signature if secret is configured
            $signatureValid = $this->validateWebhookSignature($payload, $signature);
            if (!$signatureValid['valid']) {
                Log::warning('UniPayment webhook signature validation failed', [
                    'signature_present' => !empty($signature),
                    'error' => $signatureValid['error'],
                    'invoice_id' => $webhookData['invoice_id'] ?? 'unknown'
                ]);

                return [
                    'success' => false,
                    'error' => $signatureValid['error'],
                    'verified' => false,
                    'http_status' => 401
                ];
            }

            Log::info('UniPayment webhook received and verified', [
                'invoice_id' => $webhookData['invoice_id'] ?? 'unknown',
                'status' => $webhookData['status'] ?? 'unknown',
                'signature_verified' => $signatureValid['verified']
            ]);

            $invoiceId = $webhookData['invoice_id'] ?? null;
            $status = $webhookData['status'] ?? null;

            if (!$invoiceId) {
                Log::warning('UniPayment webhook missing invoice_id', $webhookData);

                return [
                    'success' => false,
                    'error' => 'Missing invoice_id in webhook data',
                    'verified' => $signatureValid['verified'],
                    'http_status' => 400
                ];
            }

            // Check for duplicate webhook processing (idempotency)
            if ($this->isWebhookAlreadyProcessed($invoiceId, $status)) {
                Log::info('UniPayment webhook duplicate detected, returning success', [
                    'invoice_id' => $invoiceId,
                    'status' => $status
                ]);

                return [
                    'success' => true,
                    'verified' => $signatureValid['verified'],
                    'invoice_id' => $invoiceId,
                    'status' => $status,
                    'duplicate' => true,
                    'http_status' => 200
                ];
            }

            // Get the latest payment status from API to verify webhook data
            $paymentStatus = $this->getPaymentStatus($invoiceId);

            $result = [
                'success' => $this->isPaymentSuccessful($paymentStatus['status']),
                'verified' => $signatureValid['verified'],
                'invoice_id' => $invoiceId,
                'status' => $paymentStatus['status'],
                'order_id' => $paymentStatus['order_id'] ?? null,
                'amount' => $paymentStatus['price_amount'] ?? null,
                'currency' => $paymentStatus['price_currency'] ?? null,
                'raw_webhook_data' => $webhookData,
                'payment_data' => $paymentStatus,
                'http_status' => 200
            ];

            Log::info('UniPayment webhook processed successfully', [
                'invoice_id' => $invoiceId,
                'status' => $paymentStatus['status'],
                'success' => $result['success']
            ]);

            return $result;
        } catch (Exception $e) {
            Log::error('UniPayment webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload_length' => strlen($payload)
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'verified' => false,
                'http_status' => 500
            ];
        }
    }

    /**
     * Validate webhook signature using configured webhook secret
     */
    protected function validateWebhookSignature(string $payload, string $signature): array
    {
        try {
            $dbSettings = \App\Models\UniPaymentSetting::first();

            // If no webhook secret is configured, skip signature validation
            if (!$dbSettings || !$dbSettings->webhook_secret) {
                Log::info('UniPayment webhook signature validation skipped - no secret configured');

                return [
                    'valid' => true,
                    'verified' => false,
                    'error' => null
                ];
            }

            // If no signature provided but secret is configured, reject
            if (empty($signature)) {
                $this->logSecurityEvent('Webhook signature missing but secret configured', [
                    'expected_signature' => true,
                    'received_signature' => false
                ]);

                return [
                    'valid' => false,
                    'verified' => false,
                    'error' => 'Webhook signature required but not provided'
                ];
            }

            // Validate signature format (should be hex string)
            if (!ctype_xdigit(str_replace(['sha256=', 'sha1='], '', $signature))) {
                $this->logSecurityEvent('Webhook signature invalid format', [
                    'signature_format' => substr($signature, 0, 20) . '...',
                    'signature_length' => strlen($signature)
                ]);

                return [
                    'valid' => false,
                    'verified' => false,
                    'error' => 'Invalid signature format'
                ];
            }

            // Calculate expected signature
            $expectedSignature = $this->calculateWebhookSignature($payload, $dbSettings->webhook_secret);

            // Compare signatures using timing-safe comparison
            $signatureMatch = hash_equals($expectedSignature, $signature);

            if (!$signatureMatch) {
                $this->logSecurityEvent('Webhook signature verification failed', [
                    'expected_prefix' => substr($expectedSignature, 0, 20) . '...',
                    'received_prefix' => substr($signature, 0, 20) . '...',
                    'payload_length' => strlen($payload)
                ]);

                return [
                    'valid' => false,
                    'verified' => false,
                    'error' => 'Signature verification failed'
                ];
            }

            Log::info('UniPayment webhook signature validated successfully');

            return [
                'valid' => true,
                'verified' => true,
                'error' => null
            ];
        } catch (Exception $e) {
            Log::error('UniPayment webhook signature validation error', [
                'error' => $e->getMessage()
            ]);

            return [
                'valid' => false,
                'verified' => false,
                'error' => 'Signature validation error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate webhook signature using HMAC-SHA256
     */
    protected function calculateWebhookSignature(string $payload, string $secret): string
    {
        // UniPayment typically uses HMAC-SHA256 for webhook signatures
        $hash = hash_hmac('sha256', $payload, $secret);
        return 'sha256=' . $hash;
    }

    /**
     * Check if payment status indicates successful payment
     */
    protected function isPaymentSuccessful(?string $status): bool
    {
        if (!$status) {
            return false;
        }

        $successStatuses = ['confirmed', 'complete', 'paid', 'success', 'Confirmed', 'Complete', 'Paid', 'Success'];
        return in_array($status, $successStatuses);
    }

    /**
     * Check if webhook has already been processed (idempotency check)
     */
    protected function isWebhookAlreadyProcessed(string $invoiceId, string $status): bool
    {
        try {
            // Check if we have a payment transaction with this invoice_id and status
            $existingTransaction = \App\Models\PaymentTransaction::where('invoice_id', $invoiceId)
                ->where('status', $status)
                ->where('updated_at', '>', now()->subMinutes(5)) // Only check recent transactions
                ->first();

            if ($existingTransaction) {
                Log::info('UniPayment webhook already processed (idempotency check)', [
                    'invoice_id' => $invoiceId,
                    'status' => $status,
                    'transaction_id' => $existingTransaction->id,
                    'last_updated' => $existingTransaction->updated_at
                ]);
                return true;
            }

            return false;
        } catch (Exception $e) {
            Log::warning('UniPayment webhook idempotency check failed', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage()
            ]);
            // If check fails, allow processing to continue
            return false;
        }
    }

    /**
     * Log security event for webhook authentication failures
     */
    protected function logSecurityEvent(string $event, array $context = []): void
    {
        $securityContext = array_merge([
            'event_type' => 'webhook_security',
            'service' => 'unipayment',
            'timestamp' => now()->toISOString(),
            'severity' => 'warning'
        ], $context);

        Log::channel('security')->warning($event, $securityContext);

        // Also log to main log for immediate visibility
        Log::warning('UniPayment Security Event: ' . $event, $securityContext);
    }
}
