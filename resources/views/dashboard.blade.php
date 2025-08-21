@extends('layouts.app')

@section('title', 'Dashboard - Leadership Summit')
@section('meta_description', 'Your personal dashboard for the Leadership Summit. Track your registrations, discover events, and manage your profile.')

@push('styles')
<style>
    :root {
        --dashboard-primary: #2563eb;
        --dashboard-secondary: #f59e0b;
        --dashboard-success: #10b981;
        --dashboard-warning: #f59e0b;
        --dashboard-danger: #ef4444;
        --dashboard-info: #06b6d4;
        --dashboard-dark: #374151;
        --dashboard-light: #f3f4f6;
        --dashboard-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        --dashboard-shadow-hover: 0 20px 60px rgba(0, 0, 0, 0.15);
        --dashboard-border-radius: 1.5rem;
        --dashboard-transition: all 0.3s ease;
    }

    .dashboard-hero {
        background: linear-gradient(135deg, var(--dashboard-primary) 0%, #1e3a8a 100%);
        color: white;
        padding: 4rem 0 3rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .dashboard-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        opacity: 0.3;
    }

    .welcome-content {
        position: relative;
        z-index: 2;
    }

    .dashboard-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .dashboard-subtitle {
        font-size: 1.1rem;
        opacity: 0.9;
        margin-bottom: 2rem;
    }

    .hero-stats {
        display: flex;
        justify-content: center;
        gap: 3rem;
        margin-top: 2rem;
    }

    .hero-stat {
        text-align: center;
    }

    .hero-stat-number {
        font-size: 2rem;
        font-weight: 700;
        color: var(--dashboard-secondary);
        display: block;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .hero-stat-label {
        font-size: 0.9rem;
        opacity: 0.8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .dashboard-content {
        padding: 4rem 0;
        background: linear-gradient(to bottom, #f8fafc 0%, #ffffff 100%);
    }

    .dashboard-card {
        background: white;
        border-radius: var(--dashboard-border-radius);
        box-shadow: var(--dashboard-shadow);
        padding: 2.5rem;
        margin-bottom: 2rem;
        border: 1px solid #e5e7eb;
        transition: var(--dashboard-transition);
        position: relative;
        overflow: hidden;
    }

    .dashboard-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--dashboard-primary) 0%, var(--dashboard-secondary) 100%);
    }

    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--dashboard-shadow-hover);
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }

    .stat-card {
        background: white;
        border-radius: var(--dashboard-border-radius);
        padding: 2rem;
        text-align: center;
        box-shadow: var(--dashboard-shadow);
        border: 1px solid #e5e7eb;
        transition: var(--dashboard-transition);
        position: relative;
        overflow: hidden;
        cursor: pointer;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--dashboard-primary) 0%, var(--dashboard-secondary) 100%);
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }

    .stat-card:hover::before {
        transform: scaleX(1);
    }

    .stat-card:hover {
        transform: translateY(-10px);
        box-shadow: var(--dashboard-shadow-hover);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--dashboard-primary) 0%, var(--dashboard-secondary) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 1.5rem;
        color: white;
        box-shadow: 0 4px 20px rgba(37, 99, 235, 0.3);
    }

    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--dashboard-primary);
        margin-bottom: 0.5rem;
        display: block;
    }
    
    .stat-label {
        color: var(--dashboard-dark);
        font-size: 1rem;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .stat-description {
        color: #6b7280;
        font-size: 0.85rem;
        line-height: 1.4;
    }
    
    .quick-actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }

    .quick-action {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.5rem;
        background: white;
        border-radius: 1rem;
        text-decoration: none;
        color: var(--dashboard-dark);
        border: 1px solid #e5e7eb;
        transition: var(--dashboard-transition);
        position: relative;
        overflow: hidden;
    }

    .quick-action::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: var(--dashboard-primary);
        transform: scaleY(0);
        transition: transform 0.3s ease;
    }

    .quick-action:hover::before {
        transform: scaleY(1);
    }

    .quick-action:hover {
        transform: translateX(10px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        color: var(--dashboard-primary);
    }

    .quick-action-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--dashboard-primary) 0%, var(--dashboard-secondary) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .quick-action-content h4 {
        margin: 0 0 0.25rem 0;
        font-weight: 600;
        font-size: 1.1rem;
    }

    .quick-action-content p {
        margin: 0;
        font-size: 0.9rem;
        color: #6b7280;
    }

    .event-card {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        border: 1px solid #e5e7eb;
        transition: var(--dashboard-transition);
        margin-bottom: 1rem;
    }

    .event-card:hover {
transform: translateY(-3px);
box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.event-header {
display: flex;
justify-content: space-between;
align-items: flex-start;
margin-bottom: 1rem;
}

.event-title {
font-weight: 600;
color: var(--dashboard-primary);
margin-bottom: 0.5rem;
font-size: 1.1rem;
}

.event-badge {
padding: 0.25rem 0.75rem;
border-radius: 50px;
font-size: 0.8rem;
font-weight: 600;
text-transform: uppercase;
}

.badge-registered {
background: #dcfce7;
color: #166534;
}

.badge-pending {
background: #fef3c7;
color: #d97706;
}

.event-meta {
display: flex;
gap: 1rem;
margin-bottom: 1rem;
font-size: 0.9rem;
color: #6b7280;
flex-wrap: wrap;
}

.event-meta span {
display: flex;
align-items: center;
gap: 0.5rem;
}

.activity-timeline {
position: relative;
padding-left: 2rem;
}

.activity-timeline::before {
content: '';
position: absolute;
left: 1rem;
top: 0;
bottom: 0;
width: 2px;
background: linear-gradient(to bottom, var(--dashboard-primary), var(--dashboard-secondary));
}

.activity-item {
position: relative;
margin-bottom: 2rem;
padding-left: 2rem;
}

.activity-item::before {
content: '';
position: absolute;
left: -0.5rem;
top: 0.5rem;
width: 12px;
height: 12px;
border-radius: 50%;
background: var(--dashboard-primary);
border: 3px solid white;
box-shadow: 0 0 0 3px var(--dashboard-primary);
}

.activity-content {
background: white;
padding: 1.5rem;
border-radius: 1rem;
border: 1px solid #e5e7eb;
box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
}

.speaker-card {
background: white;
border-radius: 1rem;
padding: 1.5rem;
text-align: center;
border: 1px solid #e5e7eb;
transition: var(--dashboard-transition);
}

.speaker-card:hover {
transform: translateY(-5px);
box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.speaker-avatar {
width: 80px;
height: 80px;
border-radius: 50%;
background: linear-gradient(135deg, var(--dashboard-primary) 0%, var(--dashboard-secondary) 100%);
display: flex;
align-items: center;
justify-content: center;
margin: 0 auto 1rem;
font-size: 2rem;
color: white;
}

.speaker-name {
font-weight: 600;
color: var(--dashboard-primary);
margin-bottom: 0.25rem;
}

.speaker-title {
font-size: 0.9rem;
color: #6b7280;
margin-bottom: 0.5rem;
}

.speaker-company {
font-size: 0.8rem;
color: var(--dashboard-secondary);
font-weight: 500;
}

@media (max-width: 768px) {
.dashboard-hero {
padding: 3rem 0 2rem;
}

.dashboard-title {
font-size: 2rem;
}

.hero-stats {
gap: 2rem;
}

.hero-stat-number {
font-size: 1.5rem;
}

.stats-grid {
grid-template-columns: 1fr;
}

.quick-actions-grid {
grid-template-columns: 1fr;
}

.event-meta {
flex-direction: column;
gap: 0.5rem;
}
}

.fade-in {
animation: fadeIn 0.6s ease-out;
}

.slide-up {
animation: slideUp 0.6s ease-out;
}

.scale-in {
animation: scaleIn 0.4s ease-out;
}

@keyframes fadeIn {
from { opacity: 0; }
to { opacity: 1; }
}

@keyframes slideUp {
from {
opacity: 0;
transform: translateY(30px);
}
to {
opacity: 1;
transform: translateY(0);
}
}

@keyframes scaleIn {
from {
opacity: 0;
transform: scale(0.9);
}
to {
opacity: 1;
transform: scale(1);
}
}
</style>

@section('content')
<!-- Dashboard Hero Section -->
<section class="dashboard-hero">
    <div class="container">
        <div class="welcome-content fade-in">
            <h1 class="dashboard-title">Welcome back, {{ $user->name }}!</h1>
            <p class="dashboard-subtitle">
                @if($user->role)
                {{ ucfirst($user->role->name) }} •
                @endif
                Ready to explore leadership opportunities?
            </p>

            <div class="hero-stats slide-up">
                <div class="hero-stat">
                    <span class="hero-stat-number" data-count="{{ $dashboardStats['user_registrations'] }}">0</span>
                    <span class="hero-stat-label">Your Events</span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-number" data-count="{{ $dashboardStats['upcoming_events'] }}">0</span>
                    <span class="hero-stat-label">Upcoming Events</span>
                </div>
                <div class="hero-stat">
                    <span class="hero-stat-number" data-count="{{ $dashboardStats['member_since_days'] }}">0</span>
                    <span class="hero-stat-label">Days as Member</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Dashboard Content -->
<section class="dashboard-content">
    <div class="container">
        <!-- Statistics Dashboard -->
        <div class="stats-grid scale-in">
            <div class="stat-card" onclick="navigateTo('{{ route('registrations.index') }}')">
                <div class="stat-icon">
                    <i class="fas fa-ticket-alt" aria-hidden="true"></i>
                </div>
                <span class="stat-number" data-count="{{ $dashboardStats['user_registrations'] }}">0</span>
                <div class="stat-label">My Registrations</div>
                <div class="stat-description">Events you've registered for</div>
            </div>

            <div class="stat-card" onclick="navigateTo('{{ route('orders.index') }}')">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart" aria-hidden="true"></i>
                </div>
                <span class="stat-number" data-count="{{ $dashboardStats['user_orders'] }}">0</span>
                <div class="stat-label">My Orders</div>
                <div class="stat-description">Purchase history and receipts</div>
            </div>

            <div class="stat-card" onclick="navigateTo('{{ route('events.index') }}')">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                </div>
                <span class="stat-number" data-count="{{ $dashboardStats['upcoming_events'] }}">0</span>
                <div class="stat-label">Upcoming Events</div>
                <div class="stat-description">Events available for registration</div>
            </div>

            <div class="stat-card" onclick="navigateTo('{{ route('profile.show') }}')">
                <div class="stat-icon">
                    <i class="fas fa-user-circle" aria-hidden="true"></i>
                </div>
                <span class="stat-number">{{ $dashboardStats['completion_rate'] }}%</span>
                <div class="stat-label">Profile Complete</div>
                <div class="stat-description">Complete your profile setup</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-card slide-up">
            <h3 style="color: var(--dashboard-primary); font-weight: 700; font-size: 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem;">
                <i class="fas fa-bolt" aria-hidden="true"></i>
                Quick Actions
            </h3>

            <div class="quick-actions-grid">
                @foreach($quickActions as $action)
                <a href="{{ $action['url'] }}" class="quick-action">
                    <div class="quick-action-icon">
                        <i class="{{ $action['icon'] }}" aria-hidden="true"></i>
                    </div>
                    <div class="quick-action-content">
                        <h4>{{ $action['title'] }}</h4>
                        <p>{{ $action['description'] }}</p>
                    </div>
                </a>
                @endforeach
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Your Registered Events -->
                <div class="dashboard-card slide-up">
                    <h3 style="color: var(--dashboard-primary); font-weight: 700; font-size: 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fas fa-calendar-check" aria-hidden="true"></i>
                        Your Registered Events
                    </h3>

                    @if($userEvents->count() > 0)
                    @foreach($userEvents as $registration)
                    <div class="event-card">
                        <div class="event-header">
                            <div>
                                <h4 class="event-title">{{ $registration->event->title }}</h4>
                                <div class="event-meta">
                                    <span>
                                        <i class="fas fa-calendar" aria-hidden="true"></i>
                                        {{ $registration->event->start_date->format('M d, Y') }}
                                    </span>
                                    <span>
                                        <i class="fas fa-clock" aria-hidden="true"></i>
                                        {{ $registration->event->start_date->format('g:i A') }}
                                    </span>
                                    @if($registration->event->location)
                                    <span>
                                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                        {{ $registration->event->location }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <span class="event-badge badge-registered">Registered</span>
                        </div>
                        <p style="color: #6b7280; margin-bottom: 1rem; line-height: 1.5;">
                            {{ Str::limit(strip_tags($registration->event->description), 120) }}
                        </p>
                        <a href="{{ route('events.show', $registration->event->slug) }}"
                            style="color: var(--dashboard-primary); font-weight: 600; text-decoration: none;">
                            View Event Details →
                        </a>
                    </div>
                    @endforeach
                    @else
                    <div style="text-align: center; padding: 3rem; color: #6b7280;">
                        <i class="fas fa-calendar-plus" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <h4 style="color: var(--dashboard-primary); margin-bottom: 1rem;">No Events Registered</h4>
                        <p style="margin-bottom: 2rem;">You haven't registered for any events yet. Explore our upcoming events!</p>
                        <a href="{{ route('events.index') }}" class="quick-action-icon" style="display: inline-flex; text-decoration: none;">
                            <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                        </a>
                    </div>
                    @endif
                </div>

                <!-- Recent Activity -->
                <div class="dashboard-card slide-up">
                    <h3 style="color: var(--dashboard-primary); font-weight: 700; font-size: 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fas fa-history" aria-hidden="true"></i>
                        Recent Activity
                    </h3>

                    <div class="activity-timeline">
                        @forelse($recentActivity as $activity)
                        <div class="activity-item">
                            <div class="activity-content">
                                <h5 style="margin: 0 0 0.5rem 0; color: var(--dashboard-primary);">
                                    <i class="{{ $activity['icon'] }} me-2" aria-hidden="true"></i>
                                    {{ $activity['title'] }}
                                </h5>
                                <p style="margin: 0 0 0.5rem 0; color: #6b7280;">{{ $activity['description'] }}</p>
                                <small style="color: #9ca3af;">{{ $activity['date']->format('M d, Y g:i A') }}</small>
                            </div>
                        </div>
                        @empty
                        <div style="text-align: center; padding: 2rem; color: #6b7280;">
                            <i class="fas fa-clock" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p>No recent activity to show.</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Upcoming Events -->
                <div class="dashboard-card slide-up">
                    <h3 style="color: var(--dashboard-primary); font-weight: 700; font-size: 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                        Upcoming Events
                    </h3>

                    @forelse($upcomingEvents->take(4) as $event)
                    <div class="event-card">
                        <h5 style="margin: 0 0 0.5rem 0; color: var(--dashboard-primary); font-size: 1rem;">
                            {{ Str::limit($event->title, 40) }}
                        </h5>
                        <div class="event-meta" style="margin-bottom: 1rem;">
                            <span>
                                <i class="fas fa-calendar" aria-hidden="true"></i>
                                {{ $event->start_date->format('M d') }}
                            </span>
                            <span>
                                <i class="fas fa-clock" aria-hidden="true"></i>
                                {{ $event->start_date->format('g:i A') }}
                            </span>
                        </div>
                        <a href="{{ route('events.show', $event->slug) }}"
                            style="color: var(--dashboard-primary); font-weight: 600; text-decoration: none; font-size: 0.9rem;">
                            Learn More →
                        </a>
                    </div>
                    @empty
                    <div style="text-align: center; padding: 2rem; color: #6b7280;">
                        <i class="fas fa-calendar-times" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>No upcoming events scheduled.</p>
                    </div>
                    @endforelse

                    @if($upcomingEvents->count() > 4)
                    <div style="text-align: center; margin-top: 1rem;">
                        <a href="{{ route('events.index') }}"
                            style="color: var(--dashboard-primary); font-weight: 600; text-decoration: none;">
                            View All Events →
                        </a>
                    </div>
                    @endif
                </div>

                <!-- Featured Speakers -->
                <div class="dashboard-card slide-up">
                    <h3 style="color: var(--dashboard-primary); font-weight: 700; font-size: 1.5rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fas fa-users" aria-hidden="true"></i>
                        Featured Speakers
                    </h3>

                    @forelse($featuredSpeakers as $speaker)
                    <div class="speaker-card">
                        <div class="speaker-avatar">
                            <i class="fas fa-user" aria-hidden="true"></i>
                        </div>
                        <div class="speaker-name">{{ $speaker->name }}</div>
                        <div class="speaker-title">{{ $speaker->position }}</div>
                        @if($speaker->company)
                        <div class="speaker-company">{{ $speaker->company }}</div>
                        @endif
                    </div>
                    @empty
                    <div style="text-align: center; padding: 2rem; color: #6b7280;">
                        <i class="fas fa-user-friends" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>Featured speakers coming soon!</p>
                    </div>
                    @endforelse

                    <div style="text-align: center; margin-top: 1rem;">
                        <a href="{{ route('speakers.index') }}"
                            style="color: var(--dashboard-primary); font-weight: 600; text-decoration: none;">
                            View All Speakers →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    // Initialize dashboard
    document.addEventListener('DOMContentLoaded', function() {
        initializeAnimations();
        initializeCounters();
        initializeInteractions();

        console.log('🎯 Enhanced Dashboard Loaded');
    });

    // Initialize entrance animations
    function initializeAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all animated elements
        document.querySelectorAll('.slide-up, .scale-in').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'all 0.6s ease-out';
            observer.observe(el);
        });
    }

    // Initialize number counters
    function initializeCounters() {
        const counters = document.querySelectorAll('[data-count]');

        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-count'));
            const duration = 2000; // 2 seconds
            const increment = target / (duration / 16); // 60fps
            let current = 0;

            const updateCounter = () => {
                if (current < target) {
                    current += increment;
                    counter.textContent = Math.floor(current);
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.textContent = target;
                }
            };

            // Start counter when element is visible
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        setTimeout(updateCounter, 500); // Delay start
                        observer.unobserve(entry.target);
                    }
                });
            });

            observer.observe(counter);
        });
    }

    // Initialize interactive elements
    function initializeInteractions() {
        // Add hover effects to cards
        document.querySelectorAll('.stat-card, .dashboard-card, .event-card, .speaker-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                if (!this.style.transform.includes('translateY')) {
                    this.style.transform = 'translateY(-5px)';
                }
            });

            card.addEventListener('mouseleave', function() {
                if (this.style.transform.includes('translateY(-5px)')) {
                    this.style.transform = 'translateY(0)';
                }
            });
        });

        // Add click ripple effect to interactive elements
        document.querySelectorAll('.quick-action, .stat-card').forEach(element => {
            element.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;

                ripple.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    background: rgba(37, 99, 235, 0.3);
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple 0.6s ease-out;
                    pointer-events: none;
                `;

                this.style.position = 'relative';
                this.appendChild(ripple);

                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Add CSS for ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(2);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }

    // Navigation function
    function navigateTo(url) {
        // Add loading state
        document.body.style.cursor = 'wait';

        // Smooth transition
        setTimeout(() => {
            window.location.href = url;
        }, 200);
    }

    // Add loading states to links
    document.querySelectorAll('a[href]').forEach(link => {
        link.addEventListener('click', function() {
            if (this.href && !this.href.includes('#')) {
                const originalContent = this.innerHTML;
                this.style.opacity = '0.7';
                this.style.pointerEvents = 'none';

                // Reset after navigation (fallback)
                setTimeout(() => {
                    this.innerHTML = originalContent;
                    this.style.opacity = '1';
                    this.style.pointerEvents = 'auto';
                }, 3000);
            }
        });
    });

    // Add tooltips to stat cards
    document.querySelectorAll('.stat-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'stat-tooltip';
            tooltip.style.cssText = `
                position: absolute;
                bottom: 100%;
                left: 50%;
                transform: translateX(-50%);
                background: rgba(0, 0, 0, 0.8);
                color: white;
                padding: 0.5rem 1rem;
                border-radius: 0.5rem;
                font-size: 0.8rem;
                white-space: nowrap;
                z-index: 1000;
                opacity: 0;
                transition: opacity 0.3s ease;
                pointer-events: none;
            `;

            const description = this.querySelector('.stat-description').textContent;
            tooltip.textContent = description;

            this.style.position = 'relative';
            this.appendChild(tooltip);

            setTimeout(() => {
                tooltip.style.opacity = '1';
            }, 100);
        });

        card.addEventListener('mouseleave', function() {
            const tooltip = this.querySelector('.stat-tooltip');
            if (tooltip) {
                tooltip.remove();
            }
        });
    });

    // Performance optimization: Lazy load non-critical animations
    setTimeout(() => {
        // Add subtle background animations
        const cards = document.querySelectorAll('.dashboard-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
            card.classList.add('fade-in');
        });
    }, 1000);

    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + D for dashboard (already here)
        if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
            e.preventDefault();
            // Already on dashboard
        }

        // Ctrl/Cmd + E for events
        if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
            e.preventDefault();
            navigateTo('{{ route("events.index") }}');
        }

        // Ctrl/Cmd + P for profile
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            navigateTo('{{ route("profile.show") }}');
        }
    });

    // Add success message animation if present
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.transform = 'translateY(-20px)';
            alert.style.opacity = '0';

            setTimeout(() => {
                alert.style.transition = 'all 0.5s ease';
                alert.style.transform = 'translateY(0)';
                alert.style.opacity = '1';
            }, 100);
        });
    });

    // Add real-time updates (placeholder for future WebSocket integration)
    function updateDashboardStats() {
        // This would connect to WebSocket or poll for updates
        console.log('📊 Dashboard stats updated');
    }

    // Update stats every 5 minutes
    setInterval(updateDashboardStats, 300000);

    console.log('✨ Enhanced Dashboard Initialized');
    console.log('⌨️ Keyboard shortcuts: Ctrl+E (Events), Ctrl+P (Profile)');
</script>
@endpush