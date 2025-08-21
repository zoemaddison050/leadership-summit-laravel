# Leadership Summit Laravel - Development Makefile
# This file provides convenient shortcuts for common development tasks

.PHONY: help setup up down restart logs shell mysql test validate troubleshoot clean build assets

# Default target
help: ## Show this help message
	@echo "Leadership Summit Laravel - Development Commands"
	@echo "=============================================="
	@echo ""
	@echo "Available commands:"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'
	@echo ""
	@echo "Examples:"
	@echo "  make setup     # Set up local development environment"
	@echo "  make up        # Start containers"
	@echo "  make test      # Run tests"
	@echo "  make validate  # Validate environment"

setup: ## Set up local development environment
	@echo "🚀 Setting up local development environment..."
	./setup-local.sh

up: ## Start Docker containers
	@echo "📦 Starting Docker containers..."
	docker-compose up -d

down: ## Stop Docker containers
	@echo "🛑 Stopping Docker containers..."
	docker-compose down

restart: ## Restart Docker containers
	@echo "🔄 Restarting Docker containers..."
	docker-compose restart

logs: ## Show container logs
	@echo "📋 Showing container logs..."
	docker-compose logs -f

shell: ## Access app container shell
	@echo "🐚 Accessing app container shell..."
	docker-compose exec app bash

mysql: ## Access MySQL shell
	@echo "🗄️ Accessing MySQL shell..."
	docker-compose exec mysql mysql -u leadership_summit -p leadership_summit

test: ## Run tests
	@echo "🧪 Running tests..."
	docker-compose exec app php artisan test

validate: ## Validate local development environment
	@echo "🔍 Validating local development environment..."
	./validate-local.sh

troubleshoot: ## Run troubleshooting script
	@echo "🔧 Running troubleshooting script..."
	./troubleshoot-local.sh

clean: ## Clean up containers and volumes
	@echo "🧹 Cleaning up containers and volumes..."
	docker-compose down --volumes --remove-orphans
	docker system prune -f

build: ## Build Docker containers
	@echo "🔨 Building Docker containers..."
	docker-compose build --no-cache

assets: ## Build frontend assets
	@echo "🎨 Building frontend assets..."
	docker-compose exec app npm install
	docker-compose exec app npm run build

migrate: ## Run database migrations
	@echo "🗄️ Running database migrations..."
	docker-compose exec app php artisan migrate

seed: ## Seed database with sample data
	@echo "🌱 Seeding database..."
	docker-compose exec app php artisan db:seed

fresh: ## Fresh database with migrations and seeds
	@echo "🔄 Fresh database setup..."
	docker-compose exec app php artisan migrate:fresh --seed

cache-clear: ## Clear all caches
	@echo "🧹 Clearing caches..."
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

permissions: ## Fix file permissions
	@echo "🔒 Fixing file permissions..."
	docker-compose exec app chown -R www-data:www-data /var/www/html/storage
	docker-compose exec app chown -R www-data:www-data /var/www/html/bootstrap/cache
	docker-compose exec app chmod -R 775 /var/www/html/storage
	docker-compose exec app chmod -R 775 /var/www/html/bootstrap/cache

status: ## Show container status
	@echo "📊 Container status:"
	docker-compose ps

reset: ## Reset entire environment (destructive)
	@echo "⚠️ Resetting entire environment..."
	./troubleshoot-local.sh reset