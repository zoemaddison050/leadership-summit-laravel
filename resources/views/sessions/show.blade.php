@extends('layouts.app')

@section('title', $session->title . ' - Session Details')
@section('meta_description', 'Learn more about ' . $session->title . ' at the International Global Leadership Academy Summit 2025.')

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('sessions.index') }}">Sessions</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ Str::limit($session->title, 30) }}</li>
@endsection

@push('styles')
<style>
    .session-hero {
        background: linear-gradient(135deg, var(--primary-color) 0%, #1e3a8a 100%);
        color: white;
        padding: 4rem 0;
    }

    .session-category-badge {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
        display: inline-block;
        margin-bottom: 1rem;
    }

    .session-hero h1 {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 2rem;
        line-height: 1.2;
    }

    .session-meta-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        margin-top: 2rem;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        background: rgba(255, 255, 255, 0.1);
        padding: 1rem;
        border-radius: 0.5rem;
    }

    .meta-icon {
        width: 50px;
        height: 50px;
        background: var(--secondary-color);
        color: var(--primary-color);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .meta-content h6 {
        margin: 0 0 0.25rem 0;
        font-weight: 600;
        opacity: 0.8;
    }

    .meta-content p {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 500;
    }

    .session-content {
        padding: 4rem 0;
    }

    .content-section {
        background: white;
        border-radius: 1rem;
        padding: 3rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 3rem;
    }

    .content-section h2 {
        color: var(--primary-color);
        font-size: 2rem;
        font-weight: 600;
        margin-bottom: 2rem;
    }

    .session-description {
        font-size: 1.1rem;
        line-height: 1.8;
        color: var(--dark-gray);
    }

    .speakers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
    }

    .speaker-card {
        background: #f8fafc;
        border-radius: 1rem;
        padding: 2rem;
        text-align: center;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .speaker-card:hover {
        border-color: var(--primary-color);
        transform: translateY(-5px);
    }

    .speaker-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        margin: 0 auto 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--primary-color);
        color: white;
        font-size: 3rem;
        overflow: hidden;
    }

    .speaker-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }

    .speaker-name {
        font-size: 1.3rem;
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }

    .speaker-title {
        color: var(--dark-gray);
        margin-bottom: 0.5rem;
        font-weight: 500;
    }

    .speaker-company {
        color: var(--secondary-color);
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .event-info {
        background: var(--secondary-color);
        color: var(--primary-color);
        border-radius: 1rem;
        padding: 2rem;
        text-align: center;
        margin-bottom: 3rem;
    }

    .event-info h3 {
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .status-alert {
        padding: 1.5rem;
        border-radius: 1rem;
        margin-bottom: 3rem;
        text-align: center;
        font-weight: 600;
    }

    .status-upcoming {
        background: #dcfce7;
        color: #166534;
        border: 2px solid #bbf7d0;
    }

    .status-ongoing {
        background: #fef3c7;
        color: #92400e;
        border: 2px solid #fde68a;
    }

    .status-completed {
        background: #f3f4f6;
        color: #6b7280;
        border: 2px solid #d1d5db;
    }

    .back-to-sessions {
        background: var(--primary-color);
        color: white;
        padding: 2rem 0;
        text-align: center;
    }

    .back-to-sessions a {
        color: white;
        text-decoration: none;
        font-size: 1.1rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .back-to-sessions a:hover {
        color: var(--secondary-color);
        transform: translateX(-5px);
    }

    @media (max-width: 768px) {
        .session-hero {
            padding: 2rem 0;
        }

        .session-hero h1 {
            font-size: 2rem;
        }

        .session-meta-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .content-section {
            padding: 2rem;
        }

        .speakers-grid {
            grid-template-columns: 1fr;
        }

        .speaker-avatar {
            width: 100px;
            height: 100px;
            font-size: 2.5rem;
        }
    }
</style>
@endpush

@section('content')
<!-- Session Hero -->
<section class="session-hero">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                @if($session->category)
                <span class="session-category-badge">{{ ucfirst($session->category) }}</span>
                @endif

                <h1>{{ $session->title }}</h1>

                <div class="session-meta-grid">
                    @if($session->start_time)
                    <div class="meta-item">
                        <div class="meta-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="meta-content">
                            <h6>Date & Time</h6>
                            <p>
                                {{ $session->start_time->format('M j, Y') }}<br>
                                {{ $session->start_time->format('g:i A') }}
                                @if($session->end_time)
                                - {{ $session->end_time->format('g:i A') }}
                                @endif
                            </p>
                        </div>
                    </div>
                    @endif

                    @if($session->location)
                    <div class="meta-item">
                        <div class="meta-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="meta-content">
                            <h6>Location</h6>
                            <p>{{ $session->location }}</p>
                        </div>
                    </div>
                    @endif

                    @if($session->start_time && $session->end_time)
                    <div class="meta-item">
                        <div class="meta-icon">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <div class="meta-content">
                            <h6>Duration</h6>
                            <p>{{ $session->start_time->diffForHumans($session->end_time, true) }}</p>
                        </div>
                    </div>
                    @endif

                    <div class="meta-item">
                        <div class="meta-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="meta-content">
                            <h6>Speakers</h6>
                            <p>{{ $session->speakers->count() }} Expert{{ $session->speakers->count() !== 1 ? 's' : '' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Session Content -->
<section class="session-content">
    <div class="container">
        <!-- Event Info -->
        @if($session->event)
        <div class="event-info">
            <h3>Part of {{ $session->event->title }}</h3>
            <p>This session is part of the {{ $session->event->title }} event series.</p>
            <a href="{{ route('events.show', $session->event->slug ?? $session->event->id) }}" class="btn btn-primary">
                View Full Event Details
            </a>
        </div>
        @endif

        <!-- Status Alert -->
        @if($session->start_time)
        <div class="status-alert 
                @if($session->start_time->isFuture()) status-upcoming
                @elseif($session->start_time->isPast() && (!$session->end_time || $session->end_time->isFuture())) status-ongoing
                @else status-completed
                @endif">
            @if($session->start_time->isFuture())
            <i class="fas fa-calendar-check fa-2x mb-2"></i>
            <h4>Upcoming Session</h4>
            <p>This session starts {{ $session->start_time->diffForHumans() }}</p>
            @elseif($session->start_time->isPast() && (!$session->end_time || $session->end_time->isFuture()))
            <i class="fas fa-play-circle fa-2x mb-2"></i>
            <h4>Session In Progress</h4>
            <p>This session started {{ $session->start_time->diffForHumans() }}</p>
            @else
            <i class="fas fa-check-circle fa-2x mb-2"></i>
            <h4>Session Completed</h4>
            <p>This session ended {{ $session->end_time->diffForHumans() }}</p>
            @endif
        </div>
        @endif

        <!-- Description -->
        @if($session->description)
        <div class="content-section">
            <h2>About This Session</h2>
            <div class="session-description">
                {!! nl2br(e($session->description)) !!}
            </div>
        </div>
        @endif

        <!-- Speakers -->
        @if($session->speakers->count() > 0)
        <div class="content-section">
            <h2>Meet Your Speakers</h2>
            <div class="speakers-grid">
                @foreach($session->speakers as $speaker)
                <div class="speaker-card">
                    <div class="speaker-avatar">
                        @if($speaker->photo)
                        <img src="{{ asset('storage/' . $speaker->photo) }}" alt="{{ $speaker->name }}">
                        @else
                        <i class="fas fa-user"></i>
                        @endif
                    </div>

                    <h3 class="speaker-name">{{ $speaker->name }}</h3>

                    @if($speaker->position)
                    <p class="speaker-title">{{ $speaker->position }}</p>
                    @endif

                    @if($speaker->company)
                    <p class="speaker-company">{{ $speaker->company }}</p>
                    @endif

                    @if($speaker->bio)
                    <p class="text-muted mb-3">{{ Str::limit($speaker->bio, 100) }}</p>
                    @endif

                    <a href="{{ route('speakers.show', $speaker) }}" class="btn btn-outline-primary">
                        View Full Profile
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Related Sessions -->
        @if($session->event && $session->event->sessions->where('id', '!=', $session->id)->count() > 0)
        <div class="content-section">
            <h2>Other Sessions in {{ $session->event->title }}</h2>
            <div class="row">
                @foreach($session->event->sessions->where('id', '!=', $session->id)->take(3) as $relatedSession)
                <div class="col-md-4 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">{{ $relatedSession->title }}</h5>
                            @if($relatedSession->start_time)
                            <p class="card-text">
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i>
                                    {{ $relatedSession->start_time->format('M j, g:i A') }}
                                </small>
                            </p>
                            @endif
                            @if($relatedSession->description)
                            <p class="card-text">{{ Str::limit($relatedSession->description, 80) }}</p>
                            @endif
                            <a href="{{ route('sessions.show', $relatedSession) }}" class="btn btn-outline-primary btn-sm">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</section>

<!-- Back to Sessions -->
<section class="back-to-sessions">
    <div class="container">
        <a href="{{ route('sessions.index') }}">
            <i class="fas fa-arrow-left"></i>
            Back to All Sessions
        </a>
    </div>
</section>
@endsection