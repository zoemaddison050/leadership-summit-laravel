@extends('layouts.admin')

@section('title', 'Event Details')

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('admin.events.index') }}">Events</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ $event->title }}</li>
@endsection

@push('styles')
<style>
    .event-header {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 2rem;
        border-radius: 0.5rem;
        margin-bottom: 2rem;
    }

    .event-image {
        max-width: 100%;
        height: 300px;
        object-fit: cover;
        border-radius: 0.5rem;
    }

    .info-section {
        background: white;
        padding: 2rem;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 1.5rem;
    }

    .info-section h3 {
        color: var(--primary-color);
        border-bottom: 2px solid var(--primary-color);
        padding-bottom: 0.5rem;
        margin-bottom: 1.5rem;
    }

    .stat-card {
        text-align: center;
        padding: 1.5rem;
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border-left: 4px solid var(--primary-color);
    }

    .stat-number {
        font-size: 2rem;
        font-weight: bold;
        color: var(--primary-color);
    }

    .status-badge {
        font-size: 1rem;
        padding: 0.5rem 1rem;
    }

    .event-meta {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .meta-item {
        display: flex;
        align-items: center;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 0.5rem;
    }

    .meta-icon {
        width: 40px;
        height: 40px;
        background: var(--primary-color);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
    }
</style>
@endpush

@section('content')
<!-- Event Header -->
<div class="event-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="mb-2">{{ $event->title }}</h1>
            <p class="mb-3 opacity-75">{{ Str::limit($event->description, 150) }}</p>
            <div class="d-flex align-items-center gap-3">
                @php
                $statusColors = [
                'draft' => 'secondary',
                'published' => 'success',
                'featured' => 'warning',
                'cancelled' => 'danger'
                ];
                $statusColor = $statusColors[$event->status] ?? 'secondary';
                @endphp
                <span class="badge bg-{{ $statusColor }} status-badge">
                    {{ ucfirst($event->status) }}
                </span>
                <span class="opacity-75">
                    <i class="fas fa-calendar me-1" aria-hidden="true"></i>
                    {{ $event->start_date->format('M d, Y g:i A') }}
                </span>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group" role="group">
                <a href="{{ route('admin.events.edit', $event) }}" class="btn btn-light">
                    <i class="fas fa-edit me-2" aria-hidden="true"></i>Edit Event
                </a>
                <a href="{{ url('/events/' . $event->slug) }}" class="btn btn-outline-light" target="_blank">
                    <i class="fas fa-external-link-alt me-2" aria-hidden="true"></i>View Public
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Event Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number">{{ $event->registrations->count() }}</div>
            <div class="text-muted">Total Registrations</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number">{{ $event->tickets->count() }}</div>
            <div class="text-muted">Ticket Types</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number">{{ $event->sessions->count() }}</div>
            <div class="text-muted">Sessions</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-number">${{ number_format($event->tickets->sum('price'), 2) }}</div>
            <div class="text-muted">Total Revenue Potential</div>
        </div>
    </div>
</div>

<!-- Event Details -->
<div class="row">
    <div class="col-md-8">
        <!-- Basic Information -->
        <div class="info-section">
            <h3>Event Information</h3>

            <div class="event-meta">
                <div class="meta-item">
                    <div class="meta-icon">
                        <i class="fas fa-calendar" aria-hidden="true"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">Start Date</div>
                        <div class="text-muted">{{ $event->start_date->format('l, F j, Y g:i A') }}</div>
                    </div>
                </div>

                @if($event->end_date)
                <div class="meta-item">
                    <div class="meta-icon">
                        <i class="fas fa-calendar-check" aria-hidden="true"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">End Date</div>
                        <div class="text-muted">{{ $event->end_date->format('l, F j, Y g:i A') }}</div>
                    </div>
                </div>
                @endif

                @if($event->location)
                <div class="meta-item">
                    <div class="meta-icon">
                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">Location</div>
                        <div class="text-muted">{{ $event->location }}</div>
                    </div>
                </div>
                @endif

                <div class="meta-item">
                    <div class="meta-icon">
                        <i class="fas fa-link" aria-hidden="true"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">URL Slug</div>
                        <div class="text-muted">{{ $event->slug }}</div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <h5>Description</h5>
                <div class="border p-3 rounded bg-light">
                    {!! nl2br(e($event->description)) !!}
                </div>
            </div>
        </div>

        <!-- Tickets Section -->
        @if($event->tickets->count() > 0)
        <div class="info-section">
            <h3>Ticket Types</h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Ticket Name</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Available</th>
                            <th>Sold</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($event->tickets as $ticket)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $ticket->name }}</div>
                                @if($ticket->description)
                                <small class="text-muted">{{ $ticket->description }}</small>
                                @endif
                            </td>
                            <td>${{ number_format($ticket->price, 2) }}</td>
                            <td>{{ $ticket->quantity ?? 'Unlimited' }}</td>
                            <td>{{ $ticket->available ?? 'Unlimited' }}</td>
                            <td>{{ $ticket->quantity ? ($ticket->quantity - ($ticket->available ?? $ticket->quantity)) : 0 }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- Sessions Section -->
        @if($event->sessions->count() > 0)
        <div class="info-section">
            <h3>Sessions</h3>
            <div class="row">
                @foreach($event->sessions as $session)
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">{{ $session->title }}</h6>
                            <p class="card-text small text-muted">{{ Str::limit($session->description, 100) }}</p>
                            <div class="small">
                                <i class="fas fa-clock me-1" aria-hidden="true"></i>
                                {{ $session->start_time->format('g:i A') }} - {{ $session->end_time->format('g:i A') }}
                            </div>
                            @if($session->location)
                            <div class="small text-muted">
                                <i class="fas fa-map-marker-alt me-1" aria-hidden="true"></i>
                                {{ $session->location }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-4">
        <!-- Featured Image -->
        @if($event->featured_image)
        <div class="info-section">
            <h3>Featured Image</h3>
            <img src="{{ asset('storage/' . $event->featured_image) }}"
                alt="{{ $event->title }}" class="event-image">
        </div>
        @endif

        <!-- Recent Registrations -->
        @if($event->registrations->count() > 0)
        <div class="info-section">
            <h3>Recent Registrations</h3>
            @foreach($event->registrations->take(5) as $registration)
            <div class="d-flex align-items-center mb-2 pb-2 border-bottom">
                <div class="flex-grow-1">
                    <div class="fw-semibold">{{ $registration->user->name }}</div>
                    <small class="text-muted">{{ $registration->created_at->diffForHumans() }}</small>
                </div>
                <span class="badge bg-{{ $registration->payment_status === 'paid' ? 'success' : 'warning' }}">
                    {{ ucfirst($registration->payment_status) }}
                </span>
            </div>
            @endforeach

            @if($event->registrations->count() > 5)
            <div class="text-center mt-3">
                <a href="#" class="btn btn-sm btn-outline-primary">View All Registrations</a>
            </div>
            @endif
        </div>
        @endif

        <!-- Quick Actions -->
        <div class="info-section">
            <h3>Quick Actions</h3>
            <div class="d-grid gap-2">
                <a href="{{ route('admin.events.edit', $event) }}" class="btn btn-primary">
                    <i class="fas fa-edit me-2" aria-hidden="true"></i>Edit Event
                </a>
                <a href="#" class="btn btn-outline-primary">
                    <i class="fas fa-ticket-alt me-2" aria-hidden="true"></i>Manage Tickets
                </a>
                <a href="#" class="btn btn-outline-primary">
                    <i class="fas fa-users me-2" aria-hidden="true"></i>View Registrations
                </a>
                <a href="#" class="btn btn-outline-primary">
                    <i class="fas fa-download me-2" aria-hidden="true"></i>Export Data
                </a>
                <button class="btn btn-outline-danger" onclick="confirmDelete()">
                    <i class="fas fa-trash me-2" aria-hidden="true"></i>Delete Event
                </button>
            </div>
        </div>

        <!-- Event Metadata -->
        <div class="info-section">
            <h3>Metadata</h3>
            <table class="table table-sm">
                <tr>
                    <td class="fw-semibold">Created:</td>
                    <td>{{ $event->created_at->format('M j, Y g:i A') }}</td>
                </tr>
                <tr>
                    <td class="fw-semibold">Updated:</td>
                    <td>{{ $event->updated_at->format('M j, Y g:i A') }}</td>
                </tr>
                <tr>
                    <td class="fw-semibold">ID:</td>
                    <td>{{ $event->id }}</td>
                </tr>
                <tr>
                    <td class="fw-semibold">Slug:</td>
                    <td><code>{{ $event->slug }}</code></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<!-- Delete Form (hidden) -->
<form id="deleteForm" action="{{ route('admin.events.destroy', $event) }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Delete confirmation
        window.confirmDelete = function() {
            if (confirm('Are you sure you want to delete this event? This action cannot be undone and will also delete all associated registrations and tickets.')) {
                document.getElementById('deleteForm').submit();
            }
        };
    });
</script>
@endpush