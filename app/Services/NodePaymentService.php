<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class NodePaymentService
{
    protected string $serviceUrl;
    protected string $environment;

    public function __construct()
    {
        $this->serviceUrl = config('app.payment_service_url', 'http://localhost:3001');
        $this->environment = config('app.env') === 'production' ? 'production' : 'sandbox';
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
            $response = Http::timeout(30)->post($this->serviceUrl . '/api/payments/create', [
                'amount' => $amount,
                'currency' => $currency,
                'orderId' => $orderId,
                'title' => $title,
                'description' => $description,
                'notifyUrl' => $notifyUrl,
                'redirectUrl' => $redirectUrl,
                'environment' => $this->environment
            ]);

            if (!$response->successful()) {
                throw new Exception('Payment service error: ' . $response->body());
            }

            $data = $response->json();

            if (!$data['success']) {
                throw new Exception('Payment creation failed: ' . ($data['error'] ?? 'Unknown error'));
            }

            Log::info('Node.js payment service success', $data);

            return [
                'invoice_id' => $data['data']['invoice_id'],
                'checkout_url' => $data['data']['checkout_url'],
                'amount' => $data['data']['price_amount'],
                'currency' => $data['data']['price_currency'],
                'status' => $data['data']['status'],
                'order_id' => $data['data']['order_id']
            ];
        } catch (Exception $e) {
            Log::error('Node.js payment service error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getPaymentStatus(string $invoiceId): array
    {
        try {
            $response = Http::timeout(30)->get($this->serviceUrl . '/api/payments/' . $invoiceId, [
                'environment' => $this->environment
            ]);

            if (!$response->successful()) {
                throw new Exception('Payment status service error: ' . $response->body());
            }

            $data = $response->json();

            if (!$data['success']) {
                throw new Exception('Payment status failed: ' . ($data['error'] ?? 'Unknown error'));
            }

            return $data['data'];
        } catch (Exception $e) {
            Log::error('Node.js payment status error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function isServiceHealthy(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->serviceUrl . '/health');
            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }
}
