# Design Document

## Overview

The webhook 404 error fix involves replacing the hardcoded webhook.site URL with a proper Laravel route that can handle UniPayment webhook notifications. The solution needs to work in both development (using ngrok) and production environments while maintaining security and reliability.

## Architecture

### Current Problem

- Hardcoded webhook.site URL: `https://webhook.site/8f7e4c2a-1b3d-4e5f-9a8b-2c3d4e5f6a7b`
- UniPayment sends notifications to webhook.site instead of Laravel app
- Laravel webhook handler at `/payment/unipayment/webhook` never receives notifications
- Results in 404 errors and failed payment confirmations

### Proposed Solution

- Replace hardcoded URL with dynamic Laravel route URL
- Use environment-specific webhook URL generation
- Implement proper webhook URL validation
- Add fallback mechanisms for webhook failures

## Components and Interfaces

### 1. Webhook URL Generator

```php
class WebhookUrlGenerator
{
    public function generateUniPaymentWebhookUrl(): string
    {
        // Development: Use ngrok URL if available
        // Production: Use APP_URL
        // Fallback: Disable webhooks and use polling
    }

    public function isWebhookAccessible(string $url): bool
    {
        // Test webhook URL accessibility
    }
}
```

### 2. Environment Detection

- **Development**: Check for ngrok tunnel or local tunnel
- **Production**: Use configured APP_URL
- **Testing**: Use test webhook endpoints

### 3. Webhook Handler Enhancement

- Improve error handling and logging
- Add webhook signature validation
- Implement idempotency checks
- Add webhook retry mechanism

### 4. Configuration Management

- Add webhook URL configuration to UniPayment settings
- Environment-specific webhook URL resolution
- Webhook URL validation and testing

## Data Models

### UniPayment Settings Enhancement

```php
// Add to existing UniPaymentSetting model
'webhook_url' => 'string|nullable',
'webhook_enabled' => 'boolean|default:true',
'webhook_secret' => 'string|nullable',
'last_webhook_test' => 'timestamp|nullable',
'webhook_test_status' => 'enum:success,failed,pending|nullable'
```

### Webhook Log Model (Optional)

```php
class WebhookLog extends Model
{
    protected $fillable = [
        'provider',
        'event_type',
        'payload',
        'signature',
        'status',
        'response',
        'processed_at',
        'error_message'
    ];
}
```

## Error Handling

### Webhook URL Generation Errors

1. **No accessible URL**: Disable webhooks, use callback-only mode
2. **Invalid URL format**: Log error, use fallback URL
3. **Network unreachable**: Provide clear error message to admin

### Webhook Processing Errors

1. **Invalid signature**: Return 401, log security event
2. **Malformed payload**: Return 400, log parsing error
3. **Processing failure**: Return 500, queue for retry
4. **Duplicate webhook**: Return 200, log duplicate event

### Fallback Mechanisms

1. **Webhook failure**: Use callback URL for payment confirmation
2. **Network issues**: Implement webhook retry with exponential backoff
3. **Service unavailable**: Queue webhooks for later processing

## Testing Strategy

### Unit Tests

- Webhook URL generation for different environments
- Webhook signature validation
- Payload parsing and validation
- Error handling scenarios

### Integration Tests

- End-to-end webhook flow with test UniPayment notifications
- Environment-specific URL generation
- Webhook security validation
- Fallback mechanism testing

### Manual Testing

- Test webhook URL accessibility from external services
- Verify webhook processing in development environment
- Test production webhook configuration
- Validate error handling and logging

## Implementation Phases

### Phase 1: Webhook URL Generation

- Create WebhookUrlGenerator service
- Add environment detection logic
- Update PaymentController to use dynamic URLs
- Add webhook URL validation

### Phase 2: Enhanced Webhook Handling

- Improve webhook signature validation
- Add comprehensive error handling
- Implement webhook logging
- Add idempotency checks

### Phase 3: Configuration and Testing

- Add webhook settings to admin panel
- Implement webhook URL testing
- Add webhook status monitoring
- Create webhook troubleshooting tools

### Phase 4: Production Deployment

- Configure production webhook URLs
- Test webhook accessibility
- Monitor webhook processing
- Document webhook setup procedures
