# Design Document

## Overview

The direct registration enhancement removes all authentication requirements and provides a streamlined single-form registration experience. Users click "Register" and complete all necessary information in one form, then proceed directly to crypto payment, and finally receive confirmation with duplicate prevention measures in place.

## Architecture

### Registration Flow Architecture

```
Event Page → Direct Registration Form → Payment Page → Processing Message → 10s Countdown → Homepage
     ↓                ↓                      ↓              ↓                    ↓           ↓
  No Auth         Form Validation      Crypto Payment   Email/Phone        Timer Display   Redirect
  Required        + Data Storage       + "I Have Paid"   Marking           (10,9,8...)    + Cleanup
```

### Data Flow

1. **Form Submission**: Collect and validate all attendee information
2. **Session Storage**: Store registration data temporarily during payment
3. **Payment Processing**: Handle crypto payment confirmation
4. **Registration Creation**: Create final registration record after payment confirmation
5. **Duplicate Prevention**: Mark email/phone to prevent duplicate registrations

## Components and Interfaces

### 1. Direct Registration Form Component

**Location**: `resources/views/registrations/direct-form.blade.php`

**Fields Structure**:

```php
// Personal Information Section
- full_name (required, string, max:255)
- email (required, email, unique per event)
- phone (required, string, unique per event, phone format validation)

// Event Information Section
- event_date_time (readonly, populated from event data)
- ticket_selections (array of ticket_type_id => quantity)

// Emergency Contact Section (Optional)
- emergency_contact_name (optional, string, max:255)
- emergency_contact_phone (optional, string, phone format validation)

// Legal Section
- terms_accepted (required, boolean, must be true)
```

**Validation Rules**:

- Email format validation with real-time feedback
- Phone number format validation (international format support)
- Terms acceptance required before submission
- Ticket quantity validation against available capacity

### 2. Enhanced Registration Controller

**Location**: `app/Http/Controllers/RegistrationController.php`

**New Methods**:

```php
public function showDirectForm(Event $event)
// Display the direct registration form with event data

public function processDirectRegistration(Request $request, Event $event)
// Validate form data, check for duplicates, store in session, redirect to payment

public function checkDuplicateRegistration($email, $phone, $eventId)
// Check if email or phone already registered for this event

public function markRegistrationPending($email, $phone, $eventId)
// Mark email/phone as pending to prevent duplicate submissions during payment
```

### 3. Enhanced Payment Controller

**Location**: `app/Http/Controllers/PaymentController.php`

**Modified Methods**:

```php
public function showCryptoPayment()
// Display crypto payment options with registration summary

public function confirmPayment(Request $request)
// Handle "I Have Paid" button click, show processing message with 10s countdown, create registration

public function processPaymentConfirmation()
// Create final registration record, send confirmation email, redirect to homepage after countdown

public function declinePayment(Request $request)
// Admin function to decline payment and unlock registration for re-use
```

### 4. Duplicate Prevention System

**Database Changes**:

```sql
// Add to registrations table
- registration_status ENUM('pending', 'confirmed', 'cancelled', 'declined') DEFAULT 'pending'
- marked_at TIMESTAMP (when email/phone was marked)
- payment_confirmed_at TIMESTAMP (when "I Have Paid" was clicked)
- declined_at TIMESTAMP (when admin declines payment)
- declined_reason TEXT (reason for payment decline)

// Create registration_locks table for temporary duplicate prevention
- email VARCHAR(255)
- phone VARCHAR(255)
- event_id BIGINT
- locked_at TIMESTAMP
- expires_at TIMESTAMP (lock expires after 30 minutes)
```

## Data Models

### Enhanced Registration Model

**Location**: `app/Models/Registration.php`

```php
class Registration extends Model
{
    protected $fillable = [
        'event_id',
        'attendee_name',
        'attendee_email',
        'attendee_phone',
        'emergency_contact_name',
        'emergency_contact_phone',
        'ticket_selections', // JSON field
        'total_amount',
        'registration_status',
        'terms_accepted_at',
        'marked_at',
        'payment_confirmed_at'
    ];

    protected $casts = [
        'ticket_selections' => 'array',
        'terms_accepted_at' => 'datetime',
        'marked_at' => 'datetime',
        'payment_confirmed_at' => 'datetime'
    ];

    // Relationships and methods
    public function event() { return $this->belongsTo(Event::class); }
    public function isConfirmed() { return $this->registration_status === 'confirmed'; }
    public function isPending() { return $this->registration_status === 'pending'; }
}
```

### Registration Lock Model

**Location**: `app/Models/RegistrationLock.php`

