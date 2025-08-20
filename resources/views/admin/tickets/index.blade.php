@extends('layouts.admin')

@section('title', 'Tickets Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Tickets Management</h1>
                <a href="{{ route('admin.tickets.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add Ticket
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Event</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tickets as $ticket)
                                <tr>
                                    <td>{{ $ticket->id }}</td>
                                    <td>{{ $ticket->name }}</td>
                                    <td>{{ $ticket->event->title ?? 'N/A' }}</td>
                                    <td>${{ number_format($ticket->price, 2) }}</td>
                                    <td>{{ $ticket->quantity }}</td>
                                    <td>
                                        <span class="badge bg-{{ $ticket->is_active ? 'success' : 'danger' }}">
                                            {{ $ticket->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.tickets.show', $ticket) }}" class="btn btn-sm btn-outline-primary">View</a>
                                            <a href="{{ route('admin.tickets.edit', $ticket) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                            <form method="POST" action="{{ route('admin.tickets.destroy', $ticket) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Are you sure?')">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">No tickets found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $tickets->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection