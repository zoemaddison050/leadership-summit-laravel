@extends('layouts.app')

@section('title', 'Registration Details')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Registration Details</h2>
                <div>
                    <a href="{{ route('registrations.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to Registrations
                    </a>
                    <button class="btn btn-outline-primary" onclick="window.print()">
                        <i class="fas fa-print me-1"></i>
                        Print
                    </button>
                </div>
            </div>

            <div class="row">
                <!-- Registration Information -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Registration Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Registration ID:</strong></td>
                                            <td>#{{ $registration->id }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>
                                                <span class="badge bg-{{ $registration->status === 'confirmed' ? 'success' : ($registration->status === 'cancelled' ? 'danger' : 'warning') }}">
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
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Registrant:</strong></td>
                                            <td>{{ $registration->user->name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Email:</strong></td>
                                            <td>{{ $registration->user->email }}</td>
                                        </tr>
                                        @if($registration->updated_at != $registration->created_at)
                                        <tr>
                                            <td><strong>Last Updated:</strong></td>
                                            <td>{{ $registration->updated_at->format('M d, Y g:i A') }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Event Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Event Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h4>{{ $registration->event->title }}</h4>
                                    @if($registration->event->description)
                                    <p class="text-muted">{{ $registration->event->description }}</p>
                                    @endif

                                    <div class="row mt-3">
                                        <div class="col-sm-6">
                                            <p><strong>Start Date:</strong><br>
                                                {{ $registration->event->start_date->format('l, M d, Y') }}<br>
                                                {{ $registration->event->start_date->format('g:i A') }}
                                            </p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>End Date:</strong><br>
                                                {{ $registration->event->end_date->format('l, M d, Y') }}<br>
                                                {{ $registration->event->end_date->format('g:i A') }}
                                            </p>
                                        </div>
                                    </div>

                                    @if($registration->event->location)
                                    <p><strong>Location:</strong><br>
                                        {{ $registration->event->location }}
                                    </p>
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    @if($registration->event->featured_image)
                                    <img src="{{ asset('storage/' . $registration->event->featured_image) }}"
                                        alt="{{ $registration->event->title }}"
                                        class="img-fluid rounded">
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ticket Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Ticket Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6>{{ $registration->ticket->name }}</h6>
                                    @if($registration->ticket->description)
                                    <p class="text-muted mb-0">{{ $registration->ticket->description }}</p>
                                    @endif
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="h4">
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

                <!-- Actions Sidebar -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Actions</h5>
                        </div>
                        <div class="card-body">
                            @if($registration->payment_status === 'pending')
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Payment is required to confirm your registration.
                            </div>
                            <a href="{{ route('events.show', $registration->event->slug) }}"
                                class="btn btn-warning w-100 mb-3">
                                <i class="fas fa-credit-card me-1"></i>
                                Register Again (Payment Required)
                            </a>
                            @endif

                            <a href="{{ route('events.show', $registration->event->slug) }}"
                                class="btn btn-outline-primary w-100 mb-2">
                                <i class="fas fa-eye me-1"></i>
                                View Event Details
                            </a>

                            @if($registration->status !== 'cancelled' && !$registration->event->start_date->isPast())
                            <form method="POST"
                                action="{{ route('registrations.cancel', $registration) }}"
                                onsubmit="return confirm('Are you sure you want to cancel this registration? This action cannot be undone.')">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-outline-danger w-100 mb-2">
                                    <i class="fas fa-times me-1"></i>
                                    Cancel Registration
                                </button>
                            </form>
                            @endif

                            <button class="btn btn-outline-secondary w-100" onclick="window.print()">
                                <i class="fas fa-print me-1"></i>
                                Print Details
                            </button>
                        </div>
                    </div>

                    <!-- Important Information -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="mb-0">Important Information</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled small">
                                <li class="mb-2">
                                    <i class="fas fa-info-circle text-primary me-2"></i>
                                    Please arrive 15 minutes before the event starts
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-id-card text-primary me-2"></i>
                                    Bring a valid ID for verification
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-envelope text-primary me-2"></i>
                                    Check your email for updates
                                </li>
                                @if($registration->status !== 'cancelled' && !$registration->event->start_date->isPast())
                                <li class="mb-2">
                                    <i class="fas fa-clock text-warning me-2"></i>
                                    Cancellation allowed up to 24 hours before event
                                </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {

        .btn,
        .card-header,
        .alert {
            display: none !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
        }

        .col-md-4:last-child {
            display: none !important;
        }
    }
</style>
@endsection