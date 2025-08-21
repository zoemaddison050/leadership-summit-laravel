@extends('layouts.admin')

@section('title', 'Speaker Details - ' . $speaker->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Speaker Details</h1>
                <div class="btn-group">
                    <a href="{{ route('admin.speakers.edit', $speaker) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Speaker
                    </a>
                    <a href="{{ route('admin.speakers.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Speakers
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body text-center">
                            @if($speaker->photo)
                            <img src="{{ asset('storage/' . $speaker->photo) }}"
                                alt="{{ $speaker->name }}"
                                class="rounded-circle mb-3"
                                style="width: 200px; height: 200px; object-fit: cover;">
                            @else
                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                                style="width: 200px; height: 200px;">
                                <i class="fas fa-user fa-4x text-white"></i>
                            </div>
                            @endif

                            <h3 class="mb-1">{{ $speaker->name }}</h3>

                            @if($speaker->position)
                            <p class="text-muted mb-1">{{ $speaker->position }}</p>
                            @endif

                            @if($speaker->company)
                            <p class="text-muted mb-3">{{ $speaker->company }}</p>
                            @endif

                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="border-end">
                                        <h4 class="mb-0 text-primary">{{ $speaker->sessions->count() }}</h4>
                                        <small class="text-muted">Sessions</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h4 class="mb-0 text-info">{{ $speaker->sessions->pluck('event_id')->unique()->count() }}</h4>
                                    <small class="text-muted">Events</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">Speaker Information</h6>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Created:</dt>
                                <dd class="col-sm-8">{{ $speaker->created_at->format('M j, Y g:i A') }}</dd>

                                <dt class="col-sm-4">Updated:</dt>
                                <dd class="col-sm-8">{{ $speaker->updated_at->format('M j, Y g:i A') }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Biography</h5>
                        </div>
                        <div class="card-body">
                            <div class="speaker-bio">
                                {!! nl2br(e($speaker->bio)) !!}
                            </div>
                        </div>
                    </div>

                    @if($speaker->sessions->count() > 0)
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">Sessions ({{ $speaker->sessions->count() }})</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Session Title</th>
                                            <th>Event</th>
                                            <th>Date & Time</th>
                                            <th>Location</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($speaker->sessions as $session)
                                        <tr>
                                            <td>
                                                <strong>{{ $session->title }}</strong>
                                                @if($session->description)
                                                <br>
                                                <small class="text-muted">{{ Str::limit($session->description, 100) }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($session->event)
                                                <a href="{{ route('admin.events.show', $session->event) }}"
                                                    class="text-decoration-none">
                                                    {{ $session->event->title }}
                                                </a>
                                                @else
                                                <span class="text-muted">No event assigned</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($session->start_time)
                                                <div>{{ $session->start_time->format('M j, Y') }}</div>
                                                <small class="text-muted">
                                                    {{ $session->start_time->format('g:i A') }}
                                                    @if($session->end_time)
                                                    - {{ $session->end_time->format('g:i A') }}
                                                    @endif
                                                </small>
                                                @else
                                                <span class="text-muted">Not scheduled</span>
                                                @endif
                                            </td>
                                            <td>{{ $session->location ?: '-' }}</td>
                                            <td>
                                                <a href="{{ route('admin.sessions.show', $session) }}"
                                                    class="btn btn-sm btn-outline-info" title="View Session">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="card mt-3">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Sessions Assigned</h5>
                            <p class="text-muted">This speaker hasn't been assigned to any sessions yet.</p>
                            <a href="{{ route('admin.sessions.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create Session
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection