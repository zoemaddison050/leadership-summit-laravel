@extends('layouts.app')

@section('title', 'International Global Leadership Academy Summit 2025')
@section('meta_description', 'Join us for the exclusive International Global Leadership Academy Summit in September 2025, Cypress. Connect with global leaders and visionaries to shape the future of leadership.')
@section('meta_keywords', 'leadership summit, global leadership, academy, conference, professional development, networking, speakers, events')

@section('body_class', 'home-page')

@push('styles')
<style>
    /* Hero Section */
    .hero-section {
        position: relative;
        background: linear-gradient(135deg, var(--primary-color) 0%, #1e3a8a 100%);
        color: white;
        padding: 8rem 0 6rem;
        overflow: hidden;
    }

    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("{{ asset('images/hero-background.jpg') }}") center/cover;
        opacity: 0.1;
        z-index: 1;
    }

    .hero-content {
        position: relative;
        z-index: 2;
    }

    .hero-title {
        font-size: 3.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        line-height: 1.2;
    }

    .hero-subtitle {
        font-size: 1.25rem;
        margin-bottom: 2rem;
        opacity: 0.9;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    .hero-cta {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        justify-content: center;
    }

    .hero-stats {
        margin-top: 4rem;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 2rem;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    .stat-item {
        text-align: center;
    }

    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--secondary-color);
        display: block;
    }

    .stat-label {
        font-size: 0.9rem;
        opacity: 0.8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Features Section */
    .features-section {
        padding: 6rem 0;
        background-color: #f8fafc;
    }

    .section-title {
        font-size: 2.5rem;
        font-weight: 700;
        text-align: center;
        margin-bottom: 1rem;
        color: var(--primary-color);
    }

    .section-subtitle {
        text-align: center;
        font-size: 1.1rem;
        color: var(--dark-gray);
        max-width: 600px;
        margin: 0 auto 4rem;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
    }

    .feature-card {
        background: white;
        padding: 2.5rem 2rem;
        border-radius: 1rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        text-align: center;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }

    .feature-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem;
        font-size: 2rem;
        color: white;
    }

    .feature-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: var(--primary-color);
    }

    /* Events Section */
    .events-section {
        padding: 6rem 0;
        background: white;
    }

    .events-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 2rem;
    }

    .event-card {
        background: white;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .event-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }

    .event-image {
        height: 200px;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 3rem;
    }

    .event-content {
        padding: 2rem;
    }

    .event-date {
        color: var(--secondary-color);
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }

    .event-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: var(--primary-color);
    }

    .event-description {
        color: var(--dark-gray);
        margin-bottom: 1.5rem;
        line-height: 1.6;
    }

    /* CTA Section */
    .cta-section {
        padding: 6rem 0;
        background: linear-gradient(135deg, var(--primary-color) 0%, #1e3a8a 100%);
        color: white;
        text-align: center;
    }

    .cta-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .cta-description {
        font-size: 1.1rem;
        margin-bottom: 2rem;
        opacity: 0.9;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .hero-title {
            font-size: 2.5rem;
        }

        .hero-cta {
            justify-content: center;
        }

        .hero-stats {
            grid-template-columns: repeat(2, 1fr);
        }

        .section-title {
            font-size: 2rem;
        }

        .features-grid,
        .events-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content text-center">
            <h1 class="hero-title">International Global Leadership Academy Summit 2025</h1>
            <p class="hero-subtitle">Connect with global leaders and visionaries to shape the future of leadership in Cypress, September 2025</p>

            <div class="hero-cta">
                @php
                $defaultEvent = \App\Models\Event::getDefaultEvent();
                @endphp
                @if($defaultEvent)
                <a href="{{ route('events.show', $defaultEvent->slug) }}" class="btn btn-secondary btn-lg">
                    <i class="fas fa-ticket-alt me-2" aria-hidden="true"></i>Register Now
                </a>
                @endif
                <a href="{{ url('/about') }}" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-info-circle me-2" aria-hidden="true"></i>Learn More
                </a>
            </div>

            <div class="hero-stats">
                <div class="stat-item">
                    <span class="stat-number">50+</span>
                    <span class="stat-label">Expert Speakers</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">500+</span>
                    <span class="stat-label">Attendees</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">3</span>
                    <span class="stat-label">Days</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">20+</span>
                    <span class="stat-label">Sessions</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <h2 class="section-title">Why Attend Our Summit</h2>
        <p class="section-subtitle">Join the most influential leadership event of the year and transform your approach to leadership</p>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-users" aria-hidden="true"></i>
                </div>
                <h3 class="feature-title">World-Class Speakers</h3>
                <p>Learn from industry leaders, innovators, and visionaries who are shaping the future of business and leadership across the globe.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-network-wired" aria-hidden="true"></i>
                </div>
                <h3 class="feature-title">Networking Opportunities</h3>
                <p>Connect with peers, mentors, and potential collaborators in structured networking sessions and informal gatherings.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-lightbulb" aria-hidden="true"></i>
                </div>
                <h3 class="feature-title">Interactive Workshops</h3>
                <p>Participate in hands-on sessions designed to develop practical skills and actionable strategies you can implement immediately.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-certificate" aria-hidden="true"></i>
                </div>
                <h3 class="feature-title">Professional Development</h3>
                <p>Earn continuing education credits and certificates while advancing your leadership capabilities and career prospects.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-globe" aria-hidden="true"></i>
                </div>
                <h3 class="feature-title">Global Perspective</h3>
                <p>Gain insights into international leadership trends and best practices from diverse cultural and business contexts.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-rocket" aria-hidden="true"></i>
                </div>
                <h3 class="feature-title">Innovation Focus</h3>
                <p>Explore cutting-edge leadership methodologies and emerging trends that will define the future of organizational success.</p>
            </div>
        </div>
    </div>
</section>

<!-- Featured Events Section -->
<section class="events-section">
    <div class="container">
        <h2 class="section-title">Featured Events</h2>
        <p class="section-subtitle">Discover our signature events designed to elevate your leadership journey</p>

        <div class="events-grid">
            @forelse($featuredEvents ?? [] as $event)
            <div class="event-card">
                <div class="event-image">
                    @if($event->featured_image)
                    <img src="{{ asset('storage/' . $event->featured_image) }}" alt="{{ $event->title }}" class="w-100 h-100" style="object-fit: cover;">
                    @else
                    <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                    @endif
                </div>
                <div class="event-content">
                    <div class="event-date">
                        <i class="fas fa-calendar me-1" aria-hidden="true"></i>
                        {{ $event->start_date->format('M d, Y') }}
                        @if($event->end_date && $event->end_date != $event->start_date)
                        - {{ $event->end_date->format('M d, Y') }}
                        @endif
                    </div>
                    <h3 class="event-title">{{ $event->title }}</h3>
                    <p class="event-description">{{ Str::limit($event->description, 120) }}</p>
                    <a href="{{ url('/events/' . $event->slug) }}" class="btn btn-primary">
                        Learn More <i class="fas fa-arrow-right ms-1" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
            @empty
            <!-- Placeholder events when no data is available -->
            <div class="event-card">
                <div class="event-image">
                    <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                </div>
                <div class="event-content">
                    <div class="event-date">
                        <i class="fas fa-calendar me-1" aria-hidden="true"></i>
                        September 15-17, 2025
                    </div>
                    <h3 class="event-title">Leadership Excellence Summit</h3>
                    <p class="event-description">Our flagship three-day event featuring keynote speeches, panel discussions, and intensive workshops with global leadership experts.</p>
                    <a href="{{ url('/events') }}" class="btn btn-primary">
                        Learn More <i class="fas fa-arrow-right ms-1" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            <div class="event-card">
                <div class="event-image">
                    <i class="fas fa-users" aria-hidden="true"></i>
                </div>
                <div class="event-content">
                    <div class="event-date">
                        <i class="fas fa-calendar me-1" aria-hidden="true"></i>
                        September 18, 2025
                    </div>
                    <h3 class="event-title">Executive Networking Gala</h3>
                    <p class="event-description">An exclusive evening event for senior executives and industry leaders to network and celebrate leadership excellence.</p>
                    <a href="{{ url('/events') }}" class="btn btn-primary">
                        Learn More <i class="fas fa-arrow-right ms-1" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            <div class="event-card">
                <div class="event-image">
                    <i class="fas fa-lightbulb" aria-hidden="true"></i>
                </div>
                <div class="event-content">
                    <div class="event-date">
                        <i class="fas fa-calendar me-1" aria-hidden="true"></i>
                        September 19, 2025
                    </div>
                    <h3 class="event-title">Innovation Leadership Workshop</h3>
                    <p class="event-description">A hands-on workshop focused on leading innovation and driving organizational transformation in the digital age.</p>
                    <a href="{{ url('/events') }}" class="btn btn-primary">
                        Learn More <i class="fas fa-arrow-right ms-1" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
            @endforelse
        </div>

        <div class="text-center mt-4">
            <a href="{{ url('/events') }}" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-calendar-alt me-2" aria-hidden="true"></i>View All Events
            </a>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="cta-section">
    <div class="container">
        <h2 class="cta-title">Ready to Transform Your Leadership?</h2>
        <p class="cta-description">Join hundreds of leaders from around the world at the most impactful leadership event of 2025</p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            @php
            $defaultEvent = \App\Models\Event::getDefaultEvent();
            @endphp
            @if($defaultEvent)
            <a href="{{ route('events.show', $defaultEvent->slug) }}" class="btn btn-secondary btn-lg">
                <i class="fas fa-ticket-alt me-2" aria-hidden="true"></i>Register Now
            </a>
            @endif
            <a href="{{ url('/speakers') }}" class="btn btn-outline-light btn-lg">
                <i class="fas fa-users me-2" aria-hidden="true"></i>Meet Our Speakers
            </a>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animate stats on scroll
        const observerOptions = {
            threshold: 0.5,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const statNumbers = entry.target.querySelectorAll('.stat-number');
                    statNumbers.forEach(stat => {
                        const finalValue = parseInt(stat.textContent);
                        animateNumber(stat, 0, finalValue, 2000);
                    });
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        const heroStats = document.querySelector('.hero-stats');
        if (heroStats) {
            observer.observe(heroStats);
        }

        function animateNumber(element, start, end, duration) {
            const startTime = performance.now();
            const suffix = element.textContent.replace(/[0-9]/g, '');

            function updateNumber(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const current = Math.floor(start + (end - start) * progress);
                element.textContent = current + suffix;

                if (progress < 1) {
                    requestAnimationFrame(updateNumber);
                }
            }

            requestAnimationFrame(updateNumber);
        }
    });
</script>
@endpush