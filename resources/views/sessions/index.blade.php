@extends('layouts.app')

@section('title', 'Sessions - Leadership Summit 2025')
@section('meta_description', 'Explore all sessions at the International Global Leadership Academy Summit 2025. Workshops, keynotes, panels, and networking events.')

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">Sessions</li>
@endsection

@push('styles')
<style>
    .sessions-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, #1e3a8a 100%);
        color: white;
        padding: 4rem 0 3rem;
        text-align: center;
    }

    .sessions-header h1 {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .sessions-header p {
        font-size: 1.2rem;
        opacity: 0.9;
        max-width: 600px;
        margin: 0 auto;
    }

    .sessions-filters {
        background: white;
        padding: 2rem 0;
        border-bottom: 1px solid #e5e7eb;
        position: sticky;
        top: 70px;
        z-index: 100;
    }

    .filter-row {
        display: flex;
        gap: 1rem;
        align-items: end;
        flex-wrap: wrap;
    }

    .filter-group {
        flex: 1;
        min-width: 200px;
    }

    .sessions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 2rem;
        padding: 3rem 0;
    }

    .session-card {
        background: white;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border: 1px solid #f1f5f9;
        position: relative;
    }

    .session-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }

    .session-header {
        padding: 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        position: relative;
    }

    .session-category {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: var(--primary-color);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .session-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 1rem;
        line-height: 1.4;
    }

    .session-meta {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        font-size: 0.9rem;
        color: var(--dark-gray);
    }

    .session-meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .session-meta-item i {
        color: var(--secondary-color);
        width: 16px;
    }

    .session-content {
        padding: 1.5rem;
    }

    .session-description {
        color: var(--dark-gray);
        line-height: 1.6;
        margin-bottom: 1.5rem;
    }

    .session-speakers {
        margin-bottom: 1.5rem;
    }

    .speakers-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .speaker-badge {
        background: #f1f5f9;
        color: var(--primary-color);
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .speaker-badge:hover {
        background: var(--primary-color);
        color: white;
    }

    .session-event {
        background: var(--secondary-color);
        color: var(--primary-color);
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-weight: 600;
        text-align: center;
        margin-bottom: 1rem;
    }

    .session-time-status {
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-weight: 600;
        text-align: center;
        margin-bottom: 1rem;
    }

    .status-upcoming {
        background: #dcfce7;
        color: #166534;
    }

    .status-ongoing {
        background: #fef3c7;
        color: #92400e;
    }

    .status-completed {
        background: #f3f4f6;
        color: #6b7280;
    }

    .stats-section {
        background: var(--primary-color);
        color: white;
        padding: 3rem 0;
        text-align: center;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 2rem;
        max-width: 800px;
        margin: 0 auto;
    }

    .stat-item {
        text-align: center;
    }

    .stat-number {
        font-size: 3rem;
        font-weight: 700;
        color: var(--secondary-color);
        display: block;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        font-size: 1.1rem;
        opacity: 0.9;
    }

    @media (max-width: 768px) {
        .sessions-header h1 {
            font-size: 2.5rem;
        }

        .sessions-grid {
            grid-template-columns: 1fr;
            padding: 2rem 0;
        }

        .filter-row {
            flex-direction: column;
            align-items: stretch;
        }

        .sessions-filters {
            position: static;
        }
    }
</style>
@endpush

@section('content')
<!-- Sessions Header -->
<section class="sessions-header">
    <div class="container">
        <h1>Summit Sessions</h1>
        <p>Discover workshops, keynotes, panels, and networking opportunities designed to accelerate your leadership journey</p>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-number">{{ $sessions->total() }}</span>
                <span class="stat-label">Total Sessions</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">{{ $categories->count() }}</span>
                <span class="stat-label">Session Types</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">{{ $events->count() }}</span>
                <span class="stat-label">Events</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">{{ $sessions->sum(function($session) { return $session->speakers->count(); }) }}</span>
                <span class="stat-label">Expert Speakers</span>
            </div>
        </div>
    </div>
</section>

<!-- Sessions Filters -->
<section class="sessions-filters">
    <div class="container">
        <form method="GET" action="{{ route('sessions.index') }}">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="event" class="form-label">Filter by Event</label>
                    <select name="event" id="event" class="form-select">
                        <option value="">All Events</option>
                        @foreach($events as $event)
                        <option value="{{ $event->id }}" {{ request('event') == $event->id ? 'selected' : '' }}>
                            {{ $event->title }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label for="category" class="form-label">Filter by Category</label>
                    <select name="category" id="category" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                        <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>
                            {{ ucfirst($category) }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label for="search" class="form-label">Search Sessions</label>
                    <input type="text" name="search" id="search" class="form-control"
                        placeholder="Search by title, description..." value="{{ request('search') }}">
                </div>

                <div class="filter-group" style="flex: 0 0 auto;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- Sessions Grid -->
<section class="sessions-content">
    <div class="container">
        @if($sessions->count() > 0)
        <div class="sessions-grid">
            @foreach($sessions as $session)
            <article class="session-card">
                <div class="session-header">
                    @if($session->category)
                    <span class="session-category">{{ ucfirst($session->category) }}</span>
                    @endif

                    <h2 class="session-title">{{ $session->title }}</h2>

                    <div class="session-meta">
                        @if($session->event)
                        <div class="session-meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>{{ $session->event->title }}</span>
                        </div>
                        @endif

                        @if($session->start_time)
                        <div class="session-meta-item">
                            <i class="fas fa-clock"></i>
                            <span>
                                {{ $session->start_time->format('M j, Y \a\t g:i A') }}
                                @if($session->end_time)
                                - {{ $session->end_time->format('g:i A') }}
                                @endif
                            </span>
                        </div>
                        @endif

                        @if($session->location)
                        <div class="session-meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>{{ $session->location }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="session-content">
                    @if($session->event)
                    <div class="session-event">
                        {{ $session->event->title }}
                    </div>
                    @endif

                    @if($session->start_time)
                    <div class="session-time-status 
                                    @if($session->start_time->isFuture()) status-upcoming
                                    @elseif($session->start_time->isPast() && (!$session->end_time || $session->end_time->isFuture())) status-ongoing
                                    @else status-completed
                                    @endif">
                        @if($session->start_time->isFuture())
                        <i class="fas fa-calendar-check"></i> Upcoming
                        @elseif($session->start_time->isPast() && (!$session->end_time || $session->end_time->isFuture()))
                        <i class="fas fa-play-circle"></i> In Progress
                        @else
                        <i class="fas fa-check-circle"></i> Completed
                        @endif
                    </div>
                    @endif

                    @if($session->description)
                    <div class="session-description">
                        {{ Str::limit(strip_tags($session->description), 120) }}
                    </div>
                    @endif

                    @if($session->speakers->count() > 0)
                    <div class="session-speakers">
                        <h6>Speakers:</h6>
                        <div class="speakers-list">
                            @foreach($session->speakers as $speaker)
                            <a href="{{ route('speakers.show', $speaker) }}" class="speaker-badge">
                                {{ $speaker->name }}
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <a href="{{ route('sessions.show', $session) }}" class="btn btn-primary w-100">
                        View Session Details
                    </a>
                </div>
            </article>
            @endforeach
        </div>

        @if($sessions->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $sessions->appends(request()->query())->links() }}
        </div>
        @endif
        @else
        <div class="text-center py-5">
            <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
            <h3 class="text-muted">No sessions found</h3>
            <p class="text-muted">Try adjusting your filters or check back later for new sessions.</p>
            <a href="{{ route('sessions.index') }}" class="btn btn-primary">
                View All Sessions
            </a>
        </div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-submit form when filters change
        const filterSelects = document.querySelectorAll('#event, #category');
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });
    });
</script>
@endpush