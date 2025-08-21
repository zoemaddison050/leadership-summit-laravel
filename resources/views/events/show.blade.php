@extends('layouts.app')

@section('title', ($event->title ?? 'Event Details') . ' - Leadership Summit 2025')
@section('meta_description', Str::limit(strip_tags($event->description ?? 'Event details for the Leadership Summit 2025'), 160))



@push('styles')
<style>
    .event-hero {
        background: linear-gradient(135deg, var(--primary-color) 0%, #1e3a8a 100%);
        color: white;
        padding: 4rem 0;
        position: relative;
        overflow: hidden;
    }

    .event-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("{{ isset($event) && $event->featured_image ? asset('storage/' . $event->featured_image) : asset('images/default-event-bg.jpg') }}") center/cover;
        opacity: 0.2;
        z-index: 1;
    }

    .event-hero-content {
        position: relative;
        z-index: 2;
    }

    .event-badge {
        display: inline-block;
        background: var(--secondary-color);
        color: var(--primary-color);
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 1rem;
    }

    .event-title {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        line-height: 1.2;
    }

    .event-meta {
        display: flex;
        gap: 2rem;
        flex-wrap: wrap;
        margin-bottom: 2rem;
        font-size: 1.1rem;
    }

    .event-meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .event-meta-item i {
        font-size: 1.2rem;
        color: var(--secondary-color);
    }

    .event-actions {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .event-content {
        padding: 4rem 0;
    }

    .event-sidebar {
        background: #f8fafc;
        border-radius: 1rem;
        padding: 2rem;
        height: fit-content;
        position: sticky;
        top: 100px;
    }

    .sidebar-section {
        margin-bottom: 2rem;
    }

    .sidebar-section:last-child {
        margin-bottom: 0;
    }

    .sidebar-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 1rem;
    }

    .ticket-option {
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .ticket-option:hover,
    .ticket-option.selected {
        border-color: var(--primary-color);
        box-shadow: 0 4px 15px rgba(10, 36, 99, 0.1);
    }

    .ticket-name {
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }

    .ticket-price {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }

    .ticket-price.free {
        color: #10b981;
    }

    .ticket-description {
        font-size: 0.9rem;
        color: var(--dark-gray);
        margin-bottom: 1rem;
    }

    .ticket-availability {
        font-size: 0.9rem;
        color: var(--dark-gray);
    }

    .ticket-availability.low {
        color: #f59e0b;
    }

    .ticket-availability.sold-out {
        color: #ef4444;
    }

    .event-description {
        font-size: 1.1rem;
        line-height: 1.8;
        color: var(--text-color);
    }

    .event-description h2,
    .event-description h3,
    .event-description h4 {
        color: var(--primary-color);
        margin-top: 2rem;
        margin-bottom: 1rem;
    }

    .event-description ul,
    .event-description ol {
        margin-bottom: 1.5rem;
    }

    .event-description li {
        margin-bottom: 0.5rem;
    }

    .speakers-section {
        background: #f8fafc;
        padding: 4rem 0;
        margin-top: 3rem;
    }

    .speakers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
    }

    .speaker-card {
        background: white;
        border-radius: 1rem;
        padding: 2rem;
        text-align: center;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s ease;
    }

    .speaker-card:hover {
        transform: translateY(-5px);
    }

    .speaker-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        color: white;
        font-size: 2rem;
    }

    .speaker-name {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }

    .speaker-title {
        color: var(--dark-gray);
        margin-bottom: 1rem;
    }

    .related-events {
        padding: 4rem 0;
        background: white;
    }

    .related-events-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
    }

    .related-event-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 1rem;
        padding: 1.5rem;
        transition: all 0.3s ease;
    }

    .related-event-card:hover {
        border-color: var(--primary-color);
        box-shadow: 0 4px 15px rgba(10, 36, 99, 0.1);
    }

    .registration-highlight {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 1rem;
        border-radius: 0.75rem;
        margin-bottom: 1.5rem;
        text-align: center;
    }

    .registration-highlight h4 {
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    .registration-highlight p {
        margin-bottom: 0;
        opacity: 0.9;
    }

    .quick-registration-badge {
        display: inline-block;
        background: #10b981;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-left: 0.5rem;
    }

    @media (max-width: 768px) {
        .event-title {
            font-size: 2.5rem;
        }

        .event-meta {
            flex-direction: column;
            gap: 1rem;
        }

        .event-actions {
            justify-content: center;
        }

        .event-sidebar {
            position: static;
            margin-top: 2rem;
        }

        .speakers-grid {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }
    }
</style>
@endpush

@section('content')
<!-- Event Hero -->
<section class="event-hero">
    <div class="container">
        <div class="event-hero-content">
            @if(isset($event) && $event->status === 'featured')
            <span class="event-badge">Featured Event</span>
            @endif

            <h1 class="event-title">{{ $event->title ?? 'Leadership Excellence Summit' }}</h1>

            <div class="event-meta">
                <div class="event-meta-item">
                    <i class="fas fa-calendar" aria-hidden="true"></i>
                    <span>
                        @if(isset($event))
                        {{ $event->start_date->format('F d, Y') }}
                        @if($event->end_date && $event->end_date != $event->start_date)
                        - {{ $event->end_date->format('F d, Y') }}
                        @endif
                        @else
                        September 15-17, 2025
                        @endif
                    </span>
                </div>
                <div class="event-meta-item">
                    <i class="fas fa-clock" aria-hidden="true"></i>
                    <span>
                        @if(isset($event))
                        {{ $event->start_date->format('g:i A') }}
                        @if($event->end_date && $event->end_date->format('Y-m-d') == $event->start_date->format('Y-m-d'))
                        - {{ $event->end_date->format('g:i A') }}
                        @endif
                        @else
                        9:00 AM - 5:00 PM
                        @endif
                    </span>
                </div>
                <div class="event-meta-item">
                    <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                    <span>{{ $event->location ?? 'Cypress International Conference Center' }}</span>
                </div>
            </div>

            <div class="event-actions">
                <a href="{{ route('events.register', $event) }}" class="btn btn-secondary btn-lg">
                    <i class="fas fa-bolt me-2" aria-hidden="true"></i>Quick Register - No Account Required
                </a>
                <a href="#tickets" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-info-circle me-2" aria-hidden="true"></i>View Ticket Options
                </a>
                <button class="btn btn-outline-light btn-lg" id="share-event-btn" data-title="{{ $event->title ?? 'Leadership Summit Event' }}" data-url="{{ url()->current() }}">
                    <i class="fas fa-share-alt me-2" aria-hidden="true"></i>Share Event
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Event Content -->
<section class="event-content">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="event-description">
                    @if(isset($event) && $event->description)
                    {!! nl2br(e($event->description)) !!}
                    @else
                    <h2>About This Event</h2>
                    <p>Join us for the flagship event of the International Global Leadership Academy Summit 2025. This comprehensive three-day experience brings together the world's most influential leaders, innovators, and visionaries to explore the future of leadership in an ever-changing global landscape.</p>

                    <h3>What You'll Experience</h3>
                    <ul>
                        <li><strong>Inspiring Keynote Presentations</strong> - Learn from industry titans and thought leaders who are shaping the future of business and society</li>
                        <li><strong>Interactive Workshops</strong> - Participate in hands-on sessions designed to develop practical leadership skills</li>
                        <li><strong>Panel Discussions</strong> - Engage with diverse perspectives on critical leadership challenges</li>
                        <li><strong>Networking Opportunities</strong> - Connect with peers, mentors, and potential collaborators from around the world</li>
                        <li><strong>Innovation Showcases</strong> - Discover cutting-edge technologies and methodologies transforming leadership</li>
                    </ul>

                    <h3>Who Should Attend</h3>
                    <p>This summit is designed for current and aspiring leaders across all industries and sectors, including:</p>
                    <ul>
                        <li>C-suite executives and senior managers</li>
                        <li>Entrepreneurs and business owners</li>
                        <li>Non-profit and community leaders</li>
                        <li>Government and public sector officials</li>
                        <li>Emerging leaders and high-potential professionals</li>
                    </ul>

                    <h3>Key Topics</h3>
                    <ul>
                        <li>Digital transformation and technology leadership</li>
                        <li>Sustainable business practices and ESG leadership</li>
                        <li>Diversity, equity, and inclusion in leadership</li>
                        <li>Crisis management and resilient leadership</li>
                        <li>Global perspectives on leadership challenges</li>
                        <li>The future of work and remote team leadership</li>
                    </ul>
                    @endif
                </div>
            </div>

            <div class="col-lg-4">
                <div class="event-sidebar" id="tickets">
                    <div class="registration-highlight">
                        <h4><i class="fas fa-bolt me-2"></i>Quick Registration</h4>
                        <p>Register in minutes - no account creation required!</p>
                    </div>

                    <div class="sidebar-section">
                        <h3 class="sidebar-title">Registration Options</h3>

                        @if(isset($event) && $event->tickets && $event->tickets->count() > 0)
                        @foreach($event->tickets as $ticket)
                        <div class="ticket-option" data-ticket-id="{{ $ticket->id }}">
                            <div class="ticket-name">{{ $ticket->name }}</div>
                            <div class="ticket-price {{ $ticket->price == 0 ? 'free' : '' }}">
                                @if($ticket->price == 0)
                                Free
                                @else
                                ${{ number_format($ticket->price, 2) }}
                                @endif
                            </div>
                            @if($ticket->description)
                            <div class="ticket-description">{{ $ticket->description }}</div>
                            @endif
                            <div class="ticket-availability {{ $ticket->available <= 10 ? 'low' : '' }} {{ $ticket->available == 0 ? 'sold-out' : '' }}">
                                @if($ticket->available == 0)
                                Sold Out
                                @elseif($ticket->available <= 10)
                                    Only {{ $ticket->available }} left
                                    @else
                                    {{ $ticket->available }} available
                                    @endif
                                    </div>
                            </div>
                            @endforeach
                            @else
                            <!-- Placeholder tickets -->
                            <div class="ticket-option" data-ticket-id="1">
                                <div class="ticket-name">General Admission</div>
                                <div class="ticket-price">$299.00</div>
                                <div class="ticket-description">Full access to all sessions, workshops, and networking events</div>
                                <div class="ticket-availability">150 available</div>
                            </div>

                            <div class="ticket-option" data-ticket-id="2">
                                <div class="ticket-name">VIP Experience</div>
                                <div class="ticket-price">$599.00</div>
                                <div class="ticket-description">Premium seating, exclusive networking events, and meet & greet opportunities</div>
                                <div class="ticket-availability low">Only 25 available</div>
                            </div>

                            <div class="ticket-option" data-ticket-id="3">
                                <div class="ticket-name">Student/Non-Profit</div>
                                <div class="ticket-price">$99.00</div>
                                <div class="ticket-description">Discounted rate for students and non-profit organization members</div>
                                <div class="ticket-availability">50 available</div>
                            </div>
                            @endif

                            <a href="{{ route('events.register', $event) }}" class="btn btn-secondary w-100 mt-3" id="registerBtn" style="opacity: 0.5; pointer-events: none; cursor: not-allowed;">
                                <i class="fas fa-ticket-alt me-2" aria-hidden="true"></i>Start Registration
                            </a>
                            <p class="text-center mt-2 mb-0 small text-muted" id="selectionHint">
                                <i class="fas fa-arrow-up me-1"></i>
                                Please select a ticket option above
                            </p>
                            <p class="text-center mt-2 mb-0 small text-muted">
                                <i class="fas fa-check-circle me-1 text-success"></i>
                                No account creation required
                            </p>
                        </div>

                        <div class="sidebar-section">
                            <h3 class="sidebar-title">Event Details</h3>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-users me-2 text-primary" aria-hidden="true"></i>
                                <span>Expected Attendance: 500+</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-language me-2 text-primary" aria-hidden="true"></i>
                                <span>Language: English</span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-certificate me-2 text-primary" aria-hidden="true"></i>
                                <span>CPE Credits Available</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-wifi me-2 text-primary" aria-hidden="true"></i>
                                <span>Free WiFi Included</span>
                            </div>
                        </div>

                        <div class="sidebar-section">
                            <h3 class="sidebar-title">Contact</h3>
                            <p class="mb-2">
                                <i class="fas fa-envelope me-2 text-primary" aria-hidden="true"></i>
                                <a href="mailto:events@leadershipacademy.org">events@leadershipacademy.org</a>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-phone me-2 text-primary" aria-hidden="true"></i>
                                <a href="tel:+15551234567">+1 (555) 123-4567</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</section>

<!-- Speakers Section -->
@if(isset($event) && $event->sessions && $event->sessions->count() > 0)
<section class="speakers-section">
    <div class="container">
        <h2 class="section-title text-center mb-4">Featured Speakers</h2>
        <div class="speakers-grid">
            @foreach($event->sessions->take(6) as $session)
            @foreach($session->speakers as $speaker)
            <div class="speaker-card">
                <div class="speaker-avatar">
                    @if($speaker->photo)
                    <img src="{{ asset('storage/' . $speaker->photo) }}" alt="{{ $speaker->name }}" class="w-100 h-100 rounded-circle" style="object-fit: cover;">
                    @else
                    <i class="fas fa-user" aria-hidden="true"></i>
                    @endif
                </div>
                <h3 class="speaker-name">{{ $speaker->name }}</h3>
                <p class="speaker-title">{{ $speaker->position }}@if($speaker->company), {{ $speaker->company }}@endif</p>
                <a href="{{ url('/speakers/' . $speaker->id) }}" class="btn btn-outline-primary btn-sm">
                    View Profile
                </a>
            </div>
            @endforeach
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Related Events -->
<section class="related-events">
    <div class="container">
        <h2 class="section-title text-center mb-4">You Might Also Like</h2>
        <div class="related-events-grid">
            <!-- Placeholder related events -->
            <div class="related-event-card">
                <h3 class="h5 mb-2">Executive Leadership Workshop</h3>
                <p class="text-muted mb-2">
                    <i class="fas fa-calendar me-1" aria-hidden="true"></i>
                    September 19, 2025
                </p>
                <p class="mb-3">Intensive one-day workshop for senior executives focusing on strategic leadership.</p>
                <a href="#" class="btn btn-outline-primary btn-sm">Learn More</a>
            </div>

            <div class="related-event-card">
                <h3 class="h5 mb-2">Innovation Leadership Masterclass</h3>
                <p class="text-muted mb-2">
                    <i class="fas fa-calendar me-1" aria-hidden="true"></i>
                    October 5, 2025
                </p>
                <p class="mb-3">Master the art of leading innovation and driving organizational transformation.</p>
                <a href="#" class="btn btn-outline-primary btn-sm">Learn More</a>
            </div>

            <div class="related-event-card">
                <h3 class="h5 mb-2">Women in Leadership Summit</h3>
                <p class="text-muted mb-2">
                    <i class="fas fa-calendar me-1" aria-hidden="true"></i>
                    October 20, 2025
                </p>
                <p class="mb-3">Empowering women leaders to break barriers and drive meaningful change.</p>
                <a href="#" class="btn btn-outline-primary btn-sm">Learn More</a>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ticket selection functionality
        const ticketOptions = document.querySelectorAll('.ticket-option');
        const registerBtn = document.getElementById('registerBtn');
        let selectedTicketId = null;

        ticketOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Remove previous selection
                ticketOptions.forEach(opt => opt.classList.remove('selected'));

                // Add selection to clicked option
                this.classList.add('selected');

                // Store selected ticket ID
                selectedTicketId = this.dataset.ticketId;

                // Update register button text to show selection
                const ticketName = this.querySelector('.ticket-name').textContent;
                registerBtn.innerHTML = `<i class="fas fa-ticket-alt me-2" aria-hidden="true"></i>Start Registration`;

                // Enable the button and change to primary color
                registerBtn.classList.remove('btn-secondary');
                registerBtn.classList.add('btn-primary');
                registerBtn.style.opacity = '1';
                registerBtn.style.pointerEvents = 'auto';
                registerBtn.style.cursor = 'pointer';

                // Hide the selection hint
                const selectionHint = document.getElementById('selectionHint');
                if (selectionHint) {
                    selectionHint.style.display = 'none';
                }
            });
        });

        // Handle register button click
        registerBtn.addEventListener('click', function(e) {
            if (selectedTicketId) {
                // Add selected ticket as URL parameter
                const currentHref = this.getAttribute('href');
                const separator = currentHref.includes('?') ? '&' : '?';
                this.setAttribute('href', currentHref + separator + 'ticket=' + selectedTicketId);
            }
        });

        // Share event button click
        const shareBtn = document.getElementById('share-event-btn');
        if (shareBtn) {
            shareBtn.addEventListener('click', function() {
                const title = this.dataset.title;
                const url = this.dataset.url;
                shareEvent(title, url);
            });
        }
    });

    // Share event function
    function shareEvent(title, url) {
        if (navigator.share) {
            navigator.share({
                title: title,
                url: url
            }).catch(console.error);
        } else {
            // Fallback: copy to clipboard
            navigator.clipboard.writeText(url).then(() => {
                alert('Event link copied to clipboard!');
            }).catch(() => {
                // Final fallback: show URL in prompt
                prompt('Copy this link to share:', url);
            });
        }
    }
</script>
@endpush