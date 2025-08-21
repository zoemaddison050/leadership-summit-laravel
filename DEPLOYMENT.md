# Leadership Summit - Deployment Guide

## 🚀 Production Deployment Instructions

### Prerequisites

- PHP 8.1 or higher
- MySQL 8.0 or higher
- Composer
- Web server (Apache/Nginx)
- SSL certificate

### Required PHP Extensions

- BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML
- GD (for QR code generation)
- cURL (for cryptocurrency price API)

## 📦 Deployment Steps

### 1. Upload Files

Upload all project files to your web server, ensuring the document root points to the `public` directory.

### 2. Environment Configuration

```bash
# Copy the production environment template
cp .env.production .env

# Generate application key
php artisan key:generate
```

### 3. Configure Environment Variables

Edit `.env` file with your production settings:

```env
APP_URL=https://yourdomain.com
DB_DATABASE=your_database_name
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password
MAIL_HOST=your_smtp_host
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_password

# Webhook Configuration
WEBHOOK_BASE_URL=https://yourdomain.com
WEBHOOK_ENABLED=true
WEBHOOK_TIMEOUT=30
WEBHOOK_RETRY_ATTEMPTS=3

# UniPayment Configuration
UNIPAYMENT_APP_ID=your_unipayment_app_id
UNIPAYMENT_API_KEY=your_unipayment_api_key
UNIPAYMENT_WEBHOOK_SECRET=your_webhook_secret
UNIPAYMENT_ENVIRONMENT=production
```

### 4. Run Deployment Script

```bash
chmod +x deploy.sh
./deploy.sh
```

### 5. Configure Webhook URLs

After deployment, configure webhook URLs in your payment providers:

#### UniPayment Configuration

1. Login to UniPayment dashboard
2. Navigate to App Settings → Webhook
3. Set webhook URL to: `https://yourdomain.com/payment/unipayment/webhook`
4. Configure webhook secret (must match UNIPAYMENT_WEBHOOK_SECRET in .env)
5. Test webhook connectivity

#### Validate Webhook Configuration

```bash
# Test webhook configuration
php artisan webhook:validate-config

# Test webhook connectivity
php artisan webhook:test-connectivity --verbose
```

### 6. Set File Permissions

```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## 🔧 Features Implemented

### ✅ Enhanced Registration System

- **No-account registration**: Users can register without creating accounts
- **Real-time validation**: Form validation with immediate feedback
- **Duplicate prevention**: Prevents duplicate registrations by email/phone
- **Session management**: 30-minute registration sessions with expiration handling

### ✅ Cryptocurrency Payment System

- **Multi-currency support**: Bitcoin, Ethereum, USDT (ERC-20)
- **Real-time pricing**: Live price conversion using CoinGecko API
- **QR code generation**: Dynamic QR codes for each payment
- **Admin wallet management**: Configurable wallet addresses

### ✅ Comprehensive Admin Panel

- **Event management**: Create, edit, delete, and set default events
- **Speaker management**: Full CRUD operations for speakers
- **Wallet settings**: Manage cryptocurrency wallet addresses
- **Payment approval**: Review and approve crypto payments
- **Default event control**: Set which event appears on homepage

### ✅ User Experience Enhancements

- **Streamlined navigation**: Removed login barriers
- **Consistent branding**: "Register Now" buttons throughout
- **Mobile responsive**: Optimized for all device sizes
- **Loading states**: Smooth loading animations

## 🎯 Admin Panel Access

### Default Admin Setup

1. Create admin user via tinker:

```bash
php artisan tinker
User::create([
    'name' => 'Admin User',
    'email' => 'admin@yourdomain.com',
    'password' => Hash::make('your-secure-password'),
    'role_id' => 1 // Assuming role 1 is admin
]);
```

### Admin Features

- **Dashboard**: Overview of events, registrations, and payments
- **Event Management**: `/admin/events` - Full CRUD operations
- **Speaker Management**: `/admin/speakers` - Manage speaker profiles
- **Wallet Settings**: `/admin/wallet-settings` - Configure crypto wallets
- **Payment Review**: `/admin/payments/pending` - Approve crypto payments

## 🔐 Security Considerations

### Production Security

- Set `APP_DEBUG=false`
- Use HTTPS with valid SSL certificate
- Configure secure session cookies
- Set up proper file permissions
- Use strong database passwords
- Enable firewall protection

### Backup Strategy

- Regular database backups
- File system backups
- Environment configuration backups

## 🌐 Web Server Configuration

### Apache (.htaccess)

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

### Nginx

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/your/project/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 📊 Performance Optimization

### Caching

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Database Optimization

- Index frequently queried columns
- Use database connection pooling
- Regular database maintenance

## 🔍 Monitoring & Maintenance

### Log Monitoring

- Monitor `storage/logs/laravel.log`
- Set up log rotation
- Configure error alerting

### Regular Maintenance

- Update dependencies regularly
- Monitor disk space
- Review security updates
- Backup verification

## 🆘 Troubleshooting

### Common Issues

1. **500 Error**: Check file permissions and `.env` configuration
2. **Database Connection**: Verify database credentials
3. **Storage Issues**: Ensure `storage:link` is created
4. **QR Codes Not Working**: Verify GD extension is installed
5. **Crypto Prices Not Loading**: Check cURL extension and API connectivity

### Support

For technical support, check the application logs and ensure all requirements are met.

## 🎉 Deployment Complete

Your Leadership Summit application is now ready for production use with:

- ✅ Streamlined registration process
- ✅ Cryptocurrency payment system
- ✅ Comprehensive admin panel
- ✅ Mobile-responsive design
- ✅ Production-ready security

Visit your domain to see the application in action!
