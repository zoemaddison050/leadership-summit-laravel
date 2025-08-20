<?php

namespace App\Http\Controllers;

use App\Http\Requests\CardPaymentRequest;
use App\Http\Requests\PaymentRequest;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\RegistrationLock;
use App\Services\PaymentService;
use App\Services\WebhookUrlGenerator;
use App\Services\WebhookMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected $paymentService;
    protected $webhookUrlGenerator;
    protected $webhookMonitoringService;

    public function __construct(
        PaymentService $paymentService,
        WebhookUrlGenerator $webhookUrlGenerator,
        WebhookMonitoringService $webhookMonitoringService
    ) {
        $this->paymentService = $paymentService;
        $this->webhookUrlGenerator = $webhookUrlGenerator;
        $this->webhookMonitoringService = $webhookMonitoringService;
    }

    /**
     * Process card payment.
     */
    public function processCard(Request $request, Registration $registration)
    {
        // Ensure user can only pay for their own registration
        if ($registration->user_id !== Auth::id()) {
            abort(403);
        }

        // Validate card data
        $validator = Validator::make($request->all(), [
            'card_number' => 'required|string|min:13|max:19',
            'card_name' => 'required|string|max:255',
            'expiry' => 'required|string|regex:/^(0[1-9]|1[0-2])\/([0-9]{2})$/',
            'cvc' => 'required|string|min:3|max:4',
            'zip_code' => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if registration already has a completed payment
        if ($registration->payment_status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'This registration has already been paid for.',
            ], 400);
        }

        // Create payment record
        $payment = $this->paymentService->createPayment($registration, Payment::METHOD_CARD);

        // Process payment
        $result = $this->paymentService->processCardPayment($payment, [
            'card_number' => $request->card_number,
            'card_name' => $request->card_name,
            'expiry' => $request->expiry,
            'cvc' => $request->cvc,
            'zip_code' => $request->zip_code,
        ]);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'transaction_id' => $result['transaction_id'],
                'redirect_url' => route('registrations.confirmation', $registration),
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }
    }

    /**
     * Show payment method selection page for event registration.
     */
    public function showPaymentSelection(Event $event)
    {
        try {
            Log::info('Payment selection page accessed', [
                'event_id' => $event->id,
                'has_registration_data' => session()->has('registration_data')
            ]);

            // Check for direct registration data
            $registrationData = session('registration_data');

            if (!$registrationData) {
                Log::warning('Payment selection accessed without registration data', [
                    'event_id' => $event->id
                ]);

                return redirect()->route('events.show', $event)
                    ->with('error', 'No registration data found. Please start your registration again.');
            }

            // Validate registration data exists and is not expired
            if (!is_array($registrationData) || !isset($registrationData['expires_at'])) {
                Log::warning('Invalid registration data structure', [
                    'event_id' => $event->id,
                    'data_type' => gettype($registrationData)
                ]);

                session()->forget('registration_data');
                return redirect()->route('events.show', $event)
                    ->with('error', 'Invalid registration data. Please start your registration again.');
            }

            // Check if registration data has expired
            if (now()->gt($registrationData['expires_at'])) {
                Log::warning('Registration data expired', [
                    'event_id' => $event->id,
                    'expired_at' => $registrationData['expires_at'],
                    'current_time' => now(),
                    'minutes_expired' => now()->diffInMinutes($registrationData['expires_at'])
                ]);

                session()->forget('registration_data');
                return redirect()->route('events.show', $event)
                    ->with('error', 'Your registration session has expired (30 minutes). Please start your registration again. Your information was not saved for security reasons.');
            }

            // Validate event ID matches
            if (!isset($registrationData['event_id']) || $registrationData['event_id'] != $event->id) {
                Log::warning('Event ID mismatch in registration data', [
                    'session_event_id' => $registrationData['event_id'] ?? 'missing',
                    'current_event_id' => $event->id
                ]);

                session()->forget('registration_data');
                return redirect()->route('events.show', $event)
                    ->with('error', 'Registration data is for a different event. Please start your registration again.');
            }

            // Validate required registration data fields
            $requiredFields = ['attendee_name', 'attendee_email', 'attendee_phone', 'total_amount'];
            foreach ($requiredFields as $field) {
                if (!isset($registrationData[$field])) {
                    Log::warning('Missing required registration data field', [
                        'event_id' => $event->id,
                        'missing_field' => $field
                    ]);

                    session()->forget('registration_data');
                    return redirect()->route('events.show', $event)
                        ->with('error', 'Incomplete registration data. Please start your registration again.');
                }
            }

            // Check payment method availability
            $paymentOptions = $this->getAvailablePaymentOptions();

            Log::info('Payment selection page loaded successfully', [
                'event_id' => $event->id,
                'email' => $registrationData['attendee_email'],
                'total_amount' => $registrationData['total_amount'],
                'available_options' => array_keys($paymentOptions)
            ]);

            return view('payments.selection', compact('event', 'registrationData', 'paymentOptions'));
        } catch (\Exception $e) {
            Log::error('Error displaying payment selection page', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Clear potentially corrupted session data
            session()->forget('registration_data');

            return redirect()->route('events.show', $event)
                ->with('error', 'Unable to load the payment page at this time. Please start your registration again or contact support if the problem persists.');
        }
    }

    /**
     * Get available payment options based on configuration.
     */
    protected function getAvailablePaymentOptions(): array
    {
        $options = [];

        // Always include crypto payment option
        $options['crypto'] = [
            'name' => 'Pay with Cryptocurrency',
            'description' => 'Pay with Bitcoin, Ethereum, or USDT',
            'icon' => 'crypto',
            'processing_time' => 'Manual confirmation required',
            'fee_info' => 'No processing fees',
            'available' => true
        ];

        // Check if UniPayment is configured for card payments
        try {
            $uniPaymentService = app(\App\Services\UniPaymentOfficialService::class);

            if ($uniPaymentService->isAvailableForCard()) {
                $options['card'] = [
                    'name' => 'Pay with Card',
                    'description' => 'Pay with Visa, Mastercard, or American Express',
                    'icon' => 'card',
                    'processing_time' => 'Instant confirmation',
                    'fee_info' => $uniPaymentService->getProcessingFeePercentage() . '% processing fee',
                    'available' => true,
                    'min_amount' => $uniPaymentService->getMinimumAmount(),
                    'max_amount' => $uniPaymentService->getMaximumAmount()
                ];
            } else {
                $options['card'] = [
                    'name' => 'Pay with Card',
                    'description' => 'Card payments temporarily unavailable',
                    'icon' => 'card',
                    'processing_time' => 'Unavailable',
                    'fee_info' => 'Service not configured',
                    'available' => false
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to check UniPayment configuration', [
                'error' => $e->getMessage()
            ]);

            $options['card'] = [
                'name' => 'Pay with Card',
                'description' => 'Card payments temporarily unavailable',
                'icon' => 'card',
                'processing_time' => 'Unavailable',
                'fee_info' => 'Service temporarily unavailable',
                'available' => false
            ];
        }

        return $options;
    }

    /**
     * Switch payment method while preserving registration data.
     */
    public function switchPaymentMethod(Request $request, Event $event)
    {
        try {
            Log::info('Payment method switch requested', [
                'event_id' => $event->id,
                'requested_method' => $request->input('method'),
                'has_registration_data' => session()->has('registration_data')
            ]);

            // Validate the requested payment method
            $validator = Validator::make($request->all(), [
                'method' => 'required|in:card,crypto'
            ]);

            if ($validator->fails()) {
                return redirect()->route('payment.selection', $event)
                    ->with('error', 'Invalid payment method selected.');
            }

            $requestedMethod = $request->input('method');

            // Check for registration data
            $registrationData = session('registration_data');

            if (!$registrationData) {
                Log::warning('Payment method switch attempted without registration data', [
                    'event_id' => $event->id,
                    'requested_method' => $requestedMethod
                ]);

                return redirect()->route('events.show', $event)
                    ->with('error', 'No registration data found. Please start your registration again.');
            }

            // Validate registration data is not expired
            if (!is_array($registrationData) || !isset($registrationData['expires_at'])) {
                session()->forget('registration_data');
                return redirect()->route('events.show', $event)
                    ->with('error', 'Invalid registration data. Please start your registration again.');
            }

            if (now()->gt($registrationData['expires_at'])) {
                session()->forget('registration_data');
                return redirect()->route('events.show', $event)
                    ->with('error', 'Your registration session has expired. Please start your registration again.');
            }

            // Check payment method availability
            $paymentOptions = $this->getAvailablePaymentOptions();

            if (!isset($paymentOptions[$requestedMethod]) || !$paymentOptions[$requestedMethod]['available']) {
                Log::warning('Unavailable payment method requested', [
                    'event_id' => $event->id,
                    'requested_method' => $requestedMethod,
                    'available_methods' => array_keys(array_filter($paymentOptions, fn($option) => $option['available']))
                ]);

                return redirect()->route('payment.selection', $event)
                    ->with('error', 'The requested payment method is currently unavailable. Please choose an alternative method.');
            }

            // For card payments, validate amount limits
            if ($requestedMethod === 'card') {
                $amount = (float) $registrationData['total_amount'];
                $minAmount = $paymentOptions['card']['min_amount'] ?? 0;
                $maxAmount = $paymentOptions['card']['max_amount'] ?? PHP_FLOAT_MAX;

                if ($amount < $minAmount) {
                    return redirect()->route('payment.selection', $event)
                        ->with('error', "Card payment requires a minimum amount of $" . number_format($minAmount, 2) . ". Please use cryptocurrency payment for smaller amounts.");
                }

                if ($amount > $maxAmount) {
                    return redirect()->route('payment.selection', $event)
                        ->with('error', "Card payment has a maximum limit of $" . number_format($maxAmount, 2) . ". Please use cryptocurrency payment for larger amounts.");
                }
            }

            // Update registration data with selected payment method preference
            $registrationData['preferred_payment_method'] = $requestedMethod;
            $registrationData['payment_method_switched_at'] = now();
            session(['registration_data' => $registrationData]);

            Log::info('Payment method switched successfully', [
                'event_id' => $event->id,
                'new_method' => $requestedMethod,
                'email' => $registrationData['attendee_email'] ?? 'unknown'
            ]);

            // Redirect to the appropriate payment page
            if ($requestedMethod === 'card') {
                return redirect()->route('payment.selection', $event)
                    ->with('success', 'Switched to card payment. You can now proceed with your card payment.');
            } else {
                return redirect()->route('payment.crypto', $event)
                    ->with('success', 'Switched to cryptocurrency payment. Please select your preferred cryptocurrency.');
            }
        } catch (\Exception $e) {
            Log::error('Error switching payment method', [
                'event_id' => $event->id,
                'requested_method' => $request->input('method'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('payment.selection', $event)
                ->with('error', 'Unable to switch payment method. Please try again or contact support.');
        }
    }

    /**
     * Check payment method availability and provide fallback options.
     */
    public function checkPaymentAvailability(Event $event)
    {
        try {
            $paymentOptions = $this->getAvailablePaymentOptions();
            $availableMethods = array_filter($paymentOptions, fn($option) => $option['available']);

            // Check if registration data exists
            $registrationData = session('registration_data');
            $hasValidRegistration = $registrationData &&
                is_array($registrationData) &&
                isset($registrationData['expires_at']) &&
                now()->lte($registrationData['expires_at']);

            return response()->json([
                'success' => true,
                'available_methods' => array_keys($availableMethods),
                'payment_options' => $paymentOptions,
                'has_valid_registration' => $hasValidRegistration,
                'registration_expires_at' => $hasValidRegistration ? $registrationData['expires_at'] : null
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking payment availability', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to check payment availability'
            ], 500);
        }
    }

    /**
     * Show crypto payment page for event registration.
     */
    public function showCryptoPayment(Event $event)
    {
        try {
            Log::info('Crypto payment page accessed', [
                'event_id' => $event->id,
                'has_registration_data' => session()->has('registration_data'),
                'has_ticket_selection' => session()->has('ticket_selection')
            ]);

            // Check for direct registration data (new flow)
            $registrationData = session('registration_data');

            if ($registrationData) {
                // Validate registration data exists and is not expired
                if (!is_array($registrationData) || !isset($registrationData['expires_at'])) {
                    Log::warning('Invalid registration data structure', [
                        'event_id' => $event->id,
                        'data_type' => gettype($registrationData)
                    ]);

                    session()->forget('registration_data');
                    return redirect()->route('events.show', $event)
                        ->with('error', 'Invalid registration data. Please start your registration again.');
                }

                // Check if registration data has expired
                if (now()->gt($registrationData['expires_at'])) {
                    Log::warning('Registration data expired', [
                        'event_id' => $event->id,
                        'expired_at' => $registrationData['expires_at'],
                        'current_time' => now(),
                        'minutes_expired' => now()->diffInMinutes($registrationData['expires_at'])
                    ]);

                    session()->forget('registration_data');
                    return redirect()->route('events.show', $event)
                        ->with('error', 'Your registration session has expired (30 minutes). Please start your registration again. Your information was not saved for security reasons.');
                }

                // Validate event ID matches
                if (!isset($registrationData['event_id']) || $registrationData['event_id'] != $event->id) {
                    Log::warning('Event ID mismatch in registration data', [
                        'session_event_id' => $registrationData['event_id'] ?? 'missing',
                        'current_event_id' => $event->id
                    ]);

                    session()->forget('registration_data');
                    return redirect()->route('events.show', $event)
                        ->with('error', 'Registration data is for a different event. Please start your registration again.');
                }

                // Validate required registration data fields
                $requiredFields = ['attendee_name', 'attendee_email', 'attendee_phone', 'total_amount'];
                foreach ($requiredFields as $field) {
                    if (!isset($registrationData[$field])) {
                        Log::warning('Missing required registration data field', [
                            'event_id' => $event->id,
                            'missing_field' => $field
                        ]);

                        session()->forget('registration_data');
                        return redirect()->route('events.show', $event)
                            ->with('error', 'Incomplete registration data. Please start your registration again.');
                    }
                }

                Log::info('Valid registration data found for payment', [
                    'event_id' => $event->id,
                    'email' => $registrationData['attendee_email'],
                    'total_amount' => $registrationData['total_amount']
                ]);

                return view('payments.crypto', compact('event', 'registrationData'));
            }

            // Fallback to old ticket selection flow for backward compatibility
            $ticketSelection = session('ticket_selection');

            if (!$ticketSelection) {
                Log::warning('No registration or ticket selection data found', [
                    'event_id' => $event->id
                ]);

                return redirect()->route('events.show', $event)
                    ->with('error', 'No registration data found. Please start your registration from the event page.');
            }

            // Validate ticket selection data
            if (!is_array($ticketSelection) || !isset($ticketSelection['expires_at'])) {
                Log::warning('Invalid ticket selection data structure', [
                    'event_id' => $event->id,
                    'data_type' => gettype($ticketSelection)
                ]);

                session()->forget('ticket_selection');
                return redirect()->route('events.show', $event)
                    ->with('error', 'Invalid ticket selection data. Please select your tickets again.');
            }

            if (now()->gt($ticketSelection['expires_at'])) {
                Log::warning('Ticket selection expired', [
                    'event_id' => $event->id,
                    'expired_at' => $ticketSelection['expires_at'],
                    'current_time' => now(),
                    'minutes_expired' => now()->diffInMinutes($ticketSelection['expires_at'])
                ]);

                session()->forget('ticket_selection');
                return redirect()->route('events.show', $event)
                    ->with('error', 'Your ticket selection has expired (30 minutes). Please select your tickets again.');
            }

            if (!isset($ticketSelection['event_id']) || $ticketSelection['event_id'] != $event->id) {
                Log::warning('Event ID mismatch in ticket selection', [
                    'session_event_id' => $ticketSelection['event_id'] ?? 'missing',
                    'current_event_id' => $event->id
                ]);

                session()->forget('ticket_selection');
                return redirect()->route('events.show', $event)
                    ->with('error', 'Ticket selection is for a different event. Please select your tickets again.');
            }

            Log::info('Valid ticket selection found for payment', [
                'event_id' => $event->id,
                'total_amount' => $ticketSelection['total_amount'] ?? 'unknown'
            ]);

            return view('payments.crypto', compact('event', 'ticketSelection'));
        } catch (\Exception $e) {
            Log::error('Error displaying crypto payment page', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Clear potentially corrupted session data
            session()->forget(['registration_data', 'ticket_selection']);

            return redirect()->route('events.show', $event)
                ->with('error', 'Unable to load the payment page at this time. Please start your registration again or contact support if the problem persists.');
        }
    }

    /**
     * Process card payment by redirecting to UniPayment checkout.
     */
    public function processCardPayment(CardPaymentRequest $request, Event $event)
    {
        // Debug logging
        Log::info('Card payment process started', [
            'event_id' => $event->id,
            'request_method' => $request->method(),
            'has_session_data' => session()->has('registration_data')
        ]);

        try {
            Log::info('Card payment processing initiated', [
                'event_id' => $event->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Validate registration data exists and is not expired
            $registrationData = session('registration_data');

            if (!$registrationData) {
                Log::warning('Card payment attempted without registration data', [
                    'event_id' => $event->id,
                    'ip_address' => $request->ip()
                ]);

                return redirect()->route('events.show', $event)
                    ->with('error', 'No registration data found. Please start your registration again.');
            }

            if (!is_array($registrationData) || !isset($registrationData['expires_at'])) {
                Log::warning('Invalid registration data structure during card payment', [
                    'event_id' => $event->id,
                    'data_type' => gettype($registrationData)
                ]);

                session()->forget('registration_data');
                return redirect()->route('events.show', $event)
                    ->with('error', 'Invalid registration data. Please start your registration again.');
            }

            if (now()->gt($registrationData['expires_at'])) {
                Log::info('Card payment attempted with expired registration data', [
                    'event_id' => $event->id,
                    'expired_at' => $registrationData['expires_at'],
                    'minutes_expired' => now()->diffInMinutes($registrationData['expires_at']),
                    'email' => $registrationData['attendee_email'] ?? 'unknown'
                ]);

                session()->forget('registration_data');
                return redirect()->route('events.show', $event)
                    ->with('error', 'Your registration session has expired (30 minutes). Please start your registration again for security reasons.');
            }

            if (!isset($registrationData['event_id']) || $registrationData['event_id'] != $event->id) {
                Log::warning('Event ID mismatch during card payment', [
                    'session_event_id' => $registrationData['event_id'] ?? 'missing',
                    'current_event_id' => $event->id
                ]);

                session()->forget('registration_data');
                return redirect()->route('events.show', $event)
                    ->with('error', 'Registration data is for a different event. Please start your registration again.');
            }

            // Validate required fields
            $requiredFields = ['attendee_name', 'attendee_email', 'attendee_phone', 'total_amount'];
            foreach ($requiredFields as $field) {
                if (!isset($registrationData[$field])) {
                    Log::warning('Missing required registration data field during card payment', [
                        'event_id' => $event->id,
                        'missing_field' => $field
                    ]);

                    session()->forget('registration_data');
                    return redirect()->route('events.show', $event)
                        ->with('error', 'Incomplete registration data. Please start your registration again.');
                }
            }

            // Initialize UniPayment service
            $uniPaymentService = app(\App\Services\UniPaymentOfficialService::class);

            if (!$uniPaymentService->isAvailableForCard()) {
                Log::error('UniPayment not available for card payment (not configured and not in demo mode)', [
                    'event_id' => $event->id,
                    'email' => $registrationData['attendee_email']
                ]);

                return redirect()->route('payment.selection', $event)
                    ->with('error', 'Card payment is temporarily unavailable. Please try cryptocurrency payment or contact support.');
            }

            // Validate payment amount
            $amount = (float) $registrationData['total_amount'];
            $minAmount = $uniPaymentService->getMinimumAmount();
            $maxAmount = $uniPaymentService->getMaximumAmount();

            if ($amount < $minAmount) {
                return redirect()->route('payment.selection', $event)
                    ->with('error', "Payment amount must be at least $" . number_format($minAmount, 2));
            }

            if ($amount > $maxAmount) {
                return redirect()->route('payment.selection', $event)
                    ->with('error', "Payment amount cannot exceed $" . number_format($maxAmount, 2));
            }

            // Generate unique order ID
            $orderId = 'REG_' . $event->id . '_' . time() . '_' . substr(md5($registrationData['attendee_email']), 0, 8);

            // Store payment session data with extended expiration for payment processing
            $paymentSessionData = array_merge($registrationData, [
                'payment_method' => 'card',
                'payment_provider' => 'unipayment',
                'order_id' => $orderId,
                'payment_initiated_at' => now(),
                'payment_expires_at' => now()->addHours(2) // Extended time for payment completion
            ]);

            // Encrypt sensitive payment session data
            $encryptedSessionData = $this->encryptPaymentSessionData($paymentSessionData);
            session(['payment_session_data' => $encryptedSessionData]);

            // Generate and validate webhook URL
            $webhookData = $this->webhookUrlGenerator->getValidatedWebhookUrl();
            $webhookUrl = $webhookData['url'];

            Log::info('Generated webhook URL for payment', [
                'event_id' => $event->id,
                'webhook_url' => $webhookUrl,
                'environment' => $webhookData['environment'],
                'validation' => $webhookData['validation']
            ]);

            // Check if webhook URL is valid
            if (!$webhookData['validation']['valid']) {
                Log::error('Invalid webhook URL generated', [
                    'event_id' => $event->id,
                    'order_id' => $orderId,
                    'errors' => $webhookData['validation']['errors']
                ]);

                return redirect()->route('payment.selection', $event)
                    ->with('error', 'Payment system configuration error. Please try cryptocurrency payment or contact support.');
            }

            // Handle webhook URL accessibility issues
            if (!$webhookData['validation']['accessible']) {
                Log::warning('Webhook URL may not be accessible', [
                    'event_id' => $event->id,
                    'order_id' => $orderId,
                    'webhook_url' => $webhookUrl,
                    'warnings' => $webhookData['validation']['warnings']
                ]);

                // In development, provide helpful suggestions
                if ($webhookData['environment'] === 'development') {
                    $recommendations = $this->webhookUrlGenerator->getWebhookRecommendations();
                    if (!empty($recommendations)) {
                        Log::info('Webhook setup recommendations', [
                            'event_id' => $event->id,
                            'recommendations' => $recommendations
                        ]);
                    }
                }

                // Store fallback flag for enhanced callback handling
                $paymentSessionData['webhook_fallback'] = true;
                $paymentSessionData['webhook_accessibility_warnings'] = $webhookData['validation']['warnings'];
                $encryptedSessionData = $this->encryptPaymentSessionData($paymentSessionData);
                session(['payment_session_data' => $encryptedSessionData]);
            }

            // Create UniPayment invoice
            Log::info('Creating UniPayment invoice', [
                'event_id' => $event->id,
                'amount' => $amount,
                'order_id' => $orderId,
                'webhook_url' => $webhookUrl
            ]);

            $invoiceResponse = $uniPaymentService->createPayment(
                $amount,
                $uniPaymentService->getDefaultCurrency(),
                $orderId,
                'Event Registration - ' . $event->name,
                'Registration for ' . $registrationData['attendee_name'] . ' - ' . $event->name,
                $webhookUrl,
                route('payment.unipayment.callback'),
                [
                    'event_id' => $event->id,
                    'attendee_email' => $registrationData['attendee_email'],
                    'attendee_name' => $registrationData['attendee_name']
                ]
            );

            Log::info('UniPayment invoice response received', [
                'event_id' => $event->id,
                'response_type' => is_array($invoiceResponse) ? 'array' : (is_object($invoiceResponse) ? get_class($invoiceResponse) : gettype($invoiceResponse)),
                'has_success_key' => is_array($invoiceResponse) && isset($invoiceResponse['success'])
            ]);

            if (!$invoiceResponse || !$invoiceResponse['success']) {
                Log::error('Failed to create UniPayment invoice', [
                    'event_id' => $event->id,
                    'order_id' => $orderId,
                    'amount' => $amount,
                    'email' => $registrationData['attendee_email'],
                    'response' => $invoiceResponse
                ]);

                return redirect()->route('payment.selection', $event)
                    ->with('error', 'Unable to initialize card payment. Please try again or use cryptocurrency payment.');
            }

            Log::info('Invoice data retrieved', [
                'event_id' => $event->id,
                'invoice_id' => $invoiceResponse['invoice_id'] ?? 'missing',
                'checkout_url' => $invoiceResponse['checkout_url'] ?? 'missing'
            ]);

            $checkoutUrl = $invoiceResponse['checkout_url'];
            $invoiceId = $invoiceResponse['invoice_id'];

            Log::info('Extracted payment details', [
                'event_id' => $event->id,
                'invoice_id' => $invoiceId,
                'checkout_url' => $checkoutUrl
            ]);

            if (!$checkoutUrl) {
                Log::error('UniPayment invoice created but no checkout URL provided', [
                    'event_id' => $event->id,
                    'order_id' => $orderId,
                    'invoice_id' => $invoiceId
                ]);

                return redirect()->route('payment.selection', $event)
                    ->with('error', 'Unable to initialize card payment checkout. Please try again or use cryptocurrency payment.');
            }

            // Get ticket ID from registration data or ticket selection session
            $ticketId = $registrationData['ticket_id'] ?? null;
            if (!$ticketId) {
                $ticketSelection = session('ticket_selection');
                $ticketId = $ticketSelection['ticket_id'] ?? null;
            }

            // If still no ticket ID, try to determine from the amount
            if (!$ticketId) {
                $ticket = \App\Models\Ticket::where('event_id', $event->id)
                    ->where('price', $amount)
                    ->first();
                $ticketId = $ticket ? $ticket->id : null;
            }

            // If still no ticket ID, use the first available ticket as fallback
            if (!$ticketId) {
                $ticket = \App\Models\Ticket::where('event_id', $event->id)->first();
                $ticketId = $ticket ? $ticket->id : null;

                Log::warning('No ticket ID found, using fallback ticket', [
                    'event_id' => $event->id,
                    'fallback_ticket_id' => $ticketId,
                    'amount' => $amount
                ]);
            }

            // Create a temporary registration first to satisfy the foreign key constraint
            $tempRegistration = \App\Models\Registration::create([
                'event_id' => $event->id,
                'attendee_name' => $registrationData['attendee_name'],
                'attendee_email' => $registrationData['attendee_email'],
                'attendee_phone' => $registrationData['attendee_phone'] ?? null,
                'ticket_id' => $ticketId,
                'payment_status' => 'pending',
                'payment_method' => 'card',
                'total_amount' => $amount,
                'transaction_id' => $invoiceId,
                'status' => 'pending_payment',
                'registration_date' => now(),
                'expires_at' => now()->addHours(2), // 2 hour expiry for payment completion
            ]);

            // Store invoice ID and registration ID in session for callback processing
            $paymentSessionData['invoice_id'] = $invoiceId;
            $paymentSessionData['temp_registration_id'] = $tempRegistration->id;
            $encryptedSessionData = $this->encryptPaymentSessionData($paymentSessionData);
            session(['payment_session_data' => $encryptedSessionData]);

            // Create payment transaction record
            $paymentTransaction = \App\Models\PaymentTransaction::create([
                'registration_id' => $tempRegistration->id,
                'provider' => 'unipayment',
                'transaction_id' => $invoiceId,
                'payment_method' => 'card',
                'amount' => $amount,
                'currency' => $uniPaymentService->getDefaultCurrency(),
                'fee' => $amount * ($uniPaymentService->getProcessingFeePercentage() / 100),
                'status' => 'pending',
                'provider_response' => [
                    'invoice_id' => $invoiceId,
                    'order_id' => $orderId,
                    'checkout_url' => $checkoutUrl,
                    'created_at' => now()->toISOString()
                ]
            ]);

            Log::info('Card payment initialized successfully', [
                'event_id' => $event->id,
                'order_id' => $orderId,
                'invoice_id' => $invoiceId,
                'transaction_id' => $paymentTransaction->id,
                'temp_registration_id' => $tempRegistration->id,
                'amount' => $amount,
                'email' => $registrationData['attendee_email'],
                'checkout_url' => $checkoutUrl
            ]);

            // Redirect to UniPayment checkout
            return redirect($checkoutUrl);
        } catch (\Exception $e) {
            Log::error('Card payment processing failed', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // Also log to error log for easier debugging
            error_log('Card payment error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

            return redirect()->route('payment.selection', $event)
                ->with('error', 'Unable to process card payment. Please try again or use cryptocurrency payment.');
        }
    }

    /**
     * Initialize cryptocurrency payment for event registration.
     */
    public function initializeCrypto(Request $request, Event $event)
    {
        $ticketSelection = session('ticket_selection');

        if (!$ticketSelection || now()->gt($ticketSelection['expires_at'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket selection has expired. Please select tickets again.',
            ], 400);
        }

        // Validate crypto currency
        $validator = Validator::make($request->all(), [
            'crypto_currency' => 'required|in:bitcoin,ethereum,litecoin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Create a temporary payment record for tracking
        $payment = Payment::create([
            'user_id' => Auth::id(),
            'event_id' => $event->id,
            'amount' => $ticketSelection['total_amount'],
            'currency' => 'USD',
            'method' => Payment::METHOD_CRYPTO,
            'status' => Payment::STATUS_PROCESSING,
            'metadata' => [
                'ticket_selection' => $ticketSelection,
                'crypto_currency' => $request->crypto_currency
            ]
        ]);

        // Initialize crypto payment
        $result = $this->paymentService->processCryptoPayment($payment, $request->crypto_currency);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'payment_id' => $payment->id,
                'crypto_address' => $result['crypto_address'],
                'crypto_amount' => $result['crypto_amount'],
                'crypto_currency' => $result['crypto_currency'],
                'qr_code' => $result['qr_code'],
                'message' => $result['message'],
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }
    }

    /**
     * Complete payment and create registrations.
     */
    public function completeCryptoPayment(Payment $payment)
    {
        if ($payment->user_id !== Auth::id()) {
            abort(403);
        }

        if ($payment->status !== Payment::STATUS_COMPLETED) {
            return redirect()->back()
                ->with('error', 'Payment has not been completed yet.');
        }

        // Create registrations from session data
        $registrationController = new \App\Http\Controllers\RegistrationController(app(\App\Services\OrderService::class));
        return $registrationController->createRegistrationsFromSession();
    }

    /**
     * Check cryptocurrency payment status.
     */
    public function checkCryptoStatus(Payment $payment)
    {
        // Ensure user can only check their own payment
        if ($payment->registration->user_id !== Auth::id()) {
            abort(403);
        }

        // Verify payment if it's still processing
        if ($payment->status === Payment::STATUS_PROCESSING) {
            $this->paymentService->verifyCryptoPayment($payment);
            $payment->refresh();
        }

        $status = $this->paymentService->getPaymentStatus($payment);

        return response()->json([
            'success' => true,
            'status' => $status,
            'is_completed' => $payment->isCompleted(),
            'redirect_url' => $payment->isCompleted() ? route('registrations.confirmation', $payment->registration) : null,
        ]);
    }

    /**
     * Get payment status.
     */
    public function status(Payment $payment)
    {
        // Ensure user can only check their own payment
        if ($payment->registration->user_id !== Auth::id()) {
            abort(403);
        }

        $status = $this->paymentService->getPaymentStatus($payment);

        return response()->json([
            'success' => true,
            'status' => $status,
        ]);
    }

    /**
     * Get cryptocurrency payment details with real-time pricing and QR code.
     */
    public function getCryptoPaymentDetails(Request $request, Event $event)
    {
        Log::info('getCryptoPaymentDetails called', [
            'event_id' => $event->id,
            'cryptocurrency' => $request->input('cryptocurrency'),
            'has_session_data' => session()->has('registration_data'),
            'request_method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'csrf_token' => $request->header('X-CSRF-TOKEN') ? 'present' : 'missing'
        ]);

        $request->validate([
            'cryptocurrency' => 'required|in:bitcoin,ethereum,usdt'
        ]);

        $registrationData = session('registration_data');

        Log::info('Registration data check', [
            'has_registration_data' => !empty($registrationData),
            'has_total_amount' => isset($registrationData['total_amount']),
            'total_amount' => $registrationData['total_amount'] ?? 'missing'
        ]);

        if (!$registrationData || !isset($registrationData['total_amount'])) {
            Log::warning('No registration data found for crypto payment details');
            return response()->json([
                'success' => false,
                'message' => 'No registration data found. Please start your registration again.'
            ], 400);
        }

        try {
            $cryptoService = app(\App\Services\CryptocurrencyService::class);

            // Convert USD amount to cryptocurrency
            $conversion = $cryptoService->convertUsdToCrypto(
                $registrationData['total_amount'],
                $request->cryptocurrency
            );

            // Get wallet address
            $walletAddress = $cryptoService->getWalletAddress($request->cryptocurrency);

            // Generate QR code
            $qrCode = $cryptoService->generatePaymentQrCode(
                $request->cryptocurrency,
                (float) $conversion['crypto_amount']
            );

            Log::info('Crypto payment details generated', [
                'event_id' => $event->id,
                'cryptocurrency' => $request->cryptocurrency,
                'usd_amount' => $registrationData['total_amount'],
                'crypto_amount' => $conversion['crypto_amount'],
                'crypto_price' => $conversion['crypto_price_usd']
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'cryptocurrency' => $request->cryptocurrency,
                    'currency_name' => $conversion['currency_name'],
                    'currency_symbol' => $conversion['currency_symbol'],
                    'currency_code' => strtoupper($request->cryptocurrency === 'usdt' ? 'USDT' : ($request->cryptocurrency === 'bitcoin' ? 'BTC' : 'ETH')),
                    'usd_amount' => $registrationData['total_amount'],
                    'crypto_amount' => $conversion['crypto_amount'],
                    'crypto_price_usd' => $conversion['crypto_price_usd'],
                    'wallet_address' => $walletAddress,
                    'qr_code' => $qrCode,
                    'formatted_amount' => $conversion['crypto_amount'] . ' ' . strtoupper($request->cryptocurrency === 'usdt' ? 'USDT' : ($request->cryptocurrency === 'bitcoin' ? 'BTC' : 'ETH'))
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate crypto payment details', [
                'event_id' => $event->id,
                'cryptocurrency' => $request->cryptocurrency,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to generate payment details. Please try again.'
            ], 500);
        }
    }

    /**
     * Handle "I Have Paid" button click for direct registration flow.
     */
    public function confirmPayment(Request $request, Event $event)
    {
        Log::info('Payment confirmation initiated', [
            'event_id' => $event->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()
        ]);

        $registrationData = session('registration_data');

        // Validate session data exists and is not expired
        if (!$registrationData) {
            Log::warning('Payment confirmation attempted without registration data', [
                'event_id' => $event->id,
                'ip_address' => $request->ip()
            ]);

            return redirect()->route('events.show', $event)
                ->with('error', 'No registration data found. Please start your registration again.');
        }

        if (!is_array($registrationData) || !isset($registrationData['expires_at'])) {
            Log::warning('Invalid registration data structure during payment confirmation', [
                'event_id' => $event->id,
                'data_type' => gettype($registrationData)
            ]);

            session()->forget('registration_data');
            return redirect()->route('events.show', $event)
                ->with('error', 'Invalid registration data. Please start your registration again.');
        }

        if (now()->gt($registrationData['expires_at'])) {
            Log::info('Payment confirmation attempted with expired registration data', [
                'event_id' => $event->id,
                'expired_at' => $registrationData['expires_at'],
                'minutes_expired' => now()->diffInMinutes($registrationData['expires_at']),
                'email' => $registrationData['attendee_email'] ?? 'unknown'
            ]);

            session()->forget('registration_data');
            return redirect()->route('events.show', $event)
                ->with('error', 'Your registration session has expired (30 minutes). Please start your registration again for security reasons.');
        }

        if (!isset($registrationData['event_id']) || $registrationData['event_id'] != $event->id) {
            Log::warning('Event ID mismatch during payment confirmation', [
                'session_event_id' => $registrationData['event_id'] ?? 'missing',
                'current_event_id' => $event->id
            ]);

            session()->forget('registration_data');
            return redirect()->route('events.show', $event)
                ->with('error', 'Registration data is for a different event. Please start your registration again.');
        }

        try {
            // Create registration lock to prevent duplicate submissions during payment processing
            $registrationController = app(\App\Http\Controllers\RegistrationController::class, [
                'orderService' => app(\App\Services\OrderService::class)
            ]);

            // Check for duplicates one more time before creating lock
            $duplicateCheck = $registrationController->checkDuplicateRegistration(
                $registrationData['attendee_email'],
                $registrationData['attendee_phone'],
                $event->id
            );

            if ($duplicateCheck !== null) {
                Log::warning('Duplicate registration detected during payment confirmation', [
                    'email' => $registrationData['attendee_email'],
                    'phone' => $registrationData['attendee_phone'],
                    'event_id' => $event->id,
                    'error' => $duplicateCheck
                ]);

                return redirect()->route('events.show', $event)
                    ->with('error', $duplicateCheck);
            }

            // Create registration lock to prevent concurrent duplicate attempts
            $lock = $registrationController->markRegistrationPending(
                $registrationData['attendee_email'],
                $registrationData['attendee_phone'],
                $event->id
            );

            // Update session with payment confirmation timestamp and lock ID
            $registrationData['payment_confirmed_at'] = now();
            $registrationData['lock_id'] = $lock->id;
            session(['registration_data' => $registrationData]);

            Log::info('Payment confirmation received and registration locked', [
                'event_id' => $event->id,
                'email' => $registrationData['attendee_email'],
                'lock_id' => $lock->id,
                'confirmed_at' => now()
            ]);

            // Show processing message with countdown
            return view('payments.processing', compact('event', 'registrationData'));
        } catch (\Exception $e) {
            Log::error('Payment confirmation failed', [
                'event_id' => $event->id,
                'email' => $registrationData['attendee_email'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Unable to process payment confirmation. Please try again.');
        }
    }

    /**
     * Process payment confirmation and create final registration record.
     */
    public function processPaymentConfirmation(Request $request, Event $event)
    {
        $registrationData = session('registration_data');

        if (!$registrationData || !isset($registrationData['payment_confirmed_at'])) {
            // Check if this is an AJAX request
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment confirmation request.',
                    'redirect_url' => route('home')
                ], 400);
            } else {
                return redirect()->route('home')
                    ->with('error', 'Invalid payment confirmation request.');
            }
        }

        try {
            // Create the final registration record
            $registrationController = app(\App\Http\Controllers\RegistrationController::class, [
                'orderService' => app(\App\Services\OrderService::class)
            ]);

            // Call the createDirectRegistration method which handles the full registration creation
            $result = $registrationController->createDirectRegistration();

            // Check if the result is a redirect response (success case)
            if ($result instanceof \Illuminate\Http\RedirectResponse) {
                Log::info('Payment confirmation processed successfully', [
                    'event_id' => $event->id,
                    'email' => $registrationData['attendee_email']
                ]);

                // Send registration success email notification
                try {
                    // Get the created registration from the session or find it
                    $registration = Registration::where('attendee_email', $registrationData['attendee_email'])
                        ->where('event_id', $event->id)
                        ->latest()
                        ->first();

                    if ($registration) {
                        Mail::to($registration->attendee_email)
                            ->send(new \App\Mail\RegistrationSuccessMail($registration));

                        Log::info('Registration success email sent', [
                            'registration_id' => $registration->id,
                            'email' => $registration->attendee_email
                        ]);
                    }
                } catch (\Exception $emailError) {
                    Log::error('Failed to send registration success email', [
                        'event_id' => $event->id,
                        'email' => $registrationData['attendee_email'],
                        'error' => $emailError->getMessage()
                    ]);
                    // Don't fail the registration if email fails
                }
            } else {
                throw new \Exception('Registration creation failed');
            }

            // Check if this is an AJAX request
            if ($request->expectsJson() || $request->ajax()) {
                // Return JSON response for AJAX call
                return response()->json([
                    'success' => true,
                    'message' => 'Registration completed successfully!',
                    'redirect_url' => route('home')
                ]);
            } else {
                // For direct browser requests, redirect to a success page
                return redirect()->route('home')
                    ->with('success', 'Registration completed successfully! You will receive a confirmation email shortly.');
            }
        } catch (\Exception $e) {
            Log::error('Payment confirmation processing failed', [
                'event_id' => $event->id,
                'email' => $registrationData['attendee_email'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            // Check if this is an AJAX request
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to complete registration. Please contact support.',
                    'redirect_url' => route('home')
                ], 500);
            } else {
                // For direct browser requests, redirect with error message
                return redirect()->route('home')
                    ->with('error', 'Unable to complete registration. Please contact support if you made a payment.');
            }
        }
    }

    /**
     * Handle UniPayment callback when user returns from checkout.
     */
    public function handleUniPaymentCallback(Request $request)
    {
        try {
            Log::info('UniPayment callback received', [
                'query_params' => $request->query(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            $invoiceId = $request->query('invoice_id');
            $status = $request->query('status');
            $orderId = $request->query('order_id');

            if (!$invoiceId || !$status || !$orderId) {
                Log::warning('UniPayment callback missing required parameters', [
                    'invoice_id' => $invoiceId,
                    'status' => $status,
                    'order_id' => $orderId,
                    'all_params' => $request->query()
                ]);

                return redirect()->route('home')
                    ->with('error', 'Invalid payment callback. Please contact support if you completed payment.');
            }

            // Get and decrypt payment session data
            $encryptedSessionData = session('payment_session_data');

            if (!$encryptedSessionData || !isset($encryptedSessionData['invoice_id'])) {
                Log::warning('UniPayment callback without valid session data', [
                    'invoice_id' => $invoiceId,
                    'order_id' => $orderId,
                    'has_session_data' => !empty($encryptedSessionData)
                ]);

                return redirect()->route('home')
                    ->with('error', 'Payment session expired. Please contact support if you completed payment.');
            }

            // Decrypt sensitive session data
            $paymentSessionData = $this->decryptPaymentSessionData($encryptedSessionData);

            // Validate payment session
            if (!$this->validatePaymentSession($paymentSessionData)) {
                Log::warning('Invalid payment session data in callback', [
                    'invoice_id' => $invoiceId,
                    'order_id' => $orderId
                ]);

                session()->forget('payment_session_data');
                return redirect()->route('home')
                    ->with('error', 'Payment session is invalid or expired. Please contact support if you completed payment.');
            }

            if ($paymentSessionData['invoice_id'] !== $invoiceId) {
                Log::warning('UniPayment callback invoice ID mismatch', [
                    'callback_invoice_id' => $invoiceId,
                    'session_invoice_id' => $paymentSessionData['invoice_id']
                ]);

                return redirect()->route('home')
                    ->with('error', 'Payment session mismatch. Please contact support if you completed payment.');
            }

            // Process the callback using UniPayment service
            $uniPaymentService = app(\App\Services\UniPaymentOfficialService::class);
            $callbackResult = $uniPaymentService->handlePaymentCallback($request->query());

            Log::info('UniPayment callback processed', [
                'invoice_id' => $invoiceId,
                'order_id' => $orderId,
                'callback_result' => $callbackResult
            ]);

            if ($callbackResult['success'] && $callbackResult['verified']) {
                // Payment successful - process registration
                return $this->processSuccessfulCardPayment($paymentSessionData, $callbackResult);
            } else {
                // Payment failed or not verified
                $errorMessage = $callbackResult['error'] ?? 'Payment was not completed successfully.';

                Log::warning('UniPayment callback indicates failed payment', [
                    'invoice_id' => $invoiceId,
                    'order_id' => $orderId,
                    'status' => $status,
                    'error' => $errorMessage
                ]);

                // Update transaction status
                $this->updatePaymentTransactionStatus($invoiceId, 'failed', $callbackResult);

                // Restore registration data from payment session for retry
                $this->restoreRegistrationDataFromPaymentSession($paymentSessionData);

                // Redirect to failure page with detailed error information
                return redirect()->route('payment.failed', ['event' => $paymentSessionData['event_id']])
                    ->with([
                        'error' => $errorMessage,
                        'transaction_id' => $invoiceId,
                        'payment_method' => 'card'
                    ]);
            }
        } catch (\Exception $e) {
            Log::error('UniPayment callback processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->query()
            ]);

            return redirect()->route('home')
                ->with('error', 'Payment processing error. Please contact support if you completed payment.');
        }
    }

    /**
     * Handle UniPayment webhook notifications with comprehensive error handling.
     */
    public function handleUniPaymentWebhook(Request $request)
    {
        $webhookId = 'webhook_' . uniqid();
        $startTime = microtime(true);
        $payload = null;
        $signature = null;

        try {
            // Step 1: Extract and validate request data with enhanced logging
            $payload = $request->getContent();
            $signature = $request->header('X-UniPayment-Signature', '');
            $ipAddress = $request->ip();
            $userAgent = $request->header('User-Agent', 'Unknown');
            $contentType = $request->header('Content-Type', 'Unknown');
            $requestMethod = $request->method();

            // Log webhook event for monitoring
            $payloadData = json_decode($payload, true);
            $eventType = $payloadData['event_type'] ?? 'unknown';
            $this->webhookMonitoringService->logWebhookEvent($eventType, [
                'webhook_id' => $webhookId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'payload_length' => strlen($payload)
            ]);

            Log::info('UniPayment webhook processing started', [
                'webhook_id' => $webhookId,
                'request_method' => $requestMethod,
                'content_type' => $contentType,
                'payload_length' => strlen($payload),
                'signature_present' => !empty($signature),
                'signature_length' => strlen($signature),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'timestamp' => now()->toISOString(),
                'headers' => [
                    'content_length' => $request->header('Content-Length'),
                    'host' => $request->header('Host'),
                    'x_forwarded_for' => $request->header('X-Forwarded-For'),
                ]
            ]);

            // Step 2: Enhanced payload validation with specific error codes
            if (empty($payload)) {
                Log::warning('UniPayment webhook rejected - empty payload', [
                    'webhook_id' => $webhookId,
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                    'content_length' => $request->header('Content-Length', '0'),
                    'request_method' => $requestMethod
                ]);
                $this->updateWebhookAttempt($webhookId, 'failed', 'Empty payload', 400);
                return response('Bad Request: Empty payload', 400);
            }

            // Validate payload is valid JSON
            $payloadData = json_decode($payload, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('UniPayment webhook rejected - invalid JSON payload', [
                    'webhook_id' => $webhookId,
                    'ip_address' => $ipAddress,
                    'json_error' => json_last_error_msg(),
                    'payload_preview' => substr($payload, 0, 200)
                ]);
                $this->updateWebhookAttempt($webhookId, 'failed', 'Invalid JSON payload: ' . json_last_error_msg(), 400);
                return response('Bad Request: Invalid JSON payload', 400);
            }

            // Step 3: Enhanced idempotency checks with multiple strategies
            $webhookHash = hash('sha256', $payload . $ipAddress . $userAgent);
            $duplicateCheck = $this->performComprehensiveIdempotencyCheck($webhookId, $webhookHash, $payloadData);

            if ($duplicateCheck['is_duplicate']) {
                Log::info('UniPayment webhook duplicate detected - returning success', [
                    'webhook_id' => $webhookId,
                    'webhook_hash' => substr($webhookHash, 0, 16) . '...',
                    'ip_address' => $ipAddress,
                    'duplicate_reason' => $duplicateCheck['reason'],
                    'original_webhook_id' => $duplicateCheck['original_webhook_id'] ?? null,
                    'time_since_original' => $duplicateCheck['time_since_original'] ?? null
                ]);
                $this->updateWebhookAttempt($webhookId, 'duplicate', $duplicateCheck['reason'], 200);
                return response('OK - Duplicate processed', 200);
            }

            // Step 4: Record webhook processing attempt with enhanced metadata
            $this->recordWebhookAttempt($webhookId, $webhookHash, $payload, $signature, $ipAddress, [
                'content_type' => $contentType,
                'user_agent' => $userAgent,
                'payload_data_keys' => array_keys($payloadData),
                'has_invoice_id' => isset($payloadData['invoice_id']),
                'has_order_id' => isset($payloadData['order_id']),
                'event_type' => $payloadData['event_type'] ?? 'unknown'
            ]);

            // Step 5: Process webhook using UniPayment service with detailed logging
            Log::info('UniPayment webhook delegating to service', [
                'webhook_id' => $webhookId,
                'service_class' => 'UniPaymentOfficialService',
                'payload_event_type' => $payloadData['event_type'] ?? 'unknown',
                'payload_invoice_id' => $payloadData['invoice_id'] ?? null
            ]);

            $uniPaymentService = app(\App\Services\UniPaymentOfficialService::class);
            $webhookResult = $uniPaymentService->handleWebhookNotification($payload, $signature);

            // Step 6: Enhanced error handling with specific HTTP status codes
            if (!$webhookResult['success']) {
                $httpStatus = $this->determineWebhookErrorStatus($webhookResult);
                $errorMessage = $webhookResult['error'] ?? 'Unknown error';
                $verified = $webhookResult['verified'] ?? false;
                $errorType = $webhookResult['error_type'] ?? 'unknown';

                Log::warning('UniPayment webhook processing failed', [
                    'webhook_id' => $webhookId,
                    'error' => $errorMessage,
                    'error_type' => $errorType,
                    'http_status' => $httpStatus,
                    'ip_address' => $ipAddress,
                    'verified' => $verified,
                    'duplicate' => $webhookResult['duplicate'] ?? false,
                    'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                    'service_response_keys' => array_keys($webhookResult)
                ]);

                // Update webhook attempt record with detailed failure information
                $this->updateWebhookAttempt($webhookId, 'failed', $errorMessage, $httpStatus, [
                    'error_type' => $errorType,
                    'verified' => $verified,
                    'service_response' => $webhookResult
                ]);

                // Return appropriate HTTP status based on error type
                return $this->buildWebhookErrorResponse($httpStatus, $errorMessage, $webhookId, $errorType);
            }

            // Step 7: Extract and validate webhook data with comprehensive logging
            $invoiceId = $webhookResult['invoice_id'] ?? null;
            $orderId = $webhookResult['order_id'] ?? null;
            $status = $webhookResult['status'] ?? null;
            $verified = $webhookResult['verified'] ?? false;
            $isDuplicate = $webhookResult['duplicate'] ?? false;
            $eventType = $webhookResult['event_type'] ?? 'unknown';

            Log::info('UniPayment webhook processed successfully', [
                'webhook_id' => $webhookId,
                'invoice_id' => $invoiceId,
                'order_id' => $orderId,
                'status' => $status,
                'event_type' => $eventType,
                'success' => $webhookResult['success'],
                'verified' => $verified,
                'duplicate' => $isDuplicate,
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'webhook_data_completeness' => [
                    'has_invoice_id' => !empty($invoiceId),
                    'has_order_id' => !empty($orderId),
                    'has_status' => !empty($status),
                    'is_verified' => $verified
                ]
            ]);

            // Step 8: Enhanced payment processing with detailed transaction logging
            if ($invoiceId) {
                Log::info('UniPayment webhook updating payment transaction', [
                    'webhook_id' => $webhookId,
                    'invoice_id' => $invoiceId,
                    'status' => $status,
                    'event_type' => $eventType
                ]);

                // Update payment transaction status with enhanced error handling
                $transactionUpdateResult = $this->updatePaymentTransactionStatus($invoiceId, $status, $webhookResult);

                Log::info('Payment transaction update result', [
                    'webhook_id' => $webhookId,
                    'invoice_id' => $invoiceId,
                    'update_success' => $transactionUpdateResult['success'] ?? false,
                    'update_reason' => $transactionUpdateResult['reason'] ?? null,
                    'transaction_id' => $transactionUpdateResult['transaction_id'] ?? null
                ]);

                // Process registration completion for successful payments
                if ($webhookResult['success'] && !$isDuplicate && $status === 'completed') {
                    Log::info('UniPayment webhook processing registration completion', [
                        'webhook_id' => $webhookId,
                        'invoice_id' => $invoiceId,
                        'payment_status' => $status
                    ]);

                    // Try to complete registration if not already done
                    $registrationResult = $this->processWebhookRegistrationCompletion($invoiceId, $webhookResult);

                    Log::info('UniPayment webhook registration completion result', [
                        'webhook_id' => $webhookId,
                        'invoice_id' => $invoiceId,
                        'registration_completed' => $registrationResult['completed'] ?? false,
                        'registration_id' => $registrationResult['registration_id'] ?? null,
                        'completion_reason' => $registrationResult['reason'] ?? null
                    ]);
                }
            } else {
                Log::warning('UniPayment webhook missing invoice ID', [
                    'webhook_id' => $webhookId,
                    'webhook_data_keys' => array_keys($webhookResult),
                    'payload_data_keys' => array_keys($payloadData),
                    'event_type' => $eventType
                ]);
            }

            // Step 9: Update webhook attempt record with success and metadata
            $this->updateWebhookAttempt($webhookId, 'completed', null, 200, [
                'invoice_id' => $invoiceId,
                'order_id' => $orderId,
                'status' => $status,
                'event_type' => $eventType,
                'verified' => $verified,
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);

            // Step 10: Return success response with processing metadata
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('UniPayment webhook processing completed successfully', [
                'webhook_id' => $webhookId,
                'invoice_id' => $invoiceId,
                'order_id' => $orderId,
                'status' => $status,
                'event_type' => $eventType,
                'total_processing_time_ms' => $processingTime,
                'verified' => $verified,
                'duplicate' => $isDuplicate,
                'final_status' => 'success'
            ]);

            // Log successful webhook processing for monitoring
            $this->webhookMonitoringService->logWebhookSuccess($eventType, [
                'webhook_id' => $webhookId,
                'verified' => $verified,
                'duplicate' => $isDuplicate
            ], $processingTime);

            return response('OK', 200, [
                'X-Webhook-ID' => $webhookId,
                'X-Processing-Time-MS' => $processingTime
            ]);
        } catch (\Throwable $e) {
            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('UniPayment webhook processing failed with exception', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip_address' => $request->ip(),
                'payload_length' => strlen($payload ?? ''),
                'processing_time_ms' => $processingTime,
                'exception_class' => get_class($e),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
                'request_method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true)
            ]);

            // Log webhook error for monitoring
            if (isset($eventType)) {
                $this->webhookMonitoringService->logWebhookError($eventType, [
                    'webhook_id' => $webhookId,
                    'ip_address' => $request->ip(),
                    'payload_length' => strlen($payload ?? '')
                ], $e->getMessage(), $e);
            }

            // Update webhook attempt record with exception details
            if (isset($webhookId)) {
                $this->updateWebhookAttempt($webhookId, 'error', $e->getMessage(), 500, [
                    'exception_class' => get_class($e),
                    'exception_file' => $e->getFile(),
                    'exception_line' => $e->getLine(),
                    'processing_time_ms' => $processingTime
                ]);
            }

            return response('Internal Server Error', 500, [
                'X-Webhook-ID' => $webhookId ?? 'unknown',
                'X-Processing-Time-MS' => $processingTime
            ]);
        }
    }

    /**
     * Process successful card payment and create registration.
     */
    protected function processSuccessfulCardPayment(array $paymentSessionData, array $callbackResult): \Illuminate\Http\RedirectResponse
    {
        try {
            Log::info('Processing successful card payment', [
                'invoice_id' => $callbackResult['invoice_id'],
                'order_id' => $callbackResult['order_id'],
                'email' => $paymentSessionData['attendee_email']
            ]);

            // Update payment transaction status
            $this->updatePaymentTransactionStatus(
                $callbackResult['invoice_id'],
                'completed',
                $callbackResult
            );

            // Create registration using the stored session data
            $event = \App\Models\Event::find($paymentSessionData['event_id']);

            if (!$event) {
                throw new \Exception('Event not found: ' . $paymentSessionData['event_id']);
            }

            // Create registration record
            $registration = \App\Models\Registration::create([
                'event_id' => $paymentSessionData['event_id'],
                'attendee_name' => $paymentSessionData['attendee_name'],
                'attendee_email' => $paymentSessionData['attendee_email'],
                'attendee_phone' => $paymentSessionData['attendee_phone'],
                'attendee_organization' => $paymentSessionData['attendee_organization'] ?? null,
                'attendee_title' => $paymentSessionData['attendee_title'] ?? null,
                'dietary_requirements' => $paymentSessionData['dietary_requirements'] ?? null,
                'accessibility_requirements' => $paymentSessionData['accessibility_requirements'] ?? null,
                'emergency_contact_name' => $paymentSessionData['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $paymentSessionData['emergency_contact_phone'] ?? null,
                'total_amount' => $paymentSessionData['total_amount'],
                'registration_status' => 'confirmed', // Card payments are automatically confirmed
                'payment_method' => 'card',
                'payment_provider' => 'unipayment',
                'transaction_id' => $callbackResult['invoice_id'],
                'payment_amount' => $callbackResult['amount'] ?? $paymentSessionData['total_amount'],
                'payment_currency' => $callbackResult['currency'] ?? 'USD',
                'payment_completed_at' => now(),
                'confirmed_at' => now(),
                'created_by' => null, // Direct registration
                'confirmed_by' => null // Automatic confirmation
            ]);

            // Update payment transaction with registration ID
            \App\Models\PaymentTransaction::where('transaction_id', $callbackResult['invoice_id'])
                ->update([
                    'registration_id' => $registration->id,
                    'status' => 'completed',
                    'processed_at' => now(),
                    'callback_data' => $callbackResult
                ]);

            // Clear session data
            session()->forget(['registration_data', 'payment_session_data']);

            // Send confirmation email
            try {
                Mail::to($registration->attendee_email)
                    ->send(new \App\Mail\RegistrationSuccessMail($registration));

                Log::info('Registration confirmation email sent', [
                    'registration_id' => $registration->id,
                    'email' => $registration->attendee_email
                ]);
            } catch (\Exception $emailError) {
                Log::error('Failed to send registration confirmation email', [
                    'registration_id' => $registration->id,
                    'email' => $registration->attendee_email,
                    'error' => $emailError->getMessage()
                ]);
                // Don't fail the registration if email fails
            }

            Log::info('Card payment registration completed successfully', [
                'registration_id' => $registration->id,
                'invoice_id' => $callbackResult['invoice_id'],
                'email' => $registration->attendee_email,
                'amount' => $registration->total_amount
            ]);

            return redirect()->route('registrations.success', $registration)
                ->with('success', 'Payment completed successfully! Your registration is confirmed.');
        } catch (\Exception $e) {
            Log::error('Failed to process successful card payment', [
                'invoice_id' => $callbackResult['invoice_id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home')
                ->with('error', 'Payment was successful but registration processing failed. Please contact support with your payment confirmation.');
        }
    }

    /**
     * Update payment transaction status with detailed logging and error handling.
     */
    protected function updatePaymentTransactionStatus(string $invoiceId, string $status, array $data): array
    {
        try {
            Log::info('Updating payment transaction status', [
                'invoice_id' => $invoiceId,
                'new_status' => $status,
                'data_keys' => array_keys($data)
            ]);

            $transaction = \App\Models\PaymentTransaction::where('transaction_id', $invoiceId)->first();

            if (!$transaction) {
                Log::warning('Payment transaction not found for status update', [
                    'invoice_id' => $invoiceId,
                    'status' => $status,
                    'searched_field' => 'transaction_id'
                ]);
                return [
                    'success' => false,
                    'reason' => 'transaction_not_found',
                    'transaction_id' => null
                ];
            }

            $oldStatus = $transaction->status;
            $updateData = [
                'status' => $status,
                'processed_at' => now()
            ];

            // Handle status-specific updates
            if ($status === 'completed') {
                $updateData['callback_data'] = $data;
                Log::info('Payment transaction marked as completed', [
                    'transaction_id' => $transaction->id,
                    'invoice_id' => $invoiceId,
                    'old_status' => $oldStatus,
                    'amount' => $data['amount'] ?? 'unknown',
                    'currency' => $data['currency'] ?? 'unknown'
                ]);
            } elseif ($status === 'failed') {
                $errorInfo = [
                    'error' => $data['error'] ?? 'Payment failed',
                    'failed_at' => now()->toISOString(),
                    'webhook_data' => $data['raw_webhook_data'] ?? null
                ];

                $updateData['provider_response'] = array_merge(
                    $transaction->provider_response ?? [],
                    $errorInfo
                );

                Log::warning('Payment transaction marked as failed', [
                    'transaction_id' => $transaction->id,
                    'invoice_id' => $invoiceId,
                    'old_status' => $oldStatus,
                    'error' => $errorInfo['error']
                ]);
            } elseif ($status === 'pending') {
                Log::info('Payment transaction status updated to pending', [
                    'transaction_id' => $transaction->id,
                    'invoice_id' => $invoiceId,
                    'old_status' => $oldStatus
                ]);
            } else {
                Log::info('Payment transaction status updated', [
                    'transaction_id' => $transaction->id,
                    'invoice_id' => $invoiceId,
                    'old_status' => $oldStatus,
                    'new_status' => $status
                ]);
            }

            // Check for status change conflicts
            if ($oldStatus === 'completed' && $status !== 'completed') {
                Log::warning('Attempting to change completed payment transaction status', [
                    'transaction_id' => $transaction->id,
                    'invoice_id' => $invoiceId,
                    'old_status' => $oldStatus,
                    'attempted_status' => $status,
                    'action' => 'blocked'
                ]);
                return [
                    'success' => false,
                    'reason' => 'status_change_blocked',
                    'transaction_id' => $transaction->id,
                    'old_status' => $oldStatus,
                    'attempted_status' => $status
                ];
            }

            // Perform the update
            $transaction->update($updateData);

            Log::info('Payment transaction status updated successfully', [
                'transaction_id' => $transaction->id,
                'invoice_id' => $invoiceId,
                'old_status' => $oldStatus,
                'new_status' => $status,
                'updated_at' => $transaction->updated_at->toISOString()
            ]);

            return [
                'success' => true,
                'transaction_id' => $transaction->id,
                'old_status' => $oldStatus,
                'new_status' => $status,
                'updated_at' => $transaction->updated_at
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update payment transaction status', [
                'invoice_id' => $invoiceId,
                'status' => $status,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'exception_class' => get_class($e)
            ]);

            return [
                'success' => false,
                'reason' => 'exception',
                'error' => $e->getMessage(),
                'transaction_id' => null
            ];
        }
    }

    /**
     * Process registration completion from webhook (if callback failed).
     */
    protected function processWebhookRegistrationCompletion(string $invoiceId, array $webhookResult): array
    {
        try {
            // Check if registration already exists
            $existingRegistration = \App\Models\Registration::where('transaction_id', $invoiceId)->first();

            if ($existingRegistration) {
                Log::info('Registration already exists for webhook', [
                    'invoice_id' => $invoiceId,
                    'registration_id' => $existingRegistration->id
                ]);
                return [
                    'completed' => false,
                    'registration_id' => $existingRegistration->id,
                    'reason' => 'already_exists'
                ];
            }

            // Try to find payment transaction to get registration data
            $transaction = \App\Models\PaymentTransaction::where('transaction_id', $invoiceId)->first();

            if (!$transaction || !$transaction->provider_response) {
                Log::warning('Cannot process webhook registration - no transaction data', [
                    'invoice_id' => $invoiceId
                ]);
                return [
                    'completed' => false,
                    'registration_id' => null,
                    'reason' => 'no_transaction_data'
                ];
            }

            // Extract registration data from ext_args if available
            $extArgs = $webhookResult['ext_args'] ?? [];

            if (empty($extArgs) || !isset($extArgs['attendee_email'])) {
                Log::warning('Cannot process webhook registration - missing attendee data', [
                    'invoice_id' => $invoiceId,
                    'ext_args' => $extArgs
                ]);
                return [
                    'completed' => false,
                    'registration_id' => null,
                    'reason' => 'missing_attendee_data'
                ];
            }

            // This is a fallback - in normal flow, registration should be created in callback
            Log::info('Processing registration from webhook as fallback', [
                'invoice_id' => $invoiceId,
                'attendee_email' => $extArgs['attendee_email']
            ]);

            // Note: This is a simplified fallback. In production, you might want to store
            // more complete registration data in the transaction or have a different approach
            return [
                'completed' => false,
                'registration_id' => null,
                'reason' => 'fallback_not_implemented'
            ];
        } catch (\Exception $e) {
            Log::error('Failed to process webhook registration completion', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage()
            ]);
            return [
                'completed' => false,
                'registration_id' => null,
                'reason' => 'exception',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Perform comprehensive idempotency check with multiple strategies.
     */
    protected function performComprehensiveIdempotencyCheck(string $webhookId, string $webhookHash, array $payloadData): array
    {
        try {
            // Strategy 1: Check content hash (existing method)
            $hashCacheKey = "webhook_hash_{$webhookHash}";
            $recentAttempt = \Illuminate\Support\Facades\Cache::get($hashCacheKey);

            if ($recentAttempt) {
                return [
                    'is_duplicate' => true,
                    'reason' => 'content_hash_match',
                    'original_webhook_id' => $recentAttempt['webhook_id'] ?? null,
                    'time_since_original' => $recentAttempt['processed_at'] ?? null
                ];
            }

            // Strategy 2: Check by invoice ID and event type (for UniPayment specific duplicates)
            if (isset($payloadData['invoice_id']) && isset($payloadData['event_type'])) {
                $invoiceEventKey = "webhook_invoice_{$payloadData['invoice_id']}_{$payloadData['event_type']}";
                $invoiceAttempt = \Illuminate\Support\Facades\Cache::get($invoiceEventKey);

                if ($invoiceAttempt) {
                    return [
                        'is_duplicate' => true,
                        'reason' => 'invoice_event_match',
                        'original_webhook_id' => $invoiceAttempt['webhook_id'] ?? null,
                        'time_since_original' => $invoiceAttempt['processed_at'] ?? null
                    ];
                }
            }

            // Strategy 3: Check by order ID if available
            if (isset($payloadData['order_id'])) {
                $orderKey = "webhook_order_{$payloadData['order_id']}";
                $orderAttempt = \Illuminate\Support\Facades\Cache::get($orderKey);

                if ($orderAttempt) {
                    // Allow multiple events for same order (e.g., pending -> completed)
                    // but prevent exact duplicates within short timeframe
                    $timeDiff = now()->diffInMinutes($orderAttempt['processed_at'] ?? now());
                    if ($timeDiff < 2 && ($orderAttempt['event_type'] ?? '') === ($payloadData['event_type'] ?? '')) {
                        return [
                            'is_duplicate' => true,
                            'reason' => 'order_event_recent_duplicate',
                            'original_webhook_id' => $orderAttempt['webhook_id'] ?? null,
                            'time_since_original' => $orderAttempt['processed_at'] ?? null
                        ];
                    }
                }
            }

            return [
                'is_duplicate' => false,
                'reason' => 'unique_webhook'
            ];
        } catch (\Exception $e) {
            Log::warning('Comprehensive idempotency check failed', [
                'webhook_id' => $webhookId,
                'webhook_hash' => substr($webhookHash, 0, 16) . '...',
                'error' => $e->getMessage()
            ]);
            // If check fails, allow processing to continue (fail open)
            return [
                'is_duplicate' => false,
                'reason' => 'check_failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if webhook is a duplicate based on content hash and recent processing.
     * @deprecated Use performComprehensiveIdempotencyCheck instead
     */
    protected function isWebhookDuplicate(string $webhookHash): bool
    {
        $result = $this->performComprehensiveIdempotencyCheck('legacy', $webhookHash, []);
        return $result['is_duplicate'];
    }

    /**
     * Record webhook processing attempt for idempotency and debugging.
     */
    protected function recordWebhookAttempt(string $webhookId, string $webhookHash, string $payload, string $signature, string $ipAddress, array $metadata = []): void
    {
        try {
            $attemptData = [
                'webhook_id' => $webhookId,
                'processed_at' => now()->toISOString(),
                'ip_address' => $ipAddress,
                'metadata' => $metadata
            ];

            // Cache webhook hash to prevent duplicates
            \Illuminate\Support\Facades\Cache::put(
                "webhook_hash_{$webhookHash}",
                $attemptData,
                600 // 10 minutes
            );

            // Cache by invoice ID and event type if available
            if (isset($metadata['has_invoice_id']) && $metadata['has_invoice_id'] && isset($metadata['event_type'])) {
                $payloadData = json_decode($payload, true);
                if ($payloadData && isset($payloadData['invoice_id'])) {
                    $invoiceEventKey = "webhook_invoice_{$payloadData['invoice_id']}_{$metadata['event_type']}";
                    \Illuminate\Support\Facades\Cache::put($invoiceEventKey, $attemptData, 600);
                }
            }

            // Cache by order ID if available
            if (isset($metadata['has_order_id']) && $metadata['has_order_id']) {
                $payloadData = json_decode($payload, true);
                if ($payloadData && isset($payloadData['order_id'])) {
                    $orderKey = "webhook_order_{$payloadData['order_id']}";
                    \Illuminate\Support\Facades\Cache::put($orderKey, array_merge($attemptData, [
                        'event_type' => $metadata['event_type'] ?? 'unknown'
                    ]), 600);
                }
            }

            // Enhanced logging with metadata
            Log::info('Webhook processing attempt recorded', [
                'webhook_id' => $webhookId,
                'webhook_hash' => substr($webhookHash, 0, 16) . '...',
                'payload_length' => strlen($payload),
                'signature_length' => strlen($signature),
                'ip_address' => $ipAddress,
                'recorded_at' => now()->toISOString(),
                'metadata' => $metadata,
                'cache_keys_created' => [
                    'hash_key' => "webhook_hash_{$webhookHash}",
                    'invoice_key' => isset($metadata['has_invoice_id']) && $metadata['has_invoice_id'] ? 'created' : 'skipped',
                    'order_key' => isset($metadata['has_order_id']) && $metadata['has_order_id'] ? 'created' : 'skipped'
                ]
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to record webhook attempt', [
                'webhook_id' => $webhookId,
                'error' => $e->getMessage(),
                'metadata' => $metadata
            ]);
        }
    }

    /**
     * Update webhook processing attempt status.
     */
    protected function updateWebhookAttempt(string $webhookId, string $status, ?string $errorMessage = null, int $httpStatus = 200, array $metadata = []): void
    {
        try {
            $updateData = [
                'webhook_id' => $webhookId,
                'status' => $status,
                'http_status' => $httpStatus,
                'error_message' => $errorMessage,
                'updated_at' => now()->toISOString(),
                'metadata' => $metadata
            ];

            Log::info('Webhook processing attempt updated', $updateData);

            // Update cache with final status and extended metadata
            if ($status === 'completed') {
                // Extend cache time for successful webhooks to prevent duplicates
                $cacheKey = "webhook_completed_{$webhookId}";
                \Illuminate\Support\Facades\Cache::put($cacheKey, array_merge($updateData, [
                    'completed_at' => now()->toISOString()
                ]), 3600); // 1 hour

                // Store completion status by invoice ID for cross-reference
                if (isset($metadata['invoice_id'])) {
                    $invoiceCompletionKey = "webhook_invoice_completed_{$metadata['invoice_id']}";
                    \Illuminate\Support\Facades\Cache::put($invoiceCompletionKey, [
                        'webhook_id' => $webhookId,
                        'status' => $status,
                        'completed_at' => now()->toISOString(),
                        'event_type' => $metadata['event_type'] ?? 'unknown'
                    ], 3600);
                }
            } elseif ($status === 'failed' || $status === 'error') {
                // Store failure information for debugging
                $failureKey = "webhook_failed_{$webhookId}";
                \Illuminate\Support\Facades\Cache::put($failureKey, $updateData, 1800); // 30 minutes

                // Track failure rate for monitoring
                $this->trackWebhookFailureRate($httpStatus, $errorMessage, $metadata);
            } elseif ($status === 'duplicate') {
                // Track duplicate rate for monitoring
                $duplicateKey = "webhook_duplicate_{$webhookId}";
                \Illuminate\Support\Facades\Cache::put($duplicateKey, $updateData, 600); // 10 minutes
            }
        } catch (\Exception $e) {
            Log::warning('Failed to update webhook attempt', [
                'webhook_id' => $webhookId,
                'status' => $status,
                'error' => $e->getMessage(),
                'metadata' => $metadata
            ]);
        }
    }

    /**
     * Determine appropriate HTTP status code based on webhook error type.
     */
    protected function determineWebhookErrorStatus(array $webhookResult): int
    {
        $errorType = $webhookResult['error_type'] ?? 'unknown';
        $verified = $webhookResult['verified'] ?? false;
        $suggestedStatus = $webhookResult['http_status'] ?? null;

        // Use suggested status if provided and valid
        if ($suggestedStatus && $suggestedStatus >= 400 && $suggestedStatus < 600) {
            return $suggestedStatus;
        }

        // Determine status based on error type
        return match ($errorType) {
            'signature_invalid', 'signature_missing' => 401, // Unauthorized
            'payload_invalid', 'json_invalid', 'missing_required_fields' => 400, // Bad Request
            'invoice_not_found', 'order_not_found' => 404, // Not Found
            'duplicate_webhook' => 409, // Conflict
            'rate_limit_exceeded' => 429, // Too Many Requests
            'service_unavailable', 'database_error' => 503, // Service Unavailable
            'timeout', 'network_error' => 502, // Bad Gateway
            default => 500 // Internal Server Error
        };
    }

    /**
     * Track webhook failure rate for monitoring and alerting.
     */
    protected function trackWebhookFailureRate(int $httpStatus, ?string $errorMessage, array $metadata): void
    {
        try {
            $hour = now()->format('Y-m-d-H');
            $failureKey = "webhook_failures_{$hour}";

            $failures = \Illuminate\Support\Facades\Cache::get($failureKey, []);
            $failures[] = [
                'timestamp' => now()->toISOString(),
                'http_status' => $httpStatus,
                'error_message' => $errorMessage,
                'error_type' => $metadata['error_type'] ?? 'unknown'
            ];

            // Keep only last 100 failures per hour
            if (count($failures) > 100) {
                $failures = array_slice($failures, -100);
            }

            \Illuminate\Support\Facades\Cache::put($failureKey, $failures, 3600); // 1 hour

            // Log if failure rate is high
            if (count($failures) > 10) {
                Log::warning('High webhook failure rate detected', [
                    'hour' => $hour,
                    'failure_count' => count($failures),
                    'latest_error' => $errorMessage,
                    'latest_status' => $httpStatus
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to track webhook failure rate', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Build appropriate error response for webhook failures.
     */
    protected function buildWebhookErrorResponse(int $httpStatus, string $errorMessage, string $webhookId, string $errorType = 'unknown'): \Illuminate\Http\Response
    {
        $responseMessage = match ($httpStatus) {
            400 => 'Bad Request: ' . $errorMessage,
            401 => 'Unauthorized: ' . $errorMessage,
            403 => 'Forbidden: ' . $errorMessage,
            404 => 'Not Found: ' . $errorMessage,
            409 => 'Conflict: ' . $errorMessage,
            422 => 'Unprocessable Entity: ' . $errorMessage,
            429 => 'Too Many Requests: ' . $errorMessage,
            500 => 'Internal Server Error: ' . $errorMessage,
            502 => 'Bad Gateway: ' . $errorMessage,
            503 => 'Service Unavailable: ' . $errorMessage,
            default => 'Error: ' . $errorMessage
        };

        Log::info('Webhook error response built', [
            'webhook_id' => $webhookId,
            'http_status' => $httpStatus,
            'error_type' => $errorType,
            'response_message' => $responseMessage,
            'should_retry' => in_array($httpStatus, [502, 503, 429]) // Suggest retry for these statuses
        ]);

        return response($responseMessage, $httpStatus, [
            'X-Webhook-ID' => $webhookId,
            'X-Error-Type' => $errorType,
            'X-Retry-After' => in_array($httpStatus, [429, 503]) ? '60' : null
        ]);
    }

    /**
     * Handle payment webhook (for real payment gateways).
     */
    public function webhook(Request $request)
    {
        // This would handle webhooks from payment gateways
        // For now, it's a placeholder

        return response()->json(['success' => true]);
    }

    /**
     * Admin: Show pending payments for review.
     */
    public function adminPendingPayments()
    {
        $pendingRegistrations = Registration::with(['event'])
            ->where('registration_status', 'pending')
            ->whereNotNull('payment_confirmed_at')
            ->orderBy('payment_confirmed_at', 'desc')
            ->paginate(20);

        $stats = [
            'total_pending' => Registration::where('registration_status', 'pending')
                ->whereNotNull('payment_confirmed_at')
                ->count(),
            'total_confirmed' => Registration::where('registration_status', 'confirmed')->count(),
            'total_declined' => Registration::where('registration_status', 'declined')->count(),
            'pending_today' => Registration::where('registration_status', 'pending')
                ->whereNotNull('payment_confirmed_at')
                ->whereDate('payment_confirmed_at', today())
                ->count(),
        ];

        return view('admin.payments.pending', compact('pendingRegistrations', 'stats'));
    }

    /**
     * Admin: Approve a payment and confirm registration.
     */
    public function approvePayment(Registration $registration)
    {
        if ($registration->registration_status !== 'pending') {
            return redirect()->back()
                ->with('error', 'This registration is not pending approval.');
        }

        try {
            // Update registration status
            $registration->update([
                'registration_status' => 'confirmed',
                'confirmed_at' => now(),
                'confirmed_by' => auth()->id(),
            ]);

            // Log the approval action for audit trail
            Log::info('Payment approved by admin', [
                'registration_id' => $registration->id,
                'admin_id' => auth()->id(),
                'admin_name' => auth()->user()->name,
                'attendee_email' => $registration->attendee_email,
                'event_id' => $registration->event_id,
                'approved_at' => now()
            ]);

            // Send confirmation email to attendee
            try {
                Mail::to($registration->attendee_email)
                    ->send(new \App\Mail\RegistrationConfirmationMail($registration));

                Log::info('Registration confirmation email sent', [
                    'registration_id' => $registration->id,
                    'email' => $registration->attendee_email,
                    'admin_id' => auth()->id()
                ]);
            } catch (\Exception $emailError) {
                Log::error('Failed to send registration confirmation email', [
                    'registration_id' => $registration->id,
                    'email' => $registration->attendee_email,
                    'admin_id' => auth()->id(),
                    'error' => $emailError->getMessage()
                ]);
                // Don't fail the approval if email fails
            }

            return redirect()->back()
                ->with('success', 'Payment approved successfully. Registration is now confirmed and confirmation email sent.');
        } catch (\Exception $e) {
            Log::error('Payment approval failed', [
                'registration_id' => $registration->id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to approve payment. Please try again.');
        }
    }

    /**
     * Admin: Decline a payment with reason and unlock email/phone for re-use.
     */
    public function declinePayment(Request $request, Registration $registration)
    {
        $validator = Validator::make($request->all(), [
            'decline_reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        if ($registration->registration_status !== 'pending') {
            return redirect()->back()
                ->with('error', 'This registration is not pending approval.');
        }

        try {
            // Update registration status with decline information
            $registration->update([
                'registration_status' => 'declined',
                'declined_at' => now(),
                'declined_by' => auth()->id(),
                'declined_reason' => $request->decline_reason,
            ]);

            // Remove registration locks to allow re-registration with same email/phone
            RegistrationLock::where('email', $registration->attendee_email)
                ->where('event_id', $registration->event_id)
                ->delete();

            RegistrationLock::where('phone', $registration->attendee_phone)
                ->where('event_id', $registration->event_id)
                ->delete();

            // Log the decline action for audit trail
            Log::info('Payment declined by admin', [
                'registration_id' => $registration->id,
                'admin_id' => auth()->id(),
                'admin_name' => auth()->user()->name,
                'attendee_email' => $registration->attendee_email,
                'event_id' => $registration->event_id,
                'decline_reason' => $request->decline_reason,
                'declined_at' => now()
            ]);

            // Send decline notification email to attendee
            try {
                Mail::to($registration->attendee_email)
                    ->send(new \App\Mail\PaymentDeclinedMail($registration));

                Log::info('Payment decline email sent', [
                    'registration_id' => $registration->id,
                    'email' => $registration->attendee_email,
                    'admin_id' => auth()->id(),
                    'decline_reason' => $request->decline_reason
                ]);
            } catch (\Exception $emailError) {
                Log::error('Failed to send payment decline email', [
                    'registration_id' => $registration->id,
                    'email' => $registration->attendee_email,
                    'admin_id' => auth()->id(),
                    'error' => $emailError->getMessage()
                ]);
                // Don't fail the decline if email fails
            }

            return redirect()->back()
                ->with('success', 'Payment declined successfully. Email and phone are now available for re-registration. Decline notification sent to attendee.');
        } catch (\Exception $e) {
            Log::error('Payment decline failed', [
                'registration_id' => $registration->id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to decline payment. Please try again.');
        }
    }

    /**
     * Encrypt sensitive payment session data.
     */
    protected function encryptPaymentSessionData(array $data): array
    {
        $sensitiveFields = ['attendee_email', 'attendee_phone', 'attendee_name'];
        $encryptedData = $data;

        foreach ($sensitiveFields as $field) {
            if (isset($encryptedData[$field])) {
                try {
                    $encryptedData[$field] = Crypt::encryptString($encryptedData[$field]);
                } catch (\Exception $e) {
                    Log::error('Failed to encrypt payment session field', [
                        'field' => $field,
                        'error' => $e->getMessage()
                    ]);
                    // Continue without encryption if it fails
                }
            }
        }

        return $encryptedData;
    }

    /**
     * Decrypt sensitive payment session data.
     */
    protected function decryptPaymentSessionData(array $data): array
    {
        $sensitiveFields = ['attendee_email', 'attendee_phone', 'attendee_name'];
        $decryptedData = $data;

        foreach ($sensitiveFields as $field) {
            if (isset($decryptedData[$field])) {
                try {
                    $decryptedData[$field] = Crypt::decryptString($decryptedData[$field]);
                } catch (\Exception $e) {
                    Log::error('Failed to decrypt payment session field', [
                        'field' => $field,
                        'error' => $e->getMessage()
                    ]);
                    // Continue with encrypted data if decryption fails
                }
            }
        }

        return $decryptedData;
    }

    /**
     * Validate payment session data and check for expiration.
     */
    protected function validatePaymentSession(array $sessionData): bool
    {
        // Check if session data has required fields
        $requiredFields = ['payment_expires_at', 'event_id', 'total_amount'];
        foreach ($requiredFields as $field) {
            if (!isset($sessionData[$field])) {
                Log::warning('Payment session missing required field', [
                    'missing_field' => $field
                ]);
                return false;
            }
        }

        // Check if session has expired
        if (now()->gt($sessionData['payment_expires_at'])) {
            Log::info('Payment session expired', [
                'expired_at' => $sessionData['payment_expires_at'],
                'current_time' => now()
            ]);
            return false;
        }

        return true;
    }

    /**
     * Clean up expired payment sessions.
     */
    protected function cleanupExpiredPaymentSessions(): void
    {
        $sessionData = session('payment_session_data');

        if ($sessionData && isset($sessionData['payment_expires_at'])) {
            if (now()->gt($sessionData['payment_expires_at'])) {
                session()->forget('payment_session_data');
                Log::info('Expired payment session cleaned up');
            }
        }
    }

    /**
     * Handle payment failure and provide recovery options.
     */
    public function handlePaymentFailure(Request $request, Event $event)
    {
        try {
            $errorMessage = $request->input('error', 'Payment processing failed');
            $transactionId = $request->input('transaction_id');
            $paymentMethod = $request->input('payment_method', 'unknown');

            Log::warning('Payment failure handled', [
                'event_id' => $event->id,
                'payment_method' => $paymentMethod,
                'transaction_id' => $transactionId,
                'error_message' => $errorMessage,
                'ip_address' => $request->ip()
            ]);

            // Check if registration data still exists
            $registrationData = session('registration_data');
            $hasValidRegistration = $registrationData &&
                is_array($registrationData) &&
                isset($registrationData['expires_at']) &&
                now()->lte($registrationData['expires_at']);

            if (!$hasValidRegistration) {
                Log::info('Payment failure with expired registration data', [
                    'event_id' => $event->id,
                    'transaction_id' => $transactionId
                ]);

                return redirect()->route('events.show', $event)
                    ->with('error', 'Your registration session has expired. Please start your registration again.');
            }

            // Get available payment options for fallback
            $paymentOptions = $this->getAvailablePaymentOptions();
            $availableMethods = array_filter($paymentOptions, fn($option) => $option['available']);

            // Determine suggested alternative payment method
            $suggestedMethod = $this->getSuggestedAlternativeMethod($paymentMethod, $availableMethods);

            // Store failure information for analytics
            $failureData = [
                'failed_method' => $paymentMethod,
                'error_message' => $errorMessage,
                'transaction_id' => $transactionId,
                'failed_at' => now(),
                'retry_count' => ($registrationData['retry_count'] ?? 0) + 1
            ];

            // Update registration data with failure information
            $registrationData = array_merge($registrationData, $failureData);
            session(['registration_data' => $registrationData]);

            return view('payments.failed', compact(
                'event',
                'errorMessage',
                'transactionId',
                'paymentMethod',
                'suggestedMethod',
                'availableMethods',
                'registrationData'
            ));
        } catch (\Exception $e) {
            Log::error('Error handling payment failure', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('events.show', $event)
                ->with('error', 'Unable to process payment failure. Please contact support.');
        }
    }

    /**
     * Retry payment with the same method.
     */
    public function retryPayment(Request $request, Event $event)
    {
        try {
            $paymentMethod = $request->input('method', 'card');

            Log::info('Payment retry requested', [
                'event_id' => $event->id,
                'payment_method' => $paymentMethod,
                'ip_address' => $request->ip()
            ]);

            // Validate registration data
            $registrationData = session('registration_data');

            if (!$registrationData || !is_array($registrationData) || !isset($registrationData['expires_at'])) {
                return redirect()->route('events.show', $event)
                    ->with('error', 'No registration data found. Please start your registration again.');
            }

            if (now()->gt($registrationData['expires_at'])) {
                session()->forget('registration_data');
                return redirect()->route('events.show', $event)
                    ->with('error', 'Your registration session has expired. Please start your registration again.');
            }

            // Check retry limits
            $retryCount = $registrationData['retry_count'] ?? 0;
            $maxRetries = 3;

            if ($retryCount >= $maxRetries) {
                Log::warning('Maximum retry attempts reached', [
                    'event_id' => $event->id,
                    'retry_count' => $retryCount,
                    'email' => $registrationData['attendee_email'] ?? 'unknown'
                ]);

                return redirect()->route('payment.selection', $event)
                    ->with('error', 'Maximum retry attempts reached. Please try a different payment method or contact support.');
            }

            // Check payment method availability
            $paymentOptions = $this->getAvailablePaymentOptions();

            if (!isset($paymentOptions[$paymentMethod]) || !$paymentOptions[$paymentMethod]['available']) {
                return redirect()->route('payment.selection', $event)
                    ->with('error', 'The selected payment method is currently unavailable. Please choose an alternative method.');
            }

            // Update retry count
            $registrationData['retry_count'] = $retryCount + 1;
            $registrationData['last_retry_at'] = now();
            session(['registration_data' => $registrationData]);

            Log::info('Payment retry initiated', [
                'event_id' => $event->id,
                'payment_method' => $paymentMethod,
                'retry_count' => $registrationData['retry_count'],
                'email' => $registrationData['attendee_email'] ?? 'unknown'
            ]);

            // Redirect to appropriate payment method
            if ($paymentMethod === 'card') {
                return redirect()->route('payment.selection', $event)
                    ->with('info', 'Retrying card payment. Please ensure your card details are correct.');
            } else {
                return redirect()->route('payment.crypto', $event)
                    ->with('info', 'Retrying cryptocurrency payment. Please select your preferred cryptocurrency.');
            }
        } catch (\Exception $e) {
            Log::error('Error retrying payment', [
                'event_id' => $event->id,
                'payment_method' => $request->input('method'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('payment.selection', $event)
                ->with('error', 'Unable to retry payment. Please try again or contact support.');
        }
    }

    /**
     * Get suggested alternative payment method based on failure.
     */
    protected function getSuggestedAlternativeMethod(string $failedMethod, array $availableMethods): ?string
    {
        // If card payment failed, suggest crypto
        if ($failedMethod === 'card' && isset($availableMethods['crypto'])) {
            return 'crypto';
        }

        // If crypto payment failed, suggest card
        if ($failedMethod === 'crypto' && isset($availableMethods['card'])) {
            return 'card';
        }

        // Return any available method if no specific suggestion
        $available = array_keys($availableMethods);
        return !empty($available) ? $available[0] : null;
    }

    /**
     * Restore registration data from payment session for retry.
     */
    protected function restoreRegistrationDataFromPaymentSession(array $paymentSessionData): void
    {
        try {
            // Extract registration data from payment session
            $registrationData = [
                'event_id' => $paymentSessionData['event_id'],
                'attendee_name' => $paymentSessionData['attendee_name'],
                'attendee_email' => $paymentSessionData['attendee_email'],
                'attendee_phone' => $paymentSessionData['attendee_phone'],
                'total_amount' => $paymentSessionData['total_amount'],
                'expires_at' => $paymentSessionData['expires_at'] ?? now()->addMinutes(30),
                'created_at' => $paymentSessionData['created_at'] ?? now(),
                'payment_failed_at' => now(),
                'failed_payment_method' => 'card',
                'failed_transaction_id' => $paymentSessionData['invoice_id'] ?? null
            ];

            // Include optional fields if they exist
            $optionalFields = [
                'emergency_contact_name',
                'emergency_contact_phone',
                'dietary_restrictions',
                'accessibility_requirements',
                'ticket_selections',
                'special_requests'
            ];

            foreach ($optionalFields as $field) {
                if (isset($paymentSessionData[$field])) {
                    $registrationData[$field] = $paymentSessionData[$field];
                }
            }

            // Store registration data in session for retry
            session(['registration_data' => $registrationData]);

            Log::info('Registration data restored from payment session for retry', [
                'event_id' => $registrationData['event_id'],
                'email' => $registrationData['attendee_email'],
                'failed_transaction_id' => $registrationData['failed_transaction_id']
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to restore registration data from payment session', [
                'error' => $e->getMessage(),
                'payment_session_data' => $paymentSessionData
            ]);
        }
    }

    /**
     * Handle crypto payment timeout or failure.
     */
    public function handleCryptoPaymentFailure(Request $request, Event $event)
    {
        try {
            $errorMessage = $request->input('error', 'Cryptocurrency payment was not completed within the expected timeframe');
            $paymentAddress = $request->input('payment_address');
            $cryptocurrency = $request->input('cryptocurrency', 'unknown');

            Log::warning('Crypto payment failure handled', [
                'event_id' => $event->id,
                'cryptocurrency' => $cryptocurrency,
                'payment_address' => $paymentAddress,
                'error_message' => $errorMessage,
                'ip_address' => $request->ip()
            ]);

            // Check if registration data still exists
            $registrationData = session('registration_data');
            $hasValidRegistration = $registrationData &&
                is_array($registrationData) &&
                isset($registrationData['expires_at']) &&
                now()->lte($registrationData['expires_at']);

            if (!$hasValidRegistration) {
                Log::info('Crypto payment failure with expired registration data', [
                    'event_id' => $event->id,
                    'cryptocurrency' => $cryptocurrency
                ]);

                return redirect()->route('events.show', $event)
                    ->with('error', 'Your registration session has expired. Please start your registration again.');
            }

            // Get available payment options for fallback
            $paymentOptions = $this->getAvailablePaymentOptions();
            $availableMethods = array_filter($paymentOptions, fn($option) => $option['available']);

            // Suggest card payment as alternative
            $suggestedMethod = isset($availableMethods['card']) ? 'card' : null;

            // Store failure information for analytics
            $failureData = [
                'failed_method' => 'crypto',
                'failed_cryptocurrency' => $cryptocurrency,
                'error_message' => $errorMessage,
                'payment_address' => $paymentAddress,
                'failed_at' => now(),
                'retry_count' => ($registrationData['retry_count'] ?? 0) + 1
            ];

            // Update registration data with failure information
            $registrationData = array_merge($registrationData, $failureData);
            session(['registration_data' => $registrationData]);

            return view('payments.failed', [
                'event' => $event,
                'errorMessage' => $errorMessage,
                'transactionId' => $paymentAddress,
                'paymentMethod' => 'crypto',
                'suggestedMethod' => $suggestedMethod,
                'availableMethods' => $availableMethods,
                'registrationData' => $registrationData
            ]);
        } catch (\Exception $e) {
            Log::error('Error handling crypto payment failure', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('events.show', $event)
                ->with('error', 'Unable to process payment failure. Please contact support.');
        }
    }

    /**
     * Provide clear error messaging and next steps for payment failures.
     */
    public function getPaymentFailureGuidance(string $paymentMethod, string $errorMessage): array
    {
        $guidance = [
            'title' => 'Payment Failed',
            'message' => 'We were unable to process your payment.',
            'suggestions' => [],
            'next_steps' => []
        ];

        if ($paymentMethod === 'card') {
            $guidance['suggestions'] = [
                'Check that your card details are correct',
                'Ensure your card has sufficient funds',
                'Verify your card is enabled for online transactions',
                'Try a different card if available',
                'Contact your bank if the issue persists'
            ];

            $guidance['next_steps'] = [
                'Retry with the same card',
                'Try cryptocurrency payment instead',
                'Contact support for assistance'
            ];

            // Specific error message handling
            if (stripos($errorMessage, 'insufficient') !== false) {
                $guidance['message'] = 'Your card has insufficient funds for this transaction.';
                array_unshift($guidance['suggestions'], 'Add funds to your account or use a different card');
            } elseif (stripos($errorMessage, 'declined') !== false) {
                $guidance['message'] = 'Your card was declined by your bank.';
                array_unshift($guidance['suggestions'], 'Contact your bank to authorize online transactions');
            } elseif (stripos($errorMessage, 'expired') !== false) {
                $guidance['message'] = 'Your card has expired.';
                array_unshift($guidance['suggestions'], 'Use a card with a valid expiration date');
            }
        } elseif ($paymentMethod === 'crypto') {
            $guidance['suggestions'] = [
                'Ensure you sent the exact amount requested',
                'Verify you used the correct cryptocurrency',
                'Check that you sent to the correct address',
                'Allow time for blockchain confirmation',
                'Check your wallet for transaction status'
            ];

            $guidance['next_steps'] = [
                'Wait for blockchain confirmation (can take 10-60 minutes)',
                'Try card payment for instant confirmation',
                'Contact support with your transaction hash'
            ];

            if (stripos($errorMessage, 'timeout') !== false || stripos($errorMessage, 'timeframe') !== false) {
                $guidance['message'] = 'Payment was not received within the expected timeframe.';
                array_unshift($guidance['suggestions'], 'Check if your transaction is still pending in your wallet');
            }
        }

        return $guidance;
    }

    /**
     * Show demo UniPayment checkout page (for testing with fake credentials)
     */
    public function showDemoCheckout(Request $request, string $invoiceId)
    {
        $amount = $request->get('amount', 0);
        $currency = $request->get('currency', 'USD');

        return view('payments.demo-checkout', [
            'invoiceId' => $invoiceId,
            'amount' => $amount,
            'currency' => $currency
        ]);
    }

    /**
     * Complete demo UniPayment checkout (simulate successful payment)
     */
    public function completeDemoCheckout(Request $request, string $invoiceId)
    {
        // Simulate successful payment by calling the callback URL with query parameters
        $callbackUrl = route('payment.unipayment.callback') . '?' . http_build_query([
            'invoice_id' => $invoiceId,
            'status' => 'Confirmed',
            'order_id' => str_replace('DEMO_', '', explode('_', $invoiceId)[1] ?? 'unknown')
        ]);

        Log::info('Demo payment completed, redirecting to callback', [
            'invoice_id' => $invoiceId,
            'callback_url' => $callbackUrl
        ]);

        return redirect($callbackUrl);
    }
}
