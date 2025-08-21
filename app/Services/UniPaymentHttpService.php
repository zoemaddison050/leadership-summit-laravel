<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class UniPaymentHttpService
{
    protected array $config;

    public function __construct()
    {
        $this->loadConfiguration();
    }

    protected function loadConfiguration(): void
    {
        $dbSettings = \App\Models\UniPaymentSetting::first();

        if ($dbSettings && $dbSettings->is_enabled) {
            $this->config = [
                'app_id' => $dbSettings->app_id,
                'client_id' => $dbSettings->app_id,
                'client_secret' => $dbSettings->api_key,
                'environment' => $dbSettings->environment ?? 'sandbox',
                'webhook_secret' => $dbSettings->webhook_secret,
            ];
        } else {
            $this->config = config('unipayment');
        }
    }

    public function getAccessToken(): string
    {
        $baseUrl = $this->config['environment'] === 'production'
            ? 'https://api.unipayment.io'
            : 'https://sandbox-api.unipayment.io';

        $response = Http::asForm()->post($baseUrl . '/connect/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
        ]);

        if (!$response->successful()) {
            throw new Exception('Failed to get access token: ' . $response->body());
        }

        $data = $response->json();
        return $data['access_token'];
    }

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
            $accessToken = $this->getAccessToken();

            $baseUrl = $this->config['environment'] === 'production'
                ? 'https://api.unipayment.io'
                : 'https://sandbox-api.unipayment.io';

            // Try different parameter names - sometimes it's client_id instead of app_id
            $payload = [
                'client_id' => $this->config['client_id'], // Try client_id instead of app_id
                'price_amount' => $amount,
                'price_currency' => $currency,
                'pay_currency' => $currency,
                'order_id' => $orderId,
                'title' => $title,
                'description' => $description,
                'notify_url' => $notifyUrl,
                'redirect_url' => $redirectUrl,
                'lang' => 'en'
            ];

            if (!empty($extArgs)) {
                $payload['ext_args'] = json_encode($extArgs);
            }

            Log::info('UniPayment HTTP API Request', [
                'url' => $baseUrl . '/v1.0/invoices',
                'payload' => $payload,
                'environment' => $this->config['environment']
            ]);

            $response = Http::withToken($accessToken)
                ->post($baseUrl . '/v1.0/invoices', $payload);

            Log::info('UniPayment HTTP API Response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            if (!$response->successful()) {
                throw new Exception('API Error: ' . $response->body());
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('UniPayment HTTP Service Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
