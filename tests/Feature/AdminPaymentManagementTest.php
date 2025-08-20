<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Event;
use App\Models\Registration;
use App\Models\PaymentTransaction;
use App\Models\UniPaymentSetting;
use App\Services\UniPaymentService;
use UniPayment\SDK\BillingAPI;
use UniPayment\SDK\Configuration;
use UniPayment\SDK\Model\CreateInvoiceResponse;
use UniPayment\SDK\Model\InvoiceData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Role;
use Mockery;

class AdminPaymentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $event;
    protected $mockBillingAPI;
    protected $mockConfiguration;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin role and user
        $adminRole = Role::create(['name' => 'admin']);

        $this->adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password')
        ]);

        $this->adminUser->assignRole($adminRole);

        // Create test event
        $this->event = Event::create([
            'name' => 'Admin Test Event',
            'description' => 'Event for admin payment management testing',
            'start_date' => now()->addDays(30),
            'end_date' => now()->addDays(31),
            'location' => 'Admin Test Location',
            'max_attendees' => 100,
            'is_active' => true,
            'is_default' => false
        ]);

        // Mock UniPayment dependencies
        $this->mockBillingAPI = Mockery::mock(BillingAPI::class);
        $this->mockConfiguration = Mockery::mock(Configuration::class);

        $this->app->bind(BillingAPI::class, function () {
            return $this->mockBillingAPI;
        });

        $this->app->bind(Configuration::class, function () {
            return $this->mockConfiguration;
        });

        // Set up UniPayment configuration
        Config::set('unipayment', [
            'app_id' => 'admin_test_app_id',
            'client_id' => 'admin_test_client_id',
            'client_secret' => 'admin_test_client_secret',
            'environment' => 'sandbox',
            'webhook_secret' => 'admin_test_webhook_secret',
            'supported_currencies' => ['USD'],
            'default_currency' => 'USD',
            'processing_fee_percentage' => 2.9,
            'minimum_amount' => 1.00,
            'maximum_amount' => 10000.00,
            'logging' => [
                'enabled' => true,
                'level' => 'info'
            ]
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function admin_can_view_unipayment_configuration_page()
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.unipayment.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.unipayment.index');
        $response->assertSee('UniPayment Configuration');
    }

    /** @test */
    public function admin_can_test_unipayment_connection()
    {
        // Mock successful connection test
        $mockResponse = Mockery::mock(CreateInvoiceResponse::class);
        $mockInvoiceData = Mockery::mock(InvoiceData::class);

        $mockInvoiceData->shouldReceive('getInvoiceId')->andReturn('test_connection_invoice');
        $mockResponse->shouldReceive('getData')->andReturn($mockInvoiceData);

        $this->mockBillingAPI
            ->shouldReceive('createInvoice')
            ->once()
            ->andReturn($mockResponse);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.unipayment.test-connection'), [
                'app_id' => 'test_app_id',
                'api_key' => 'test_api_key',
                'environment' => 'sandbox'
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Connection successful! API credentials are valid.'
        ]);
    }

    /** @test */
    public function admin_can_save_unipayment_configuration()
    {
        $configData = [
            'app_id' => 'new_app_id_123',
            'api_key' => 'new_api_key_456',
            'environment' => 'production',
            'webhook_secret' => 'new_webhook_secret_789',
            'is_enabled' => true,
            'supported_currencies' => ['USD', 'EUR'],
            'processing_fee_percentage' => 3.5,
            'minimum_amount' => 5.00,
            'maximum_amount' => 50000.00
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.unipayment.store'), $configData);

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.unipayment.index'));
        $response->assertSessionHas('success');

        // Verify configuration was saved
        $this->assertDatabaseHas('unipayment_settings', [
            'app_id' => 'new_app_id_123',
            'environment' => 'production',
            'is_enabled' => true,
            'processing_fee_percentage' => 3.5,
            'minimum_amount' => 5.00,
            'maximum_amount' => 50000.00
        ]);
    }

    /** @test */
    public function admin_can_view_payment_transactions()
    {
        // Create test registrations and transactions
        $registration1 = Registration::create([
            'event_id' => $this->event->id,
            'attendee_name' => 'Transaction Test User 1',
            'attendee_email' => 'transaction1@example.com',
            'attendee_phone' => '+1 (555) 111-1111',
            'payment_method' => 'card',
            'payment_provider' => 'unipayment',
            'transaction_id' => 'txn_admin_test_1',
            'payment_amount' => 99.99,
            'payment_currency' => 'USD',
            'payment_status' => 'completed'
        ]);

        $registration2 = Registration::create([
            'event_id' => $this->event->id,
            'attendee_name' => 'Transaction Test User 2',
            'attendee_email' => 'transaction2@example.com',
            'attendee_phone' => '+1 (555) 222-2222',
            'payment_method' => 'card',
            'payment_provider' => 'unipayment',
            'transaction_id' => 'txn_admin_test_2',
            'payment_amount' => 149.99,
            'payment_currency' => 'USD',
            'payment_status' => 'pending'
        ]);

        PaymentTransaction::create([
            'registration_id' => $registration1->id,
            'provider' => 'unipayment',
            'transaction_id' => 'txn_admin_test_1',
            'payment_method' => 'card',
            'amount' => 99.99,
            'currency' => 'USD',
            'status' => 'completed',
            'processed_at' => now()
        ]);

        PaymentTransaction::create([
            'registration_id' => $registration2->id,
            'provider' => 'unipayment',
            'transaction_id' => 'txn_admin_test_2',
            'payment_method' => 'card',
            'amount' => 149.99,
            'currency' => 'USD',
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.unipayment.transactions'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.unipayment.transactions');
        $response->assertSee('Transaction Test User 1');
        $response->assertSee('Transaction Test User 2');
        $response->assertSee('txn_admin_test_1');
        $response->assertSee('txn_admin_test_2');
        $response->assertSee('$99.99');
        $response->assertSee('$149.99');
        $response->assertSee('completed');
        $response->assertSee('pending');
    }

    /** @test */
    public function admin_can_filter_payment_transactions()
    {
        // Create transactions with different statuses
        $completedRegistration = Registration::create([
            'event_id' => $this->event->id,
            'attendee_name' => 'Completed User',
            'attendee_email' => 'completed@example.com',
            'attendee_phone' => '+1 (555) 333-3333',
            'payment_method' => 'card',
            'payment_provider' => 'unipayment',
            'transaction_id' => 'txn_completed',
            'payment_amount' => 75.00,
            'payment_currency' => 'USD',
            'payment_status' => 'completed'
        ]);

        $pendingRegistration = Registration::create([
            'event_id' => $this->event->id,
            'attendee_name' => 'Pending User',
            'attendee_email' => 'pending@example.com',
            'attendee_phone' => '+1 (555) 444-4444',
            'payment_method' => 'card',
            'payment_provider' => 'unipayment',
            'transaction_id' => 'txn_pending',
            'payment_amount' => 125.00,
            'payment_currency' => 'USD',
            'payment_status' => 'pending'
        ]);

        PaymentTransaction::create([
            'registration_id' => $completedRegistration->id,
            'provider' => 'unipayment',
            'transaction_id' => 'txn_completed',
            'payment_method' => 'card',
            'amount' => 75.00,
            'currency' => 'USD',
            'status' => 'completed',
            'processed_at' => now()
        ]);

        PaymentTransaction::create([
            'registration_id' => $pendingRegistration->id,
            'provider' => 'unipayment',
            'transaction_id' => 'txn_pending',
            'payment_method' => 'card',
            'amount' => 125.00,
            'currency' => 'USD',
            'status' => 'pending'
        ]);

        // Filter by completed status
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.unipayment.transactions', ['status' => 'completed']));

        $response->assertStatus(200);
        $response->assertSee('Completed User');
        $response->assertDontSee('Pending User');

        // Filter by pending status
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.unipayment.transactions', ['status' => 'pending']));

        $response->assertStatus(200);
        $response->assertSee('Pending User');
        $response->assertDontSee('Completed User');

        // Filter by event
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.unipayment.transactions', ['event_id' => $this->event->id]));

        $response->assertStatus(200);
        $response->assertSee('Completed User');
        $response->assertSee('Pending User');
    }

    /** @test */
    public function admin_can_export_payment_transactions()
    {
        // Create test transactions
        $registration = Registration::create([
            'event_id' => $this->event->id,
            'attendee_name' => 'Export Test User',
            'attendee_email' => 'export@example.com',
            'attendee_phone' => '+1 (555) 555-5555',
            'payment_method' => 'card',
            'payment_provider' => 'unipayment',
            'transaction_id' => 'txn_export_test',
            'payment_amount' => 199.99,
            'payment_currency' => 'USD',
            'payment_status' => 'completed'
        ]);

        PaymentTransaction::create([
            'registration_id' => $registration->id,
            'provider' => 'unipayment',
            'transaction_id' => 'txn_export_test',
            'payment_method' => 'card',
            'amount' => 199.99,
            'currency' => 'USD',
            'status' => 'completed',
            'processed_at' => now()
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.unipayment.transactions.export', ['format' => 'csv']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="unipayment_transactions_' . now()->format('Y-m-d') . '.csv"');

        $content = $response->getContent();
        $this->assertStringContainsString('Export Test User', $content);
        $this->assertStringContainsString('txn_export_test', $content);
        $this->assertStringContainsString('199.99', $content);
    }

    /** @test */
    public function admin_can_manually_verify_payment_status()
    {
        // Create a pending registration
        $registration = Registration::create([
            'event_id' => $this->event->id,
            'attendee_name' => 'Manual Verify User',
            'attendee_email' => 'verify@example.com',
            'attendee_phone' => '+1 (555) 666-6666',
            'payment_method' => 'card',
            'payment_provider' => 'unipayment',
            'transaction_id' => 'txn_manual_verify',
            'payment_amount' => 89.99,
            'payment_currency' => 'USD',
            'payment_status' => 'pending'
        ]);

        PaymentTransaction::create([
            'registration_id' => $registration->id,
            'provider' => 'unipayment',
            'transaction_id' => 'txn_manual_verify',
            'payment_method' => 'card',
            'amount' => 89.99,
            'currency' => 'USD',
            'status' => 'pending'
        ]);

        // Mock successful payment verification
        $mockStatusResponse = Mockery::mock(\UniPayment\SDK\Model\GetInvoiceByIdResponse::class);
        $mockStatusData = Mockery::mock(InvoiceData::class);

        $mockStatusData->shouldReceive('getStatus')->andReturn('confirmed');
        $mockStatusResponse->shouldReceive('getData')->andReturn($mockStatusData);

        $this->mockBillingAPI
            ->shouldReceive('getInvoiceById')
            ->once()
            ->with('txn_manual_verify')
            ->andReturn($mockStatusResponse);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.unipayment.verify-payment'), [
                'transaction_id' => 'txn_manual_verify'
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'confirmed'
        ]);

        // Verify registration was updated
        $registration->refresh();
        $this->assertEquals('completed', $registration->payment_status);
        $this->assertNotNull($registration->payment_completed_at);
    }

    /** @test */
    public function admin_can_refund_payment()
    {
        // Create a completed registration
        $registration = Registration::create([
            'event_id' => $this->event->id,
            'attendee_name' => 'Refund Test User',
            'attendee_email' => 'refund@example.com',
            'attendee_phone' => '+1 (555) 777-7777',
            'payment_method' => 'card',
            'payment_provider' => 'unipayment',
            'transaction_id' => 'txn_refund_test',
            'payment_amount' => 299.99,
            'payment_currency' => 'USD',
            'payment_status' => 'completed',
            'payment_completed_at' => now()->subDays(5)
        ]);

        PaymentTransaction::create([
            'registration_id' => $registration->id,
            'provider' => 'unipayment',
            'transaction_id' => 'txn_refund_test',
            'payment_method' => 'card',
            'amount' => 299.99,
            'currency' => 'USD',
            'status' => 'completed',
            'processed_at' => now()->subDays(5)
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.unipayment.refund'), [
                'transaction_id' => 'txn_refund_test',
                'refund_amount' => 299.99,
                'refund_reason' => 'Event cancelled'
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Refund processed successfully'
        ]);

        // Verify registration was updated
        $registration->refresh();
        $this->assertEquals('refunded', $registration->payment_status);
        $this->assertNotNull($registration->refunded_at);
        $this->assertEquals('Event cancelled', $registration->refund_reason);
    }

    /** @test */
    public function admin_can_view_payment_statistics()
    {
        // Create various transactions for statistics
        $registrations = [
            [
                'attendee_name' => 'Stats User 1',
                'attendee_email' => 'stats1@example.com',
                'payment_amount' => 100.00,
                'payment_status' => 'completed'
            ],
            [
                'attendee_name' => 'Stats User 2',
                'attendee_email' => 'stats2@example.com',
                'payment_amount' => 150.00,
                'payment_status' => 'completed'
            ],
            [
                'attendee_name' => 'Stats User 3',
                'attendee_email' => 'stats3@example.com',
                'payment_amount' => 75.00,
                'payment_status' => 'pending'
            ]
        ];

        foreach ($registrations as $index => $regData) {
            $registration = Registration::create([
                'event_id' => $this->event->id,
                'attendee_name' => $regData['attendee_name'],
                'attendee_email' => $regData['attendee_email'],
                'attendee_phone' => '+1 (555) 888-888' . $index,
                'payment_method' => 'card',
                'payment_provider' => 'unipayment',
                'transaction_id' => 'txn_stats_' . ($index + 1),
                'payment_amount' => $regData['payment_amount'],
                'payment_currency' => 'USD',
                'payment_status' => $regData['payment_status']
            ]);

            PaymentTransaction::create([
                'registration_id' => $registration->id,
                'provider' => 'unipayment',
                'transaction_id' => 'txn_stats_' . ($index + 1),
                'payment_method' => 'card',
                'amount' => $regData['payment_amount'],
                'currency' => 'USD',
                'status' => $regData['payment_status'],
                'processed_at' => $regData['payment_status'] === 'completed' ? now() : null
            ]);
        }

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.unipayment.statistics'));

        $response->assertStatus(200);
        $response->assertJson([
            'total_transactions' => 3,
            'completed_transactions' => 2,
            'pending_transactions' => 1,
            'total_revenue' => 250.00, // 100 + 150
            'pending_revenue' => 75.00,
            'average_transaction_amount' => 108.33 // (100 + 150 + 75) / 3
        ]);
    }

    /** @test */
    public function admin_cannot_access_unipayment_without_permission()
    {
        // Create regular user without admin role
        $regularUser = User::factory()->create([
            'name' => 'Regular User',
            'email' => 'regular@example.com'
        ]);

        $response = $this->actingAs($regularUser)
            ->get(route('admin.unipayment.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_disable_unipayment_service()
    {
        // Create initial configuration
        UniPaymentSetting::create([
            'app_id' => 'disable_test_app_id',
            'api_key' => 'disable_test_api_key',
            'environment' => 'sandbox',
            'webhook_secret' => 'disable_test_webhook_secret',
            'is_enabled' => true,
            'supported_currencies' => ['USD'],
            'processing_fee_percentage' => 2.9,
            'minimum_amount' => 1.00,
            'maximum_amount' => 10000.00
        ]);

        // Disable the service
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.unipayment.toggle'), [
                'is_enabled' => false
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'UniPayment service disabled successfully'
        ]);

        // Verify service was disabled
        $this->assertDatabaseHas('unipayment_settings', [
            'app_id' => 'disable_test_app_id',
            'is_enabled' => false
        ]);
    }

    /** @test */
    public function admin_receives_validation_errors_for_invalid_configuration()
    {
        $invalidConfigData = [
            'app_id' => '', // Required field empty
            'api_key' => 'short', // Too short
            'environment' => 'invalid_env', // Invalid environment
            'processing_fee_percentage' => -1, // Negative percentage
            'minimum_amount' => -5.00, // Negative amount
            'maximum_amount' => 0 // Zero maximum
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.unipayment.store'), $invalidConfigData);

        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'app_id',
            'api_key',
            'environment',
            'processing_fee_percentage',
            'minimum_amount',
            'maximum_amount'
        ]);
    }
}
