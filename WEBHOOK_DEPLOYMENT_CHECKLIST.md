# Webhook Deployment Checklist

This checklist ensures proper webhook configuration during deployment across all environments.

## Pre-Deployment Checklist

### Environment Configuration

- [ ] **APP_URL configured correctly**

  ```bash
  # Development
  APP_URL=https://abc123.ngrok.io

  # Staging
  APP_URL=https://staging.yourdomain.com

  # Production
  APP_URL=https://yourdomain.com
  ```

- [ ] **WEBHOOK_BASE_URL configured**

  ```bash
  # Should match APP_URL in most cases
  WEBHOOK_BASE_URL=${APP_URL}
  ```

- [ ] **UniPayment credentials configured**

  ```bash
  UNIPAYMENT_APP_ID=your_app_id
  UNIPAYMENT_API_KEY=your_api_key
  UNIPAYMENT_WEBHOOK_SECRET=your_webhook_secret
  UNIPAYMENT_ENVIRONMENT=sandbox  # or 'production'
  ```

- [ ] **Webhook settings configured**

  ```bash
  WEBHOOK_ENABLED=true
  WEBHOOK_TIMEOUT=30
  WEBHOOK_RETRY_ATTEMPTS=3
  ```

### SSL/HTTPS Configuration

- [ ] **Valid SSL certificate installed**

  ```bash
  # Test certificate validity
  openssl x509 -in /path/to/cert.crt -noout -dates
  ```

- [ ] **Certificate not expiring within 30 days**

  ```bash
  # Check expiration
  openssl x509 -in /path/to/cert.crt -noout -checkend 2592000
  ```

- [ ] **HTTPS redirect configured in web server**

- [ ] **Mixed content warnings resolved**

### Web Server Configuration

#### Nginx Configuration

- [ ] **Webhook route properly configured**

  ```nginx
  location /payment/unipayment/webhook {
      try_files $uri /index.php?$query_string;
  }
  ```

- [ ] **SSL configuration correct**

  ```nginx
  ssl_certificate /path/to/cert.pem;
  ssl_certificate_key /path/to/private.key;
  ```

- [ ] **Request size limits appropriate**

  ```nginx
  client_max_body_size 10M;
  ```

#### Apache Configuration

- [ ] **URL rewriting enabled**

  ```apache
  RewriteEngine On
  RewriteRule ^(.*)$ public/$1 [L]
  ```

- [ ] **.htaccess file in public directory**

### Laravel Application

- [ ] **Routes registered correctly**

  ```bash
  php artisan route:list | grep webhook
  ```

- [ ] **Middleware configured**

  ```php
  Route::post('/payment/unipayment/webhook', [PaymentController::class, 'handleUniPaymentWebhook'])
      ->middleware(['webhook.auth']);
  ```

- [ ] **Database migrations run**

  ```bash
  php artisan migrate --force
  ```

- [ ] **Configuration cached**

  ```bash
  php artisan config:cache
  ```

## Deployment Steps

### 1. Pre-Deployment Validation

- [ ] **Run webhook configuration validation**

  ```bash
  php artisan webhook:validate-config
  ```

- [ ] **Test webhook connectivity (if possible)**

  ```bash
  php artisan webhook:test-connectivity --verbose
  ```

- [ ] **Verify environment variables**

  ```bash
  php artisan tinker --execute="
  echo 'APP_URL: ' . config('app.url') . PHP_EOL;
  echo 'WEBHOOK_BASE_URL: ' . env('WEBHOOK_BASE_URL') . PHP_EOL;
  echo 'WEBHOOK_ENABLED: ' . (env('WEBHOOK_ENABLED') ? 'true' : 'false') . PHP_EOL;
  "
  ```

### 2. Deploy Application

- [ ] **Stop application gracefully**

  ```bash
  # For Docker
  docker-compose down --timeout 60

  # For traditional deployment
  sudo systemctl stop nginx php8.1-fpm
  ```

