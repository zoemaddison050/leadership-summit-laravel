<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistrationRequest;
use App\Models\Event;
use App\Models\Registration;
use App\Models\RegistrationLock;
use App\Models\Ticket;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RegistrationController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Display the direct registration form with event data (no authentication required).
     *
     * @param Event $event
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function showDirectForm(Event $event, Request $request)
    {
        try {
            // Get available tickets for the event
            $tickets = $event->tickets()
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->where('available', '>', 0)
                        ->orWhereNull('available'); // Handle NULL available field
                })
                ->where(function ($query) {
                    $query->whereNull('sale_start')
                        ->orWhere('sale_start', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('sale_end')
                        ->orWhere('sale_end', '>=', now());
                })
                ->get();

            if ($tickets->isEmpty()) {
                Log::warning('No tickets available for event', [
                    'event_id' => $event->id,
                    'event_title' => $event->title
                ]);

                return redirect()->route('events.show', $event->slug)
                    ->with('error', 'No tickets are currently available for this event. Please check back later or contact support for assistance.');
            }

            // Get selected ticket ID from URL parameter
            $selectedTicketId = $request->get('ticket');
            $selectedTicket = null;

            if ($selectedTicketId) {
                $selectedTicket = $tickets->firstWhere('id', $selectedTicketId);
                if (!$selectedTicket) {
                    // Invalid ticket ID, redirect back to event page
                    return redirect()->route('events.show', $event->slug)
                        ->with('warning', 'The selected ticket is not available. Please choose from the available options.');
                }
            } else {
                // No ticket selected, redirect back to event page to select one
                return redirect()->route('events.show', $event->slug)
                    ->with('info', 'Please select a ticket option to continue with registration.');
            }

            // Clean up expired locks
            $cleanedLocks = RegistrationLock::cleanupExpiredLocks();

            if ($cleanedLocks > 0) {
                Log::info('Cleaned up expired registration locks', [
                    'event_id' => $event->id,
                    'locks_cleaned' => $cleanedLocks
                ]);
            }

            Log::info('Direct registration form displayed', [
                'event_id' => $event->id,
                'available_tickets' => $tickets->count(),
                'selected_ticket_id' => $selectedTicketId
            ]);

            // If a ticket is selected, only show that ticket
            if ($selectedTicket) {
                $tickets = collect([$selectedTicket]);
            }

            return view('registrations.direct-form', compact('event', 'tickets', 'selectedTicket'));
        } catch (\Exception $e) {
            Log::error('Failed to display direct registration form', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('events.show', $event->slug)
                ->with('error', 'Unable to load the registration form at this time. Please try again later or contact support if the problem persists.');
        }
    }

    /**
     * Process the direct registration form with comprehensive validation.
     *
     * @param Request $request
     * @param Event $event
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processDirectRegistration(Request $request, Event $event)
    {
        Log::info('Direct registration process started', [
            'event_id' => $event->id,
            'event_title' => $event->title,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()
        ]);
        try {
            // Validate the form data
            $validatedData = $request->validate([
                'attendee_name' => 'required|string|max:255',
                'attendee_email' => 'required|email|max:255',
                'attendee_phone' => ['required', 'string', 'max:20', 'regex:/^[\+]?[1-9][\d]{6,15}$/'],
                'ticket_selections' => 'required|array|min:1',
                'ticket_selections.*' => 'integer|min:1',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_phone' => ['nullable', 'string', 'max:20', 'regex:/^[\+]?[1-9][\d]{6,15}$/'],
                'terms_accepted' => 'required|accepted',
            ], [
                'attendee_name.required' => 'Full name is required.',
                'attendee_name.max' => 'Full name cannot exceed 255 characters.',
                'attendee_email.required' => 'Email address is required.',
                'attendee_email.email' => 'Please enter a valid email address.',
                'attendee_email.max' => 'Email address cannot exceed 255 characters.',
                'attendee_phone.required' => 'Phone number is required.',
                'attendee_phone.regex' => 'Please enter a valid phone number (e.g., +1234567890 or 1234567890).',
                'attendee_phone.max' => 'Phone number cannot exceed 20 characters.',
                'ticket_selections.required' => 'Please select at least one ticket.',
                'ticket_selections.array' => 'Invalid ticket selection format.',
                'ticket_selections.min' => 'Please select at least one ticket.',
                'ticket_selections.*.integer' => 'Ticket quantity must be a valid number.',
                'ticket_selections.*.min' => 'Ticket quantity must be at least 1.',
                'emergency_contact_name.max' => 'Emergency contact name cannot exceed 255 characters.',
                'emergency_contact_phone.regex' => 'Please enter a valid emergency contact phone number (e.g., +1234567890 or 1234567890).',
                'emergency_contact_phone.max' => 'Emergency contact phone cannot exceed 20 characters.',
                'terms_accepted.required' => 'You must accept the terms and conditions to proceed.',
                'terms_accepted.accepted' => 'You must accept the terms and conditions to proceed.',
            ]);

            Log::info('Form validation passed', [
                'event_id' => $event->id,
                'email' => $validatedData['attendee_email'],
                'ticket_count' => count($validatedData['ticket_selections']),
                'has_emergency_contact' => !empty($validatedData['emergency_contact_name'])
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Form validation failed', [
                'event_id' => $event->id,
                'errors' => $e->errors(),
                'input_data' => [
                    'has_name' => !empty($request->attendee_name),
                    'has_email' => !empty($request->attendee_email),
                    'has_phone' => !empty($request->attendee_phone),
                    'has_tickets' => !empty($request->ticket_selections),
                    'terms_accepted' => $request->terms_accepted
                ]
            ]);

            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', 'Please correct the errors below and try again.');
        }

        // Check for duplicate registrations
        $duplicateCheck = $this->checkDuplicateRegistration(
            $validatedData['attendee_email'],
            $validatedData['attendee_phone'],
            $event->id
        );

        if ($duplicateCheck !== null) {
            return redirect()->back()
                ->with('error', $duplicateCheck)
                ->withInput();
        }

        // Validate ticket selections and calculate total
        $tickets = Ticket::whereIn('id', array_keys($validatedData['ticket_selections']))
            ->where('event_id', $event->id)
            ->get();

        if ($tickets->count() !== count($validatedData['ticket_selections'])) {
            return redirect()->back()
                ->with('error', 'Invalid ticket selection.')
                ->withInput();
        }

        $totalAmount = 0;
        $ticketData = [];

        foreach ($tickets as $ticket) {
            $quantity = $validatedData['ticket_selections'][$ticket->id];

            // Check ticket availability
            if ($ticket->available < $quantity) {
                return redirect()->back()
                    ->with('error', "Not enough {$ticket->name} tickets available. Only {$ticket->available} remaining.")
                    ->withInput();
            }

            $subtotal = $ticket->price * $quantity;
            $totalAmount += $subtotal;

            $ticketData[$ticket->id] = [
                'ticket_id' => $ticket->id,
                'ticket_name' => $ticket->name,
                'price' => $ticket->price,
                'quantity' => $quantity,
                'subtotal' => $subtotal
            ];
        }

        // Store registration data in session for payment process
        $registrationData = [
            'event_id' => $event->id,
            'attendee_name' => $validatedData['attendee_name'],
            'attendee_email' => $validatedData['attendee_email'],
            'attendee_phone' => $validatedData['attendee_phone'],
            'emergency_contact_name' => $validatedData['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $validatedData['emergency_contact_phone'] ?? null,
            'ticket_selections' => $ticketData,
            'total_amount' => $totalAmount,
            'terms_accepted_at' => now(),
            'expires_at' => now()->addMinutes(30)
        ];

        session(['registration_data' => $registrationData]);

        Log::info('Direct registration data stored in session', [
            'event_id' => $event->id,
            'email' => $validatedData['attendee_email'],
            'total_amount' => $totalAmount
        ]);

        // If all tickets are free, create registration immediately
        if ($totalAmount == 0) {
            return $this->createDirectRegistration();
        }

        // Redirect to payment selection
        return redirect()->route('payment.selection', $event)
            ->with('success', 'Please select your preferred payment method to complete your registration.');
    }

    /**
     * Check for duplicate registrations by email or phone for the same event.
     *
     * @param string $email
     * @param string $phone
     * @param int $eventId
     * @return string|null Error message if duplicate found, null if no duplicate
     */
    public function checkDuplicateRegistration(string $email, string $phone, int $eventId): ?string
    {
        try {
            // Clean up expired locks first to avoid false positives
            $cleanedLocks = RegistrationLock::cleanupExpiredLocks();

            if ($cleanedLocks > 0) {
                Log::info('Cleaned up expired locks during duplicate check', [
                    'event_id' => $eventId,
                    'locks_cleaned' => $cleanedLocks
                ]);
            }

            // Check for existing confirmed or pending registrations by email
            $existingByEmail = Registration::where('attendee_email', $email)
                ->where('event_id', $eventId)
                ->whereIn('registration_status', ['confirmed', 'pending'])
                ->first();

            if ($existingByEmail) {
                Log::info('Duplicate registration attempt blocked - email already exists', [
                    'email' => $email,
                    'event_id' => $eventId,
                    'existing_registration_id' => $existingByEmail->id,
                    'existing_status' => $existingByEmail->registration_status,
                    'existing_created_at' => $existingByEmail->created_at
                ]);

                if ($existingByEmail->registration_status === 'confirmed') {
                    return 'This email address is already registered for this event. If you need to make changes to your registration, please contact our support team at [email] or [phone].';
                } else {
                    $timeSinceRegistration = now()->diffInMinutes($existingByEmail->created_at);
                    if ($timeSinceRegistration > 30) {
                        return 'This email address has a pending registration that may have expired. Please contact support for assistance, or try registering with a different email address.';
                    } else {
                        return 'This email address has a pending registration for this event. Please check your email for payment instructions or wait a few minutes before trying again.';
                    }
                }
            }

            // Check for existing confirmed or pending registrations by phone
            $existingByPhone = Registration::where('attendee_phone', $phone)
                ->where('event_id', $eventId)
                ->whereIn('registration_status', ['confirmed', 'pending'])
                ->first();

            if ($existingByPhone) {
                Log::info('Duplicate registration attempt blocked - phone already exists', [
                    'phone' => $phone,
                    'event_id' => $eventId,
                    'existing_registration_id' => $existingByPhone->id,
                    'existing_status' => $existingByPhone->registration_status,
                    'existing_created_at' => $existingByPhone->created_at
                ]);

                if ($existingByPhone->registration_status === 'confirmed') {
                    return 'This phone number is already registered for this event. If you need to make changes to your registration, please contact our support team at [email] or [phone].';
                } else {
                    $timeSinceRegistration = now()->diffInMinutes($existingByPhone->created_at);
                    if ($timeSinceRegistration > 30) {
                        return 'This phone number has a pending registration that may have expired. Please contact support for assistance, or try registering with a different phone number.';
                    } else {
                        return 'This phone number has a pending registration for this event. Please check your email for payment instructions or wait a few minutes before trying again.';
                    }
                }
            }

            // Check for active registration locks (prevents concurrent duplicate attempts)
            if (RegistrationLock::isLocked($email, $phone, $eventId)) {
                Log::info('Duplicate registration attempt blocked - registration currently locked', [
                    'email' => $email,
                    'phone' => $phone,
                    'event_id' => $eventId
                ]);
                return 'A registration with this email and phone number is currently being processed. Please wait a few minutes and try again, or use different contact information.';
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error during duplicate registration check', [
                'email' => $email,
                'phone' => $phone,
                'event_id' => $eventId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return a generic error message to avoid exposing system details
            return 'Unable to verify registration status at this time. Please try again in a few minutes or contact support if the problem persists.';
        }
    }

    /**
     * Mark registration as pending by creating a lock for email/phone combination.
     *
     * @param string $email
     * @param string $phone
     * @param int $eventId
     * @return RegistrationLock
     */
    public function markRegistrationPending(string $email, string $phone, int $eventId): RegistrationLock
    {
        // Clean up any expired locks first
        RegistrationLock::cleanupExpiredLocks();

        // Create new lock
        $lock = RegistrationLock::createLock($email, $phone, $eventId);

        Log::info('Registration marked as pending', [
            'email' => $email,
            'phone' => $phone,
            'event_id' => $eventId,
            'lock_id' => $lock->id
        ]);

        return $lock;
    }

    /**
     * Release registration lock after successful registration or failure.
     *
     * @param string $email
     * @param string $phone
     * @param int $eventId
     * @return int Number of locks released
     */
    public function releaseRegistrationLock(string $email, string $phone, int $eventId): int
    {
        try {
            $deletedCount = RegistrationLock::where('email', $email)
                ->where('phone', $phone)
                ->where('event_id', $eventId)
                ->delete();

            if ($deletedCount > 0) {
                Log::info('Registration lock released', [
                    'email' => $email,
                    'phone' => $phone,
                    'event_id' => $eventId,
                    'locks_released' => $deletedCount
                ]);
            }

            return $deletedCount;
        } catch (\Exception $e) {
            Log::error('Failed to release registration lock', [
                'email' => $email,
                'phone' => $phone,
                'event_id' => $eventId,
                'error' => $e->getMessage()
            ]);

            return 0;
        }
    }

    /**
     * Handle expired registration session gracefully.
     *
     * @param Event $event
     * @param string $context
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleExpiredSession(Event $event, string $context = 'registration'): \Illuminate\Http\RedirectResponse
    {
        Log::info('Handling expired registration session', [
            'event_id' => $event->id,
            'context' => $context,
            'user_agent' => request()->userAgent(),
            'ip' => request()->ip()
        ]);

        // Clear any potentially corrupted session data
        session()->forget(['registration_data', 'ticket_selection']);

        // Return user-friendly error with helpful guidance
        return redirect()->route('events.show', $event)
            ->with('warning', 'Your registration session has expired for security reasons. Please start your registration again - it only takes a few minutes to complete.')
            ->with('expired_context', $context);
    }

    /**
     * Validate session data integrity and expiration.
     *
     * @param array|null $sessionData
     * @param Event $event
     * @return array|null Returns validated data or null if invalid
     */
    public function validateSessionData($sessionData, Event $event): ?array
    {
        if (!$sessionData || !is_array($sessionData)) {
            Log::warning('Invalid session data structure', [
                'event_id' => $event->id,
                'data_type' => gettype($sessionData)
            ]);
            return null;
        }

        // Check required fields
        $requiredFields = ['event_id', 'expires_at'];
        foreach ($requiredFields as $field) {
            if (!isset($sessionData[$field])) {
                Log::warning('Missing required session data field', [
                    'event_id' => $event->id,
                    'missing_field' => $field
                ]);
                return null;
            }
        }

        // Check expiration
        if (now()->gt($sessionData['expires_at'])) {
            Log::info('Session data expired', [
                'event_id' => $event->id,
                'expired_at' => $sessionData['expires_at'],
                'current_time' => now(),
                'minutes_expired' => now()->diffInMinutes($sessionData['expires_at'])
            ]);
            return null;
        }

        // Check event ID match
        if ($sessionData['event_id'] != $event->id) {
            Log::warning('Event ID mismatch in session data', [
                'session_event_id' => $sessionData['event_id'],
                'current_event_id' => $event->id
            ]);
            return null;
        }

        return $sessionData;
    }
    /**
     * Display the registration form for an event.
     */
    public function create(Event $event)
    {
        $tickets = $event->tickets()->where('available', '>', 0)->get();

        if ($tickets->isEmpty()) {
            return redirect()->route('events.show', $event->slug)
                ->with('error', 'No tickets are available for this event.');
        }

        return view('registrations.create', compact('event', 'tickets'));
    }

    /**
     * Show the direct registration form (no authentication required).
     * @deprecated Use showDirectForm instead
     */
    public function showRegistrationForm(Event $event)
    {
        return $this->showDirectForm($event);
    }

    /**
     * Process the direct registration form.
     * Handles both old format (ticket_id/quantity) and new format (ticket_selections)
     */
    public function processRegistration(Request $request, Event $event)
    {
        // Handle old format validation for backward compatibility
        // Check if request is using old format (has ticket_id field or no ticket_selections)
        if ($request->has('ticket_id') || !$request->has('ticket_selections')) {
            $request->validate([
                'full_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => ['required', 'string', 'max:20', 'regex:/^[\+]?[1-9][\d]{6,15}$/'],
                'ticket_id' => 'required|exists:tickets,id',
                'quantity' => 'required|integer|min:1|max:10',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_phone' => ['nullable', 'string', 'max:20', 'regex:/^[\+]?[1-9][\d]{6,15}$/'],
                'terms_accepted' => 'required|accepted',
            ], [
                'phone.regex' => 'Please enter a valid phone number (e.g., +1234567890 or 1234567890).',
                'emergency_contact_phone.regex' => 'Please enter a valid emergency contact phone number (e.g., +1234567890 or 1234567890).',
            ]);

            $ticket = Ticket::findOrFail($request->ticket_id);

            // Verify ticket belongs to the event
            if ($ticket->event_id !== $event->id) {
                return redirect()->back()
                    ->with('error', 'Invalid ticket selection.')
                    ->withInput();
            }

            // Check for duplicate registrations
            $duplicateCheck = $this->checkDuplicateRegistration(
                $request->email,
                $request->phone,
                $event->id
            );

            if ($duplicateCheck !== null) {
                return redirect()->back()
                    ->with('error', $duplicateCheck)
                    ->withInput();
            }

            // Check ticket availability
            if ($ticket->available < $request->quantity) {
                return redirect()->back()
                    ->with('error', "Not enough tickets available. Only {$ticket->available} tickets remaining.")
                    ->withInput();
            }

            // Store registration data in session for payment
            session([
                'registration_data' => [
                    'event_id' => $event->id,
                    'attendee_name' => $request->full_name,
                    'attendee_email' => $request->email,
                    'attendee_phone' => $request->phone,
                    'emergency_contact_name' => $request->emergency_contact_name,
                    'emergency_contact_phone' => $request->emergency_contact_phone,
                    'ticket_selections' => [
                        $ticket->id => [
                            'ticket_id' => $ticket->id,
                            'ticket_name' => $ticket->name,
                            'price' => $ticket->price,
                            'quantity' => $request->quantity,
                            'subtotal' => $ticket->price * $request->quantity
                        ]
                    ],
                    'total_amount' => $ticket->price * $request->quantity,
                    'terms_accepted_at' => now(),
                    'expires_at' => now()->addMinutes(30)
                ]
            ]);

            // If free tickets, create registration immediately
            if ($ticket->price == 0) {
                return $this->createDirectRegistration();
            }

            // Redirect to payment selection
            return redirect()->route('payment.selection', $event)
                ->with('success', 'Please select your preferred payment method to complete your registration.');
        }

        // Use new format processing
        return $this->processDirectRegistration($request, $event);
    }

    /**
     * Create registration from session data (for direct registration).
     */
    public function createDirectRegistration()
    {
        $registrationData = session('registration_data');

        if (!$registrationData || now()->gt($registrationData['expires_at'])) {
            return redirect()->route('events.index')
                ->with('error', 'Registration data has expired. Please register again.');
        }

        $event = Event::findOrFail($registrationData['event_id']);

        try {
            DB::beginTransaction();

            // Mark registration as pending to prevent duplicates
            $lock = $this->markRegistrationPending(
                $registrationData['attendee_email'],
                $registrationData['attendee_phone'],
                $event->id
            );

            // Validate ticket availability again
            foreach ($registrationData['ticket_selections'] as $ticketData) {
                $ticket = Ticket::find($ticketData['ticket_id']);
                if (!$ticket || $ticket->available < $ticketData['quantity']) {
                    throw new \Exception("Ticket {$ticketData['ticket_name']} is no longer available in the requested quantity.");
                }
            }

            // Create the registration record
            $registration = Registration::create([
                'user_id' => null, // No user account required
                'event_id' => $event->id,
                'ticket_id' => null, // Will be handled via ticket_selections
                'registration_status' => $registrationData['total_amount'] > 0 ? 'pending' : 'confirmed',
                'payment_status' => $registrationData['total_amount'] > 0 ? 'pending' : 'completed',
                'attendee_name' => $registrationData['attendee_name'],
                'attendee_email' => $registrationData['attendee_email'],
                'attendee_phone' => $registrationData['attendee_phone'],
                'emergency_contact_name' => $registrationData['emergency_contact_name'],
                'emergency_contact_phone' => $registrationData['emergency_contact_phone'],
                'ticket_selections' => $registrationData['ticket_selections'],
                'total_amount' => $registrationData['total_amount'],
                'terms_accepted_at' => $registrationData['terms_accepted_at'],
                'marked_at' => now(),
            ]);

            // Update ticket availability for each selected ticket
            foreach ($registrationData['ticket_selections'] as $ticketData) {
                $ticket = Ticket::find($ticketData['ticket_id']);
                $ticket->decrement('available', $ticketData['quantity']);
            }

            DB::commit();

            // Release registration lock after successful creation
            $this->releaseRegistrationLock(
                $registrationData['attendee_email'],
                $registrationData['attendee_phone'],
                $event->id
            );

            // Clear session data
            session()->forget('registration_data');

            Log::info('Direct registration created successfully', [
                'registration_id' => $registration->id,
                'event_id' => $event->id,
                'email' => $registrationData['attendee_email']
            ]);

            // Send registration success email notification
            try {
                \Mail::to($registration->attendee_email)
                    ->send(new \App\Mail\RegistrationSuccessMail($registration));

                Log::info('Registration success email sent', [
                    'registration_id' => $registration->id,
                    'email' => $registration->attendee_email
                ]);
            } catch (\Exception $emailError) {
                Log::error('Failed to send registration success email', [
                    'registration_id' => $registration->id,
                    'email' => $registration->attendee_email,
                    'error' => $emailError->getMessage()
                ]);
                // Don't fail the registration if email fails
            }

            return redirect()->route('registration.success', $registration)
                ->with('success', 'Registration completed successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            // Release registration lock on failure
            if (isset($registrationData['attendee_email']) && isset($registrationData['attendee_phone'])) {
                $this->releaseRegistrationLock(
                    $registrationData['attendee_email'],
                    $registrationData['attendee_phone'],
                    $event->id
                );
            }

            Log::error('Direct registration creation failed', [
                'event_id' => $event->id,
                'email' => $registrationData['attendee_email'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return redirect()->route('events.show', $event)
                ->with('error', 'Registration failed: ' . $e->getMessage());
        }
    }

    /**
     * Process ticket selection from the ticket selection page.
     */
    public function process(Request $request, Event $event)
    {
        Log::info('Ticket selection process started', [
            'user_id' => Auth::id(),
            'event_id' => $event->id,
            'tickets' => $request->tickets
        ]);

        $request->validate([
            'tickets' => 'required|array|min:1',
            'tickets.*' => 'integer|min:0',
        ]);

        $selectedTickets = array_filter($request->tickets, function ($quantity) {
            return $quantity > 0;
        });

        if (empty($selectedTickets)) {
            return redirect()->back()
                ->with('error', 'Please select at least one ticket.')
                ->withInput();
        }

        // Check if user is already registered for this event
        $existingRegistration = Registration::where('user_id', Auth::id())
            ->where('event_id', $event->id)
            ->first();

        if ($existingRegistration) {
            return redirect()->back()
                ->with('error', 'You are already registered for this event.')
                ->withInput();
        }

        // Validate ticket availability and calculate total
        $tickets = Ticket::whereIn('id', array_keys($selectedTickets))
            ->where('event_id', $event->id)
            ->get();

        $totalAmount = 0;
        $ticketValidation = [];

        foreach ($tickets as $ticket) {
            $requestedQuantity = $selectedTickets[$ticket->id];

            if ($ticket->quantity && $ticket->available < $requestedQuantity) {
                return redirect()->back()
                    ->with('error', "Not enough {$ticket->name} tickets available. Only {$ticket->available} remaining.")
                    ->withInput();
            }

            $totalAmount += $ticket->price * $requestedQuantity;
            $ticketValidation[$ticket->id] = [
                'ticket' => $ticket,
                'quantity' => $requestedQuantity,
                'subtotal' => $ticket->price * $requestedQuantity
            ];
        }

        // Store ticket selection in session for later processing
        session([
            'ticket_selection' => [
                'event_id' => $event->id,
                'tickets' => $ticketValidation,
                'total_amount' => $totalAmount,
                'user_id' => Auth::id(),
                'expires_at' => now()->addMinutes(30) // Selection expires in 30 minutes
            ]
        ]);

        // If all tickets are free, create registrations immediately
        if ($totalAmount == 0) {
            return $this->createRegistrationsFromSession();
        }

        // Redirect to payment selection page with session data
        return redirect()->route('payment.selection', $event)
            ->with('success', 'Please select your preferred payment method to complete your registration.');
    }

    /**
     * Create registrations from session data (called after successful payment)
     */
    public function createRegistrationsFromSession()
    {
        $ticketSelection = session('ticket_selection');

        if (!$ticketSelection || now()->gt($ticketSelection['expires_at'])) {
            return redirect()->route('events.index')
                ->with('error', 'Ticket selection has expired. Please select tickets again.');
        }

        $event = Event::findOrFail($ticketSelection['event_id']);

        try {
            DB::beginTransaction();

            $registrations = [];

            // Create registrations for each ticket type
            foreach ($ticketSelection['tickets'] as $data) {
                $ticket = $data['ticket'];
                $quantity = $data['quantity'];

                // Double-check availability
                $currentTicket = Ticket::find($ticket->id);
                if ($currentTicket->quantity && $currentTicket->available < $quantity) {
                    throw new \Exception("Ticket {$ticket->name} is no longer available in the requested quantity.");
                }

                for ($i = 0; $i < $quantity; $i++) {
                    $registration = Registration::create([
                        'user_id' => $ticketSelection['user_id'],
                        'event_id' => $event->id,
                        'ticket_id' => $ticket->id,
                        'status' => 'confirmed',
                        'payment_status' => 'completed',
                    ]);

                    $registrations[] = $registration;
                }

                // Update ticket availability
                $currentTicket->decrement('available', $quantity);
            }

            // Create order for the registrations
            if (!empty($registrations)) {
                $order = $this->orderService->createOrderForMultipleRegistrations($registrations, Auth::user());
                $order->update(['status' => 'completed', 'completed_at' => now()]);
            }

            DB::commit();

            // Clear session data
            session()->forget('ticket_selection');

            Log::info('Registration completed after payment', [
                'user_id' => Auth::id(),
                'event_id' => $event->id,
                'registration_count' => count($registrations)
            ]);

            return redirect()->route('registrations.confirmation', $registrations[0])
                ->with('success', 'Registration completed successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Registration creation failed after payment', [
                'user_id' => Auth::id(),
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('events.show', $event)
                ->with('error', 'Registration failed: ' . $e->getMessage());
        }
    }

    /**
     * Store a new registration.
     */
    public function store(RegistrationRequest $request, Event $event)
    {

        $ticket = Ticket::findOrFail($request->ticket_id);

        // Verify ticket belongs to the event
        if ($ticket->event_id !== $event->id) {
            return redirect()->back()
                ->with('error', 'Invalid ticket selection.')
                ->withInput();
        }

        // Check if user is already registered for this event
        $existingRegistration = Registration::where('user_id', Auth::id())
            ->where('event_id', $event->id)
            ->first();

        if ($existingRegistration) {
            return redirect()->back()
                ->with('error', 'You are already registered for this event.')
                ->withInput();
        }

        // Check ticket availability
        if ($ticket->available < $request->quantity) {
            return redirect()->back()
                ->with('error', 'Not enough tickets available. Only ' . $ticket->available . ' tickets remaining.')
                ->withInput();
        }

        try {
            DB::beginTransaction();

            // Create registration
            $registration = Registration::create([
                'user_id' => Auth::id(),
                'event_id' => $event->id,
                'ticket_id' => $ticket->id,
                'status' => 'pending',
                'payment_status' => $ticket->price > 0 ? 'pending' : 'completed',
            ]);

            // Create order for the registration
            $order = $this->orderService->createOrderForRegistration($registration);

            // Update ticket availability
            $ticket->decrement('available', $request->quantity);

            DB::commit();

            // If it's a free ticket, mark as confirmed
            if ($ticket->price == 0) {
                $registration->update(['status' => 'confirmed']);

                return redirect()->route('registrations.confirmation', $registration)
                    ->with('success', 'Registration completed successfully!');
            }

            // For paid tickets, redirect to crypto payment
            // Store single ticket selection in session for payment
            session([
                'ticket_selection' => [
                    'event_id' => $event->id,
                    'tickets' => [
                        $ticket->id => [
                            'ticket' => $ticket,
                            'quantity' => 1,
                            'subtotal' => $ticket->price
                        ]
                    ],
                    'total_amount' => $ticket->price,
                    'user_id' => Auth::id(),
                    'expires_at' => now()->addMinutes(30)
                ]
            ]);

            return redirect()->route('payment.selection', $event)
                ->with('success', 'Please select your preferred payment method to complete your registration.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Registration failed. Please try again.')
                ->withInput();
        }
    }

    /**
     * Display registration confirmation.
     */
    public function confirmation(Registration $registration)
    {
        // Ensure user can only view their own registration
        if ($registration->user_id !== Auth::id()) {
            abort(403);
        }

        return view('registrations.confirmation', compact('registration'));
    }



    /**
     * Display user's registrations.
     */
    public function index()
    {
        $registrations = Auth::user()->registrations()
            ->with(['event', 'ticket'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('registrations.index', compact('registrations'));
    }

    /**
     * Display a specific registration.
     */
    public function show(Registration $registration)
    {
        // Ensure user can only view their own registration
        if ($registration->user_id !== Auth::id()) {
            abort(403);
        }

        return view('registrations.show', compact('registration'));
    }

    /**
     * Cancel a registration.
     */
    public function cancel(Registration $registration)
    {
        // Ensure user can only cancel their own registration
        if ($registration->user_id !== Auth::id()) {
            abort(403);
        }

        // Check if cancellation is allowed
        if ($registration->status === 'cancelled') {
            return redirect()->back()
                ->with('error', 'Registration is already cancelled.');
        }

        if ($registration->event->start_date->isPast()) {
            return redirect()->back()
                ->with('error', 'Cannot cancel registration for past events.');
        }

        try {
            DB::beginTransaction();

            // Update registration status
            $registration->update(['status' => 'cancelled']);

            // Return ticket availability
            $registration->ticket->increment('available');

            DB::commit();

            return redirect()->route('registrations.index')
                ->with('success', 'Registration cancelled successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Failed to cancel registration. Please try again.');
        }
    }

    /**
     * Display registration success page (for direct registration).
     */
    public function success(Registration $registration)
    {
        return view('registrations.success', compact('registration'));
    }
}
