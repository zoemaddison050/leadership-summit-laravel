@component('mail::message')
# Payment Issue - Registration Not Confirmed

Hello {{ $registration->attendee_name }},

We've reviewed your payment for **{{ $registration->event->title }}** and unfortunately, we were unable to confirm it.

## Reason for Decline
{{ $registration->declined_reason }}

## What This Means
Your registration has not been confirmed and your spot has been released. However, you can register again using the same email address and phone number if you'd like to try again.

## Event Details
- **Event:** {{ $registration->event->title }}
- **Date:** {{ $registration->event->start_date->format('F j, Y') }}
- **Time:** {{ $registration->event->start_date->format('g:i A') }}

## Your Registration Details
- **Name:** {{ $registration->attendee_name }}
- **Email:** {{ $registration->attendee_email }}
- **Phone:** {{ $registration->attendee_phone }}

## Tickets You Attempted to Purchase
@foreach($registration->ticket_selections as $ticket)
- {{ $ticket['ticket_name'] }} x{{ $ticket['quantity'] }} - ${{ number_format($ticket['price'] * $ticket['quantity'], 2) }}
@endforeach

**Total Amount:** ${{ number_format($registration->total_amount, 2) }}

## Next Steps
If you believe this was an error or if you've resolved the payment issue, you can register again using the button below. Your email and phone number are now available for a new registration.

If you need assistance or have questions about the payment decline, please contact our support team.

@component('mail::button', ['url' => route('registration.direct', $registration->event)])
Register Again
@endcomponent

Thanks,<br>
{{ config('app.name') }} Team
@endcomponent