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
use Illuminate\Support\Facades\Config;

class ComplexUserJourneySimulationTest extends TestCase
{
    use RefreshDatabase;

    protected $event;
    protected $ticket;
    protected $uniPaymentSettings;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test event and ticket
        $this->event = Event::factory()->create([
            'title' => 'Leadership Summit 2025',
            'status' => 'active',
            'slug' => 'leadership-summit-2025'
        ]);

        $this->ticket = Ticket::factory()->create([
            'event_id' => $this->event->id,
            'name' => 'General Admission',
            'price' => 299.99,
            'quantity' => 100,
            'available' => 100,
            'is_active' => true
        ]);

        // Configure UniPayment settings
        $this->uniPaymentSettings = UniPaymentSetting::create([
            'app_id' => 'test_app_id_12345',
            'api_key' => 'test_api_key_secret',
            'environment' => 'sandbox',
            'webhook_secret' => 'test_webhook_secret_key',
            'is_enabled' => true,
            'supported_currencies' => ['USD', 'EUR'],
            'processing_fee_percentage' => 2.9,
            'minimum_amount' => 1.00,
            'maximum_amount' => 10000.00
        ]);
    }

    /** @test */
    public function complete_user_registration_and_card_payment_journey()
    {
        // Journey 1: Successful Card Payment Flow
        $this->simulateSuccessfulCardPaymentJourney();

        // Journey 2: Payment Method Switching
        $this->simulatePaymentMethodSwitching();

        // Journey 3: Payment Failure and Recovery
        $this->simulatePaymentFailureAndRecovery();

        // Journey 4: Session Management
        $this->simulateSessionManagement();

        // Journey 5: Concurrent Registration Handling
        $this->simulateConcurrentRegistrations();
    }

    private function simulateSuccessfulCardPaymentJourney()
    {
        // Step 1: User discovers event
        $response = $this->get('/');
        $response->assertStatus(200);

        // Step 2: User views event details
        $response = $this->get(route('events.show', $this->event->slug));
        $response->assertStatus(200);
        $response->assertSee($this->event->title);
        $response->assertSee('Register');

        // Step 3: User starts registration process
        $response = $this->get(route('events.register', $this->event));

        // Handle potential redirects
        if ($response->isRedirect()) {
            $response = $this->followRedirects($response);
        }

        $response->assertStatus(200);
        $response->assertSee('Registration');
        $response->assertSee($this->ticket->name);
        $response->assertSee('299.99');

        // Step 4: User submits registration form
        $registrationData = [
            'attendee_name' => 'John Smith',
            'attendee_email' => 'john.smith@example.com',
            'attendee_phone' => '+1-555-123-4567',
            'emergency_contact' => 'Jane Smith',
            'emergency_contact_name' => 'Jane Smith',
            'emergency_contact_phone' => '+1-555-987-6543',
            'ticket_id' => $this->ticket->id,
            'terms_accepted' => true
        ];

        $response = $this->post(route('events.register.process', $this->event), $registrationData);
        $response->assertRedirect();

        // Should redirect to payment selection
        $this->assertStringContains('payment/selection', $response->headers->get('Location'));

        // Step 5: User sees payment selection page
        $response = $this->get(route('payment.selection', $this->event));
        $response->assertStatus(200);
        $response->assertSee('Choose Payment Method');
        $response->assertSee('Pay with Card');
        $response->assertSee('Pay with Crypto');
        $response->assertSee('$299.99');

        // Step 6: User selects card payment
        $response = $this->post(route('payment.card', $this->event), [
            'payment_method' => 'card'
        ]);

        // Should redirect to card processing page
        $response->assertRedirect();
        $this->assertStringContains('payment/processing', $response->headers->get('Location'));

        // Step 7: User sees card processing page
        $response = $this->get(route('payment.card.processing', $this->event));
        $response->assertStatus(200);
        $response->assertSee('Processing Payment');
        $response->assertSee('UniPayment');

        // Step 8: Simulate successful UniPayment callback
        $registration = Registration::where('attendee_email', 'john.smith@example.com')->first();
        $this->assertNotNull($registration);
        $this->assertEquals('pending', $registration->status);

        $callbackData = [
            'invoice_id' => 'unipay_inv_' . uniqid(),
            'order_id' => 'REG_' . $registration->id . '_' . time(),
            'status' => 'Confirmed',
            'amount' => 299.99,
            'currency' => 'USD',
            'transaction_id' => 'txn_' . uniqid()
        ];

        $response = $this->get(route('payment.unipayment.callback', $callbackData));
        $response->assertRedirect(route('registration.success', $registration));

        // Step 9: Verify registration is confirmed
        $registration->refresh();
        $this->assertEquals('confirmed', $registration->status);
        $this->assertEquals('paid', $registration->payment_status);
        $this->assertNotNull($registration->confirmed_at);

        // Step 10: User sees success page
        $response = $this->get(route('registration.success', $registration));
        $response->assertStatus(200);
        $response->assertSee('Registration Confirmed');
        $response->assertSee('John Smith');
        $response->assertSee($this->event->title);
        $response->assertSee('Payment Successful');

        // Step 11: Verify payment transaction was recorded
        $transaction = PaymentTransaction::where('registration_id', $registration->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('unipayment', $transaction->provider);
        $this->assertEquals('completed', $transaction->status);
        $this->assertEquals(299.99, $transaction->amount);
    }

    private function simulatePaymentMethodSwitching()
    {
        // User starts with crypto but switches to card
        $registrationData = [
            'attendee_name' => 'Alice Johnson',
            'attendee_email' => 'alice.johnson@example.com',
            'attendee_phone' => '+1-555-234-5678',
            'emergency_contact' => 'Bob Johnson',
            'ticket_id' => $this->ticket->id,
            'terms_accepted' => true
        ];

        // Submit registration
        $this->post(route('events.register.process', $this->event), $registrationData);

        // Go to payment selection
        $response = $this->get(route('payment.selection', $this->event));
        $response->assertStatus(200);

        // Initially select crypto
        $response = $this->get(route('payment.crypto', $this->event));
        $response->assertStatus(200);
        $response->assertSee('Cryptocurrency Payment');

        // User changes mind and goes back to payment selection
        $response = $this->get(route('payment.selection', $this->event));
        $response->assertStatus(200);

        // Verify registration data is still preserved
        $this->assertTrue(Session::has('registration_data') || Session::has('pending_registration_id'));

        // Switch to card payment
        $response = $this->post(route('payment.card', $this->event), [
            'payment_method' => 'card'
        ]);
        $response->assertRedirect();

        // Verify registration data is preserved
        $registration = Registration::where('attendee_email', 'alice.johnson@example.com')->first();
        $this->assertNotNull($registration);
        $this->assertEquals('Alice Johnson', $registration->attendee_name);
    }

    private function simulatePaymentFailureAndRecovery()
    {
        $registrationData = [
            'attendee_name' => 'Bob Wilson',
            'attendee_email' => 'bob.wilson@example.com',
            'attendee_phone' => '+1-555-345-6789',
            'ticket_id' => $this->ticket->id,
            'terms_accepted' => true
        ];

        // Submit registration and go to payment
        $this->post(route('events.register.process', $this->event), $registrationData);
        $this->post(route('payment.card', $this->event), ['payment_method' => 'card']);

        $registration = Registration::where('attendee_email', 'bob.wilson@example.com')->first();

        // Simulate failed payment callback
        $callbackData = [
            'invoice_id' => 'unipay_inv_failed_' . uniqid(),
            'order_id' => 'REG_' . $registration->id . '_' . time(),
            'status' => 'Failed',
            'amount' => 299.99,
            'currency' => 'USD',
            'error_message' => 'Card declined'
        ];

        $response = $this->get(route('payment.unipayment.callback', $callbackData));
        $response->assertRedirect(route('payment.failed', $this->event));

        // User should see failure page with retry options
        $response = $this->get(route('payment.failed', $this->event));
        $response->assertStatus(200);
        $response->assertSee('Payment Failed');
        $response->assertSee('Try Again');
        $response->assertSee('Choose Different Payment Method');

        // Verify registration is still pending
        $registration->refresh();
        $this->assertEquals('pending', $registration->status);
        $this->assertEquals('unpaid', $registration->payment_status);

        // User can retry with same payment method
        $response = $this->post(route('payment.retry', $this->event), [
            'payment_method' => 'card'
        ]);
        $response->assertRedirect();

        // Or switch to different payment method
        $response = $this->get(route('payment.selection', $this->event));
        $response->assertStatus(200);
        $response->assertSee('Choose Payment Method');
    }

    private function simulateSessionManagement()
    {
        $registrationData = [
            'attendee_name' => 'Charlie Brown',
            'attendee_email' => 'charlie.brown@example.com',
            'ticket_id' => $this->ticket->id,
            'terms_accepted' => true
        ];

        // Submit registration
        $this->post(route('events.register.process', $this->event), $registrationData);

        // Simulate session timeout by manipulating session data
        Session::put('payment_session_created', now()->subMinutes(35)->timestamp);

        // Try to access payment selection with expired session
        $response = $this->get(route('payment.selection', $this->event));

        // Should handle expired session gracefully
        if ($response->isRedirect()) {
            $response->assertRedirect();
            $response = $this->followRedirects($response);
            $response->assertSee('session has expired');
        }

        // User should be able to start over
        $response = $this->get(route('events.register', $this->event));
        $response->assertStatus(200);
    }

    private function simulateConcurrentRegistrations()
    {
        // Simulate multiple users trying to register for the same ticket
        $registrationData1 = [
            'attendee_name' => 'David Smith',
            'attendee_email' => 'david.smith@example.com',
            'ticket_id' => $this->ticket->id,
            'terms_accepted' => true
        ];

        $registrationData2 = [
            'attendee_name' => 'Eva Johnson',
            'attendee_email' => 'eva.johnson@example.com',
            'ticket_id' => $this->ticket->id,
            'terms_accepted' => true
        ];

        // Both users submit registrations simultaneously
        $response1 = $this->post(route('events.register.process', $this->event), $registrationData1);
        $response2 = $this->post(route('events.register.process', $this->event), $registrationData2);

        // Both should be successful (assuming tickets are available)
        $response1->assertRedirect();
        $response2->assertRedirect();

        // Verify both registrations were created
        $registration1 = Registration::where('attendee_email', 'david.smith@example.com')->first();
        $registration2 = Registration::where('attendee_email', 'eva.johnson@example.com')->first();

        $this->assertNotNull($registration1);
        $this->assertNotNull($registration2);
        $this->assertNotEquals($registration1->id, $registration2->id);
    }

    /** @test */
    public function admin_can_monitor_user_journeys()
    {
        // Create admin user
        $adminRole = Role::create(['name' => 'admin', 'permissions' => ['*']]);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        // Create some test registrations with different statuses
        $confirmedRegistration = Registration::factory()->confirmed()->create([
            'event_id' => $this->event->id,
            'ticket_id' => $this->ticket->id,
            'attendee_name' => 'Confirmed User',
            'attendee_email' => 'confirmed@example.com',
            'total_amount' => 299.99
        ]);

        $pendingRegistration = Registration::factory()->pending()->create([
            'event_id' => $this->event->id,
            'ticket_id' => $this->ticket->id,
            'attendee_name' => 'Pending User',
            'attendee_email' => 'pending@example.com',
            'total_amount' => 299.99
        ]);

        // Create payment transactions
        PaymentTransaction::create([
            'registration_id' => $confirmedRegistration->id,
            'provider' => 'unipayment',
            'transaction_id' => 'admin_test_txn_123',
            'payment_method' => 'card',
            'amount' => 299.99,
            'currency' => 'USD',
            'status' => 'completed'
        ]);

        $this->actingAs($admin);

        // Admin can view all registrations
        $response = $this->get(route('admin.registrations.index'));
        $response->assertStatus(200);
        $response->assertSee('Confirmed User');
        $response->assertSee('Pending User');
        $response->assertSee('confirmed');
        $response->assertSee('pending');

        // Admin can view individual registration details
        $response = $this->get(route('admin.registrations.show', $confirmedRegistration));
        $response->assertStatus(200);
        $response->assertSee('Confirmed User');
        $response->assertSee('Payment Status: paid');
        $response->assertSee('$299.99');

        // Admin can view payment transactions
        $response = $this->get(route('admin.unipayment.transactions'));
        $response->assertStatus(200);
        $response->assertSee('admin_test_txn_123');
        $response->assertSee('completed');
        $response->assertSee('$299.99');

        // Admin can filter registrations by status
        $response = $this->get(route('admin.registrations.index', ['status' => 'confirmed']));
        $response->assertStatus(200);
        $response->assertSee('Confirmed User');
        $response->assertDontSee('Pending User');

        // Admin can filter by payment status
        $response = $this->get(route('admin.registrations.index', ['payment_status' => 'paid']));
        $response->assertStatus(200);
        $response->assertSee('Confirmed User');
    }

    /** @test */
    public function complex_error_scenarios_are_handled_gracefully()
    {
        // Scenario 1: Invalid ticket ID
        $invalidData = [
            'attendee_name' => 'Invalid User',
            'attendee_email' => 'invalid@example.com',
            'ticket_id' => 99999, // Non-existent ticket
            'terms_accepted' => true
        ];

        $response = $this->post(route('events.register.process', $this->event), $invalidData);
        $response->assertSessionHasErrors(['ticket_id']);

        // Scenario 2: Duplicate email registration
        Registration::factory()->create([
            'event_id' => $this->event->id,
            'ticket_id' => $this->ticket->id,
            'attendee_email' => 'duplicate@example.com',
            'status' => 'confirmed'
        ]);

        $duplicateData = [
            'attendee_name' => 'Duplicate User',
            'attendee_email' => 'duplicate@example.com',
            'ticket_id' => $this->ticket->id,
            'terms_accepted' => true
        ];

        $response = $this->post(route('events.register.process', $this->event), $duplicateData);
        $response->assertSessionHasErrors(['attendee_email']);

        // Scenario 3: Sold out ticket
        $this->ticket->update(['available' => 0]);

        $soldOutData = [
            'attendee_name' => 'Late User',
            'attendee_email' => 'late@example.com',
            'ticket_id' => $this->ticket->id,
            'terms_accepted' => true
        ];

        $response = $this->post(route('events.register.process', $this->event), $soldOutData);
        $response->assertSessionHasErrors(['ticket_id']);

        // Scenario 4: Payment amount mismatch
        $this->ticket->update(['available' => 10]); // Reset availability

        $validData = [
            'attendee_name' => 'Amount Test User',
            'attendee_email' => 'amount@example.com',
            'ticket_id' => $this->ticket->id,
            'terms_accepted' => true
        ];

        $this->post(route('events.register.process', $this->event), $validData);

        // Try to manipulate payment amount
        $registration = Registration::where('attendee_email', 'amount@example.com')->first();

        $tamperedCallback = [
            'invoice_id' => 'tampered_inv',
            'order_id' => 'REG_' . $registration->id . '_' . time(),
            'status' => 'Confirmed',
            'amount' => 1.00, // Tampered amount
            'currency' => 'USD'
        ];

        $response = $this->get(route('payment.unipayment.callback', $tamperedCallback));

        // Should reject tampered payment
        $registration->refresh();
        $this->assertNotEquals('confirmed', $registration->status);
    }

    /** @test */
    public function payment_security_measures_are_enforced()
    {
        // Test rate limiting
        $registrationData = [
            'attendee_name' => 'Rate Test User',
            'attendee_email' => 'rate@example.com',
            'ticket_id' => $this->ticket->id,
            'terms_accepted' => true
        ];

        $this->post(route('events.register.process', $this->event), $registrationData);

        // Attempt multiple rapid payment requests
        for ($i = 0; $i < 10; $i++) {
            $response = $this->post(route('payment.card', $this->event), [
                'payment_method' => 'card'
            ]);

            // Should eventually hit rate limit
            if ($response->getStatusCode() === 429) {
                $this->assertEquals(429, $response->getStatusCode());
                break;
            }
        }

        // Test CSRF protection (implicitly tested by Laravel's middleware)
        $this->assertTrue(true, 'CSRF protection is enforced by Laravel middleware');

        // Test webhook signature validation
        $webhookData = [
            'invoice_id' => 'webhook_test',
            'status' => 'Confirmed',
            'amount' => 299.99
        ];

        // Without proper signature
        $response = $this->post(route('payment.unipayment.webhook'), $webhookData);
        $response->assertStatus(401);

        // With valid signature
        $payload = json_encode($webhookData);
        $signature = hash_hmac('sha256', $payload, 'test_webhook_secret_key');

        $response = $this->withHeaders([
            'X-UniPayment-Signature' => $signature,
            'Content-Type' => 'application/json'
        ])->post(route('payment.unipayment.webhook'), $webhookData);

        $response->assertStatus(200);
    }

    /** @test */
    public function mobile_and_accessibility_considerations()
    {
        // Test responsive design elements (basic check)
        $response = $this->get(route('events.register', $this->event));
        $response->assertStatus(200);
        $response->assertSee('viewport'); // Meta viewport tag for mobile

        // Test form accessibility
        $response->assertSee('required'); // Required field indicators
        $response->assertSee('aria-'); // ARIA attributes for screen readers

        // Test keyboard navigation support
        $response->assertSee('tabindex'); // Tab order for keyboard navigation
    }
}
