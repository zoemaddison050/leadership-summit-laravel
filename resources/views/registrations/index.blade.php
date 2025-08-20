@extends('layouts.app')

@section('title', 'My Registrations')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>My Registrations</h2>
                <a href="{{ route('events.index') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>
                    Register for Event
                </a>
            </div>

            @if($registrations->count() > 0)
            <div class="row">
                @foreach($registrations as $registration)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        @if($registration->event->featured_image)
                        <img src="{{ asset('storage/' . $registration->event->featured_image) }}"
                            class="card-img-top"
                            alt="{{ $registration->event->title }}"
                            style="height: 200px; object-fit: cover;">
                        @endif
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">{{ $registration->event->title }}</h5>

                            <!-- Event Details -->
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    {{ $registration->event->start_date->format('M d, Y') }}
                                </small><br>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    {{ $registration->event->start_date->format('g:i A') }}
                                </small><br>
                                @if($registration->event->location)
                                <small class="text-muted">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    {{ $registration->event->location }}
                                </small>
                                @endif
                            </div>

                            <!-- Registration Status -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-{{ $registration->status === 'confirmed' ? 'success' : ($registration->status === 'cancelled' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($registration->status) }}
                                    </span>
                                    <span class="badge bg-{{ $registration->payment_status === 'completed' ? 'success' : 'warning' }}">
                                        Payment: {{ ucfirst($registration->payment_status) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Ticket Info -->
                            <div class="mb-3">
                                <strong>{{ $registration->ticket->name }}</strong><br>
                                <span class="text-muted">
                                    @if($registration->ticket->price > 0)
                                    ${{ number_format($registration->ticket->price, 2) }}
                                    @else
                                    Free
                                    @endif
                                </span>
                            </div>

                            <!-- Registration Date -->
                            <div class="mb-3">
                                <small class="text-muted">
                                    Registered: {{ $registration->created_at->format('M d, Y') }}
                                </small>
                            </div>

                            <!-- Actions -->
                            <div class="mt-auto">
                                <div class="btn-group w-100" role="group">
                                    <a href="{{ route('registrations.show', $registration) }}"
                                        class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>
                                        View
                                    </a>

                                    @if($registration->status !== 'cancelled' && !$registration->event->start_date->isPast())
                                    <form method="POST"
                                        action="{{ route('registrations.cancel', $registration) }}"
                                        class="d-inline"
                                        onsubmit="return confirm('Are you sure you want to cancel this registration?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="fas fa-times me-1"></i>
                                            Cancel
                                        </button>
                                    </form>
                                    @endif

                                    @if($registration->payment_status === 'pending')
                                    <a href="{{ route('events.show', $registration->event->slug) }}"
                                        class="btn btn-warning btn-sm">
                                        <i class="fas fa-credit-card me-1"></i>
                                        Complete Registration
                                    </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $registrations->links() }}
            </div>
            @else
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-calendar-times fa-4x text-muted"></i>
                </div>
                <h4>No Registrations Yet</h4>
                <p class="text-muted">You haven't registered for any events yet.</p>
                <a href="{{ route('events.index') }}" class="btn btn-primary">
                    <i class="fas fa-search me-1"></i>
                    Browse Events
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection