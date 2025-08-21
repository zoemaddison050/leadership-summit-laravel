@extends('layouts.admin')

@section('title', 'Edit Session - ' . $session->title)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Edit Session</h1>
                <div class="btn-group">
                    <a href="{{ route('admin.sessions.show', $session) }}" class="btn btn-info">
                        <i class="fas fa-eye"></i> View Session
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
                            <h5 class="mb-0">Session Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.sessions.update', $session) }}">
                                @csrf
                                @method('PUT')

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="event_id" class="form-label">Event <span class="text-danger">*</span></label>
                                            <select class="form-select @error('event_id') is-invalid @enderror"
                                                id="event_id"
                                                name="event_id"
                                                required>
                                                <option value="">Select an event</option>
                                                @foreach($events as $event)
                                                <option value="{{ $event->id }}"
                                                    {{ old('event_id', $session->event_id) == $event->id ? 'selected' : '' }}>
                                                    {{ $event->title }}
                                                </option>
                                                @endforeach
                                            </select>
                                            @error('event_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="category" class="form-label">Category</label>
                                            <select class="form-select @error('category') is-invalid @enderror"
                                                id="category"
                                                name="category">
                                                <option value="">Select category</option>
                                                <option value="keynote" {{ old('category', $session->category) == 'keynote' ? 'selected' : '' }}>Keynote</option>
                                                <option value="workshop" {{ old('category', $session->category) == 'workshop' ? 'selected' : '' }}>Workshop</option>
                                                <option value="panel" {{ old('category', $session->category) == 'panel' ? 'selected' : '' }}>Panel Discussion</option>
                                                <option value="presentation" {{ old('category', $session->category) == 'presentation' ? 'selected' : '' }}>Presentation</option>
                                                <option value="networking" {{ old('category', $session->category) == 'networking' ? 'selected' : '' }}>Networking</option>
                                            </select>
                                            @error('category')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="title" class="form-label">Session Title <span class="text-danger">*</span></label>
                                    <input type="text"
                                        class="form-control @error('title') is-invalid @enderror"
                                        id="title"
                                        name="title"
                                        value="{{ old('title', $session->title) }}"
                                        required>
                                    @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror"
                                        id="description"
                                        name="description"
                                        rows="4">{{ old('description', $session->description) }}</textarea>
                                    @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Provide a detailed description of the session content and objectives.</div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="start_time" class="form-label">Start Date & Time</label>
                                            <input type="datetime-local"
                                                class="form-control @error('start_time') is-invalid @enderror"
                                                id="start_time"
                                                name="start_time"
                                                value="{{ old('start_time', $session->start_time ? $session->start_time->format('Y-m-d\TH:i') : '') }}">
                                            @error('start_time')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="end_time" class="form-label">End Date & Time</label>
                                            <input type="datetime-local"
                                                class="form-control @error('end_time') is-invalid @enderror"
                                                id="end_time"
                                                name="end_time"
                                                value="{{ old('end_time', $session->end_time ? $session->end_time->format('Y-m-d\TH:i') : '') }}">
                                            @error('end_time')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text"
                                        class="form-control @error('location') is-invalid @enderror"
                                        id="location"
                                        name="location"
                                        value="{{ old('location', $session->location) }}"
                                        placeholder="e.g., Main Auditorium, Room 101, Virtual">
                                    @error('location')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="speakers" class="form-label">Assign Speakers</label>
                                    <select class="form-select @error('speakers') is-invalid @enderror"
                                        id="speakers"
                                        name="speakers[]"
                                        multiple
                                        size="6">
                                        @foreach($speakers as $speaker)
                                        <option value="{{ $speaker->id }}"
                                            {{ in_array($speaker->id, old('speakers', $session->speakers->pluck('id')->toArray())) ? 'selected' : '' }}>
                                            {{ $speaker->name }}
                                            @if($speaker->position)
                                            - {{ $speaker->position }}
                                            @endif
                                            @if($speaker->company)
                                            ({{ $speaker->company }})
                                            @endif
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('speakers')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Hold Ctrl/Cmd to select multiple speakers.</div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.sessions.index') }}" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Session
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Current Speakers</h6>
                        </div>
                        <div class="card-body">
                            @if($session->speakers->count() > 0)
                            @foreach($session->speakers as $speaker)
                            <div class="d-flex align-items-center mb-2">
                                @if($speaker->photo)
                                <img src="{{ asset('storage/' . $speaker->photo) }}"
                                    alt="{{ $speaker->name }}"
                                    class="rounded-circle me-2"
                                    width="40" height="40"
                                    style="object-fit: cover;">
                                @else
                                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center me-2"
                                    style="width: 40px; height: 40px;">
                                    <i class="fas fa-user text-white"></i>
                                </div>
                                @endif
                                <div>
                                    <div class="fw-bold">{{ $speaker->name }}</div>
                                    @if($speaker->position)
                                    <small class="text-muted">{{ $speaker->position }}</small>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                            @else
                            <p class="text-muted mb-0">No speakers assigned</p>
                            @endif
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">Session Stats</h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="border-end">
                                        <h4 class="mb-0 text-primary">{{ $session->speakers->count() }}</h4>
                                        <small class="text-muted">Speakers</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h4 class="mb-0 text-info">
                                        @if($session->start_time && $session->end_time)
                                        {{ $session->start_time->diffInMinutes($session->end_time) }}m
                                        @else
                                        -
                                        @endif
                                    </h4>
                                    <small class="text-muted">Duration</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h6 class="mb-0">Session Info</h6>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0 small">
                                <dt class="col-sm-4">Created:</dt>
                                <dd class="col-sm-8">{{ $session->created_at->format('M j, Y') }}</dd>

                                <dt class="col-sm-4">Updated:</dt>
                                <dd class="col-sm-8">{{ $session->updated_at->format('M j, Y') }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection