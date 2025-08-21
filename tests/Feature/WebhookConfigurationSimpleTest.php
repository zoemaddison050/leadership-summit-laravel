<?php

namespace Tests\Feature;

use App\Models\UniPaymentSetting;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookConfigurationSimpleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function webhook_configuration_fields_are_added_to_database()
    {
        $settings = UniPaymentSetting::create([
            'app_id' => 'test_app_id',
            'api_key' => 'test_api_key',
            'environment' => 'sandbox',
            'webhook_secret' => 'test_secret',
            'webhook_url' => 'https://example.com/webhook',
            'webhook_enabled' => true,
            'is_enabled' => true
        ]);

        $this->assertDatabaseHas('unipayment_settings', [
            'webhook_url' => 'https://example.com/webhook',
            'webhook_enabled' => true
        ]);

        // Test the model methods
        $this->assertTrue($settings->isWebhookConfigured());
        $this->assertEquals('https://example.com/webhook', $settings->webhook_url);

        $status = $settings->getWebhookStatus();
        $this->assertIsArray($status);
        $this->assertTrue($status['enabled']);
        $this->assertTrue($status['configured']);
    }

    /** @test */
    public function webhook_test_status_can_be_updated()
    {
        $settings = UniPaymentSetting::create([
            'app_id' => 'test_app_id',
            'api_key' => 'test_api_key',
            'environment' => 'sandbox',
            'webhook_enabled' => true,
            'is_enabled' => true
        ]);

        $settings->updateWebhookTestStatus('success', 'Test successful');

        $this->assertDatabaseHas('unipayment_settings', [
            'webhook_test_status' => 'success',
            'webhook_test_response' => 'Test successful'
        ]);

        $settings->refresh();
        $this->assertNotNull($settings->last_webhook_test);
    }

    /** @test */
    public function webhook_received_can_be_recorded()
    {
        $settings = UniPaymentSetting::create([
            'app_id' => 'test_app_id',
            'api_key' => 'test_api_key',
            'environment' => 'sandbox',
            'webhook_enabled' => true,
            'webhook_retry_count' => 3,
            'is_enabled' => true
        ]);

        $settings->recordWebhookReceived();

        $settings->refresh();
        $this->assertNotNull($settings->last_webhook_received);
        $this->assertEquals(0, $settings->webhook_retry_count);
    }
}
