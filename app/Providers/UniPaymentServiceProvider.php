<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use UniPayment\SDK\BillingAPI;
use UniPayment\SDK\Configuration;

class UniPaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Configuration
        $this->app->singleton(Configuration::class, function ($app) {
            // Try to load from database first, then fallback to config
            $dbSettings = \App\Models\UniPaymentSetting::first();

            if ($dbSettings && $dbSettings->is_enabled) {
                $clientId = $dbSettings->app_id;
                $clientSecret = $dbSettings->api_key;
                $appId = $dbSettings->app_id;
                $environment = $dbSettings->environment ?? 'sandbox';
            } else {
                $config = config('unipayment');
                $clientId = $config['client_id'] ?: 'dev_client_id';
                $clientSecret = $config['client_secret'] ?: 'dev_client_secret';
                $appId = $config['app_id'] ?: 'dev_app_id';
                $environment = $config['environment'] ?? 'sandbox';
            }

            $configuration = new Configuration();
            $configuration->setClientId($clientId);
            $configuration->setClientSecret($clientSecret);
            $configuration->setAppId($appId);

            // Set sandbox mode based on environment
            $configuration->setIsSandbox($environment !== 'production');

            return $configuration;
        });

        // Register BillingAPI
        $this->app->singleton(BillingAPI::class, function ($app) {
            return new BillingAPI($app->make(Configuration::class));
        });

        // Register the client with an alias for easier access
        $this->app->alias(BillingAPI::class, 'unipayment');

        // Register UniPaymentService
        $this->app->singleton(\App\Services\UniPaymentService::class, function ($app) {
            return new \App\Services\UniPaymentService(
                $app->make(BillingAPI::class),
                $app->make(Configuration::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__ . '/../../config/unipayment.php' => config_path('unipayment.php'),
        ], 'unipayment-config');
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            Configuration::class,
            BillingAPI::class,
            'unipayment',
            \App\Services\UniPaymentService::class,
        ];
    }
}
