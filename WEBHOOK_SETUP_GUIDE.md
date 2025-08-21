# Webhook Setup Guide

This guide provides comprehensive instructions for setting up and configuring webhooks for the Leadership Summit application across different environments.

## Table of Contents

1. [Overview](#overview)
2. [Environment-Specific Setup](#environment-specific-setup)
3. [Configuration](#configuration)
4. [Testing and Validation](#testing-and-validation)
5. [Troubleshooting](#troubleshooting)
6. [Monitoring](#monitoring)

## Overview

The Leadership Summit application uses webhooks to receive real-time payment notifications from UniPayment. Proper webhook configuration is essential for:

- Automatic payment confirmation
- Real-time registration updates
- Reliable payment processing
- User experience optimization

### Webhook Flow

```
UniPayment → Webhook URL → Laravel Application → Payment Processing → User Notification
```

## Environment-Specific Setup

### Development Environment

#### Prerequisites

- ngrok or similar tunneling service
- Local Laravel application running
- UniPayment sandbox account

#### Setup Steps

1. **Install ngrok:**

```bash
# macOS
brew install ngrok

# Linux
wget https://bin.equinox.io/c/4VmDzA7iaHb/ngrok-stable-linux-amd64.zip
unzip ngrok-stable-linux-amd64.zip
sudo mv ngrok /usr/local/bin/
```

2. **Start ngrok tunnel:**

```bash
# Start your Laravel application first
php artisan serve

# In another terminal, start ngrok
ngrok http 8000
```

3. **Configure webhook URL:**

```bash
# Copy the ngrok HTTPS URL (e.g., https://abc123.ngrok.io)
# Add to your .env file
WEBHOOK_BASE_URL=https://abc123.ngrok.io
APP_URL=https://abc123.ngrok.io
```

4. **Test webhook accessibility:**

```bash
# Test the webhook endpoint
curl -X POST https://abc123.ngrok.io/payment/unipayment/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": "webhook"}'
```

#### Alternative: Local Tunnel Services

**Using localtunnel:**

```bash
npm install -g localtunnel
lt --port 8000 --subdomain your-app-name
```

**Using serveo:**

```bash
ssh -R 80:localhost:8000 serveo.net
```

### Staging Environment

#### Prerequisites

- Staging server with public IP
- SSL certificate configured
- Domain name pointing to server

#### Setup Steps

1. **Configure environment:**

```bash
# Set staging URL in .env
APP_URL=https://staging.yourdomain.com
WEBHOOK_BASE_URL=https://staging.yourdomain.com
```

2. **Verify SSL certificate:**

```bash
# Test SSL configuration
curl -I https://staging.yourdomain.com
openssl s_client -connect staging.yourdomain.com:443 -servername staging.yourdomain.com
```

3. **Test webhook endpoint:**

```bash
curl -X POST https://staging.yourdomain.com/payment/unipayment/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": "webhook"}'
```

### Production Environment

#### Prerequisites

- Production server with public IP
- Valid SSL certificate
- Production domain configured
- UniPayment production account

#### Setup Steps

1. **Configure production environment:**

```bash
# Set production URL in .env
APP_URL=https://yourdomain.com
WEBHOOK_BASE_URL=https://yourdomain.com
APP_ENV=production
APP_DEBUG=false
```

2. **Verify webhook endpoint:**

```bash
# Test webhook accessibility
curl -X POST https://yourdomain.com/payment/unipayment/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": "webhook"}'
```

3. **Configure firewall (if applicable):**

```bash
# Allow HTTP/HTTPS traffic
sudo ufw allow 80
sudo ufw allow 443
```

## Configuration

### Environment Variables

Add these variables to your `.env` file:

```env
# Webhook Configuration
WEBHOOK_BASE_URL=https://yourdomain.com
WEBHOOK_ENABLED=true
WEBHOOK_TIMEOUT=30
WEBHOOK_RETRY_ATTEMPTS=3

# UniPayment Configuration
UNIPAYMENT_APP_ID=your_app_id
UNIPAYMENT_API_KEY=your_api_key
UNIPAYMENT_WEBHOOK_SECRET=your_webhook_secret
UNIPAYMENT_ENVIRONMENT=sandbox  # or 'production'
```

### Database Configuration

The webhook system uses the following database tables:

- `unipayment_settings` - Webhook configuration
- `payment_transactions` - Payment tracking
- `payments` - Payment records

Ensure migrations are run:

```bash
php artisan migrate
```

### Admin Panel Configuration

1. **Access admin panel:**

   - Navigate to `/admin/unipayment`
   - Login with admin credentials

2. **Configure webhook settings:**

   - Set webhook URL
   - Configure retry settings
   - Enable/disable webhooks
   - Set webhook secret

3. **Test webhook configuration:**
   - Use the "Test Webhook" button
   - Verify connectivity
   - Check response codes

## Testing and Validation

### Manual Testing

#### Test Webhook Endpoint

```bash
# Basic connectivity test
curl -X POST https://yourdomain.com/payment/unipayment/webhook \
  -H "Content-Type: application/json" \
  -H "User-Agent: UniPayment-Webhook/1.0" \
  -d '{
    "event_type": "invoice_paid",
    "invoice_id": "test_invoice_123",
    "order_id": "test_order_456",
    "status": "Paid",
    "amount": "100.00",
    "currency": "USD"
  }'
```

#### Test with Signature Validation

```bash
# Generate test signature (use your webhook secret)
WEBHOOK_SECRET="your_webhook_secret"
PAYLOAD='{"event_type":"invoice_paid","invoice_id":"test_123"}'
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$WEBHOOK_SECRET" -binary | base64)

curl -X POST https://yourdomain.com/payment/unipayment/webhook \
  -H "Content-Type: application/json" \
  -H "X-UniPayment-Signature: $SIGNATURE" \
  -d "$PAYLOAD"
```

### Automated Testing

#### Laravel Artisan Commands

```bash
# Test webhook URL generation
php artisan webhook:test-url

# Test webhook connectivity
php artisan webhook:test-connectivity

# Monitor webhook status
php artisan webhook:monitor
```

#### PHPUnit Tests

Run the webhook test suite:

```bash
# Run all webhook tests
php artisan test --filter=Webhook

# Run specific test classes
php artisan test tests/Feature/WebhookProcessingFlowTest.php
php artisan test tests/Feature/WebhookSignatureValidationTest.php
php artisan test tests/Feature/WebhookErrorHandlingTest.php
```

### Integration Testing

#### UniPayment Sandbox Testing

1. **Create test invoice:**

   - Use UniPayment sandbox dashboard
   - Set webhook URL to your endpoint
   - Create test payment

2. **Simulate payment:**

   - Complete payment in sandbox
   - Monitor webhook delivery
   - Verify payment processing

3. **Check logs:**

```bash
# Monitor webhook processing
tail -f storage/logs/laravel.log | grep -i webhook

# Check specific webhook events
grep "webhook_received" storage/logs/laravel.log
```

## Troubleshooting

### Common Issues

#### 1. Webhook 404 Errors

**Symptoms:**

- UniPayment reports 404 errors
- Webhooks not received
- Payment confirmations failing

**Diagnosis:**

```bash
# Check route registration
php artisan route:list | grep webhook

# Test endpoint accessibility
curl -I https://yourdomain.com/payment/unipayment/webhook

# Check web server configuration
nginx -t  # for Nginx
apache2ctl configtest  # for Apache
```

**Solutions:**

1. **Verify route configuration:**

```php
// In routes/web.php
Route::post('/payment/unipayment/webhook', [PaymentController::class, 'handleUniPaymentWebhook'])
    ->name('unipayment.webhook')
    ->middleware(['webhook.auth']);
```

2. **Check web server configuration:**

For Nginx:

```nginx
location /payment/unipayment/webhook {
    try_files $uri $uri/ /index.php?$query_string;
}
```

For Apache:

```apache
RewriteRule ^payment/unipayment/webhook$ index.php [L]
```

3. **Verify SSL configuration:**

```bash
# Test SSL certificate
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com

# Check certificate chain
curl -I https://yourdomain.com
```

#### 2. Signature Validation Failures

**Symptoms:**

- Webhooks rejected with 401 errors
- "Invalid signature" log entries
- Legitimate webhooks being blocked

**Diagnosis:**

```bash
# Check webhook secret configuration
grep WEBHOOK_SECRET .env

# Review signature validation logs
grep "signature_validation" storage/logs/laravel.log
```

**Solutions:**

1. **Verify webhook secret:**

   - Check UniPayment dashboard settings
   - Ensure secret matches .env configuration
   - Regenerate secret if necessary

2. **Debug signature calculation:**

```php
// Add to webhook handler for debugging
Log::info('Webhook signature debug', [
    'received_signature' => $request->header('X-UniPayment-Signature'),
    'calculated_signature' => hash_hmac('sha256', $request->getContent(), config('unipayment.webhook_secret')),
    'payload' => $request->getContent()
]);
```

#### 3. Timeout Issues

**Symptoms:**

- Webhook processing takes too long
- UniPayment retries webhooks
- Duplicate processing

**Diagnosis:**

```bash
# Check webhook processing time
grep "webhook_processing_time" storage/logs/laravel.log

# Monitor system resources
top
iostat 1
```

**Solutions:**

1. **Optimize webhook processing:**

```php
// Use queued jobs for heavy processing
dispatch(new ProcessPaymentWebhook($webhookData));
```

2. **Increase timeout limits:**

```php
// In webhook handler
set_time_limit(60);
ini_set('max_execution_time', 60);
```

3. **Implement idempotency:**

```php
// Check for duplicate processing
if (PaymentTransaction::where('webhook_id', $webhookId)->exists()) {
    return response()->json(['status' => 'already_processed'], 200);
}
```

#### 4. Network Connectivity Issues

**Symptoms:**

- Webhooks not reaching server
- Intermittent delivery failures
- Firewall blocking requests

**Diagnosis:**

```bash
# Check firewall rules
sudo ufw status
iptables -L

# Test connectivity from external source
curl -X POST https://yourdomain.com/payment/unipayment/webhook \
  --connect-timeout 10 \
  --max-time 30

# Check DNS resolution
nslookup yourdomain.com
dig yourdomain.com
```

**Solutions:**

1. **Configure firewall:**

```bash
# Allow webhook traffic
sudo ufw allow from 0.0.0.0/0 to any port 443
sudo ufw allow from 0.0.0.0/0 to any port 80
```

2. **Check load balancer configuration:**
   - Ensure webhook endpoints are properly routed
   - Verify health checks don't interfere
   - Configure sticky sessions if needed

### Debug Mode

Enable debug mode for detailed webhook logging:

```bash
# Add to .env
WEBHOOK_DEBUG=true
LOG_LEVEL=debug

# Clear configuration cache
php artisan config:cache
```

This will log detailed information about:

- Incoming webhook requests
- Signature validation process
- Payment processing steps
- Response generation

## Monitoring

### Webhook Status Dashboard

Access the webhook monitoring dashboard at `/admin/webhooks/monitor`:

- Real-time webhook status
- Success/failure rates
- Response time metrics
- Error analysis

### Log Monitoring

#### Key Log Entries

Monitor these log entries for webhook health:

```bash
# Successful webhook processing
grep "webhook_processed_successfully" storage/logs/laravel.log

# Failed webhook processing
grep "webhook_processing_failed" storage/logs/laravel.log

# Signature validation failures
grep "webhook_signature_invalid" storage/logs/laravel.log

# Timeout issues
grep "webhook_timeout" storage/logs/laravel.log
```

#### Automated Monitoring

Set up automated monitoring with:

```bash
# Create monitoring script
cat > monitor_webhooks.sh << 'EOF'
#!/bin/bash
WEBHOOK_ERRORS=$(grep -c "webhook_processing_failed" storage/logs/laravel.log)
if [ $WEBHOOK_ERRORS -gt 10 ]; then
    echo "High webhook error rate detected: $WEBHOOK_ERRORS failures"
    # Send alert email or notification
fi
EOF

# Add to crontab
echo "*/5 * * * * /path/to/monitor_webhooks.sh" | crontab -
```

### Performance Metrics

Track these webhook performance metrics:

- **Response Time**: Average webhook processing time
- **Success Rate**: Percentage of successfully processed webhooks
- **Error Rate**: Percentage of failed webhook processing
- **Retry Rate**: Percentage of webhooks requiring retries

### Alerting

Set up alerts for:

- Webhook endpoint downtime
- High error rates (>5%)
- Slow response times (>5 seconds)
- Signature validation failures

## Best Practices

### Security

1. **Always validate signatures:**

   - Use HMAC-SHA256 validation
   - Implement timing-safe comparison
   - Log validation failures

2. **Implement rate limiting:**

   - Limit webhook requests per IP
   - Prevent abuse and DoS attacks
   - Use exponential backoff for retries

3. **Use HTTPS only:**
   - Never accept webhooks over HTTP
   - Validate SSL certificates
   - Use strong cipher suites

### Reliability

1. **Implement idempotency:**

   - Check for duplicate webhooks
   - Use unique webhook IDs
   - Handle retries gracefully

2. **Use queued processing:**

   - Process webhooks asynchronously
   - Prevent timeout issues
   - Enable horizontal scaling

3. **Implement fallback mechanisms:**
   - Polling for missed webhooks
   - Manual payment verification
   - Admin notification systems

### Performance

1. **Optimize response time:**

   - Return 200 status quickly
   - Process heavy operations asynchronously
   - Use database indexing

2. **Monitor resource usage:**
   - Track memory consumption
   - Monitor CPU usage
   - Scale resources as needed

## Support

For webhook-related issues:

1. **Check this guide first**
2. **Review application logs**
3. **Test webhook connectivity**
4. **Contact technical support with:**
   - Error logs
   - Webhook configuration
   - Test results
   - Environment details

---

**Last Updated:** $(date)
**Version:** 1.0
