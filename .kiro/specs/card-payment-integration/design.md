# Design Document

## Overview

The card payment integration adds UniPayment API support to the existing event registration system, providing users with both cryptocurrency and card payment options. The design maintains the current direct registration flow while adding a secure card payment pathway that integrates seamlessly with the existing duplicate prevention and admin management systems.

## Architecture

### Enhanced Payment Flow Architecture

```
Event Page → Direct Registration Form → Payment Selection → Card/Crypto Payment → Confirmation
     ↓                ↓                      ↓                    ↓                ↓
  No Auth         Form Validation      Payment Method      UniPayment API      Registration
  Required        + Data Storage        Selection          or Crypto Flow       Confirmed
```

### Payment Method Selection Flow

```
Registration Form Submission
           ↓
    Payment Selection Page
           ↓
    ┌─────────────────┐
    │  Card Payment   │ ──→ UniPayment Checkout ──→ Success/Failure Callback
    └─────────────────┘
           │
    ┌─────────────────┐
    │ Crypto Payment  │ ──→ Existing Crypto Flow ──→ Manual Confirmation
    └─────────────────┘
           ↓
    Registration Confirmation
```

## Components and Interfaces

### 1. Enhanced Payment Selection Component

**Location**: `resources/views/payments/selection.blade.php`

**Payment Options Display**:

```php
// Payment Method Cards
- Card Payment Option
  - Visa, Mastercard, American Express icons
  - "Pay with Card" button
  - Processing fee information (if applicable)
  - Estimated processing time

- Cryptocurrency Payment Option
  - Existing crypto payment flow
  - "Pay with Crypto" button
  - Available cryptocurrency options
  - Manual confirmation process
```

### 2. UniPayment Service Integration

**Location**: `app/Services/UniPaymentService.php`

**Core Methods**:

```php
class UniPaymentService
{
    public function createPayment($amount, $currency, $orderId, $notifyUrl, $redirectUrl)
    // Create payment request with UniPayment API

    public function verifyPayment($paymentId)
    // Verify payment status with UniPayment API

    public function handleCallback($callbackData)
    // Process UniPayment callback/webhook data

    public function getPaymentStatus($paymentId)
    // Check current payment status

    public function refundPayment($paymentId, $amount)
    // Process refund through UniPayment API
}
```

### 3. Enhanced Payment Controller

**Location**: `app/Http/Controllers/PaymentController.php`

**New Methods**:

```php
public function showPaymentSelection()
// Display payment method selection page with registration summary

public function processCardPayment(Request $request)
// Initialize UniPayment checkout and redirect user

public function handleUniPaymentCallback(Request $request)
// Process UniPayment success/failure callbacks

public function handleUniPaymentWebhook(Request $request)
// Handle UniPayment webhook notifications

public function verifyCardPayment($paymentId)
// Verify payment completion and update registration status
```

### 4. UniPayment Configuration Management

**Location**: `app/Http/Controllers/Admin/UniPaymentController.php`

**Configuration Methods**:

```php
public function showSettings()
// Display UniPayment configuration form

public function updateSettings(Request $request)
// Save and validate UniPayment API credentials

public function testConnection()
// Test UniPayment API connectivity

public function viewTransactions()
// Display UniPayment transaction history
```

## Data Models

### Enhanced Registration Model

**Location**: `app/Models/Registration.php`

**Additional Fields**:

```php
protected $fillable = [
    // ... existing fields
    'payment_method', // 'card' or 'crypto'
    'payment_provider', // 'unipayment' or 'crypto'
    'transaction_id', // UniPayment transaction ID
    'payment_amount', // Actual amount paid
    'payment_currency', // Currency used for payment
    'payment_fee', // Processing fee charged
    'payment_completed_at', // When payment was confirmed
    'refund_amount', // Amount refunded (if any)
    'refund_reason', // Reason for refund
    'refunded_at' // When refund was processed
];

protected $casts = [
    // ... existing casts
    'payment_completed_at' => 'datetime',
    'refunded_at' => 'datetime'
];

// New methods
public function isCardPayment() { return $this->payment_method === 'card'; }
public function isCryptoPayment() { return $this->payment_method === 'crypto'; }
public function isRefunded() { return !is_null($this->refunded_at); }
```

### UniPayment Settings Model

**Location**: `app/Models/UniPaymentSetting.php`

```php
class UniPaymentSetting extends Model
{
    protected $fillable = [
        'app_id',
        'api_key',
        'environment', // 'sandbox' or 'production'
        'webhook_secret',
        'is_enabled',
        'supported_currencies', // JSON array
        'processing_fee_percentage',
        'minimum_amount',
        'maximum_amount'
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'supported_currencies' => 'array',
        'processing_fee_percentage' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'maximum_amount' => 'decimal:2'
    ];

    public static function isConfigured()
    {
        $settings = self::first();
        return $settings && $settings->app_id && $settings->api_key && $settings->is_enabled;
    }
}
```

