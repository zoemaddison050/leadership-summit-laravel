@extends('layouts.app')

@section('title', 'Registration Successful')

@push('styles')
<style>
    .success-container {
        min-height: 100vh;
        background: linear-gradient(135deg, #f8fafc 0%, #e3f2fd 100%);
        padding: 2rem 0;
        display: flex;
        align-items: center;
    }

    .success-card {
        background: white;
        border-radius: 1.5rem;
        padding: 3rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border: 2px solid #e5e7eb;
        max-width: 600px;
        margin: 0 auto;
        text-align: center;
    }

    .success-icon {
        font-size: 4rem;
        color: #10b981;
        margin-bottom: 1.5rem;
    }

    .success-title {
        color: var(--primary-color);
        font-weight: 700;
        font-size: 2rem;
        margin-bottom: 1rem;
    }

    .success-message {
        color: #6b7280;
        font-size: 1.1rem;
        line-height: 1.6;
        margin-bottom: 2rem;
    }

    .event-details {
        background: #f8fafc;
        border-radius: 1rem;
        padding: 1.5rem;
        margin: 2rem 0;
        text-align: left;
    }

    .event-details h4 {
        color: var(--primary-color);
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid #e5e7eb;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        font-weight: 600;
        color: #374151;
    }

    .detail-value {
        color: #6b7280;
    }

    .action-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
        margin-top: 2rem;
    }

    .btn-action {
        padding: 0.875rem 1.5rem;
        border-radius: 0.75rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    @media (max-width: 768px) {
        .success-container {
            padding: 1rem;
        }

        .success-card {
            padding: 2rem;
        }

        .action-buttons {
            flex-direction: column;
        }
    }
</style>
@endpush

@section('content')
<div class="success-container">
    <div class="container">
        <div class="success-card">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>

            <h1 class="success-title">Registration Successful!</h1>

            <p class="success-message">
                Thank you for registering! Your registration has been confirmed and you should receive a confirmation email shortly.
            </p>

            <div class="event-details">
                <h4>Registration Details</h4>

                <div class="detail-row">
                    <span class="detail-label">Event:</span>
                    <span class="detail-value">{{ $registration->event->title }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Date & Time:</span>
                    <span class="detail-value">{{ $registration->event->start_date->format('l, F j, Y \a\t g:i A') }}</span>
                </div>

                @if($registration->event->location)
                <div class="detail-row">
                    <span class="detail-label">Location:</span>
                    <span class="detail-value">{{ $registration->event->location }}</span>
                </div>
                @endif

                <div class="detail-row">
                    <span class="detail-label">Ticket Type:</span>
                    <span class="detail-value">{{ $registration->ticket->name }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Attendee:</span>
                    <span class="detail-value">{{ $registration->attendee_name }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">{{ $registration->attendee_email }}</span>
                </div>

                @if($registration->payment_method)
                <div class="detail-row">
                    <span class="detail-label">Payment Method:</span>
                    <span class="detail-value">
                        @if($registration->payment_method === 'card')
                        <i class="fas fa-credit-card me-1"></i>
                        Credit/Debit Card
                        @elseif($registration->payment_method === 'crypto')
                        <i class="fas fa-coins me-1"></i>
                        Cryptocurrency
                        @else
                        {{ ucfirst($registration->payment_method) }}
                        @endif
                    </span>
                </div>
                @endif

                @if($registration->payment_completed_at)
                <div class="detail-row">
                    <span class="detail-label">Payment Completed:</span>
                    <span class="detail-value">{{ $registration->payment_completed_at->format('M j, Y \a\t g:i A') }}</span>
                </div>
                @endif

                @if($registration->transaction_id)
                <div class="detail-row">
                    <span class="detail-label">Transaction ID:</span>
                    <span class="detail-value">{{ $registration->transaction_id }}</span>
                </div>
                @endif

                <div class="detail-row">
                    <span class="detail-label">Registration ID:</span>
                    <span class="detail-value">#{{ $registration->id }}</span>
                </div>
            </div>

            <div class="action-buttons">
                <a href="{{ route('events.index') }}" class="btn btn-primary btn-action">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Browse More Events
                </a>

                <a href="{{ route('events.show', $registration->event->slug) }}" class="btn btn-outline-primary btn-action">
                    <i class="fas fa-eye me-2"></i>
                    View Event Details
                </a>
            </div>
        </div>
    </div>
</div>
@endsection