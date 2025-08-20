# Webhook Quick Reference Card

## Quick Diagnostics

### Test Webhook Endpoint

```bash
curl -X POST https://yourdomain.com/payment/unipayment/webhook \
  -H "Content-Type: application/json" \
  -d '{"test": "webhook"}'
```

### Validate Configuration

```bash
php artisan webhook:validate-config
```

### Test Connectivity

```bash
php artisan webhook:test-connectivity --verbose
```

### Check Routes

```bash
php artisan route:list | grep webhook
```

## Common HTTP Status Codes

| Code | Meaning          | Action                        |
| ---- | ---------------- | ----------------------------- |
| 200  | Success          | ✅ Webhook working            |
| 401  | Unauthorized     | Check webhook secret          |
| 404  | Not Found        | Check route/web server config |
| 422  | Validation Error | Check payload format          |
| 500  | Server Error     | Check Laravel logs            |

## Environment Variables

```env
# Required
APP_URL=https://yourdomain.com
WEBHOOK_BASE_URL=https://yourdomain.com

# UniPayment
UNIPAYMENT_APP_ID=your_app_id
UNIPAYMENT_API_KEY=your_api_key
UNIPAYMENT_WEBHOOK_SECRET=your_secret
UNIPAYMENT_ENVIRONMENT=production

# Optional
WEBHOOK_ENABLED=true
WEBHOOK_TIMEOUT=30
WEBHOOK_RETRY_ATTEMPTS=3
```

## Quick Fixes

### Clear Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Restart Services

```bash
sudo systemctl restart nginx php8.1-fpm
# or
docker-compose restart
```

### Check Logs

```bash
tail -f storage/logs/laravel.log | grep webhook
```

## Emergency Contacts

- **Tech Support**: tech-support@yourdomain.com
- **Emergency**: +1-XXX-XXX-XXXX
- **Dev Team**: dev-team@yourdomain.com

## Documentation Links

- [Webhook Setup Guide](WEBHOOK_SETUP_GUIDE.md)
- [404 Troubleshooting](WEBHOOK_404_TROUBLESHOOTING.md)
- [Deployment Checklist](WEBHOOK_DEPLOYMENT_CHECKLIST.md)
- [Main Troubleshooting](TROUBLESHOOTING.md)
