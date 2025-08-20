@component('mail::message')
# Registration Successful

Hello {{ $registration->attendee_name }},

Thank you for registering for **{{ $registration->event->title }}**! Your registration has been successfully completed.

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
- {{ $ticket['ticket_name'] }} x{{ $ticket['quantity'] }}
@if($ticket['price'] > 0)
- ${{ number_format($ticket['price'] * $ticket['quantity'], 2) }}
@else
- Free
@endif
@endforeach

@if($registration->total_amount > 0)
**Total Amount:** ${{ number_format($registration->total_amount, 2) }}
@else
**Total Amount:** Free
@endif

We're excited to have you join us at this event! Please save this email for your records.

@if($registration->total_amount > 0)
Your payment is currently being processed. You'll receive another confirmation email once your payment has been approved by our team.
@endif

If you have any questions or need to make changes to your registration, please contact our support team.

@component('mail::button', ['url' => route('events.show', $registration->event)])
View Event Details
@endcomponent

Thanks,<br>
{{ config('app.name') }} Team
@endcomponent