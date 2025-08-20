# Leadership Summit Laravel - Troubleshooting Guide

This guide provides solutions to common issues encountered during deployment and operation of the Leadership Summit Laravel application.

## Table of Contents

1. [Deployment Issues](#deployment-issues)
2. [Database Issues](#database-issues)
3. [Application Issues](#application-issues)
4. [Performance Issues](#performance-issues)
5. [Security Issues](#security-issues)
6. [Docker Issues](#docker-issues)
7. [SSL/HTTPS Issues](#sslhttps-issues)
8. [Payment Gateway Issues](#payment-gateway-issues)
9. [Email Issues](#email-issues)
10. [Monitoring and Logging](#monitoring-and-logging)

## Deployment Issues

### Issue: Deployment Script Fails

**Symptoms:**

- Deployment script exits with error
- Containers fail to start
- Build process fails

**Diagnosis:**

```bash
# Check deployment logs
./scripts/deploy.sh production 2>&1 | tee deployment.log

# Check Docker logs
docker-compose logs

# Check system resources
df -h
free -h
```

**Solutions:**

1. **Insufficient disk space:**

```bash
# Clean up Docker images
docker system prune -a

# Remove old backups
find ./backups -mtime +30 -delete
```

2. **Memory issues:**

```bash
# Increase swap space
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
```

3. **Permission issues:**

```bash
# Fix Docker permissions
sudo usermod -aG docker $USER
newgrp docker

# Fix file permissions
sudo chown -R $USER:$USER .
```

### Issue: Container Build Fails

**Symptoms:**

- Docker build command fails
- "No space left on device" error
- Package installation fails

**Diagnosis:**

```bash
# Check Docker build logs
docker-compose build --no-cache 2>&1 | tee build.log

# Check available space
docker system df
```

**Solutions:**

1. **Clean Docker cache:**

```bash
docker builder prune -a
docker system prune -a --volumes
```

2. **Fix package installation:**

```bash
# Update package lists
docker exec container_name apt-get update

# Fix broken packages
docker exec container_name apt-get install -f
```

## Database Issues

### Issue: Database Connection Failed

**Symptoms:**

- "Connection refused" errors
- "Access denied" errors
- Application cannot connect to database

**Diagnosis:**

```bash
# Check database container status
docker-compose ps db

# Check database logs
docker-compose logs db

# Test connection manually
docker exec -it leadership-summit-db mysql -u root -p
```

**Solutions:**

1. **Database container not running:**

```bash
# Start database container
docker-compose up -d db

# Check if port is available
netstat -tlnp | grep 3306
```

2. **Wrong credentials:**

```bash
# Verify environment variables
grep DB_ .env

# Reset database password
docker exec leadership-summit-db mysql -u root -p -e "ALTER USER 'laravel'@'%' IDENTIFIED BY 'new_password';"
```

3. **Network connectivity:**

```bash
# Check Docker network
docker network ls
docker network inspect leadership-summit_default

# Test connectivity between containers
docker exec leadership-summit-app ping db
```

### Issue: Database Migration Fails

**Symptoms:**

- Migration commands fail
- "Table already exists" errors
- "Column not found" errors

**Diagnosis:**

```bash
# Check migration status
docker exec leadership-summit-app php artisan migrate:status

# Check database structure
docker exec leadership-summit-db mysql -u root -p -e "SHOW TABLES;" database_name
```

**Solutions:**

1. **Reset migrations:**

```bash
# Rollback all migrations
docker exec leadership-summit-app php artisan migrate:reset

# Run fresh migrations
docker exec leadership-summit-app php artisan migrate:fresh
```

2. **Fix specific migration:**

```bash
# Rollback to specific migration
docker exec leadership-summit-app php artisan migrate:rollback --step=1

# Run specific migration
docker exec leadership-summit-app php artisan migrate --path=/database/migrations/specific_migration.php
```

## Application Issues

### Issue: 500 Internal Server Error

**Symptoms:**

- White screen or generic error page
- HTTP 500 status code
- Application not loading

**Diagnosis:**

```bash
# Check application logs
docker-compose logs app

# Check Laravel logs
docker exec leadership-summit-app tail -f storage/logs/laravel.log

# Check web server logs
docker-compose logs nginx
```

**Solutions:**

1. **Clear application cache:**

```bash
docker exec leadership-summit-app php artisan cache:clear
docker exec leadership-summit-app php artisan config:clear
docker exec leadership-summit-app php artisan view:clear
docker exec leadership-summit-app php artisan route:clear
```

2. **Fix file permissions:**

```bash
docker exec leadership-summit-app chown -R www-data:www-data storage
docker exec leadership-summit-app chown -R www-data:www-data bootstrap/cache
docker exec leadership-summit-app chmod -R 775 storage
docker exec leadership-summit-app chmod -R 775 bootstrap/cache
```

3. **Check environment configuration:**

```bash
# Verify .env file
docker exec leadership-summit-app cat .env

# Generate application key
docker exec leadership-summit-app php artisan key:generate
```

### Issue: Authentication Not Working

**Symptoms:**

- Users cannot log in
- Session issues
- CSRF token mismatch

**Diagnosis:**

```bash
# Check session configuration
docker exec leadership-summit-app php artisan config:show session

# Check authentication logs
docker exec leadership-summit-app grep -i "auth" storage/logs/laravel.log
```

**Solutions:**

1. **Fix session configuration:**

```bash
# Clear session cache
docker exec leadership-summit-app php artisan session:table
docker exec leadership-summit-app php artisan migrate

# Restart Redis (if using Redis for sessions)
docker-compose restart redis
```

2. **Fix CSRF issues:**

```bash
# Clear application cache
docker exec leadership-summit-app php artisan cache:clear

# Check CSRF middleware
docker exec leadership-summit-app php artisan route:list | grep csrf
```

## Performance Issues

### Issue: Slow Page Load Times

**Symptoms:**

- Pages take more than 3 seconds to load
- High server response times
- Database queries taking too long

**Diagnosis:**

```bash
# Monitor resource usage
docker stats

# Check database performance
docker exec leadership-summit-db mysqladmin processlist

# Check slow query log
docker exec leadership-summit-db tail -f /var/log/mysql/slow.log
```

**Solutions:**

1. **Optimize database:**

```bash
# Analyze and optimize tables
docker exec leadership-summit-db mysqlcheck -o --all-databases -u root -p

# Add database indexes
docker exec leadership-summit-app php artisan tinker
# Run: DB::statement('CREATE INDEX idx_events_date ON events(start_date)');
```

2. **Enable caching:**

```bash
# Cache configuration
docker exec leadership-summit-app php artisan config:cache

# Cache routes
docker exec leadership-summit-app php artisan route:cache

# Cache views
docker exec leadership-summit-app php artisan view:cache
```

3. **Optimize PHP:**

```bash
# Enable OPcache (add to php.ini)
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000

# Restart PHP-FPM
docker-compose restart app
```

### Issue: High Memory Usage

**Symptoms:**

- Out of memory errors
- Application becomes unresponsive
- Container restarts frequently

**Diagnosis:**

```bash
# Check memory usage
docker stats
free -h

# Check PHP memory usage
docker exec leadership-summit-app php -i | grep memory_limit
```

**Solutions:**

1. **Increase PHP memory limit:**

```bash
# Edit PHP configuration
docker exec leadership-summit-app sed -i 's/memory_limit = .*/memory_limit = 512M/' /usr/local/etc/php/php.ini

# Restart container
docker-compose restart app
```

2. **Optimize application:**

```bash
# Clear unnecessary caches
docker exec leadership-summit-app php artisan cache:clear

# Optimize Composer autoloader
docker exec leadership-summit-app composer dump-autoload --optimize
```

## Security Issues

### Issue: SSL Certificate Problems

**Symptoms:**

- "Certificate expired" warnings
- "Certificate not trusted" errors
- HTTPS not working

**Diagnosis:**

```bash
# Check certificate validity
openssl x509 -in docker/ssl/yourdomain.com.crt -text -noout

# Check certificate expiration
openssl x509 -in docker/ssl/yourdomain.com.crt -noout -dates

# Test SSL configuration
curl -I https://yourdomain.com
```

**Solutions:**

1. **Renew certificate:**

```bash
# For Let's Encrypt
certbot renew

# Copy new certificates
cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem docker/ssl/yourdomain.com.crt
cp /etc/letsencrypt/live/yourdomain.com/privkey.pem docker/ssl/yourdomain.com.key

# Restart nginx
docker-compose restart nginx
```

2. **Fix certificate configuration:**

```bash
# Check nginx SSL configuration
docker exec leadership-summit-nginx nginx -t

# Reload nginx configuration
docker exec leadership-summit-nginx nginx -s reload
```

## Docker Issues

### Issue: Container Keeps Restarting

**Symptoms:**

- Container exits and restarts repeatedly
- "Exited (1)" status
- Application not accessible

**Diagnosis:**

```bash
# Check container status
docker-compose ps

# Check container logs
docker-compose logs container_name

# Check container resource usage
docker stats
```

**Solutions:**

1. **Fix application errors:**

```bash
# Check application logs for errors
docker-compose logs app | grep -i error

# Fix configuration issues
docker exec leadership-summit-app php artisan config:clear
```

2. **Increase resource limits:**

```bash
# Edit docker-compose.yml
services:
  app:
    deploy:
      resources:
        limits:
          memory: 1G
        reservations:
          memory: 512M
```

### Issue: Port Already in Use

**Symptoms:**

- "Port is already allocated" error
- Cannot start containers
- Service unavailable

**Diagnosis:**

```bash
# Check which process is using the port
sudo netstat -tlnp | grep :80
sudo lsof -i :80
```

**Solutions:**

1. **Stop conflicting service:**

```bash
# Stop Apache/Nginx if running
sudo systemctl stop apache2
sudo systemctl stop nginx

# Kill process using the port
sudo kill -9 PID
```

2. **Change port mapping:**

```bash
# Edit docker-compose.yml
ports:
  - "8080:80"  # Use different external port
```

## SSL/HTTPS Issues

### Issue: Mixed Content Warnings

**Symptoms:**

- Browser shows "Not secure" warning
- Some resources load over HTTP
- JavaScript/CSS not loading

**Diagnosis:**

```bash
# Check for mixed content in browser developer tools
# Look for HTTP resources on HTTPS pages

# Check application URL configuration
docker exec leadership-summit-app php artisan config:show app.url
```

**Solutions:**

1. **Force HTTPS:**

```bash
# Add to .env
APP_URL=https://yourdomain.com
FORCE_HTTPS=true

# Clear configuration cache
docker exec leadership-summit-app php artisan config:cache
```

2. **Update asset URLs:**

```bash
# Use secure asset helper in Blade templates
{{ secure_asset('css/app.css') }}

# Or use asset() with HTTPS forced
{{ asset('css/app.css') }}
```

## Payment Gateway Issues

### Issue: Payment Processing Fails

**Symptoms:**

- Payment forms not submitting
- "Payment failed" errors
- Transactions not completing

**Diagnosis:**

```bash
# Check payment logs
docker exec leadership-summit-app grep -i "payment" storage/logs/laravel.log

# Check payment gateway credentials
docker exec leadership-summit-app php artisan config:show services.stripe
```

**Solutions:**

1. **Verify credentials:**

```bash
# Check environment variables
grep STRIPE_ .env
grep PAYPAL_ .env

# Test API connection
docker exec leadership-summit-app php artisan tinker
# Test Stripe connection in tinker
```

2. **Check webhook configuration:**

```bash
# Verify webhook URLs in payment gateway dashboard
# Ensure webhook endpoints are accessible
curl -X POST https://yourdomain.com/webhooks/stripe
```

## Email Issues

### Issue: Emails Not Sending

**Symptoms:**

- Registration emails not received
- Password reset emails not sent
- No email notifications

**Diagnosis:**

```bash
# Check mail configuration
docker exec leadership-summit-app php artisan config:show mail

# Check mail logs
docker exec leadership-summit-app grep -i "mail" storage/logs/laravel.log

# Test mail configuration
docker exec leadership-summit-app php artisan tinker
# Test: Mail::raw('Test email', function($msg) { $msg->to('test@example.com')->subject('Test'); });
```

**Solutions:**

1. **Fix SMTP configuration:**

```bash
# Verify SMTP settings in .env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls

# Clear configuration cache
docker exec leadership-summit-app php artisan config:cache
```

2. **Test email sending:**

```bash
# Send test email
docker exec leadership-summit-app php artisan tinker --execute="
Mail::raw('Test email from Laravel', function(\$message) {
    \$message->to('test@example.com')->subject('Test Email');
});
echo 'Test email sent';
"
```

## Monitoring and Logging

### Issue: Logs Not Accessible

**Symptoms:**

- Cannot view application logs
- Log files not being created
- Insufficient logging information

**Diagnosis:**

```bash
# Check log file permissions
docker exec leadership-summit-app ls -la storage/logs/

# Check log configuration
docker exec leadership-summit-app php artisan config:show logging
```

**Solutions:**

1. **Fix log permissions:**

```bash
# Fix storage permissions
docker exec leadership-summit-app chown -R www-data:www-data storage/logs
docker exec leadership-summit-app chmod -R 775 storage/logs
```

2. **Configure logging:**

```bash
# Set log level in .env
LOG_LEVEL=debug

# Clear configuration cache
docker exec leadership-summit-app php artisan config:cache
```

### Issue: High Log Volume

**Symptoms:**

- Log files growing too large
- Disk space running out
- Performance degradation

**Solutions:**

1. **Configure log rotation:**

```bash
# Add to /etc/logrotate.d/laravel
/path/to/leadership-summit-laravel/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    create 0644 www-data www-data
}
```

2. **Reduce log level:**

```bash
# Set log level to warning or error in production
LOG_LEVEL=warning
```

## Emergency Procedures

### Complete System Failure

1. **Immediate Response:**

```bash
# Check system status
docker-compose ps
systemctl status docker

# Check system resources
df -h
free -h
top
```

2. **Recovery Steps:**

```bash
# Stop all containers
docker-compose down

# Restart Docker service
sudo systemctl restart docker

# Start containers
docker-compose up -d

# If that fails, restore from backup
./scripts/rollback.sh production auto
```

### Data Corruption

1. **Stop application:**

```bash
docker-compose down
```

2. **Restore from backup:**

```bash
./scripts/restore-database.sh production /path/to/latest/backup.sql.gz
```

3. **Verify data integrity:**

```bash
docker exec leadership-summit-app php artisan tinker --execute="
echo 'Users: ' . App\Models\User::count();
echo 'Events: ' . App\Models\Event::count();
"
```

## Webhook Issues

### Issue: Webhook 404 Errors

**Symptoms:**

- UniPayment reports 404 errors
- Webhooks not received in Laravel
- Payment confirmations failing

**Diagnosis:**

```bash
# Test webhook endpoint directly
curl -X POST https://yourdomain.com/payment/unipayment/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": "webhook"}'

# Check route registration
php artisan route:list | grep webhook

# Validate webhook configuration
php artisan webhook:validate-config
```

**Solutions:**

1. **Verify route registration:**

```bash
# Check if webhook route exists
php artisan route:list | grep "payment/unipayment/webhook"

# Clear route cache
php artisan route:clear
php artisan route:cache
```

2. **Check web server configuration:**

For Nginx:

```nginx
location /payment/unipayment/webhook {
    try_files $uri /index.php?$query_string;
}
```

For Apache:

```apache
RewriteRule ^payment/unipayment/webhook$ index.php [L]
```

3. **Verify SSL configuration:**

```bash
# Test SSL certificate
openssl s_client -connect yourdomain.com:443

# Test webhook with HTTPS
curl -X POST https://yourdomain.com/payment/unipayment/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": "ssl"}'
```

### Issue: Webhook Signature Validation Failures

**Symptoms:**

- Webhooks rejected with 401 errors
- "Invalid signature" in logs
- Legitimate webhooks being blocked

**Diagnosis:**

```bash
# Check webhook secret configuration
grep WEBHOOK_SECRET .env

# Test webhook connectivity
php artisan webhook:test-connectivity --verbose

# Check signature validation logs
grep "signature" storage/logs/laravel.log
```

**Solutions:**

1. **Verify webhook secret:**

```bash
# Check environment configuration
php artisan config:show unipayment.webhook_secret

# Update webhook secret in UniPayment dashboard
# Ensure it matches your .env configuration
```

2. **Debug signature calculation:**

```php
// Temporarily add to webhook handler for debugging
Log::info('Webhook signature debug', [
    'received_signature' => $request->header('X-UniPayment-Signature'),
    'calculated_signature' => hash_hmac('sha256', $request->getContent(), config('unipayment.webhook_secret')),
    'payload' => $request->getContent()
]);
```

### Issue: Webhook Timeout Errors

**Symptoms:**

- Webhook processing takes too long
- UniPayment retries webhooks
- Duplicate processing

**Diagnosis:**

```bash
# Check webhook processing time in logs
grep "webhook_processing_time" storage/logs/laravel.log

# Monitor system resources during webhook processing
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

```bash
# In .env file
WEBHOOK_TIMEOUT=60

# Clear configuration cache
php artisan config:cache
```

3. **Implement idempotency:**

```php
// Check for duplicate processing
if (PaymentTransaction::where('webhook_id', $webhookId)->exists()) {
    return response()->json(['status' => 'already_processed'], 200);
}
```

## Getting Help

### Log Collection for Support

```bash
# Collect all relevant logs
mkdir -p support-logs/$(date +%Y%m%d_%H%M%S)
docker-compose logs > support-logs/$(date +%Y%m%d_%H%M%S)/docker-compose.log
docker exec leadership-summit-app cp storage/logs/laravel.log support-logs/$(date +%Y%m%d_%H%M%S)/
cp .env support-logs/$(date +%Y%m%d_%H%M%S)/env-config.txt
docker-compose ps > support-logs/$(date +%Y%m%d_%H%M%S)/container-status.txt
```

### Contact Information

- **Technical Support**: tech-support@yourdomain.com
- **Emergency Hotline**: +1-XXX-XXX-XXXX
- **Development Team**: dev-team@yourdomain.com

---

**Note**: This troubleshooting guide should be updated regularly based on new issues encountered and their solutions. Keep it accessible to all team members responsible for system maintenance.
