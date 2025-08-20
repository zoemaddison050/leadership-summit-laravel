@extends('layouts.admin')

@section('title', 'Events Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Events Management</h1>
        <a href="{{ route('admin.events.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Create Event
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Default</th>
                            <th>Registrations</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($events as $event)
                        <tr>
                            <td>
                                <strong>{{ $event->title }}</strong>
                                <br>
                                <small class="text-muted">{{ $event->slug }}</small>
                            </td>
                            <td>
                                {{ $event->start_date->format('M d, Y') }}
                                @if($event->end_date && $event->end_date != $event->start_date)
                                <br><small class="text-muted">to {{ $event->end_date->format('M d, Y') }}</small>
                                @endif
                            </td>
                            <td>{{ $event->location }}</td>
                            <td>
                                <span class="badge bg-{{ $event->status === 'published' ? 'success' : ($event->status === 'draft' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($event->status) }}
                                </span>
                            </td>
                            <td>
                                @if($event->is_default)
                                <span class="badge bg-primary">Default</span>
                                @else
                                <form method="POST" action="{{ route('admin.events.set-default', $event) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Set Default</button>
                                </form>
                                @endif
                            </td>
                            <td>{{ $event->registrations_count }}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('admin.events.show', $event) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.events.edit', $event) }}" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if($event->registrations_count == 0)
                                    <form method="POST" action="{{ route('admin.events.destroy', $event) }}" class="d-inline"
                                        onsubmit="return confirm('Are you sure you want to delete this event?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No events found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $events->links() }}
        </div>
    </div>
</div>
@endsection