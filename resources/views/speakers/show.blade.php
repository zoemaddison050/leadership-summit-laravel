@extends('layouts.app')

@section('title', $speaker->name . ' - Speaker Profile')
@section('meta_description', 'Learn more about ' . $speaker->name . ', ' . ($speaker->position ?: 'speaker') . ' at the International Global Leadership Academy Summit 2025.')

@section('breadcrumbs')
<li class="breadcrumb-item"><a href="{{ route('speakers.index') }}">Speakers</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ $speaker->name }}</li>
@endsection

@push('styles')
<style>
    .speaker-hero {
        background: linear-gradient(135deg, var(--primary-color) 0%, #1e3a8a 100%);
        color: white;
        padding: 4rem 0;
    }

    .speaker-avatar {
        width: 200px;
        height: 200px;
        border-radius: 50%;
        border: 5px solid rgba(255, 255, 255, 0.2);
        margin: 0 auto 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 4rem;
        background: rgba(255, 255, 255, 0.1);
        overflow: hidden;
    }

    .speaker-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }

    .speaker-hero h1 {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 1rem;
        text-align: center;
    }

    .speaker-meta {
        text-align: center;
        font-size: 1.2rem;
        opacity: 0.9;
        margin-bottom: 2rem;
    }

    .speaker-social {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-top: 2rem;
    }

    .social-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border-radius: 50%;
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 1.2rem;
    }

    .social-link:hover {
        background: var(--secondary-color);
        color: var(--primary-color);
        transform: translateY(-2px);
    }

    .speaker-content {
        padding: 4rem 0;
    }

    .bio-section {
        background: white;
        border-radius: 1rem;
        padding: 3rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 3rem;
    }

    .bio-section h2 {
        color: var(--primary-color);
        font-size: 2rem;
        font-weight: 600;
        margin-bottom: 2rem;
        text-align: center;
    }

    .bio-text {
        font-size: 1.1rem;
        line-height: 1.8;
        color: var(--dark-gray);
        text-align: justify;
    }

    .sessions-section {
        background: white;
        border-radius: 1rem;
        padding: 3rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 3rem;
    }

    .sessions-section h2 {
        color: var(--primary-color);
        font-size: 2rem;
        font-weight: 600;
        margin-bottom: 2rem;
        text-align: center;
    }

    .session-card {
        background: #f8fafc;
        border-radius: 0.75rem;
        padding: 2rem;
        margin-bottom: 1.5rem;
        border-left: 4px solid var(--primary-color);
        transition: all 0.3s ease;
    }

    .session-card:hover {
        transform: translateX(5px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .session-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 1rem;
    }

    .session-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
        margin-bottom: 1rem;
        font-size: 0.95rem;
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

    .session-description {
        color: var(--dark-gray);
        line-height: 1.6;
        margin-bottom: 1rem;
    }

    .event-badge {
        display: inline-block;
        background: var(--secondary-color);
        color: var(--primary-color);
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 2rem;
        margin: 3rem 0;
    }

    .stat-card {
        background: white;
        border-radius: 1rem;
        padding: 2rem;
        text-align: center;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border-top: 4px solid var(--primary-color);
    }

    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--primary-color);
        display: block;
        margin-bottom: 0.5rem;
    }

    .stat-label {
        color: var(--dark-gray);
        font-weight: 500;
    }

    .back-to-speakers {
        background: var(--primary-color);
        color: white;
        padding: 2rem 0;
        text-align: center;
    }

    .back-to-speakers a {
        color: white;
        text-decoration: none;
        font-size: 1.1rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .back-to-speakers a:hover {
        color: var(--secondary-color);
        transform: translateX(-5px);
    }

    @media (max-width: 768px) {
        .speaker-hero {
            padding: 2rem 0;
        }

        .speaker-hero h1 {
            font-size: 2rem;
        }

        .speaker-avatar {
            width: 150px;
            height: 150px;
            font-size: 3rem;
        }

        .bio-section,
        .sessions-section {
            padding: 2rem;
        }

        .session-meta {
            flex-direction: column;
            gap: 0.5rem;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .stat-card {
            padding: 1.5rem;
        }

        .stat-number {
            font-size: 2rem;
        }
    }
</style>
@endpush

@section('content')
<!-- Speaker Hero -->
<section class="speaker-hero">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <div class="speaker-avatar">
                    @if($speaker->photo)
                    <img src="{{ asset('storage/' . $speaker->photo) }}" alt="{{ $speaker->name }}">
                    @else
                    <i class="fas fa-user" aria-hidden="true"></i>
                    @endif
                </div>

                <h1>{{ $speaker->name }}</h1>

                <div class="speaker-meta">
                    @if($speaker->position)
                    <div>{{ $speaker->position }}</div>
                    @endif
                    @if($speaker->company)
                    <div class="mt-1">{{ $speaker->company }}</div>
                    @endif
                </div>

                <div class="speaker-social">
                    @if($speaker->linkedin ?? false)
                    <a href="{{ $speaker->linkedin }}" class="social-link" target="_blank" aria-label="LinkedIn">
                        <i class="fab fa-linkedin-in" aria-hidden="true"></i>
                    </a>
                    @endif
                    @if($speaker->twitter ?? false)
                    <a href="{{ $speaker->twitter }}" class="social-link" target="_blank" aria-label="Twitter">
                        <i class="fab fa-twitter" aria-hidden="true"></i>
                    </a>
                    @endif
                    @if($speaker->website ?? false)
                    <a href="{{ $speaker->website }}" class="social-link" target="_blank" aria-label="Website">
                        <i class="fas fa-globe" aria-hidden="true"></i>
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Speaker Stats -->
<section class="speaker-content">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number">{{ $speaker->sessions->count() }}</span>
                <span class="stat-label">Sessions</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">{{ $speaker->sessions->pluck('event_id')->unique()->count() }}</span>
                <span class="stat-label">Events</span>
            </div>
            <div class="stat-card">
                <span class="stat-number">{{ $speaker->sessions->where('start_time', '>=', now())->count() }}</span>
                <span class="stat-label">Upcoming</span>
            </div>
        </div>
    </div>
</section>

<!-- Biography -->
<section class="speaker-content">
    <div class="container">
        <div class="bio-section">
            <h2>About {{ $speaker->name }}</h2>
            <div class="bio-text">
                {!! nl2br(e($speaker->bio)) !!}
            </div>
        </div>
    </div>
</section>

<!-- Sessions -->
@if($speaker->sessions->count() > 0)
<section class="speaker-content">
    <div class="container">
        <div class="sessions-section">
            <h2>Sessions & Presentations</h2>

            @foreach($speaker->sessions as $session)
            <div class="session-card">
                <h3 class="session-title">{{ $session->title }}</h3>

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
                            {{ $session->start_time->format('M j, Y') }} at {{ $session->start_time->format('g:i A') }}
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

                @if($session->description)
                <div class="session-description">
                    {{ $session->description }}
                </div>
                @endif

                @if($session->event)
                <span class="event-badge">{{ $session->event->title }}</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Back to Speakers -->
<section class="back-to-speakers">
    <div class="container">
        <a href="{{ route('speakers.index') }}">
            <i class="fas fa-arrow-left"></i>
            Back to All Speakers
        </a>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animate stats on load
        const statNumbers = document.querySelectorAll('.stat-number');

        statNumbers.forEach(stat => {
            const finalValue = parseInt(stat.textContent);
            animateNumber(stat, 0, finalValue, 1500);
        });

        function animateNumber(element, start, end, duration) {
            const startTime = performance.now();

            function updateNumber(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const current = Math.floor(start + (end - start) * progress);
                element.textContent = current;

                if (progress < 1) {
                    requestAnimationFrame(updateNumber);
                }
            }

            requestAnimationFrame(updateNumber);
        }
    });
</script>
@endpush