### Payment Transaction Model

**Location**: `app/Models/PaymentTransaction.php`

```php
class PaymentTransaction extends Model
{
    protected $fillable = [
        'registration_id',
        'provider', // 'unipayment'
        'transaction_id',
        'payment_method', // 'card'
        'amount',
        'currency',
        'fee',
        'status', // 'pending', 'completed', 'failed', 'refunded'
        'provider_response', // JSON response from UniPayment
        'callback_data', // JSON callback data
        'processed_at'
    ];

    protected $casts = [
        'provider_response' => 'array',
        'callback_data' => 'array',
        'processed_at' => 'datetime'
    ];

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }
}
```

## Database Schema Changes

### Registration Table Updates

```sql
ALTER TABLE registrations ADD COLUMN payment_method VARCHAR(20) DEFAULT 'crypto';
ALTER TABLE registrations ADD COLUMN payment_provider VARCHAR(50);
ALTER TABLE registrations ADD COLUMN transaction_id VARCHAR(255);
ALTER TABLE registrations ADD COLUMN payment_amount DECIMAL(10,2);
ALTER TABLE registrations ADD COLUMN payment_currency VARCHAR(10);
ALTER TABLE registrations ADD COLUMN payment_fee DECIMAL(10,2);
ALTER TABLE registrations ADD COLUMN payment_completed_at TIMESTAMP NULL;
ALTER TABLE registrations ADD COLUMN refund_amount DECIMAL(10,2);
ALTER TABLE registrations ADD COLUMN refund_reason TEXT;
ALTER TABLE registrations ADD COLUMN refunded_at TIMESTAMP NULL;
```

### New Tables

```sql
-- UniPayment Settings Table
CREATE TABLE unipayment_settings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    app_id VARCHAR(255) NOT NULL,
    api_key TEXT NOT NULL,
    environment ENUM('sandbox', 'production') DEFAULT 'sandbox',
    webhook_secret VARCHAR(255),
    is_enabled BOOLEAN DEFAULT FALSE,
    supported_currencies JSON,
    processing_fee_percentage DECIMAL(5,2) DEFAULT 0.00,
    minimum_amount DECIMAL(10,2) DEFAULT 1.00,
    maximum_amount DECIMAL(10,2) DEFAULT 10000.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Payment Transactions Table
CREATE TABLE payment_transactions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    registration_id BIGINT NOT NULL,
    provider VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(255) NOT NULL,
    payment_method VARCHAR(20) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) NOT NULL,
    fee DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    provider_response JSON,
    callback_data JSON,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE,
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_status (status)
);
```

## UniPayment API Integration

### SDK Installation and Configuration

**Composer Installation**:

```bash
composer require unipayment/client
```

**Environment Configuration**:

```env
UNIPAYMENT_APP_ID=your_app_id
UNIPAYMENT_API_KEY=your_api_key
UNIPAYMENT_ENVIRONMENT=sandbox # or production
UNIPAYMENT_WEBHOOK_SECRET=your_webhook_secret
```

### Payment Flow Implementation

**Payment Creation**:

```php
use UniPayment\Client\UniPaymentClient;

public function createCardPayment($registrationData, $amount)
{
    $client = new UniPaymentClient([
        'app_id' => config('unipayment.app_id'),
        'api_key' => config('unipayment.api_key'),
        'environment' => config('unipayment.environment')
    ]);

    $paymentData = [
        'app_id' => config('unipayment.app_id'),
        'pricing' => [
            'local' => [
                'amount' => $amount,
                'currency' => 'USD'
            ]
        ],
        'order_id' => 'REG_' . $registrationData['id'] . '_' . time(),
        'title' => 'Event Registration - ' . $registrationData['event_name'],
        'description' => 'Registration for ' . $registrationData['attendee_name'],
        'lang' => 'en',
        'ext_args' => json_encode([
            'registration_id' => $registrationData['id'],
            'event_id' => $registrationData['event_id']
        ]),
        'notify_url' => route('payment.unipayment.webhook'),
        'redirect_url' => route('payment.unipayment.callback'),
        'cancel_url' => route('payment.selection')
    ];

    return $client->createInvoice($paymentData);
}
```

## Error Handling

### Payment Processing Errors

```php
// UniPayment API Errors
try {
    $invoice = $this->uniPaymentService->createPayment($paymentData);
} catch (UniPaymentException $e) {
    Log::error('UniPayment API Error: ' . $e->getMessage());
    return redirect()->back()->with('error', 'Payment processing is temporarily unavailable.');
} catch (Exception $e) {
    Log::error('Payment Creation Error: ' . $e->getMessage());
    return redirect()->back()->with('error', 'Unable to process payment. Please try again.');
}

// Payment Verification Errors
if (!$this->uniPaymentService->verifyPayment($paymentId)) {
    Log::warning('Payment verification failed for: ' . $paymentId);
    return redirect()->route('payment.failed')->with('error', 'Payment verification failed.');
}
```