- [ ] **Update code**

  ```bash
  git pull origin main
  composer install --no-dev --optimize-autoloader
  ```

- [ ] **Run migrations**

  ```bash
  php artisan migrate --force
  ```

- [ ] **Clear and cache configuration**

  ```bash
  php artisan config:clear
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```

- [ ] **Start application**

  ```bash
  # For Docker
  docker-compose up -d

  # For traditional deployment
  sudo systemctl start php8.1-fpm nginx
  ```

### 3. Post-Deployment Validation

- [ ] **Test application accessibility**

  ```bash
  curl -I https://yourdomain.com/
  ```

- [ ] **Test webhook endpoint specifically**

  ```bash
  curl -X POST https://yourdomain.com/payment/unipayment/webhook \
    -H "Content-Type: application/json" \
    -d '{"test": "deployment"}'
  ```

- [ ] **Run comprehensive webhook tests**

  ```bash
  php artisan webhook:test-connectivity --verbose
  ```

- [ ] **Check application logs for errors**

  ```bash
  tail -f storage/logs/laravel.log
  ```

### 4. Payment Provider Configuration

- [ ] **Update webhook URL in UniPayment dashboard**

  - Login to UniPayment dashboard
  - Navigate to App Settings → Webhook
  - Set URL to: `https://yourdomain.com/payment/unipayment/webhook`
  - Save configuration

- [ ] **Test webhook from UniPayment dashboard**

  - Use the "Test Webhook" feature if available
  - Verify webhook is received and processed

- [ ] **Configure webhook secret (if not already done)**
  - Generate secure webhook secret
  - Update in UniPayment dashboard
  - Update UNIPAYMENT_WEBHOOK_SECRET in .env

### 5. Admin Panel Configuration

- [ ] **Access admin panel**

  - Navigate to `/admin/unipayment`
  - Login with admin credentials

- [ ] **Configure webhook settings**

  - Verify webhook URL is correct
  - Test webhook connectivity
  - Enable webhook processing
  - Configure retry settings

- [ ] **Test payment flow end-to-end**
  - Create test registration
  - Process test payment
  - Verify webhook processing
  - Check payment confirmation

## Environment-Specific Checklists

### Development Environment

- [ ] **ngrok or tunnel service running**

  ```bash
  ngrok http 8000
  ```

- [ ] **Webhook URL updated with tunnel URL**

  ```bash
  WEBHOOK_BASE_URL=https://abc123.ngrok.io
  ```

- [ ] **UniPayment configured for sandbox**

  ```bash
  UNIPAYMENT_ENVIRONMENT=sandbox
  ```

- [ ] **Test webhook with sandbox payments**

### Staging Environment

- [ ] **Staging domain configured**

  ```bash
  APP_URL=https://staging.yourdomain.com
  ```

- [ ] **SSL certificate valid for staging domain**

- [ ] **Staging database configured**

- [ ] **UniPayment sandbox or separate staging app**

- [ ] **End-to-end testing completed**

### Production Environment

- [ ] **Production domain configured**

  ```bash
  APP_URL=https://yourdomain.com
  ```

- [ ] **Production SSL certificate installed**

- [ ] **Production database configured**

- [ ] **UniPayment production app configured**

- [ ] **Monitoring and alerting configured**

- [ ] **Backup procedures in place**

## Troubleshooting Checklist

### If Webhook Returns 404

- [ ] **Check route registration**

  ```bash
  php artisan route:list | grep webhook
  ```

- [ ] **Verify web server configuration**

  ```bash
  nginx -t  # for Nginx
  apache2ctl configtest  # for Apache
  ```

- [ ] **Check Laravel application status**

  ```bash
  curl https://yourdomain.com/login
  ```

- [ ] **Review web server error logs**

  ```bash
  tail -f /var/log/nginx/error.log
  ```

