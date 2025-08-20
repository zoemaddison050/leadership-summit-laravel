<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class WebhookUrlGenerator
{
    /**
     * Generate UniPayment webhook URL based on environment
     */
    public function generateUniPaymentWebhookUrl(): string
    {
        $environment = $this->detectEnvironment();

        switch ($environment) {
            case 'development':
                return $this->generateDevelopmentWebhookUrl();
            case 'production':
                return $this->generateProductionWebhookUrl();
            case 'testing':
                return $this->generateTestingWebhookUrl();
            default:
                return $this->generateFallbackWebhookUrl();
        }
    }

    /**
     * Detect current environment for webhook URL generation
     */
    public function detectEnvironment(): string
    {
        $env = config('app.env');

        if ($env === 'testing') {
            return 'testing';
        }

        if (in_array($env, ['local', 'development'])) {
            return 'development';
        }

        if ($env === 'production') {
            return 'production';
        }

        return 'unknown';
    }

    /**
     * Generate webhook URL for development environment
     */
    protected function generateDevelopmentWebhookUrl(): string
    {
        // Check for ngrok tunnel first
        $ngrokUrl = $this->detectNgrokUrl();
        if ($ngrokUrl) {
            return $ngrokUrl . '/payment/unipayment/webhook';
        }

        // Check for other tunnel services
        $tunnelUrl = $this->detectTunnelUrl();
        if ($tunnelUrl) {
            return $tunnelUrl . '/payment/unipayment/webhook';
        }

        // Fallback to APP_URL if configured
        $appUrl = config('app.url');
        if ($appUrl && $appUrl !== 'http://localhost') {
            return rtrim($appUrl, '/') . '/payment/unipayment/webhook';
        }

        // Log warning about webhook accessibility
        Log::warning('Development webhook URL may not be accessible externally', [
            'app_url' => $appUrl,
            'suggestion' => 'Consider using ngrok or similar tunneling service'
        ]);

        return 'http://localhost:8000/payment/unipayment/webhook';
    }

    /**
     * Generate webhook URL for production environment
     */
    protected function generateProductionWebhookUrl(): string
    {
        $appUrl = config('app.url');

        if (!$appUrl) {
            throw new \RuntimeException('APP_URL must be configured in production environment');
        }

        return rtrim($appUrl, '/') . '/payment/unipayment/webhook';
    }

    /**
     * Generate webhook URL for testing environment
     */
    protected function generateTestingWebhookUrl(): string
    {
        return 'https://test.example.com/payment/unipayment/webhook';
    }

    /**
     * Generate fallback webhook URL
     */
    protected function generateFallbackWebhookUrl(): string
    {
        $appUrl = config('app.url', 'http://localhost:8000');
        return rtrim($appUrl, '/') . '/payment/unipayment/webhook';
    }

    /**
     * Detect ngrok tunnel URL
     */
    protected function detectNgrokUrl(): ?string
    {
        try {
            // Try to get ngrok tunnel info from local API
            $response = Http::timeout(2)->get('http://127.0.0.1:4040/api/tunnels');

            if ($response->successful()) {
                $tunnels = $response->json('tunnels', []);

                foreach ($tunnels as $tunnel) {
                    if (isset($tunnel['public_url']) && str_contains($tunnel['public_url'], 'https://')) {
                        return $tunnel['public_url'];
                    }
                }
            }
        } catch (\Exception $e) {
            // Ngrok not running or not accessible
            Log::debug('Could not detect ngrok tunnel', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Detect other tunnel service URLs (localtunnel, etc.)
     */
    protected function detectTunnelUrl(): ?string
    {
        // Check environment variables for tunnel URLs
        $tunnelUrl = env('TUNNEL_URL');
        if ($tunnelUrl) {
            return rtrim($tunnelUrl, '/');
        }

        // Could add detection for other tunnel services here
        return null;
    }

    /**
     * Test if webhook URL is accessible from external services
     */
    public function isWebhookAccessible(string $url): bool
    {
        try {
            // Test with a HEAD request to avoid triggering webhook logic
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'WebhookUrlGenerator/1.0 (Accessibility Test)'
                ])
                ->head($url);

            // Consider 200, 405 (Method Not Allowed), or 404 as accessible
            // 405 means the endpoint exists but doesn't accept HEAD requests
            // 404 might mean the route exists but requires POST
            return in_array($response->status(), [200, 404, 405]);
        } catch (\Exception $e) {
            Log::warning('Webhook URL accessibility test failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Validate webhook URL format and accessibility
     */
    public function validateWebhookUrl(string $url): array
    {
        $result = [
            'valid' => false,
            'accessible' => false,
            'errors' => [],
            'warnings' => []
        ];

        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $result['errors'][] = 'Invalid URL format';
            return $result;
        }

        // Check if HTTPS in production
        if (config('app.env') === 'production' && !str_starts_with($url, 'https://')) {
            $result['errors'][] = 'HTTPS required in production environment';
            return $result;
        }

        // Check if localhost in production
        if (config('app.env') === 'production' && str_contains($url, 'localhost')) {
            $result['errors'][] = 'Localhost URLs not accessible in production';
            return $result;
        }

        $result['valid'] = true;

        // Test accessibility
        $result['accessible'] = $this->isWebhookAccessible($url);

        if (!$result['accessible']) {
            $result['warnings'][] = 'Webhook URL may not be accessible from external services';
        }

        return $result;
    }

    /**
     * Get webhook URL with validation
     */
    public function getValidatedWebhookUrl(): array
    {
        try {
            $url = $this->generateUniPaymentWebhookUrl();
            $validation = $this->validateWebhookUrl($url);

            return [
                'url' => $url,
                'environment' => $this->detectEnvironment(),
                'validation' => $validation
            ];
        } catch (\Exception $e) {
            return [
                'url' => null,
                'environment' => $this->detectEnvironment(),
                'validation' => [
                    'valid' => false,
                    'accessible' => false,
                    'errors' => ['Failed to generate webhook URL: ' . $e->getMessage()],
                    'warnings' => []
                ]
            ];
        }
    }

    /**
     * Get webhook configuration recommendations
     */
    public function getWebhookRecommendations(): array
    {
        $environment = $this->detectEnvironment();
        $recommendations = [];

        switch ($environment) {
            case 'development':
                if (!$this->detectNgrokUrl() && !$this->detectTunnelUrl()) {
                    $recommendations[] = [
                        'type' => 'setup',
                        'message' => 'Install and run ngrok for webhook testing: npm install -g ngrok && ngrok http 8000'
                    ];
                }
                break;

            case 'production':
                $appUrl = config('app.url');
                if (!$appUrl) {
                    $recommendations[] = [
                        'type' => 'configuration',
                        'message' => 'Set APP_URL in production environment configuration'
                    ];
                }
                if ($appUrl && !str_starts_with($appUrl, 'https://')) {
                    $recommendations[] = [
                        'type' => 'security',
                        'message' => 'Use HTTPS for webhook URLs in production'
                    ];
                }
                break;
        }

        return $recommendations;
    }
}