### Callback and Webhook Handling

```php
public function handleUniPaymentCallback(Request $request)
{
    try {
        $paymentId = $request->input('invoice_id');
        $status = $request->input('status');

        if ($status === 'Confirmed') {
            $this->processSuccessfulPayment($paymentId);
            return redirect()->route('registration.success');
        } else {
            return redirect()->route('payment.failed')->with('error', 'Payment was not completed.');
        }
    } catch (Exception $e) {
        Log::error('Callback handling error: ' . $e->getMessage());
        return redirect()->route('payment.failed')->with('error', 'Payment processing error.');
    }
}
```

## Security Considerations

### API Security

- Store API credentials encrypted in database
- Use environment-specific API endpoints
- Implement webhook signature verification
- Rate limit payment creation requests

### Payment Data Security

- Never store sensitive card information
- Use HTTPS for all payment-related communications
- Implement CSRF protection on payment forms
- Log all payment transactions for audit purposes

### Webhook Security

```php
public function handleUniPaymentWebhook(Request $request)
{
    $signature = $request->header('X-UniPayment-Signature');
    $payload = $request->getContent();

    if (!$this->verifyWebhookSignature($signature, $payload)) {
        Log::warning('Invalid webhook signature received');
        return response('Unauthorized', 401);
    }

    // Process webhook data
    $this->processWebhookData(json_decode($payload, true));

    return response('OK', 200);
}

private function verifyWebhookSignature($signature, $payload)
{
    $expectedSignature = hash_hmac('sha256', $payload, config('unipayment.webhook_secret'));
    return hash_equals($signature, $expectedSignature);
}
```

## Testing Strategy

### Unit Tests

- UniPayment service methods
- Payment creation and verification logic
- Webhook signature verification
- Registration status updates

### Integration Tests

- Complete card payment flow
- Payment callback handling
- Webhook processing
- Admin configuration management

### API Testing

- UniPayment API connectivity
- Payment creation and status checking
- Error handling for API failures
- Webhook endpoint security

## User Interface Design

### Payment Selection Page Layout

```
┌─────────────────────────────────────┐
│ Choose Payment Method               │
├─────────────────────────────────────┤
│ Registration Summary                │
│ • Event: [Event Name]               │
│ • Attendee: [Full Name]             │
│ • Total: $[Amount]                  │
├─────────────────────────────────────┤
│ ┌─────────────────┐ ┌─────────────┐ │
│ │  💳 Pay with    │ │ ₿ Pay with  │ │
│ │     Card        │ │   Crypto    │ │
│ │                 │ │             │ │
│ │ Visa, MC, Amex  │ │ BTC, ETH    │ │
│ │ Instant confirm │ │ Manual conf │ │
│ │                 │ │             │ │
│ │ [Select Card]   │ │[Select Crypto]│ │
│ └─────────────────┘ └─────────────┘ │
└─────────────────────────────────────┘
```

### Admin UniPayment Settings

```
┌─────────────────────────────────────┐
│ UniPayment Configuration            │
├─────────────────────────────────────┤
│ API Credentials                     │
│ • App ID: [________________]        │
│ • API Key: [________________]       │
│ • Environment: [Sandbox ▼]         │
│ • Webhook Secret: [____________]    │
├─────────────────────────────────────┤
│ Payment Settings                    │
│ • Processing Fee: [2.9]%            │
│ • Min Amount: $[1.00]               │
│ • Max Amount: $[10000.00]           │
│ • Supported Currencies: [USD,EUR]   │
├─────────────────────────────────────┤
│ ☑ Enable Card Payments             │
│                                     │
│ [Test Connection] [Save Settings]   │
└─────────────────────────────────────┘
```

## Admin Features

### Payment Management Dashboard

- View all card payment transactions
- Filter by payment status and date range
- Process refunds through UniPayment API
- Export payment reports
- Monitor failed payment attempts

### Transaction Reconciliation

- Match UniPayment transactions with registrations
- Identify discrepancies in payment amounts
- Handle partial refunds and adjustments
- Generate financial reports for accounting

## Performance Considerations

### Caching Strategy

- Cache UniPayment API responses for status checks
- Store payment settings in application cache
- Implement Redis for session data during payment flow

### Database Optimization

- Index payment transaction tables for quick lookups
- Partition large transaction tables by date
- Implement soft deletes for audit trail maintenance

### API Rate Limiting

- Implement exponential backoff for API retries
- Queue webhook processing for high-volume events
- Cache payment status to reduce API calls
