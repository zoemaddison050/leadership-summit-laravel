<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\UniPaymentOfficialService;

class WebhookAuthentication
{
    protected $uniPaymentService;

    public function __construct(UniPaymentOfficialService $uniPaymentService)
    {
        $this->uniPaymentService = $uniPaymentService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Only apply webhook authentication to webhook endpoints
        if (!$request->is('payment/unipayment/webhook')) {
            return $next($request);
        }

        try {
            // Get the webhook signature from headers
            $signatureHeader = config('payment_security.webhook.signature_header', 'X-UniPayment-Signature');
            $signature = $request->header($signatureHeader, '');
            $payload = $request->getContent();

            Log::info('Webhook authentication middleware processing request', [
                'ip' => $request->ip(),
                'signature_present' => !empty($signature),
                'payload_length' => strlen($payload)
            ]);

            // Use the enhanced signature validation from UniPaymentOfficialService
            $validationResult = $this->validateWebhookSignature($payload, $signature);

            if (!$validationResult['valid']) {
                $httpStatus = $validationResult['http_status'] ?? 401;
                $errorMessage = $validationResult['error'] ?? 'Webhook authentication failed';

                Log::warning('Webhook authentication failed in middleware', [
                    'ip' => $request->ip(),
                    'error' => $errorMessage,
                    'http_status' => $httpStatus
                ]);

                return response('Unauthorized - ' . $errorMessage, $httpStatus);
            }

            Log::info('Webhook authentication successful', [
                'ip' => $request->ip(),
                'verified' => $validationResult['verified']
            ]);

            return $next($request);
        } catch (\Exception $e) {
            Log::error('Webhook authentication middleware error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'trace' => $e->getTraceAsString()
            ]);

            return response('Internal Server Error', 500);
        }
    }

    /**
     * Validate webhook signature using the same logic as UniPaymentOfficialService
     */
    protected function validateWebhookSignature(string $payload, string $signature): array
    {
        try {
            $dbSettings = \App\Models\UniPaymentSetting::first();

            // If no webhook secret is configured, skip signature validation
            if (!$dbSettings || !$dbSettings->webhook_secret) {
                Log::info('Webhook signature validation skipped - no secret configured');

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
                    'error' => 'Missing signature',
                    'http_status' => 401
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
                    'error' => 'Invalid signature',
                    'http_status' => 401
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
                    'error' => 'Invalid signature',
                    'http_status' => 401
                ];
            }

            Log::info('Webhook signature validated successfully in middleware');

            return [
                'valid' => true,
                'verified' => true,
                'error' => null
            ];
        } catch (\Exception $e) {
            Log::error('Webhook signature validation error in middleware', [
                'error' => $e->getMessage()
            ]);

            return [
                'valid' => false,
                'verified' => false,
                'error' => 'Signature validation error',
                'http_status' => 500
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
