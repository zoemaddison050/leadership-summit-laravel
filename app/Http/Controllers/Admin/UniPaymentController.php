<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UniPaymentSetting;
use App\Services\UniPaymentService;
use App\Services\WebhookTestingService;
use App\Services\WebhookMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UniPaymentController extends Controller
{
    protected $uniPaymentService;
    protected $webhookTestingService;
    protected $webhookMonitoringService;

    public function __construct(
        UniPaymentService $uniPaymentService,
        WebhookTestingService $webhookTestingService,
        WebhookMonitoringService $webhookMonitoringService
    ) {
        $this->middleware(['auth', 'role:admin']);
        $this->uniPaymentService = $uniPaymentService;
        $this->webhookTestingService = $webhookTestingService;
        $this->webhookMonitoringService = $webhookMonitoringService;
    }

    /**
     * Display UniPayment settings form
     */
    public function index()
    {
        $settings = UniPaymentSetting::first();

        return view('admin.unipayment.index', compact('settings'));
    }

    /**
     * Update UniPayment settings
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'app_id' => 'required|string|max:255',
            'api_key' => 'required|string|max:500',
            'environment' => 'required|in:sandbox,production',
            'webhook_secret' => 'nullable|string|max:255',
            'webhook_url' => 'nullable|url|max:500',
            'webhook_enabled' => 'boolean',
            'processing_fee_percentage' => 'nullable|numeric|min:0|max:100',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_amount' => 'nullable|numeric|min:0',
            'supported_currencies' => 'nullable|string',
            'is_enabled' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $settings = UniPaymentSetting::first();

            if (!$settings) {
                $settings = new UniPaymentSetting();
            }

            // Process supported currencies
            $supportedCurrencies = [];
            if ($request->filled('supported_currencies')) {
                $currencies = explode(',', $request->supported_currencies);
                $supportedCurrencies = array_map('trim', $currencies);
                $supportedCurrencies = array_filter($supportedCurrencies);
            }

            $settings->fill([
                'app_id' => $request->app_id,
                'api_key' => $request->api_key,
                'environment' => $request->environment,
                'webhook_secret' => $request->webhook_secret,
                'webhook_url' => $request->webhook_url,
                'webhook_enabled' => $request->boolean('webhook_enabled', true),
                'processing_fee_percentage' => $request->processing_fee_percentage ?? 0,
                'minimum_amount' => $request->minimum_amount ?? 1.00,
                'maximum_amount' => $request->maximum_amount ?? 10000.00,
                'supported_currencies' => $supportedCurrencies,
                'is_enabled' => $request->boolean('is_enabled', false)
            ]);

            $settings->save();

            return redirect()->back()->with('success', 'UniPayment settings updated successfully.');
        } catch (\Exception $e) {
            Log::error('UniPayment settings update failed: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to update settings. Please try again.')
                ->withInput();
        }
    }

    /**
     * Test UniPayment API connection
     */
    public function testConnection(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'app_id' => 'required|string',
            'api_key' => 'required|string',
            'environment' => 'required|in:sandbox,production'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials provided.'
            ], 400);
        }

        try {
            // Test the connection with provided credentials
            $testResult = $this->uniPaymentService->testConnection(
                $request->app_id,
                $request->api_key,
                $request->environment
            );

            if ($testResult['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Connection successful! API credentials are valid.',
                    'data' => $testResult['data'] ?? null
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $testResult['message'] ?? 'Connection failed. Please check your credentials.'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('UniPayment connection test failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current connection status
     */
    public function connectionStatus()
    {
        try {
            $settings = UniPaymentSetting::first();

            if (!$settings || !$settings->app_id || !$settings->api_key) {
                return response()->json([
                    'success' => false,
                    'message' => 'UniPayment not configured.',
                    'configured' => false
                ]);
            }

            $testResult = $this->uniPaymentService->testConnection(
                $settings->app_id,
                $settings->api_key,
                $settings->environment
            );

            return response()->json([
                'success' => $testResult['success'],
                'message' => $testResult['message'] ?? ($testResult['success'] ? 'Connected' : 'Connection failed'),
                'configured' => true,
                'enabled' => $settings->is_enabled
            ]);
        } catch (\Exception $e) {
            Log::error('UniPayment status check failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Status check failed.',
                'configured' => false
            ]);
        }
    }

    /**
     * Validate API credentials
     */
    protected function validateCredentials($appId, $apiKey, $environment)
    {
        if (empty($appId) || empty($apiKey)) {
            return [
                'valid' => false,
                'message' => 'App ID and API Key are required.'
            ];
        }

        // Basic format validation
        if (strlen($appId) < 10) {
            return [
                'valid' => false,
                'message' => 'App ID appears to be invalid.'
            ];
        }

        if (strlen($apiKey) < 20) {
            return [
                'valid' => false,
                'message' => 'API Key appears to be invalid.'
            ];
        }

        return [
            'valid' => true,
            'message' => 'Credentials format is valid.'
        ];
    }

    /**
     * Show transaction history
     */
    public function transactions(Request $request)
    {
        $query = \App\Models\PaymentTransaction::with(['registration.event', 'registration.ticket'])
            ->where('provider', 'unipayment')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->paginate(20);

        // Get filter options
        $paymentMethods = \App\Models\PaymentTransaction::where('provider', 'unipayment')
            ->distinct()
            ->pluck('payment_method')
            ->filter()
            ->values();

        $statuses = \App\Models\PaymentTransaction::where('provider', 'unipayment')
            ->distinct()
            ->pluck('status')
            ->filter()
            ->values();

        return view('admin.unipayment.transactions', compact('transactions', 'paymentMethods', 'statuses'));
    }

    /**
     * Test webhook URL accessibility
     */
    public function testWebhook(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'webhook_url' => 'nullable|url|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook URL provided.'
            ], 400);
        }

        try {
            $settings = UniPaymentSetting::first();

            if (!$settings) {
                return response()->json([
                    'success' => false,
                    'message' => 'UniPayment settings not found.'
                ], 404);
            }

            // Use provided URL or current settings URL
            $webhookUrl = $request->webhook_url ?: $settings->getWebhookUrl();

            if (!$webhookUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'No webhook URL to test.'
                ], 400);
            }

            // Test the webhook URL
            $testResult = $settings->testWebhookUrl();

            return response()->json([
                'success' => $testResult['success'],
                'message' => $testResult['message'],
                'webhook_url' => $webhookUrl,
                'status_code' => $testResult['status_code'],
                'tested_at' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook test failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Webhook test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get webhook status and configuration
     */
    public function webhookStatus()
    {
        try {
            $settings = UniPaymentSetting::first();

            if (!$settings) {
                return response()->json([
                    'success' => false,
                    'message' => 'UniPayment settings not found.',
                    'status' => null
                ]);
            }

            $webhookStatus = $settings->getWebhookStatus();

            return response()->json([
                'success' => true,
                'message' => 'Webhook status retrieved successfully.',
                'status' => $webhookStatus
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook status check failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve webhook status.',
                'status' => null
            ]);
        }
    }

    /**
     * Generate webhook URL for current environment
     */
    public function generateWebhookUrl()
    {
        try {
            $generator = app(\App\Services\WebhookUrlGenerator::class);
            $webhookUrl = $generator->generateUniPaymentWebhookUrl();

            if (!$webhookUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to generate webhook URL for current environment.',
                    'webhook_url' => null
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Webhook URL generated successfully.',
                'webhook_url' => $webhookUrl
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook URL generation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate webhook URL: ' . $e->getMessage(),
                'webhook_url' => null
            ]);
        }
    }

    /**
     * Validate webhook URL format and accessibility
     */
    public function validateWebhookUrl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'webhook_url' => 'required|url|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook URL format.',
                'valid' => false
            ], 400);
        }

        try {
            $webhookUrl = $request->webhook_url;
            $generator = app(\App\Services\WebhookUrlGenerator::class);

            // Check if URL is accessible
            $isAccessible = $generator->isWebhookAccessible($webhookUrl);

            return response()->json([
                'success' => true,
                'message' => $isAccessible ? 'Webhook URL is valid and accessible.' : 'Webhook URL format is valid but not accessible.',
                'valid' => true,
                'accessible' => $isAccessible,
                'webhook_url' => $webhookUrl
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook URL validation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Webhook URL validation failed: ' . $e->getMessage(),
                'valid' => false,
                'accessible' => false
            ]);
        }
    }

    /**
     * Run comprehensive webhook diagnostics
     */
    public function webhookDiagnostics()
    {
        try {
            $diagnostics = $this->webhookTestingService->runDiagnostics();

            return response()->json([
                'success' => true,
                'message' => 'Webhook diagnostics completed successfully.',
                'diagnostics' => $diagnostics
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook diagnostics failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Webhook diagnostics failed: ' . $e->getMessage(),
                'diagnostics' => null
            ], 500);
        }
    }

    /**
     * Test webhook with sample payload
     */
    public function testWebhookPayload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'webhook_url' => 'nullable|url|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook URL provided.'
            ], 400);
        }

        try {
            $webhookUrl = $request->webhook_url;
            $testResult = $this->webhookTestingService->testWebhookWithPayload($webhookUrl);

            return response()->json([
                'success' => $testResult['success'],
                'message' => $testResult['success'] ? 'Webhook payload test completed successfully.' : 'Webhook payload test failed.',
                'test_result' => $testResult
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook payload test failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Webhook payload test failed: ' . $e->getMessage(),
                'test_result' => null
            ], 500);
        }
    }

    /**
     * Get webhook accessibility test results
     */
    public function testWebhookAccessibility(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'webhook_url' => 'nullable|url|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook URL provided.'
            ], 400);
        }

        try {
            $webhookUrl = $request->webhook_url;
            $testResult = $this->webhookTestingService->testWebhookAccessibility($webhookUrl);

            return response()->json([
                'success' => true,
                'message' => 'Webhook accessibility test completed.',
                'test_result' => $testResult
            ]);
        } catch (\Exception $e) {
            Log::error('Webhook accessibility test failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Webhook accessibility test failed: ' . $e->getMessage(),
                'test_result' => null
            ], 500);
        }
    }

    /**
     * Get webhook processing metrics
     */
    public function webhookMetrics(Request $request)
    {
        try {
            $hours = $request->get('hours', 24);
            $metrics = $this->webhookMonitoringService->getWebhookMetrics($hours);

            return response()->json([
                'success' => true,
                'message' => 'Webhook metrics retrieved successfully.',
                'metrics' => $metrics
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get webhook metrics: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve webhook metrics: ' . $e->getMessage(),
                'metrics' => null
            ], 500);
        }
    }

    /**
     * Get webhook health status
     */
    public function webhookHealth()
    {
        try {
            $health = $this->webhookMonitoringService->getHealthStatus();

            return response()->json([
                'success' => true,
                'message' => 'Webhook health status retrieved successfully.',
                'health' => $health
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get webhook health status: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve webhook health status: ' . $e->getMessage(),
                'health' => null
            ], 500);
        }
    }

    /**
     * Get webhook processing trends
     */
    public function webhookTrends(Request $request)
    {
        try {
            $days = $request->get('days', 7);
            $trends = $this->webhookMonitoringService->getProcessingTrends($days);

            return response()->json([
                'success' => true,
                'message' => 'Webhook trends retrieved successfully.',
                'trends' => $trends
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get webhook trends: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve webhook trends: ' . $e->getMessage(),
                'trends' => null
            ], 500);
        }
    }

    /**
     * Reset webhook monitoring counters
     */
    public function resetWebhookCounters()
    {
        try {
            $this->webhookMonitoringService->resetCounters();

            return response()->json([
                'success' => true,
                'message' => 'Webhook monitoring counters reset successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to reset webhook counters: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset webhook counters: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear webhook test cache
     */
    public function clearWebhookTestCache(Request $request)
    {
        try {
            $webhookUrl = $request->get('webhook_url');
            $this->webhookTestingService->clearTestCache($webhookUrl);

            return response()->json([
                'success' => true,
                'message' => 'Webhook test cache cleared successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clear webhook test cache: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear webhook test cache: ' . $e->getMessage()
            ], 500);
        }
    }
}
