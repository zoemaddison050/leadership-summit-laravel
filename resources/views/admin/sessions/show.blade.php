@extends('layouts.admin')

@section('title', 'Session Details - ' . $session->title)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Session Details</h1>
                <div class="btn-group">
                    <a href="{{ route('admin.sessions.edit', $session) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Session
                    </a>
                    <a href="{{ route('admin.sessions.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Sessions
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="mb-0">{{ $session->title }}</h3>
                                @if($session->category)
                                <span class="badge bg-primary fs-6">{{ ucfirst($session->category) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            @if($session->description)
                            <div class="mb-4">
                                <h5>Description</h5>
                                <div class="session-description">
                                    {!! nl2br(e($session->description)) !!}
                                </div>
                            </div>
                            @endif

                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Event</h6>
                                    @if($session->event)
                                    <p>
                                        <a href="{{ route('admin.events.show', $session->event) }}"
                                            class="text-decoration-none">
                                            <i class="fas fa-calendar-alt text-primary"></i>
                                            {{ $session->event->title }}
                                        </a>
                                    </p>
                                    @else
                                    <p class="text-muted">No event assigned</p>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <h6>Location</h6>
                                    <p>
                                        @if($session->location)
                                        <i class="fas fa-map-marker-alt text-danger"></i>
                                        {{ $session->location }}
                                        @else
                                        <span class="text-muted">Location not specified</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Start Time</h6>
                                    <p>
                                        @if($session->start_time)
                                        <i class="fas fa-clock text-success"></i>
                                        {{ $session->start_time->format('M j, Y \a\t g:i A') }}
                                        @else
                                        <span class="text-muted">Not scheduled</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6>End Time</h6>
                                    <p>
                                        @if($session->end_time)
                                        <i class="fas fa-clock text-warning"></i>
                                        {{ $session->end_time->format('M j, Y \a\t g:i A') }}
                                        @else
                                        <span class="text-muted">Not specified</span>
                                        @endif
                                    </p>
                                </div>
                            </div>

                            @if($session->start_time && $session->end_time)
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Duration:</strong> {{ $session->start_time->diffForHumans($session->end_time, true) }}
                            </div>
                            @endif
                        </div>
                    </div>

                    @if($session->speakers->count() > 0)
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Speakers ({{ $session->speakers->count() }})</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach($session->speakers as $speaker)
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-start">
                                        @if($speaker->photo)
                                        <img src="{{ asset('storage/' . $speaker->photo) }}"
                                            alt="{{ $speaker->name }}"
                                            class="rounded-circle me-3"
                                            width="60" height="60"
                                            style="object-fit: cover;">
                                        @else
                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center me-3"
                                            style="width: 60px; height: 60px;">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                        @endif
                                        <div>
                                            <h6 class="mb-1">
                                                <a href="{{ route('admin.speakers.show', $speaker) }}"
                                                    class="text-decoration-none">
                                                    {{ $speaker->name }}
                                                </a>
                                            </h6>
                                            @if($speaker->position)
                                            <p class="text-muted mb-1 small">{{ $speaker->position }}</p>
                                            @endif
                                            @if($speaker->company)
                                            <p class="text-muted mb-0 small">{{ $speaker->company }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="card mt-4">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-microphone-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Speakers Assigned</h5>
                            <p class="text-muted">This session doesn't have any speakers assigned yet.</p>
                            <a href="{{ route('admin.sessions.edit', $session) }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Assign Speakers
                            </a>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Session Statistics</h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="border-end">
                                        <h3 class="mb-0 text-primary">{{ $session->speakers->count() }}</h3>
                                        <small class="text-muted">Speakers</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h3 class="mb-0 text-info">
                                        @if($session->start_time && $session->end_time)
                                        {{ $session->start_time->diffInMinutes($session->end_time) }}
                                        @else
                                        -
                                        @endif
                                    </h3>
                                    <small class="text-muted">Minutes</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">Session Information</h6>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">ID:</dt>
                                <dd class="col-sm-8">#{{ $session->id }}</dd>

                                <dt class="col-sm-4">Created:</dt>
                                <dd class="col-sm-8">{{ $session->created_at->format('M j, Y g:i A') }}</dd>

                                <dt class="col-sm-4">Updated:</dt>
                                <dd class="col-sm-8">{{ $session->updated_at->format('M j, Y g:i A') }}</dd>

                                @if($session->category)
                                <dt class="col-sm-4">Category:</dt>
                                <dd class="col-sm-8">
                                    <span class="badge bg-primary">{{ ucfirst($session->category) }}</span>
                                </dd>
                                @endif
                            </dl>
                        </div>
                    </div>

                    @if($session->start_time)
                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">Schedule Status</h6>
                        </div>
                        <div class="card-body">
                            @if($session->start_time->isFuture())
                            <div class="alert alert-success mb-0">
                                <i class="fas fa-calendar-check"></i>
                                <strong>Upcoming</strong><br>
                                <small>{{ $session->start_time->diffForHumans() }}</small>
                            </div>
                            @elseif($session->start_time->isPast() && (!$session->end_time || $session->end_time->isFuture()))
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-play-circle"></i>
                                <strong>In Progress</strong><br>
                                <small>Started {{ $session->start_time->diffForHumans() }}</small>
                            </div>
                            @else
                            <div class="alert alert-secondary mb-0">
                                <i class="fas fa-check-circle"></i>
                                <strong>Completed</strong><br>
                                <small>Ended {{ $session->end_time->diffForHumans() }}</small>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="{{ route('admin.sessions.edit', $session) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-edit"></i> Edit Session
                                </a>
                                @if($session->event)
                                <a href="{{ route('admin.events.show', $session->event) }}" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-calendar-alt"></i> View Event
                                </a>
                                @endif
                                <form method="POST" action="{{ route('admin.sessions.destroy', $session) }}"
                                    onsubmit="return confirm('Are you sure you want to delete this session?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                        <i class="fas fa-trash"></i> Delete Session
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection