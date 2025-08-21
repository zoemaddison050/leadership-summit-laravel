#!/bin/bash

# Leadership Summit Laravel Application Deployment Script
# This script prepares the application for production deployment

echo "🚀 Preparing Leadership Summit Application for Deployment..."

# 1. Clear all caches
echo "📦 Clearing application caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 2. Optimize for production
echo "⚡ Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# 4. Create storage link
echo "🔗 Creating storage symbolic link..."
php artisan storage:link

# 5. Set proper permissions
echo "🔐 Setting proper file permissions..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# 6. Install/update dependencies
echo "📚 Installing production dependencies..."
composer install --optimize-autoloader --no-dev

# 7. Generate application key if not exists
if [ ! -f .env ]; then
    echo "⚠️  .env file not found. Please create one from .env.example"
    cp .env.example .env
    php artisan key:generate
fi

# 8. Build assets (if using Laravel Mix/Vite)
if [ -f "package.json" ]; then
    echo "🎨 Building frontend assets..."
    npm install
    npm run build
fi

# 9. Validate webhook configuration
echo "🔗 Validating webhook configuration..."
php artisan webhook:validate-config || echo "⚠️  Webhook validation failed - check configuration"

echo "✅ Deployment preparation complete!"
echo ""
echo "📋 Next steps for your hosting server:"
echo "1. Upload all files to your web root directory"
echo "2. Configure your web server to point to the 'public' directory"
echo "3. Set up your .env file with production settings:"
echo "   - APP_ENV=production"
echo "   - APP_DEBUG=false"
echo "   - Database credentials"
echo "   - Mail configuration"
echo "   - App URL"
echo "   - Webhook configuration (WEBHOOK_BASE_URL, UNIPAYMENT_WEBHOOK_SECRET)"
echo "4. Run: php artisan migrate --force"
echo "5. Run: php artisan storage:link"
echo "6. Configure webhook URLs in payment provider dashboards"
echo "7. Test webhook connectivity: php artisan webhook:test-connectivity"
echo "8. Set proper file permissions (755 for directories, 644 for files)"
echo ""
echo "🔧 Required PHP Extensions:"
echo "- PHP 8.1 or higher"
echo "- BCMath PHP Extension"
echo "- Ctype PHP Extension"
echo "- Fileinfo PHP Extension"
echo "- JSON PHP Extension"
echo "- Mbstring PHP Extension"
echo "- OpenSSL PHP Extension"
echo "- PDO PHP Extension"
echo "- Tokenizer PHP Extension"
echo "- XML PHP Extension"
echo "- GD PHP Extension (for QR codes)"
echo "- cURL PHP Extension (for crypto price API)"
echo ""
echo "🌐 Web Server Configuration:"
echo "- Document root should point to 'public' directory"
echo "- Enable URL rewriting"
echo "- Set up SSL certificate for HTTPS"
echo ""
echo "🎉 Your Leadership Summit application is ready for deployment!"