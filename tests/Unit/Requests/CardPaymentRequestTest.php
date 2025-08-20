<?php

namespace Tests\Unit\Requests;

use Tests\TestCase;
use App\Http\Requests\CardPaymentRequest;
use App\Models\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CardPaymentRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test event for validation
        Event::create([
            'name' => 'Test Event',
            'description' => 'Test event description',
            'start_date' => now()->addDays(30),
            'end_date' => now()->addDays(31),
            'location' => 'Test Location',
            'max_attendees' => 100,
            'is_active' => true,
            'is_default' => false
        ]);
    }

    /** @test */
    public function it_authorizes_all_requests()
    {
        $request = new CardPaymentRequest();
        $this->assertTrue($request->authorize());
    }

    /** @test */
    public function it_validates_valid_payment_data()
    {
        $data = [
            'event_id' => 1,
            'total_amount' => '99.99',
            'attendee_name' => 'John Doe',
            'attendee_email' => 'john.doe@example.com',
            'attendee_phone' => '+1 (555) 123-4567'
        ];

        $request = new CardPaymentRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function it_requires_event_id()
    {
        $data = [
            'total_amount' => '99.99',
            'attendee_name' => 'John Doe',
            'attendee_email' => 'john.doe@example.com',
            'attendee_phone' => '+1 (555) 123-4567'
        ];

        $request = new CardPaymentRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('event_id', $validator->errors()->toArray());
        $this->assertEquals('Event selection is required.', $validator->errors()->first('event_id'));
    }

    /** @test */
    public function it_validates_event_exists()
    {
        $data = [
            'event_id' => 999, // Non-existent event
            'total_amount' => '99.99',
            'attendee_name' => 'John Doe',
            'attendee_email' => 'john.doe@example.com',
            'attendee_phone' => '+1 (555) 123-4567'
        ];

        $request = new CardPaymentRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('event_id', $validator->errors()->toArray());
        $this->assertEquals('Selected event does not exist.', $validator->errors()->first('event_id'));
    }

    /** @test */
    public function it_requires_total_amount()
    {
        $data = [
            'event_id' => 1,
            'attendee_name' => 'John Doe',
            'attendee_email' => 'john.doe@example.com',
            'attendee_phone' => '+1 (555) 123-4567'
        ];

        $request = new CardPaymentRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('total_amount', $validator->errors()->toArray());
        $this->assertEquals('Payment amount is required.', $validator->errors()->first('total_amount'));
    }

    /** @test */
    public function it_validates_total_amount_is_numeric()
    {
        $data = [
            'event_id' => 1,
            'total_amount' => 'not-a-number',
            'attendee_name' => 'John Doe',
            'attendee_email' => 'john.doe@example.com',
            'attendee_phone' => '+1 (555) 123-4567'
        ];

        $request = new CardPaymentRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('total_amount', $validator->errors()->toArray());
        $this->assertEquals('Payment amount must be a valid number.', $validator->errors()->first('total_amount'));
    }

    /** @test */
    public function it_validates_total_amount_minimum()
    {
        $data = [
            'event_id' => 1,
            'total_amount' => '0.50',
            'attendee_name' => 'John Doe',
            'attendee_email' => 'john.doe@example.com',
            'attendee_phone' => '+1 (555) 123-4567'
        ];

        $request = new CardPaymentRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('total_amount', $validator->errors()->toArray());
        $this->assertEquals('Payment amount must be at least $1.00.', $validator->errors()->first('total_amount'));
    }

    /** @test */
    public function it_validates_total_amount_maximum()
    {
        $data = [
            'event_id' => 1,
            'total_amount' => '100001.00',
            'attendee_name' => 'John Doe',
            'attendee_email' => 'john.doe@example.com',
            'attendee_phone' => '+1 (555) 123-4567'
        ];

        $request = new CardPaymentRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('total_amount', $validator->errors()->toArray());
        $this->assertEquals('Payment amount cannot exceed $100,000.00.', $validator->errors()->first('total_amount'));
    }

    /** @test */
    public function it_validates_total_amount_format()
    {
        $invalidAmounts = ['99.999', '99.9.9', '99..99'];

        foreach ($invalidAmounts as $amount) {
            $data = [
                'event_id' => 1,
                'total_amount' => $amount,
                'attendee_name' => 'John Doe',
                'attendee_email' => 'john.doe@example.com',
                'attendee_phone' => '+1 (555) 123-4567'
            ];

            $request = new CardPaymentRequest();
            $validator = Validator::make($data, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Amount {$amount} should fail validation");
            $this->assertArrayHasKey('total_amount', $validator->errors()->toArray());
        }
    }

    /** @test */
    public function it_accepts_valid_total_amount_formats()
    {
        $validAmounts = ['99', '99.9', '99.99', '1.00', '1000', '9999.99'];

        foreach ($validAmounts as $amount) {
            $data = [
                'event_id' => 1,
                'total_amount' => $amount,
                'attendee_name' => 'John Doe',
                'attendee_email' => 'john.doe@example.com',
                'attendee_phone' => '+1 (555) 123-4567'
            ];

            $request = new CardPaymentRequest();
            $validator = Validator::make($data, $request->rules(), $request->messages());

            $this->assertFalse($validator->fails(), "Amount {$amount} should pass validation");
        }
    }

    /** @test */
    public function it_requires_attendee_name()
    {
        $data = [
            'event_id' => 1,
            'total_amount' => '99.99',
            'attendee_email' => 'john.doe@example.com',
            'attendee_phone' => '+1 (555) 123-4567'
        ];

        $request = new CardPaymentRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('attendee_name', $validator->errors()->toArray());
        $this->assertEquals('Attendee name is required.', $validator->errors()->first('attendee_name'));
    }

    /** @test */
    public function it_validates_attendee_name_format()
    {
        $invalidNames = ['John123', 'John@Doe', 'John#Doe', 'John$Doe'];

        foreach ($invalidNames as $name) {
            $data = [
                'event_id' => 1,
                'total_amount' => '99.99',
                'attendee_name' => $name,
                'attendee_email' => 'john.doe@example.com',
                'attendee_phone' => '+1 (555) 123-4567'
            ];

            $request = new CardPaymentRequest();
            $validator = Validator::make($data, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Name '{$name}' should fail validation");
            $this->assertArrayHasKey('attendee_name', $validator->errors()->toArray());
            $this->assertEquals('Attendee name contains invalid characters.', $validator->errors()->first('attendee_name'));
        }
    }

    /** @test */
    public function it_accepts_valid_attendee_name_formats()
    {
        $validNames = ['John Doe', 'Mary-Jane Smith', "O'Connor", 'Dr. Smith', 'Jean-Pierre'];

        foreach ($validNames as $name) {
            $data = [
                'event_id' => 1,
                'total_amount' => '99.99',
                'attendee_name' => $name,
                'attendee_email' => 'john.doe@example.com',
                'attendee_phone' => '+1 (555) 123-4567'
            ];

            $request = new CardPaymentRequest();
            $validator = Validator::make($data, $request->rules(), $request->messages());

            $this->assertFalse($validator->fails(), "Name '{$name}' should pass validation");
        }
    }

    /** @test */
    public function it_requires_attendee_email()
    {
        $data = [
            'event_id' => 1,
            'total_amount' => '99.99',
            'attendee_name' => 'John Doe',
            'attendee_phone' => '+1 (555) 123-4567'
        ];

        $request = new CardPaymentRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('attendee_email', $validator->errors()->toArray());
        $this->assertEquals('Email address is required.', $validator->errors()->first('attendee_email'));
    }

    /** @test */
    public function it_validates_attendee_email_format()
    {
        $invalidEmails = ['invalid-email', 'test@', '@example.com', 'test..test@example.com'];

        foreach ($invalidEmails as $email) {
            $data = [
                'event_id' => 1,
                'total_amount' => '99.99',
                'attendee_name' => 'John Doe',
                'attendee_email' => $email,
                'attendee_phone' => '+1 (555) 123-4567'
            ];

            $request = new CardPaymentRequest();
            $validator = Validator::make($data, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Email '{$email}' should fail validation");
            $this->assertArrayHasKey('attendee_email', $validator->errors()->toArray());
        }
    }

    /** @test */
    public function it_requires_attendee_phone()
    {
        $data = [
            'event_id' => 1,
            'total_amount' => '99.99',
            'attendee_name' => 'John Doe',
            'attendee_email' => 'john.doe@example.com'
        ];

        $request = new CardPaymentRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('attendee_phone', $validator->errors()->toArray());
        $this->assertEquals('Phone number is required.', $validator->errors()->first('attendee_phone'));
    }

    /** @test */
    public function it_validates_attendee_phone_format()
    {
        $invalidPhones = ['abc-def-ghij', '123-456-789a', '123@456-7890'];

        foreach ($invalidPhones as $phone) {
            $data = [
                'event_id' => 1,
                'total_amount' => '99.99',
                'attendee_name' => 'John Doe',
                'attendee_email' => 'john.doe@example.com',
                'attendee_phone' => $phone
            ];

            $request = new CardPaymentRequest();
            $validator = Validator::make($data, $request->rules(), $request->messages());

            $this->assertTrue($validator->fails(), "Phone '{$phone}' should fail validation");
            $this->assertArrayHasKey('attendee_phone', $validator->errors()->toArray());
            $this->assertEquals('Phone number format is invalid.', $validator->errors()->first('attendee_phone'));
        }
    }

    /** @test */
    public function it_accepts_valid_attendee_phone_formats()
    {
        $validPhones = ['+1 (555) 123-4567', '555-123-4567', '5551234567', '+44 20 7946 0958', '(555) 123-4567'];

        foreach ($validPhones as $phone) {
            $data = [
                'event_id' => 1,
                'total_amount' => '99.99',
                'attendee_name' => 'John Doe',
                'attendee_email' => 'john.doe@example.com',
                'attendee_phone' => $phone
            ];

            $request = new CardPaymentRequest();
            $validator = Validator::make($data, $request->rules(), $request->messages());

            $this->assertFalse($validator->fails(), "Phone '{$phone}' should pass validation");
        }
    }

    /** @test */
    public function it_sanitizes_total_amount_during_preparation()
    {
        $request = new CardPaymentRequest();

        // Use reflection to test the protected method
        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('prepareForValidation');
        $method->setAccessible(true);

        // Test amount sanitization
        $request->merge(['total_amount' => '$99.99']);
        $method->invoke($request);
        $this->assertEquals('99.99', $request->input('total_amount'));

        $request->merge(['total_amount' => '99.99.99']);
        $method->invoke($request);
        $this->assertEquals('99.99', $request->input('total_amount'));
    }

    /** @test */
    public function it_sanitizes_attendee_name_during_preparation()
    {
        $request = new CardPaymentRequest();

        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('prepareForValidation');
        $method->setAccessible(true);

        // Test name sanitization
        $request->merge(['attendee_name' => '  John    Doe  ']);
        $method->invoke($request);
        $this->assertEquals('John Doe', $request->input('attendee_name'));
    }

    /** @test */
    public function it_sanitizes_attendee_email_during_preparation()
    {
        $request = new CardPaymentRequest();

        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('prepareForValidation');
        $method->setAccessible(true);

        // Test email sanitization
        $request->merge(['attendee_email' => '  JOHN.DOE@EXAMPLE.COM  ']);
        $method->invoke($request);
        $this->assertEquals('john.doe@example.com', $request->input('attendee_email'));
    }

    /** @test */
    public function it_sanitizes_attendee_phone_during_preparation()
    {
        $request = new CardPaymentRequest();

        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('prepareForValidation');
        $method->setAccessible(true);

        // Test phone sanitization
        $request->merge(['attendee_phone' => '  +1   (555)   123-4567  ']);
        $method->invoke($request);
        $this->assertEquals('+1 (555) 123-4567', $request->input('attendee_phone'));
    }

    /** @test */
    public function it_logs_validation_failures()
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'Card payment request validation failed' &&
                    isset($context['errors']) &&
                    isset($context['sanitized_input']);
            });

        $data = [
            'event_id' => 999, // Invalid event
            'total_amount' => 'invalid',
            'attendee_name' => 'John123',
            'attendee_email' => 'invalid-email',
            'attendee_phone' => 'invalid-phone'
        ];

        $request = new CardPaymentRequest();
        $request->merge($data);

        // Mock the route
        $route = \Mockery::mock(\Illuminate\Routing\Route::class);
        $route->shouldReceive('getName')->andReturn('payment.card.process');
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $validator = Validator::make($data, $request->rules(), $request->messages());

        try {
            $reflection = new \ReflectionClass($request);
            $method = $reflection->getMethod('failedValidation');
            $method->setAccessible(true);
            $method->invoke($request, $validator);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Expected exception
        }
    }

    /** @test */
    public function it_masks_sensitive_data_in_sanitized_input()
    {
        $request = new CardPaymentRequest();
        $request->merge([
            'attendee_email' => 'john.doe@example.com',
            'attendee_phone' => '+1 (555) 123-4567'
        ]);

        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('getSanitizedInput');
        $method->setAccessible(true);

        $sanitized = $method->invoke($request);

        $this->assertEquals('jo***@example.com', $sanitized['attendee_email']);
        $this->assertEquals('+1 ***67', $sanitized['attendee_phone']);
    }
}
