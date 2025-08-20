@extends('layouts.app')

@section('title', 'Registration Confirmation')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        Registration Confirmed
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <h5>Thank you for registering!</h5>
                        <p class="mb-0">Your registration has been confirmed. You will receive a confirmation email shortly.</p>
                    </div>

                    <!-- Registration Details -->
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Registration Details</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Registration ID:</strong></td>
                                    <td>#{{ $registration->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $registration->status === 'confirmed' ? 'success' : 'warning' }}">
                                            {{ ucfirst($registration->status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Registration Date:</strong></td>
                                    <td>{{ $registration->created_at->format('M d, Y g:i A') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Payment Status:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $registration->payment_status === 'completed' ? 'success' : 'warning' }}">
                                            {{ ucfirst($registration->payment_status) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Event Information</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Event:</strong></td>
                                    <td>{{ $registration->event->title }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Date:</strong></td>
                                    <td>{{ $registration->event->start_date->format('M d, Y') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Time:</strong></td>
                                    <td>{{ $registration->event->start_date->format('g:i A') }}</td>
                                </tr>
                                @if($registration->event->location)
                                <tr>
                                    <td><strong>Location:</strong></td>
                                    <td>{{ $registration->event->location }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    <!-- Ticket Information -->
                    <div class="mt-4">
                        <h6>Ticket Information</h6>
                        <div class="card">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h6 class="mb-1">{{ $registration->ticket->name }}</h6>
                                        @if($registration->ticket->description)
                                        <p class="text-muted mb-0">{{ $registration->ticket->description }}</p>
                                        @endif
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <span class="h5 mb-0">
                                            @if($registration->ticket->price > 0)
                                            ${{ number_format($registration->ticket->price, 2) }}
                                            @else
                                            Free
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Next Steps -->
                    <div class="mt-4">
                        <h6>What's Next?</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-envelope text-primary me-2"></i>
                                You will receive a confirmation email with your ticket details
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-calendar text-primary me-2"></i>
                                Add this event to your calendar
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-bell text-primary me-2"></i>
                                We'll send you reminders before the event
                            </li>
                            @if($registration->event->start_date->diffInDays() > 7)
                            <li class="mb-2">
                                <i class="fas fa-info-circle text-primary me-2"></i>
                                Event details and updates will be sent closer to the date
                            </li>
                            @endif
                        </ul>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-4 d-flex gap-2">
                        <a href="{{ route('events.show', $registration->event->slug) }}" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-1"></i>
                            Back to Event
                        </a>
                        <a href="{{ route('registrations.index') }}" class="btn btn-primary">
                            <i class="fas fa-list me-1"></i>
                            My Registrations
                        </a>
                        <button class="btn btn-outline-secondary" onclick="window.print()">
                            <i class="fas fa-print me-1"></i>
                            Print Confirmation
                        </button>
                    </div>

                    <!-- Cancellation Policy -->
                    <div class="mt-4">
                        <small class="text-muted">
                            <strong>Cancellation Policy:</strong>
                            You can cancel your registration up to 24 hours before the event starts.
                            <a href="{{ route('registrations.show', $registration) }}">View details</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {

        .btn,
        .alert {
            display: none !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
        }
    }
</style>
@endsection