# Node.js Payment Service Setup

## Why Node.js?

The Laravel UniPayment SDK has persistent issues with parameter validation. A Node.js microservice provides:

- ✅ Direct HTTP API calls (no SDK issues)
- ✅ Better error handling
- ✅ Easier debugging
- ✅ Microservice architecture
- ✅ Can be deployed separately

## Setup Instructions

### 1. Install Node.js Dependencies

```bash
cd payment-service
npm install
```

### 2. Start the Payment Service

```bash
# Development mode (auto-restart)
npm run dev

# Or production mode
npm start
```

The service will run on `http://localhost:3001`

### 3. Test the Service

```bash
# Health check
curl http://localhost:3001/health

# Test payment creation
curl -X POST http://localhost:3001/api/payments/create \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 25.00,
    "currency": "USD",
    "orderId": "TEST_123",
    "title": "Test Payment",
    "description": "Test payment description",
    "notifyUrl": "https://webhook.site/test",
    "redirectUrl": "https://example.com/success"
  }'
```

### 4. Update Laravel to Use Node.js Service

Add to your `.env`:

```env
PAYMENT_SERVICE_URL=http://localhost:3001
```

### 5. Update PaymentController

Replace the UniPayment service calls with:

```php
// Instead of:
// $response = $this->uniPaymentService->createPayment(...)

// Use:
$nodeService = new \App\Services\NodePaymentService();
$response = $nodeService->createPayment(
    $amount,
    $currency,
    $orderId,
    $title,
    $description,
    $notifyUrl,
    $redirectUrl
);
```

## Production Deployment

### Option 1: Same Server

- Run Node.js service on port 3001
- Use PM2 for process management:

```bash
npm install -g pm2
pm2 start server.js --name "payment-service"
pm2 startup
pm2 save
```

### Option 2: Separate Server

- Deploy Node.js service to separate server/container
- Update `PAYMENT_SERVICE_URL` in Laravel `.env`
- Use HTTPS in production

## Advantages of This Approach

1. **Reliability**: Direct API calls, no SDK issues
2. **Debugging**: Clear error messages and logs
3. **Scalability**: Can scale payment service independently
4. **Maintenance**: Easier to update and maintain
5. **Testing**: Can test payment service independently

## Testing the Integration

```php
// Test in Laravel tinker
$service = new \App\Services\NodePaymentService();

// Check if service is healthy
if ($service->isServiceHealthy()) {
    echo "Payment service is running!";

    // Create a test payment
    $result = $service->createPayment(
        25.00,
        'USD',
        'TEST_' . time(),
        'Test Payment',
        'Test Description',
        'https://webhook.site/test',
        'https://example.com/success'
    );

    echo "Payment created: " . $result['checkout_url'];
}
```

This approach should resolve all the UniPayment integration issues you've been experiencing.
