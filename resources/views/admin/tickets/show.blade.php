@extends('layouts.admin')

@section('title', 'Ticket Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Ticket Details</h1>
                <div class="btn-group">
                    <a href="{{ route('admin.tickets.edit', $ticket) }}" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Edit Ticket
                    </a>
                    <a href="{{ route('admin.tickets.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Tickets
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Ticket Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Ticket Name</h6>
                                    <p>{{ $ticket->name }}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Event</h6>
                                    <p>{{ $ticket->event->title ?? 'N/A' }}</p>
                                </div>
                            </div>

                            @if($ticket->description)
                            <div class="mb-3">
                                <h6>Description</h6>
                                <p>{{ $ticket->description }}</p>
                            </div>
                            @endif

                            <div class="row">
                                <div class="col-md-3">
                                    <h6>Price</h6>
                                    <p class="h5 text-success">${{ number_format($ticket->price, 2) }}</p>
                                </div>
                                <div class="col-md-3">
                                    <h6>Available Quantity</h6>
                                    <p class="h5">{{ number_format($ticket->quantity) }}</p>
                                </div>
                                <div class="col-md-3">
                                    <h6>Max Per Order</h6>
                                    <p class="h5">{{ $ticket->max_per_order ?? 'No limit' }}</p>
                                </div>
                                <div class="col-md-3">
                                    <h6>Status</h6>
                                    <p>
                                        <span class="badge bg-{{ $ticket->is_active ? 'success' : 'danger' }} fs-6">
                                            {{ $ticket->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            @if($ticket->sale_start || $ticket->sale_end)
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Sale Start</h6>
                                    <p>{{ $ticket->sale_start ? $ticket->sale_start->format('M d, Y g:i A') : 'Immediately' }}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Sale End</h6>
                                    <p>{{ $ticket->sale_end ? $ticket->sale_end->format('M d, Y g:i A') : 'Until event starts' }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('admin.tickets.edit', $ticket) }}" class="btn btn-primary">
                                    <i class="fas fa-edit me-2"></i>Edit Ticket
                                </a>
                                @if($ticket->event)
                                <a href="{{ route('admin.events.show', $ticket->event) }}" class="btn btn-outline-info">
                                    <i class="fas fa-calendar me-2"></i>View Event
                                </a>
                                @endif
                                <form method="POST" action="{{ route('admin.tickets.destroy', $ticket) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger w-100"
                                        onclick="return confirm('Are you sure you want to delete this ticket?')">
                                        <i class="fas fa-trash me-2"></i>Delete Ticket
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">Ticket Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center">
                                <h4 class="text-primary">0</h4>
                                <small>Tickets Sold</small>
                            </div>
                            <hr>
                            <div class="text-center">
                                <h4 class="text-success">${{ number_format($ticket->price * 0, 2) }}</h4>
                                <small>Revenue Generated</small>
                            </div>
                            <hr>
                            <div class="text-center">
                                <h4 class="text-info">{{ $ticket->quantity }}</h4>
                                <small>Remaining</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection