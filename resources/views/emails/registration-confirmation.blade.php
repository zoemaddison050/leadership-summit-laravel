@component('mail::message')
# Registration Confirmed

Hello {{ $registration->attendee_name }},

Great news! Your payment has been approved and your registration for **{{ $registration->event->title }}** is now confirmed.

## Event Details
- **Event:** {{ $registration->event->title }}
- **Date:** {{ $registration->event->start_date->format('F j, Y') }}
- **Time:** {{ $registration->event->start_date->format('g:i A') }}
@if($registration->event->location)
- **Location:** {{ $registration->event->location }}
@endif

## Registration Details
- **Name:** {{ $registration->attendee_name }}
- **Email:** {{ $registration->attendee_email }}
- **Phone:** {{ $registration->attendee_phone }}
@if($registration->emergency_contact_name)
- **Emergency Contact:** {{ $registration->emergency_contact_name }} ({{ $registration->emergency_contact_phone }})
@endif

## Tickets
@foreach($registration->ticket_selections as $ticket)
- {{ $ticket['ticket_name'] }} x{{ $ticket['quantity'] }} - ${{ number_format($ticket['price'] * $ticket['quantity'], 2) }}
@endforeach

**Total Amount:** ${{ number_format($registration->total_amount, 2) }}

We're excited to have you join us at this event! Please save this email for your records.

If you have any questions or need to make changes to your registration, please contact our support team.

@component('mail::button', ['url' => route('events.show', $registration->event)])
View Event Details
@endcomponent

Thanks,<br>
{{ config('app.name') }} Team
@endcomponent