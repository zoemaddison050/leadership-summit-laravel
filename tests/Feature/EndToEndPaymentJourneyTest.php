<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\Registration;
use App\Models\UniPaymentSetting;
use App\Models\PaymentTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;

class EndToEndPaymentJourneyTest extends TestCase
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
            'title' => 'Test Conference 2025',
            'status' => 'active'
        ]);

        $this->ticket = Ticket::factory()->create([
            'event_id' => $this->event->id,
            'name' => 'General Admission',
            'price' => 99.99,
            'quantity' => 100,
            'available' => 100
        ]);

        // Configure UniPayment settings
        $this->uniPaymentSettings = UniPaymentSetting::create([
            'app_id' => 'test_app_id',
            'api_key' => 'test_api_key',
            'environment' => 'sandbox',
            'webhook_secret' => 'test_webhook_secret',
            'is_enabled' => true,
            'supported_currencies' => ['USD'],
            'processing_fee_percentage' => 2.9,
            'minimum_amount' => 1.00,
            'maximum_amount' => 10000.00
        ]);
    }

    /** @test */
    public function complete_card_payment_user_journey_works_correctly()
    {
        // Step 1: User visits event page
        $response = $this->get(route('events.show', $this->event));
        $response->assertStatus(200);
        $response->assertSee($this->event->title);
        $response->assertSee('Register Now');

        // Step 2: User fills out registration form
        $registrationData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1234567890',
            'company' => 'Test Company',
            'job_title' => 'Developer',
            'ticket_id' => $this->ticket->id,
            'event_id' => $this->event->id,
            'dietary_requirements' => 'None',
            'accessibility_requirements' => 'None'
        ];

        $response = $this->post(route('registrations.store'), $registrationData);
        $response->assertRedirect();

        // Should redirect to payment selection
        $this->assertStringContains('payment/selection', $response->headers->get('Location'));

        // Step 3: User sees payment selection page
        $response = $this->followRedirects($response);
        $response->assertStatus(200);
        $response->assertSee('Choose Payment Method');
        $response->assertSee('Pay with Card');
        $response->assertSee('Pay with Crypto');
        $response->assertSee($this->ticket->price);

        // Step 4: User selects card payment
        $response = $this->post(route('payment.card.process'), [
            'payment_method' => 'card'
        ]);

        // Should redirect to UniPayment (mocked)
        $response->assertRedirect();
        $this->assertStringContains('unipayment', $response->headers->get('Location'));

        // Step 5: Simulate successful UniPayment callback
        $registration = Registration::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($registration);

        $callbackData = [
            'invoice_id' => 'test_invoice_123',
            'order_id' => 'REG_' . $registration->id . '_' . time(),
            'status' => 'Confirmed',
            'amount' => $this->ticket->price,
            'currency' => 'USD'
        ];

        $response = $this->get(route('payment.unipayment.callback', $callbackData));
        $response->assertRedirect(route('registrations.success'));

        // Step 6: Verify registration is confirmed
        $registration->refresh();
        $this->assertEquals('confirmed', $registration->status);
        $this->assertEquals('card', $registration->payment_method);
        $this->assertEquals('unipayment', $registration->payment_provider);
        $this->assertNotNull($registration->payment_completed_at);

        // Step 7: User sees success page
        $response = $this->get(route('registrations.success'));
        $response->assertStatus(200);
        $response->assertSee('Registration Confirmed');
        $response->assertSee('John Doe');
        $response->assertSee($this->event->title);
    }

    /** @test */
    public function payment_method_switching_preserves_registration_data()
    {
        $registrationData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'phone' => '+1987654321',
            'company' => 'Another Company',
            'job_title' => 'Manager',
            'ticket_id' => $this->ticket->id,
            'event_id' => $this->event->id
        ];

        // Submit registration
        $response = $this->post(route('registrations.store'), $registrationData);
        $response->assertRedirect();

        // Go to payment selection
        $response = $this->followRedirects($response);
        $response->assertStatus(200);

        // Try card payment first
        $response = $this->post(route('payment.card.process'), [
            'payment_method' => 'card'
        ]);
        $response->assertRedirect();

        // Simulate user going back to payment selection
        $response = $this->get(route('payment.selection'));
        $response->assertStatus(200);

        // Verify registration data is still in session
        $this->assertTrue(Session::has('registration_data'));
        $sessionData = Session::get('registration_data');
        $this->assertEquals('Jane', $sessionData['first_name']);
        $this->assertEquals('jane.smith@example.com', $sessionData['email']);

        // Switch to crypto payment
        $response = $this->post(route('payment.crypto.process'), [
            'payment_method' => 'crypto'
        ]);
        $response->assertRedirect();

        // Verify registration data is preserved
        $registration = Registration::where('email', 'jane.smith@example.com')->first();
        $this->assertNotNull($registration);
        $this->assertEquals('Jane', $registration->first_name);
        $this->assertEquals('Another Company', $registration->company);
    }

    /** @test */
    public function payment_failure_recovery_flow_works()
    {
        $registrationData = [
            'first_name' => 'Bob',
            'last_name' => 'Wilson',
            'email' => 'bob.wilson@example.com',
            'phone' => '+1122334455',
            'ticket_id' => $this->ticket->id,
            'event_id' => $this->event->id
        ];

        // Submit registration and go to payment
        $this->post(route('registrations.store'), $registrationData);

        $registration = Registration::where('email', 'bob.wilson@example.com')->first();

        // Simulate failed payment callback
        $callbackData = [
            'invoice_id' => 'test_invoice_failed',
            'order_id' => 'REG_' . $registration->id . '_' . time(),
            'status' => 'Failed',
            'amount' => $this->ticket->price,
            'currency' => 'USD'
        ];

        $response = $this->get(route('payment.unipayment.callback', $callbackData));
        $response->assertRedirect(route('payment.failed'));

        // User should see failure page with retry options
        $response = $this->get(route('payment.failed'));
        $response->assertStatus(200);
        $response->assertSee('Payment Failed');
        $response->assertSee('Try Again');
        $response->assertSee('Choose Different Payment Method');

        // Verify registration is still pending
        $registration->refresh();
        $this->assertEquals('pending', $registration->status);
        $this->assertNull($registration->payment_completed_at);

        // User can retry with different payment method
        $response = $this->get(route('payment.selection'));
        $response->assertStatus(200);
        $response->assertSee('Choose Payment Method');
    }

    /** @test */
    public function session_timeout_handling_works_correctly()
    {
        $registrationData = [
            'first_name' => 'Alice',
            'last_name' => 'Johnson',
            'email' => 'alice.johnson@example.com',
            'ticket_id' => $this->ticket->id,
            'event_id' => $this->event->id
        ];

        // Submit registration
        $this->post(route('registrations.store'), $registrationData);

        // Simulate session timeout by clearing session
        Session::flush();

        // Try to access payment selection without session data
        $response = $this->get(route('payment.selection'));
        $response->assertRedirect(route('registrations.create'));

        // Should see message about expired session
        $response = $this->followRedirects($response);
        $response->assertSee('session has expired');
    }

    /** @test */
    public function duplicate_registration_prevention_works_with_card_payments()
    {
        $registrationData = [
            'first_name' => 'Charlie',
            'last_name' => 'Brown',
            'email' => 'charlie.brown@example.com',
            'phone' => '+1555666777',
            'ticket_id' => $this->ticket->id,
            'event_id' => $this->event->id
        ];

        // First registration
        $response = $this->post(route('registrations.store'), $registrationData);
        $response->assertRedirect();

        // Complete payment
        $registration = Registration::where('email', 'charlie.brown@example.com')->first();
        $callbackData = [
            'invoice_id' => 'test_invoice_duplicate',
            'order_id' => 'REG_' . $registration->id . '_' . time(),
            'status' => 'Confirmed',
            'amount' => $this->ticket->price,
            'currency' => 'USD'
        ];

        $this->get(route('payment.unipayment.callback', $callbackData));

        // Verify first registration is confirmed
        $registration->refresh();
        $this->assertEquals('confirmed', $registration->status);

        // Try to register again with same email
        $response = $this->post(route('registrations.store'), $registrationData);
        $response->assertSessionHasErrors(['email']);
        $response->assertRedirect();

        // Should not create duplicate registration
        $registrationCount = Registration::where('email', 'charlie.brown@example.com')->count();
        $this->assertEquals(1, $registrationCount);
    }

    /** @test */
    public function payment_amount_validation_works()
    {
        // Test with ticket price below minimum
        $cheapTicket = Ticket::factory()->create([
            'event_id' => $this->event->id,
            'price' => 0.50 // Below minimum of $1.00
        ]);

        $registrationData = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'ticket_id' => $cheapTicket->id,
            'event_id' => $this->event->id
        ];

        $response = $this->post(route('registrations.store'), $registrationData);
        $response->assertRedirect();

        // Should redirect to payment selection but card option should be disabled
        $response = $this->followRedirects($response);
        $response->assertSee('minimum amount');

        // Test with ticket price above maximum
        $expensiveTicket = Ticket::factory()->create([
            'event_id' => $this->event->id,
            'price' => 15000.00 // Above maximum of $10,000.00
        ]);

        $registrationData['ticket_id'] = $expensiveTicket->id;
        $response = $this->post(route('registrations.store'), $registrationData);
        $response->assertRedirect();

        $response = $this->followRedirects($response);
        $response->assertSee('maximum amount');
    }
}
