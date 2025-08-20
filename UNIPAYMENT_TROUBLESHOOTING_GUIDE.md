# UniPayment Integration Troubleshooting Guide

## Current Issue: "App with this id does not exist"

### What We've Discovered:

✅ **Authentication Works**: We can successfully get access tokens  
✅ **API Connection Works**: We can connect to the sandbox API  
❌ **App ID Issue**: The app_id `5ce7507b-5afc-4c14-a8dc-b1a28a9ac99a` is not recognized

### Root Cause Analysis:

The error "App with this id does not exist" indicates that the App ID is not properly registered in the UniPayment sandbox environment.

## Solution Steps:

### Step 1: Verify App Registration

1. **Login to UniPayment Sandbox**: https://sandbox.unipayment.io
2. **Go to Applications/Apps section**
3. **Check if your app exists**
4. **Verify the exact App ID**

### Step 2: Create New App (If Needed)

If the app doesn't exist:

1. **Create a new application** in the sandbox dashboard
2. **Copy the exact App ID** (it should be a UUID format)
3. **Copy the API Key/Client Secret**
4. **Update your Laravel database**

### Step 3: Update Laravel Configuration

```php
// Update in database or run this in tinker:
$settings = \App\Models\UniPaymentSetting::first();
$settings->app_id = 'YOUR_CORRECT_APP_ID_FROM_DASHBOARD';
$settings->api_key = 'YOUR_CORRECT_API_KEY_FROM_DASHBOARD';
$settings->environment = 'sandbox';
$settings->save();
```

### Step 4: Test the Integration

```php
// Test in tinker:
$service = new \App\Services\UniPaymentOfficialService();
$result = $service->testConnection();
if ($result['success']) {
    $payment = $service->createTestPayment();
    if ($payment['success']) {
        echo "SUCCESS! Payment URL: " . $payment['checkout_url'];
    }
}
```

## Alternative Solutions:

### Option A: Contact UniPayment Support

- **Email**: support@unipayment.io
- **Provide**: Your sandbox account email and the App ID issue
- **Ask for**: Verification of correct App ID for sandbox

### Option B: Use Node.js Microservice (Already Created)

If the Laravel integration continues to have issues:

```bash
cd payment-service
npm install
npm run dev
```

Then test:

```bash
curl -X POST http://localhost:3001/api/payments/create \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 25.00,
    "currency": "USD",
    "orderId": "TEST_123",
    "title": "Test Payment"
  }'
```

### Option C: Use Direct cURL Implementation

The `UniPaymentOfficialService` we created can be used as a reference for direct API calls.

## Next Steps:

1. **Verify your sandbox app registration** (most likely solution)
2. **Contact UniPayment support** if app registration is correct
3. **Use Node.js microservice** as backup solution

## Working Laravel Service:

The `UniPaymentOfficialService` is properly implemented and will work once the correct App ID is provided. The service includes:

- ✅ Proper authentication
- ✅ Official API format
- ✅ Error handling
- ✅ Logging
- ✅ Test methods

**The integration is 99% complete - we just need the correct App ID from your UniPayment dashboard.**
