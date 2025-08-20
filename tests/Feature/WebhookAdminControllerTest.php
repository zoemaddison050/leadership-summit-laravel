<?php

namespace Tests\Feature;

use App\Models\UniPaymentSetting;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookAdminControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role and user
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'System Administrator'
        ]);

        $this->adminUser = User::factory()->create([
            'role_id' => $adminRole->id,
            'email' => 'admin@test.com'
        ]);

        // Create basic UniPayment settings
        UniPaymentSetting::create([
            'app_id' => 'test_app_id',
            'api_key' => 'test_api_key',
            'environment' => 'sandbox',
            'webhook_enabled' => true,
            'is_enabled' => true
        ]);
    }

    /** @test */
    public function admin_can_get_webhook_status()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.unipayment.webhook-status'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'status' => [
                'enabled',
                'configured',
                'url',
                'last_test',
                'test_status',
                'test_response',
                'last_received',
                'retry_count'
            ]
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertTrue($data['status']['enabled']);
    }

    /** @test */
    public function admin_can_generate_webhook_url()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.unipayment.generate-webhook-url'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'webhook_url'
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertNotEmpty($data['webhook_url']);
    }

    /** @test */
    public function admin_can_validate_webhook_url()
    {
        $validUrl = 'https://example.com/webhook';

        $response = $this->actingAs($this->adminUser)
            ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->post(route('admin.unipayment.validate-webhook-url'), [
                'webhook_url' => $validUrl
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'valid',
            'accessible',
            'webhook_url'
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertTrue($data['valid']);
        $this->assertEquals($validUrl, $data['webhook_url']);
    }

    /** @test */
    public function webhook_url_validation_fails_for_invalid_url()
    {
        $invalidUrl = 'not-a-valid-url';

        $response = $this->actingAs($this->adminUser)
            ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->post(route('admin.unipayment.validate-webhook-url'), [
                'webhook_url' => $invalidUrl
            ]);

        $response->assertStatus(400);
        $data = $response->json();
        $this->assertFalse($data['success']);
        $this->assertFalse($data['valid']);
    }

    /** @test */
    public function admin_can_update_webhook_settings()
    {
        $webhookData = [
            'app_id' => 'test_app_id',
            'api_key' => 'test_api_key',
            'environment' => 'sandbox',
            'webhook_secret' => 'updated_secret',
            'webhook_url' => 'https://example.com/webhook',
            'webhook_enabled' => true,
            'is_enabled' => true,
            'processing_fee_percentage' => 2.9,
            'minimum_amount' => 1.00,
            'maximum_amount' => 10000.00,
            'supported_currencies' => 'USD, EUR'
        ];

        $response = $this->actingAs($this->adminUser)
            ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->from(route('admin.unipayment.index'))
            ->patch(route('admin.unipayment.update'), $webhookData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('unipayment_settings', [
            'webhook_url' => 'https://example.com/webhook',
            'webhook_enabled' => true,
            'webhook_secret' => 'updated_secret'
        ]);
    }

    /** @test */
    public function non_admin_cannot_access_webhook_endpoints()
    {
        $regularUser = User::factory()->create();

        $response = $this->actingAs($regularUser)
            ->get(route('admin.unipayment.webhook-status'));

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_webhook_endpoints()
    {
        $response = $this->get(route('admin.unipayment.webhook-status'));
        $response->assertRedirect(route('login'));
    }
}
