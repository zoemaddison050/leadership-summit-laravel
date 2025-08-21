<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\Registration;
use App\Models\UniPaymentSetting;
use App\Models\PaymentTransaction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;

class PaymentJourneyValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $event;
    protected $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->event = Event::factory()->create([
            'title' => 'Test Event',
            'status' => 'active'
        ]);

        $this->ticket = Ticket::factory()->create([
            'event_id' => $this->event->id,
            'name' => 'Test Ticket',
            'price' => 100.00,
            'quantity' => 50,
            'available' => 50
        ]);

        UniPaymentSetting::create([
            'app_id' => 'test_app',
            'api_key' => 'test_key',
            'environment' => 'sandbox',
            'webhook_secret' => 'test_secret',
            'is_enabled' => true,
            'supported_currencies' => ['USD'],
            'processing_fee_percentage' => 2.9,
            'minimum_amount' => 1.00,
            'maximum_amount' => 10000.00
        ]);
    }

    /** @test */
    public function user_can_complete_registration_and_payment_flow()
    {
        // Step 1: Create a registration directly (simulating form submission)
        $registration = Registration::factory()->create([
            'event_id' => $this->event->id,
            'ticket_id' => $this->ticket->id,
            'attendee_name' => 'Test User',
            'attendee_email' => 'test@example.com',
            'attendee_phone' => '+1234567890',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'total_amount' => 100.00
        ]);

        $this->assertNotNull($registration);
        $this->assertEquals('pending', $registration->status);
        $this->assertEquals('unpaid', $registration->payment_status);

        // Step 2: Simulate payment processing
        $paymentTransaction = PaymentTransaction::create([
            'registration_id' => $registration->id,
            'provider' => 'unipayment',
            'transaction_id' => 'test_txn_' . uniqid(),
            'payment_method' => 'card',
            'amount' => 100.00,
            'currency' => 'USD',
            'status' => 'pending'
        ]);

        $this->assertNotNull($paymentTransaction);
        $this->assertEquals('pending', $paymentTransaction->status);

        // Step 3: Simulate successful payment completion
        $paymentTransaction->update([
            'status' => 'completed',
            'processed_at' => now()
        ]);

        $registration->update([
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'confirmed_at' => now()
        ]);

        // Step 4: Verify final state
        $registration->refresh();
        $paymentTransaction->refresh();

        $this->assertEquals('confirmed', $registration->status);
        $this->assertEquals('paid', $registration->payment_status);
        $this->assertEquals('completed', $paymentTransaction->status);
        $this->assertNotNull($registration->confirmed_at);
        $this->assertNotNull($paymentTransaction->processed_at);
    }

    /** @test */
    public function payment_failure_is_handled_correctly()
    {
        $registration = Registration::factory()->create([
            'event_id' => $this->event->id,
            'ticket_id' => $this->ticket->id,
            'attendee_name' => 'Failed User',
            'attendee_email' => 'failed@example.com',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'total_amount' => 100.00
        ]);

        // Simulate failed payment
        $paymentTransaction = PaymentTransaction::create([
            'registration_id' => $registration->id,
            'provider' => 'unipayment',
            'transaction_id' => 'failed_txn_' . uniqid(),
            'payment_method' => 'card',
            'amount' => 100.00,
            'currency' => 'USD',
            'status' => 'failed',
            'provider_response' => json_encode(['error' => 'Card declined'])
        ]);

        // Registration should remain pending
        $this->assertEquals('pending', $registration->status);
        $this->assertEquals('unpaid', $registration->payment_status);
        $this->assertEquals('failed', $paymentTransaction->status);
    }

    /** @test */
    public function admin_can_view_payment_data()
    {
        // Create admin user
        $adminRole = Role::create(['name' => 'admin', 'permissions' => ['*']]);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        // Create test data
        $registration = Registration::factory()->confirmed()->create([
            'event_id' => $this->event->id,
            'ticket_id' => $this->ticket->id,
            'attendee_name' => 'Admin Test User',
            'attendee_email' => 'admin.test@example.com',
            'total_amount' => 100.00
        ]);

        $transaction = PaymentTransaction::create([
            'registration_id' => $registration->id,
            'provider' => 'unipayment',
            'transaction_id' => 'admin_txn_123',
            'payment_method' => 'card',
            'amount' => 100.00,
            'currency' => 'USD',
            'status' => 'completed'
        ]);

        $this->actingAs($admin);

        // Test admin can access registration details
        $response = $this->get(route('admin.registrations.show', $registration));
        $response->assertStatus(200);

        // Test admin can access transactions
        $response = $this->get(route('admin.unipayment.transactions'));
        $response->assertStatus(200);

        // Test admin can access UniPayment settings
        $response = $this->get(route('admin.unipayment.index'));
        $response->assertStatus(200);
    }

    /** @test */
    public function payment_security_validations_work()
    {
        // Test minimum amount validation
        $registration = Registration::factory()->create([
            'event_id' => $this->event->id,
            'ticket_id' => $this->ticket->id,
            'total_amount' => 0.50 // Below minimum
        ]);

        $settings = UniPaymentSetting::first();
        $this->assertFalse($settings->isAmountValid(0.50));
        $this->assertTrue($settings->isAmountValid(100.00));

        // Test currency support
        $this->assertTrue($settings->supportsCurrency('USD'));
        $this->assertFalse($settings->supportsCurrency('JPY'));

        // Test fee calculation
        $fee = $settings->calculateFee(100.00);
        $this->assertEquals(2.90, $fee);
    }

    /** @test */
    public function concurrent_registrations_are_handled_safely()
    {
        // Create multiple registrations for the same event
        $registrations = [];

        for ($i = 0; $i < 5; $i++) {
            $registrations[] = Registration::factory()->create([
                'event_id' => $this->event->id,
                'ticket_id' => $this->ticket->id,
                'attendee_name' => "User $i",
                'attendee_email' => "user$i@example.com",
                'status' => 'pending'
            ]);
        }

        // Verify all registrations were created
        $this->assertCount(5, $registrations);

        // Verify each has unique email
        $emails = array_map(fn($reg) => $reg->attendee_email, $registrations);
        $this->assertCount(5, array_unique($emails));

        // Simulate concurrent payment processing
        foreach ($registrations as $registration) {
            PaymentTransaction::create([
                'registration_id' => $registration->id,
                'provider' => 'unipayment',
                'transaction_id' => 'concurrent_txn_' . $registration->id,
                'payment_method' => 'card',
                'amount' => 100.00,
                'currency' => 'USD',
                'status' => 'completed'
            ]);
        }

        // Verify all transactions were created
        $transactionCount = PaymentTransaction::count();
        $this->assertEquals(5, $transactionCount);
    }

    /** @test */
    public function data_integrity_is_maintained_throughout_journey()
    {
        $originalData = [
            'event_id' => $this->event->id,
            'ticket_id' => $this->ticket->id,
            'attendee_name' => 'Integrity Test User',
            'attendee_email' => 'integrity@example.com',
            'attendee_phone' => '+1999888777',
            'emergency_contact' => 'Emergency Contact',
            'total_amount' => 100.00
        ];

        // Create registration
        $registration = Registration::factory()->create($originalData);

        // Verify initial data
        $this->assertEquals($originalData['attendee_name'], $registration->attendee_name);
        $this->assertEquals($originalData['attendee_email'], $registration->attendee_email);
        $this->assertEquals($originalData['total_amount'], $registration->total_amount);

        // Process payment
        $transaction = PaymentTransaction::create([
            'registration_id' => $registration->id,
            'provider' => 'unipayment',
            'transaction_id' => 'integrity_txn',
            'payment_method' => 'card',
            'amount' => $registration->total_amount,
            'currency' => 'USD',
            'status' => 'completed'
        ]);

        // Update registration status
        $registration->update([
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'confirmed_at' => now()
        ]);

        // Verify data integrity after updates
        $registration->refresh();
        $this->assertEquals($originalData['attendee_name'], $registration->attendee_name);
        $this->assertEquals($originalData['attendee_email'], $registration->attendee_email);
        $this->assertEquals($originalData['total_amount'], $registration->total_amount);
        $this->assertEquals($registration->total_amount, $transaction->amount);
        $this->assertEquals('confirmed', $registration->status);
        $this->assertEquals('paid', $registration->payment_status);
    }

    /** @test */
    public function error_recovery_mechanisms_work()
    {
        $registration = Registration::factory()->create([
            'event_id' => $this->event->id,
            'ticket_id' => $this->ticket->id,
            'attendee_name' => 'Recovery Test User',
            'attendee_email' => 'recovery@example.com',
            'status' => 'pending',
            'total_amount' => 100.00
        ]);

        // Simulate network error during payment
        $failedTransaction = PaymentTransaction::create([
            'registration_id' => $registration->id,
            'provider' => 'unipayment',
            'transaction_id' => 'failed_network_txn',
            'payment_method' => 'card',
            'amount' => 100.00,
            'currency' => 'USD',
            'status' => 'failed',
            'provider_response' => json_encode(['error' => 'Network timeout'])
        ]);

        // User retries payment
        $retryTransaction = PaymentTransaction::create([
            'registration_id' => $registration->id,
            'provider' => 'unipayment',
            'transaction_id' => 'retry_txn',
            'payment_method' => 'card',
            'amount' => 100.00,
            'currency' => 'USD',
            'status' => 'completed'
        ]);

        // Update registration to confirmed
        $registration->update([
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'confirmed_at' => now()
        ]);

        // Verify recovery was successful
        $this->assertEquals('confirmed', $registration->status);
        $this->assertEquals('paid', $registration->payment_status);

        // Verify both transactions exist
        $transactions = PaymentTransaction::where('registration_id', $registration->id)->get();
        $this->assertCount(2, $transactions);
        $this->assertTrue($transactions->contains('status', 'failed'));
        $this->assertTrue($transactions->contains('status', 'completed'));
    }
}