### If Webhook Returns 500

- [ ] **Check Laravel error logs**

  ```bash
  tail -f storage/logs/laravel.log
  ```

- [ ] **Verify database connectivity**

  ```bash
  php artisan tinker --execute="DB::connection()->getPdo();"
  ```

- [ ] **Check file permissions**

  ```bash
  ls -la storage/logs/
  ```

- [ ] **Clear application caches**

  ```bash
  php artisan cache:clear
  php artisan config:clear
  ```

### If Signature Validation Fails

- [ ] **Verify webhook secret configuration**

  ```bash
  grep WEBHOOK_SECRET .env
  ```

- [ ] **Check signature calculation in logs**

  ```bash
  grep "signature" storage/logs/laravel.log
  ```

- [ ] **Verify UniPayment dashboard configuration**

- [ ] **Test with signature validation disabled temporarily**

## Monitoring and Maintenance

### Ongoing Monitoring

- [ ] **Set up webhook endpoint monitoring**

  ```bash
  # Add to crontab
  */5 * * * * curl -f https://yourdomain.com/payment/unipayment/webhook || echo "Webhook down" | mail admin@yourdomain.com
  ```

- [ ] **Monitor webhook processing logs**

  ```bash
  grep "webhook" storage/logs/laravel.log | tail -20
  ```

- [ ] **Set up SSL certificate expiration alerts**

- [ ] **Monitor payment processing success rates**

### Regular Maintenance

- [ ] **Weekly webhook connectivity tests**

  ```bash
  php artisan webhook:test-connectivity
  ```

- [ ] **Monthly SSL certificate checks**

  ```bash
  openssl x509 -in /path/to/cert.crt -noout -checkend 2592000
  ```

- [ ] **Quarterly webhook configuration review**

- [ ] **Annual security audit of webhook implementation**

## Emergency Procedures

### Webhook System Failure

1. **Immediate Response**

   - [ ] Enable manual payment processing
   - [ ] Notify administrators
   - [ ] Check for pending payments

2. **Quick Fixes**

   - [ ] Restart web server and PHP-FPM
   - [ ] Clear Laravel caches
   - [ ] Test webhook endpoint

3. **Rollback if Necessary**
   - [ ] Revert to previous deployment
   - [ ] Restore previous configuration
   - [ ] Verify system functionality

### Contact Information

- **Technical Support**: tech-support@yourdomain.com
- **Emergency Hotline**: +1-XXX-XXX-XXXX
- **Development Team**: dev-team@yourdomain.com

## Sign-off

### Development Team

- [ ] **Developer**: **\*\*\*\***\_**\*\*\*\*** Date: \***\*\_\*\***
- [ ] **Code Review**: **\*\*\*\***\_**\*\*\*\*** Date: \***\*\_\*\***
- [ ] **Testing**: **\*\*\*\***\_**\*\*\*\*** Date: \***\*\_\*\***

### Operations Team

- [ ] **System Administrator**: **\*\*\*\***\_**\*\*\*\*** Date: \***\*\_\*\***
- [ ] **Security Review**: **\*\*\*\***\_**\*\*\*\*** Date: \***\*\_\*\***
- [ ] **Deployment**: **\*\*\*\***\_**\*\*\*\*** Date: \***\*\_\*\***

### Business Team

- [ ] **Product Owner**: **\*\*\*\***\_**\*\*\*\*** Date: \***\*\_\*\***
- [ ] **Final Approval**: **\*\*\*\***\_**\*\*\*\*** Date: \***\*\_\*\***

---

**Deployment Date**: **\*\***\_\_\_**\*\***
**Environment**: **\*\***\_\_\_**\*\***
**Version/Commit**: **\*\***\_\_\_**\*\***
**Deployed By**: **\*\***\_\_\_**\*\***

**Notes**:

---

---

---

---

**Last Updated**: $(date)
**Version**: 1.0