```php
class RegistrationLock extends Model
{
    protected $fillable = ['email', 'phone', 'event_id', 'locked_at', 'expires_at'];

    protected $casts = [
        'locked_at' => 'datetime',
        'expires_at' => 'datetime'
    ];

    public function isExpired() { return $this->expires_at < now(); }

    public static function createLock($email, $phone, $eventId) {
        return self::create([
            'email' => $email,
            'phone' => $phone,
            'event_id' => $eventId,
            'locked_at' => now(),
            'expires_at' => now()->addMinutes(30)
        ]);
    }
}
```

## Error Handling

### Form Validation Errors

- Real-time validation feedback for email and phone formats
- Clear error messages for required fields
- Duplicate registration prevention with user-friendly messages

### Payment Flow Errors

- Handle payment page access without valid session data
- Graceful handling of expired registration locks
- Clear messaging when registration is no longer available

### Duplicate Prevention Errors

```php
// Error messages for duplicate attempts
"This email address is already registered for this event."
"This phone number is already registered for this event."
"A registration with this information is currently being processed."
```

## Testing Strategy

### Unit Tests

- Registration form validation logic
- Duplicate detection algorithms
- Registration lock creation and expiration
- Payment confirmation processing

### Integration Tests

- Complete registration flow from form to confirmation
- Duplicate prevention across multiple simultaneous registrations
- Payment processing with session data handling
- Email/phone marking and cleanup processes

### User Acceptance Tests

- Registration form usability and validation feedback
- Payment flow completion and confirmation messaging
- Duplicate registration prevention user experience
- Admin panel override functionality for duplicate registrations

## User Interface Design

### Direct Registration Form Layout

```
┌─────────────────────────────────────┐
│ Event Registration                  │
├─────────────────────────────────────┤
│ Personal Information                │
│ • Full Name [________________]      │
│ • Email     [________________]      │
│ • Phone     [________________]      │
├─────────────────────────────────────┤
│ Event Details                       │
│ • Date/Time [____GREYED_OUT____]    │
│ • Tickets   [Ticket Selection UI]   │
├─────────────────────────────────────┤
│ Emergency Contact (Optional)        │
│ • Name      [________________]      │
│ • Phone     [________________]      │
├─────────────────────────────────────┤
│ ☐ I accept the terms and conditions │
│                                     │
│        [Continue to Payment]        │
└─────────────────────────────────────┘
```

### Payment Confirmation Flow

```
Payment Page → "I Have Paid" Button → Processing Message → Homepage Redirect
     ↓                    ↓                    ↓                ↓
Crypto Options    Mark Email/Phone    "Processing payment...   Clean session
+ Registration         as Used         will send invite"      + Show success
Summary
```

## Security Considerations

### Data Protection

- Validate and sanitize all form inputs
- Encrypt sensitive information in session storage
- Implement CSRF protection on all forms

### Duplicate Prevention Security

- Use database transactions for registration creation
- Implement proper locking mechanisms to prevent race conditions
- Set reasonable expiration times for registration locks

### Payment Security

- Validate payment confirmation requests
- Implement rate limiting on "I Have Paid" button
- Log all payment-related actions for audit purposes

## Admin Payment Management

### Admin Panel Functionality

**Location**: Admin dashboard with payment review section

**Features**:

- View all pending registrations with payment status
- Review payment confirmations from users
- Decline payments with reason (invalid payment, insufficient amount, etc.)
- Unlock declined registrations to allow re-registration with same email/phone

**Admin Actions**:

```php
public function reviewPendingPayments()
// Display list of registrations awaiting payment confirmation

public function declineRegistrationPayment($registrationId, $reason)
// Mark registration as declined, remove email/phone lock, send notification

public function approveRegistrationPayment($registrationId)
// Confirm registration, send confirmation email to attendee
```

## Processing Message with Countdown

### User Experience Flow

1. User clicks "I Have Paid" button
2. System shows processing message: "Processing your payment... This will take a few minutes. We will send you an invite once it's done."
3. 10-second countdown timer displays: "Redirecting to homepage in 10 seconds..."
4. Automatic redirect to homepage after countdown completes
5. User can click "Go to Homepage Now" to skip countdown

### Technical Implementation

- JavaScript countdown timer with visual feedback
- Automatic redirect using `setTimeout()` after 10 seconds
- Optional manual redirect button for immediate navigation
- Session cleanup after successful processing

### Admin Decline Recovery

- When admin declines payment, email/phone locks are removed
- User receives notification email about payment issue
- User can re-register with same information for the event
- Previous registration record marked as 'declined' for audit purposes
