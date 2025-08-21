# Webhook 404 Error Troubleshooting Guide

This guide provides step-by-step troubleshooting for webhook 404 errors in the Leadership Summit application.

## Table of Contents

1. [Understanding Webhook 404 Errors](#understanding-webhook-404-errors)
2. [Quick Diagnosis](#quick-diagnosis)
3. [Common Causes and Solutions](#common-causes-and-solutions)
4. [Step-by-Step Troubleshooting](#step-by-step-troubleshooting)
5. [Prevention Strategies](#prevention-strategies)
6. [Emergency Procedures](#emergency-procedures)

## Understanding Webhook 404 Errors

### What is a Webhook 404 Error?

A webhook 404 error occurs when UniPayment attempts to send a payment notification to your application, but receives an HTTP 404 "Not Found" response. This means:

- The webhook URL is incorrect or inaccessible
- The Laravel route is not properly configured
- The web server cannot find the endpoint
- The application is not running or misconfigured

### Impact of Webhook 404 Errors

- **Payment confirmations fail** - Users don't receive automatic confirmation
- **Registration process breaks** - Users remain in pending state
- **Manual intervention required** - Admin must manually process payments
- **Poor user experience** - Users may think payment failed
- **Revenue loss** - Potential abandoned transactions

### Webhook Flow Diagram

```
UniPayment → Internet → Your Server → Web Server → Laravel → Webhook Handler
                                         ↑
                                    404 Error occurs here
```

## Quick Diagnosis

### 1. Test Webhook URL Directly

```bash
# Replace with your actual webhook URL
curl -X POST https://yourdomain.com/payment/unipayment/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": "webhook"}'
```

**Expected Response:**

- Status: 200 OK (or 422 for validation errors)
- Body: JSON response from Laravel

**404 Error Response:**

```html
<html>
  <head>
    <title>404 Not Found</title>
  </head>
  <body>
    <center><h1>404 Not Found</h1></center>
  </body>
</html>
```

### 2. Check Route Registration

```bash
# List all routes and filter for webhook
php artisan route:list | grep -i webhook

# Expected output should include:
# POST | payment/unipayment/webhook | unipayment.webhook | App\Http\Controllers\PaymentController@handleUniPaymentWebhook
```

### 3. Verify Application Status

```bash
# Check if Laravel application is running
curl -I https://yourdomain.com/

# Check specific webhook endpoint
curl -I https://yourdomain.com/payment/unipayment/webhook
```

## Common Causes and Solutions

### 1. Incorrect Webhook URL Configuration

#### Cause

The webhook URL configured in UniPayment doesn't match your Laravel route.

#### Symptoms

- 404 errors in UniPayment dashboard
- No webhook requests in Laravel logs
- Payments remain in pending status

#### Diagnosis

```bash
# Check current webhook URL in admin panel
# Navigate to /admin/unipayment and verify webhook URL

# Check environment configuration
grep WEBHOOK_BASE_URL .env
grep APP_URL .env
```

#### Solution

1. **Update webhook URL in UniPayment dashboard:**

   - Login to UniPayment dashboard
   - Navigate to App Settings → Webhook
   - Set URL to: `https://yourdomain.com/payment/unipayment/webhook`

2. **Update environment configuration:**

```bash
# In .env file
APP_URL=https://yourdomain.com
WEBHOOK_BASE_URL=https://yourdomain.com

# Clear configuration cache
php artisan config:cache
```

3. **Update admin panel settings:**
   - Navigate to `/admin/unipayment`
   - Update webhook URL field
   - Test webhook connectivity

### 2. Missing or Incorrect Route Definition

#### Cause

The webhook route is not properly defined in Laravel routes.

#### Symptoms

- Route not found in `php artisan route:list`
- 404 errors even when URL is correct
- Other Laravel routes work fine

#### Diagnosis

```bash
# Check if route exists
php artisan route:list | grep "payment/unipayment/webhook"

# Check route file
grep -n "unipayment/webhook" routes/web.php
```

#### Solution

1. **Add route to routes/web.php:**

```php
// Add this route if missing
Route::post('/payment/unipayment/webhook', [PaymentController::class, 'handleUniPaymentWebhook'])
    ->name('unipayment.webhook')
    ->middleware(['webhook.auth']);
```

2. **Clear route cache:**

```bash
php artisan route:clear
php artisan route:cache
```

3. **Verify route registration:**

```bash
php artisan route:list | grep webhook
```

### 3. Web Server Configuration Issues

#### Cause

Nginx or Apache is not properly configured to handle the webhook route.

#### Symptoms

- 404 errors from web server (not Laravel)
- Other Laravel routes work
- Direct PHP file access works

#### For Nginx

**Diagnosis:**

```bash
# Check Nginx configuration
nginx -t

# Check access logs
tail -f /var/log/nginx/access.log | grep webhook

# Check error logs
tail -f /var/log/nginx/error.log
```

**Solution:**

```nginx
# In your Nginx site configuration
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/your/project/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Specific webhook location (optional)
    location /payment/unipayment/webhook {
        try_files $uri /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

#### For Apache

**Diagnosis:**

```bash
# Check Apache configuration
apache2ctl configtest

# Check access logs
tail -f /var/log/apache2/access.log | grep webhook

# Check error logs
tail -f /var/log/apache2/error.log
```

**Solution:**

```apache
# In your .htaccess file (should be in public directory)
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### 4. SSL/HTTPS Configuration Problems

#### Cause

Webhook URL uses HTTPS but SSL is not properly configured.

#### Symptoms

- 404 errors only on HTTPS requests
- HTTP requests work fine
- SSL certificate warnings

#### Diagnosis

```bash
# Test SSL configuration
curl -I https://yourdomain.com/payment/unipayment/webhook

# Check SSL certificate
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com

# Test without SSL verification (for debugging only)
curl -k -I https://yourdomain.com/payment/unipayment/webhook
```

#### Solution

1. **Fix SSL certificate:**

```bash
# For Let's Encrypt
certbot renew

# Copy certificates to correct location
cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem /path/to/ssl/cert.pem
cp /etc/letsencrypt/live/yourdomain.com/privkey.pem /path/to/ssl/private.key
```

2. **Update web server SSL configuration:**

```nginx
# Nginx SSL configuration
server {
    listen 443 ssl http2;
    server_name yourdomain.com;

    ssl_certificate /path/to/ssl/cert.pem;
    ssl_certificate_key /path/to/ssl/private.key;

    # ... rest of configuration
}
```

3. **Force HTTPS in Laravel:**

```php
// In AppServiceProvider boot method
if (config('app.env') === 'production') {
    URL::forceScheme('https');
}
```

### 5. Application Not Running or Misconfigured

#### Cause

Laravel application is not running or has configuration errors.

#### Symptoms

- All Laravel routes return 404
- Web server serves static files only
- PHP errors in logs

#### Diagnosis

```bash
# Check if PHP-FPM is running
systemctl status php8.1-fpm

# Check Laravel application
curl https://yourdomain.com/

# Check PHP error logs
tail -f /var/log/php8.1-fpm.log

# Check Laravel logs
tail -f storage/logs/laravel.log
```

#### Solution

1. **Restart PHP-FPM:**

```bash
sudo systemctl restart php8.1-fpm
```

2. **Fix Laravel configuration:**

```bash
# Generate application key if missing
php artisan key:generate

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Fix file permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

3. **Check environment configuration:**

```bash
# Verify .env file exists and is readable
ls -la .env

# Check critical environment variables
php artisan tinker --execute="
echo 'APP_URL: ' . config('app.url') . PHP_EOL;
echo 'APP_ENV: ' . config('app.env') . PHP_EOL;
echo 'DB_CONNECTION: ' . config('database.default') . PHP_EOL;
"
```

## Step-by-Step Troubleshooting

### Step 1: Verify Basic Connectivity

```bash
# Test if your domain is accessible
curl -I https://yourdomain.com/

# Expected: 200 OK response
# If this fails, check DNS and web server configuration
```

### Step 2: Test Laravel Application

```bash
# Test a known Laravel route
curl -I https://yourdomain.com/login

# Expected: 200 OK or 302 redirect
# If this fails, Laravel application has issues
```

### Step 3: Check Webhook Route Specifically

```bash
# Test webhook endpoint with GET request
curl -I https://yourdomain.com/payment/unipayment/webhook

# Expected: 405 Method Not Allowed (route exists but wrong method)
# If 404: Route doesn't exist or web server issue
```

### Step 4: Test with POST Request

```bash
# Test webhook endpoint with POST request
curl -X POST https://yourdomain.com/payment/unipayment/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": "data"}'

# Expected: 200 OK or 422 validation error
# If 404: Route or web server configuration issue
```

### Step 5: Check Route Registration

```bash
# List all routes
php artisan route:list | grep -E "(webhook|unipayment)"

# Expected: Should show the webhook route
# If missing: Route not properly defined
```

### Step 6: Verify Web Server Configuration

```bash
# For Nginx
nginx -t && echo "Nginx config OK"

# For Apache
apache2ctl configtest && echo "Apache config OK"

# Check if web server is running
systemctl status nginx  # or apache2
```

### Step 7: Check Application Logs

```bash
# Check Laravel logs for errors
tail -20 storage/logs/laravel.log

# Check web server error logs
tail -20 /var/log/nginx/error.log  # or /var/log/apache2/error.log

# Check PHP error logs
tail -20 /var/log/php8.1-fpm.log
```

## Prevention Strategies

### 1. Automated Monitoring

Create a monitoring script to check webhook health:

```bash
#!/bin/bash
# webhook_monitor.sh

WEBHOOK_URL="https://yourdomain.com/payment/unipayment/webhook"
LOG_FILE="/var/log/webhook_monitor.log"

# Test webhook endpoint
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$WEBHOOK_URL" \
  -H "Content-Type: application/json" \
  -d '{"test": "monitor"}')

if [ "$RESPONSE" != "200" ] && [ "$RESPONSE" != "422" ]; then
    echo "$(date): Webhook endpoint returned $RESPONSE" >> "$LOG_FILE"
    # Send alert email or notification
    echo "Webhook endpoint error: HTTP $RESPONSE" | mail -s "Webhook Alert" admin@yourdomain.com
fi
```

Add to crontab:

```bash
# Check webhook every 5 minutes
*/5 * * * * /path/to/webhook_monitor.sh
```

### 2. Health Check Endpoint

Add a dedicated health check endpoint:

```php
// In routes/web.php
Route::get('/health/webhook', function () {
    return response()->json([
        'status' => 'ok',
        'webhook_url' => route('unipayment.webhook'),
        'timestamp' => now()->toISOString()
    ]);
});
```

### 3. Configuration Validation

Add webhook URL validation to admin panel:

```php
// In UniPayment settings validation
public function validateWebhookUrl($url)
{
    $response = Http::timeout(10)->post($url, ['test' => 'validation']);

    if ($response->status() !== 200 && $response->status() !== 422) {
        throw new ValidationException('Webhook URL is not accessible');
    }

    return true;
}
```

### 4. Deployment Checks

Add webhook validation to deployment scripts:

```bash
# In deploy.sh
echo "Testing webhook endpoint..."
WEBHOOK_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$APP_URL/payment/unipayment/webhook" \
  -H "Content-Type: application/json" \
  -d '{"test": "deployment"}')

if [ "$WEBHOOK_RESPONSE" != "200" ] && [ "$WEBHOOK_RESPONSE" != "422" ]; then
    echo "ERROR: Webhook endpoint not accessible (HTTP $WEBHOOK_RESPONSE)"
    exit 1
fi

echo "Webhook endpoint OK"
```

## Emergency Procedures

### Immediate Response (When Webhooks Are Failing)

1. **Enable manual payment processing:**

```bash
# Set fallback mode in .env
WEBHOOK_ENABLED=false
MANUAL_PAYMENT_PROCESSING=true

# Clear configuration cache
php artisan config:cache
```

2. **Notify administrators:**

```bash
# Send immediate alert
echo "Webhook system failure detected. Manual processing required." | \
  mail -s "URGENT: Webhook System Down" admin@yourdomain.com
```

3. **Check for pending payments:**

```bash
# List payments awaiting confirmation
php artisan tinker --execute="
\$pending = App\Models\Payment::where('status', 'pending')->get();
echo 'Pending payments: ' . \$pending->count() . PHP_EOL;
foreach (\$pending as \$payment) {
    echo 'Payment ID: ' . \$payment->id . ', Amount: ' . \$payment->amount . PHP_EOL;
}
"
```

### Quick Fix Attempts

1. **Restart services:**

```bash
# Restart web server
sudo systemctl restart nginx  # or apache2

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm

# Clear Laravel caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

2. **Verify and fix permissions:**

```bash
# Fix file permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Fix web root permissions
sudo chown -R www-data:www-data public
```

3. **Test webhook immediately:**

```bash
# Quick webhook test
curl -X POST https://yourdomain.com/payment/unipayment/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": "emergency"}'
```

### Rollback Procedures

If recent changes caused the issue:

```bash
# Rollback to previous deployment
git checkout HEAD~1

# Restore previous configuration
cp .env.backup .env

# Clear caches and restart services
php artisan config:cache
sudo systemctl restart nginx php8.1-fpm
```

## Getting Help

### Information to Collect

Before contacting support, collect:

1. **Error details:**

```bash
# Webhook test results
curl -v -X POST https://yourdomain.com/payment/unipayment/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": "support"}' 2>&1 | tee webhook_test.log
```

2. **Route information:**

```bash
php artisan route:list | grep webhook > routes.log
```

3. **Server logs:**

```bash
# Last 50 lines of relevant logs
tail -50 storage/logs/laravel.log > laravel.log
tail -50 /var/log/nginx/error.log > nginx_error.log
tail -50 /var/log/php8.1-fpm.log > php_error.log
```

4. **Configuration details:**

```bash
# Environment configuration (remove sensitive data)
grep -E "(APP_URL|WEBHOOK|UNIPAYMENT)" .env > config.log
```

### Support Contacts

- **Technical Support:** tech-support@yourdomain.com
- **Emergency Hotline:** +1-XXX-XXX-XXXX
- **Development Team:** dev-team@yourdomain.com

### Support Ticket Template

```
Subject: Webhook 404 Error - [Environment: Production/Staging/Development]

Description:
- When did the issue start?
- What changes were made recently?
- Are all webhooks failing or just some?

Error Details:
- HTTP status code received
- Error message from UniPayment
- Browser/curl test results

Attachments:
- webhook_test.log
- laravel.log
- nginx_error.log (or apache error log)
- config.log

Steps Already Taken:
- List troubleshooting steps already attempted
- Results of each step
```

---

**Remember:** Webhook 404 errors are usually configuration issues that can be resolved quickly with systematic troubleshooting. Always test changes in a staging environment first!

**Last Updated:** $(date)
**Version:** 1.0
