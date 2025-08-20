# Implementation Plan

- [x] 1. Create webhook URL generator service

  - Create WebhookUrlGenerator service class with environment detection
  - Implement dynamic webhook URL generation for development and production
  - Add webhook URL accessibility testing functionality
  - _Requirements: 3.1, 3.2, 3.3_

- [x] 2. Update PaymentController to use dynamic webhook URLs

  - Replace hardcoded webhook.site URL with dynamic URL generation
  - Add webhook URL validation before creating UniPayment invoices
  - Implement fallback handling when webhook URL is not accessible
  - _Requirements: 1.1, 3.3_

- [x] 3. Enhance webhook signature validation

  - Improve existing webhook signature validation in UniPaymentOfficialService
  - Add proper error handling for invalid signatures
  - Implement security logging for webhook authentication failures
  - _Requirements: 4.1, 4.2_

- [x] 4. Add comprehensive webhook error handling

  - Enhance handleUniPaymentWebhook method with proper HTTP status codes
  - Add detailed logging for webhook processing steps
  - Implement idempotency checks to prevent duplicate processing
  - _Requirements: 1.2, 1.3, 4.3, 4.4_

- [x] 5. Create webhook configuration management

  - Add webhook settings to UniPaymentSetting model
  - Create admin interface for webhook URL configuration and testing
  - Implement webhook URL validation and status checking
  - _Requirements: 3.1, 3.2, 3.3_

- [x] 6. Add webhook testing and monitoring

  - Create webhook URL accessibility testing functionality
  - Add webhook processing status monitoring
  - Implement webhook troubleshooting tools for administrators
  - _Requirements: 1.3, 3.3_

- [x] 7. Write comprehensive tests for webhook functionality

  - Create unit tests for webhook URL generation and validation
  - Write integration tests for webhook processing flow
  - Add tests for error handling and fallback mechanisms
  - _Requirements: 1.1, 1.2, 1.3, 4.1, 4.2, 4.3, 4.4_

- [x] 8. Update documentation and deployment procedures
  - Document webhook setup procedures for different environments
  - Create troubleshooting guide for webhook 404 errors
  - Update deployment scripts to include webhook URL configuration
  - _Requirements: 3.1, 3.2, 3.3_
