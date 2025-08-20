<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;

class UniPaymentCurlService
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
                'client_id' => $dbSettings->app_id,
                'client_secret' => $dbSettings->api_key,
                'environment' => $dbSettings->environment ?? 'sandbox',
            ];
        }
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
            // Get access token
            $accessToken = $this->getAccessToken();

            // Create payment
            $baseUrl = $this->config['environment'] === 'production'
                ? 'https://api.unipayment.io'
                : 'https://sandbox-api.unipayment.io';

            $payload = [
                'price_amount' => $amount,
                'price_currency' => $currency,
                'order_id' => $orderId,
                'title' => $title,
                'description' => $description,
                'notify_url' => $notifyUrl,
                'redirect_url' => $redirectUrl,
                'lang' => 'en'
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $baseUrl . '/v1.0/invoices',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false, // For testing
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new Exception('cURL Error: ' . $error);
            }

            if ($httpCode !== 200) {
                throw new Exception('HTTP Error ' . $httpCode . ': ' . $response);
            }

            $data = json_decode($response, true);

            if (!$data || !isset($data['data'])) {
                throw new Exception('Invalid response format: ' . $response);
            }

            Log::info('UniPayment cURL Success', $data);

            return $data;
        } catch (Exception $e) {
            Log::error('UniPayment cURL Error: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function getAccessToken(): string
    {
        $baseUrl = $this->config['environment'] === 'production'
            ? 'https://api.unipayment.io'
            : 'https://sandbox-api.unipayment.io';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $baseUrl . '/connect/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'client_credentials',
                'client_id' => $this->config['client_id'],
                'client_secret' => $this->config['client_secret'],
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('Token cURL Error: ' . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception('Token HTTP Error ' . $httpCode . ': ' . $response);
        }

        $data = json_decode($response, true);

        if (!$data || !isset($data['access_token'])) {
            throw new Exception('Invalid token response: ' . $response);
        }

        return $data['access_token'];
    }
}
