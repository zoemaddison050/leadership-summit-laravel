@extends('layouts.app')

@section('title', 'Speakers - Leadership Summit 2025')
@section('meta_description', 'Meet our world-class speakers at the International Global Leadership Academy Summit 2025. Industry leaders, innovators, and visionaries.')

@section('breadcrumbs')
<li class="breadcrumb-item active" aria-current="page">Speakers</li>
@endsection

@push('styles')
<style>
    .speakers-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, #1e3a8a 100%);
        color: white;
        padding: 4rem 0 3rem;
        text-align: center;
    }

    .speakers-header h1 {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .speakers-header p {
        font-size: 1.2rem;
        opacity: 0.9;
        max-width: 600px;
        margin: 0 auto;
    }

    .speakers-filters {
        background: white;
        padding: 2rem 0;
        border-bottom: 1px solid #e5e7eb;
        position: sticky;
        top: 70px;
        z-index: 100;
    }

    .filter-tabs {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
    }

    .filter-tab {
        padding: 0.75rem 1.5rem;
        border: 2px solid var(--primary-color);
        background: white;
        color: var(--primary-color);
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .filter-tab:hover,
    .filter-tab.active {
        background: var(--primary-color);
        color: white;
    }

    .search-bar {
        max-width: 400px;
        margin: 0 auto;
    }

    .speakers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 2rem;
        padding: 3rem 0;
        transition: opacity 0.3s ease, transform 0.3s ease;
    }

    /* Search transition styles */
    .search-transition-enter {
        opacity: 0;
        transform: translateY(20px);
    }

    .search-transition-enter-active {
        opacity: 1;
        transform: translateY(0);
        transition: opacity 0.3s ease, transform 0.3s ease;
    }

    .search-transition-exit {
        opacity: 1;
        transform: translateY(0);
    }

    .search-transition-exit-active {
        opacity: 0;
        transform: translateY(-20px);
        transition: opacity 0.3s ease, transform 0.3s ease;
    }

    /* Clear button transitions */
    .clear-btn-hidden {
        opacity: 0;
        transform: scale(0.8);
        transition: opacity 0.2s ease, transform 0.2s ease;
    }

    .clear-btn-visible {
        opacity: 1;
        transform: scale(1);
        transition: opacity 0.2s ease, transform 0.2s ease;
    }

    /* Search results container styling */
    #speakerSearchResults {
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    #speakerSearchResults.visible {
        opacity: 1;
    }

    #defaultSpeakersGrid {
        opacity: 1;
        transition: opacity 0.3s ease;
    }

    #defaultSpeakersGrid.hidden {
        opacity: 0;
    }

    .speaker-card {
        background: white;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border: 1px solid #f1f5f9;
        text-align: center;
    }

    .speaker-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }

    .speaker-image {
        height: 250px;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 4rem;
        position: relative;
    }

    .speaker-badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: var(--secondary-color);
        color: var(--primary-color);
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .speaker-content {
        padding: 2rem;
    }

    .speaker-name {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: var(--primary-color);
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

    .speaker-bio {
        color: var(--dark-gray);
        margin-bottom: 1.5rem;
        line-height: 1.6;
        font-size: 0.95rem;
    }

    .speaker-topics {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        justify-content: center;
    }

    .topic-tag {
        background: #f1f5f9;
        color: var(--primary-color);
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .speaker-social {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .social-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background: var(--primary-color);
        color: white;
        border-radius: 50%;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .social-link:hover {
        background: var(--secondary-color);
        color: var(--primary-color);
        transform: translateY(-2px);
    }

    .featured-speakers {
        background: #f8fafc;
        padding: 4rem 0;
    }

    .featured-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }

    .featured-speaker-card {
        background: white;
        border-radius: 1rem;
        padding: 2rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        display: flex;
        gap: 2rem;
        align-items: center;
    }

    .featured-speaker-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 3rem;
        flex-shrink: 0;
    }

    .featured-speaker-info h3 {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }

    .featured-speaker-info .speaker-title {
        margin-bottom: 1rem;
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

    .no-speakers {
        text-align: center;
        padding: 4rem 0;
        color: var(--dark-gray);
        background: #f8fafc;
        border-radius: 1rem;
        margin: 2rem 0;
        border: 2px dashed #e2e8f0;
    }

    .no-speakers i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
        color: var(--primary-color);
    }

    .no-speakers h3 {
        color: var(--primary-color);
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .no-speakers p {
        color: var(--dark-gray);
        font-size: 1rem;
        line-height: 1.6;
        margin-bottom: 2rem;
        max-width: 500px;
        margin-left: auto;
        margin-right: auto;
    }

    .search-error {
        text-align: center;
        padding: 3rem 0;
        color: #dc2626;
        background: #fef2f2;
        border-radius: 1rem;
        margin: 2rem 0;
        border: 2px solid #fecaca;
    }

    .search-error i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.7;
    }

    .search-error h3 {
        color: #dc2626;
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .search-error p {
        color: #7f1d1d;
        font-size: 0.95rem;
        line-height: 1.5;
        margin-bottom: 1.5rem;
    }

    @media (max-width: 768px) {
        .speakers-header h1 {
            font-size: 2.5rem;
        }

        .speakers-grid {
            grid-template-columns: 1fr;
            padding: 2rem 0;
        }

        .filter-tabs {
            justify-content: flex-start;
            overflow-x: auto;
            padding-bottom: 0.5rem;
        }

        .speakers-filters {
            position: static;
        }

        .featured-speaker-card {
            flex-direction: column;
            text-align: center;
        }

        .featured-speaker-avatar {
            width: 100px;
            height: 100px;
            font-size: 2.5rem;
        }
    }
</style>
@endpush

@section('content')
<!-- Speakers Header -->
<section class="speakers-header">
    <div class="container">
        <h1>Our Speakers</h1>
        <p>Meet the world-class leaders, innovators, and visionaries who will share their insights at the summit</p>
    </div>
</section>

<!-- Featured Speakers -->
<section class="featured-speakers">
    <div class="container">
        <h2 class="section-title text-center mb-4">Keynote Speakers</h2>
        <div class="featured-grid">
            @if(isset($featuredSpeakers) && $featuredSpeakers->count() > 0)
            @foreach($featuredSpeakers as $speaker)
            <div class="featured-speaker-card">
                <div class="featured-speaker-avatar">
                    @if($speaker->photo)
                    <img src="{{ asset('storage/' . $speaker->photo) }}" alt="{{ $speaker->name }}"
                        class="w-100 h-100 rounded-circle" style="object-fit: cover;">
                    @else
                    <i class="fas fa-user" aria-hidden="true"></i>
                    @endif
                </div>
                <div class="featured-speaker-info">
                    <h3>{{ $speaker->name }}</h3>
                    <p class="speaker-title">{{ $speaker->position }}</p>
                    @if($speaker->company)
                    <p class="speaker-company">{{ $speaker->company }}</p>
                    @endif
                    <p class="speaker-bio">{{ Str::limit($speaker->bio, 120) }}</p>
                    <a href="{{ url('/speakers/' . $speaker->id) }}" class="btn btn-primary">
                        View Profile
                    </a>
                </div>
            </div>
            @endforeach
            @else
            <!-- Placeholder featured speakers -->
            <div class="featured-speaker-card">
                <div class="featured-speaker-avatar">
                    <i class="fas fa-user" aria-hidden="true"></i>
                </div>
                <div class="featured-speaker-info">
                    <h3>Dr. Sarah Johnson</h3>
                    <p class="speaker-title">Chief Executive Officer</p>
                    <p class="speaker-company">Global Innovation Corp</p>
                    <p class="speaker-bio">Renowned leadership expert with over 20 years of experience in transforming organizations and driving innovation across multiple industries.</p>
                    <a href="#" class="btn btn-primary">View Profile</a>
                </div>
            </div>

            <div class="featured-speaker-card">
                <div class="featured-speaker-avatar">
                    <i class="fas fa-user" aria-hidden="true"></i>
                </div>
                <div class="featured-speaker-info">
                    <h3>Michael Chen</h3>
                    <p class="speaker-title">Founder & Chairman</p>
                    <p class="speaker-company">TechVision Enterprises</p>
                    <p class="speaker-bio">Visionary entrepreneur and thought leader in digital transformation, helping organizations navigate the complexities of the modern business landscape.</p>
                    <a href="#" class="btn btn-primary">View Profile</a>
                </div>
            </div>
            @endif
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-number">50+</span>
                <span class="stat-label">Expert Speakers</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">25+</span>
                <span class="stat-label">Countries Represented</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">15+</span>
                <span class="stat-label">Industries Covered</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">100%</span>
                <span class="stat-label">Industry Leaders</span>
            </div>
        </div>
    </div>
</section>

<!-- Speakers Filters -->
<section class="speakers-filters">
    <div class="container">
        <div class="filter-tabs">
            <a href="{{ url('/speakers') }}" class="filter-tab {{ !request('category') ? 'active' : '' }}">
                All Speakers
            </a>
            <a href="{{ url('/speakers?category=ceo') }}" class="filter-tab {{ request('category') == 'ceo' ? 'active' : '' }}">
                CEOs & Executives
            </a>
            <a href="{{ url('/speakers?category=entrepreneur') }}" class="filter-tab {{ request('category') == 'entrepreneur' ? 'active' : '' }}">
                Entrepreneurs
            </a>
            <a href="{{ url('/speakers?category=academic') }}" class="filter-tab {{ request('category') == 'academic' ? 'active' : '' }}">
                Academics
            </a>
            <a href="{{ url('/speakers?category=consultant') }}" class="filter-tab {{ request('category') == 'consultant' ? 'active' : '' }}">
                Consultants
            </a>
        </div>

        <div class="search-bar">
            <div class="input-group">
                <input type="text" id="speakerSearchInput" class="form-control" placeholder="Search speakers..."
                    aria-label="Search speakers">
                <button class="btn btn-primary" type="button" onclick="searchSpeakers()" aria-label="Search">
                    <i class="fas fa-search" aria-hidden="true"></i>
                </button>
                <button class="btn btn-outline-secondary" type="button" onclick="clearSpeakerSearch()"
                    title="Clear search" id="clearSpeakerBtn" style="display: none;">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </div>
</section>

<!-- All Speakers Grid -->
<section class="speakers-content">
    <div class="container">
        <!-- Search Results Container -->
        <div id="speakerSearchResults" style="display: none;">
            <div class="speakers-grid" id="searchSpeakersGrid">
                <!-- Search results will be populated here -->
            </div>
        </div>

        <!-- Default Speakers Container -->
        @if(isset($speakers) && $speakers->count() > 0)
        <div id="defaultSpeakersGrid">
            <div class="speakers-grid">
                @foreach($speakers as $speaker)
                <article class="speaker-card">
                    <div class="speaker-image">
                        @if($speaker->photo)
                        <img src="{{ asset('storage/' . $speaker->photo) }}" alt="{{ $speaker->name }}"
                            class="w-100 h-100" style="object-fit: cover;">
                        @else
                        <i class="fas fa-user" aria-hidden="true"></i>
                        @endif

                        @if($speaker->featured ?? false)
                        <span class="speaker-badge">Keynote</span>
                        @endif
                    </div>

                    <div class="speaker-content">
                        <h2 class="speaker-name">{{ $speaker->name }}</h2>
                        <p class="speaker-title">{{ $speaker->position }}</p>
                        @if($speaker->company)
                        <p class="speaker-company">{{ $speaker->company }}</p>
                        @endif

                        <p class="speaker-bio">{{ Str::limit(strip_tags($speaker->bio), 100) }}</p>

                        @if(isset($speaker->topics))
                        <div class="speaker-topics">
                            @foreach(explode(',', $speaker->topics) as $topic)
                            <span class="topic-tag">{{ trim($topic) }}</span>
                            @endforeach
                        </div>
                        @endif

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

                        <a href="{{ url('/speakers/' . $speaker->id) }}" class="btn btn-primary">
                            View Full Profile
                        </a>
                    </div>
                </article>
                @endforeach
            </div>

            @if(method_exists($speakers, 'links'))
            <div class="d-flex justify-content-center mt-4">
                {{ $speakers->links() }}
            </div>
            @endif
        </div>
        @else
        <!-- Placeholder speakers when no data is available -->
        <div id="defaultSpeakersGrid">
            <div class="speakers-grid">
                @for($i = 1; $i <= 6; $i++)
                    <article class="speaker-card">
                    <div class="speaker-image">
                        <i class="fas fa-user" aria-hidden="true"></i>
                        @if($i <= 2)
                            <span class="speaker-badge">Keynote</span>
                            @endif
                    </div>
                    <div class="speaker-content">
                        <h2 class="speaker-name">Speaker {{ $i }}</h2>
                        <p class="speaker-title">{{ ['CEO', 'Founder', 'Director', 'President', 'VP'][($i-1) % 5] }}</p>
                        <p class="speaker-company">{{ ['Innovation Corp', 'Tech Solutions', 'Global Ventures', 'Future Systems', 'Leadership Inc'][($i-1) % 5] }}</p>
                        <p class="speaker-bio">Experienced leader with expertise in organizational transformation and strategic innovation.</p>
                        <div class="speaker-topics">
                            <span class="topic-tag">Leadership</span>
                            <span class="topic-tag">Innovation</span>
                        </div>
                        <div class="speaker-social">
                            <a href="#" class="social-link" aria-label="LinkedIn">
                                <i class="fab fa-linkedin-in" aria-hidden="true"></i>
                            </a>
                            <a href="#" class="social-link" aria-label="Twitter">
                                <i class="fab fa-twitter" aria-hidden="true"></i>
                            </a>
                        </div>
                        <a href="#" class="btn btn-primary">View Full Profile</a>
                    </div>
                    </article>
                    @endfor
            </div>
        </div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
    // Sample speakers data array with all necessary fields
    const speakersData = [{
            id: 1,
            name: "Dr. Sarah Johnson",
            position: "Chief Executive Officer",
            company: "Global Innovation Corp",
            bio: "Renowned leadership expert with over 20 years of experience in transforming organizations and driving innovation across multiple industries. She has led digital transformation initiatives at Fortune 500 companies and is a sought-after speaker on leadership and organizational change.",
            topics: ["Leadership", "Innovation", "Strategy", "Digital Transformation", "Organizational Change"],
            featured: true,
            linkedin: "https://linkedin.com/in/sarahjohnson",
            twitter: "https://twitter.com/sarahjohnson",
            website: "https://sarahjohnson.com"
        },
        {
            id: 2,
            name: "Michael Chen",
            position: "Founder & Chairman",
            company: "TechVision Enterprises",
            bio: "Visionary entrepreneur and thought leader in digital transformation, helping organizations navigate the complexities of the modern business landscape. Michael has founded three successful tech companies and mentors startup founders worldwide.",
            topics: ["Digital Transformation", "Entrepreneurship", "Technology", "Startup Leadership", "Innovation"],
            featured: true,
            linkedin: "https://linkedin.com/in/michaelchen",
            twitter: "https://twitter.com/michaelchen",
            website: "https://michaelchen.tech"
        },
        {
            id: 3,
            name: "Lisa Rodriguez",
            position: "VP of Human Resources",
            company: "Global Dynamics Corp",
            bio: "HR leader with expertise in talent development, employee engagement, and building high-performance teams. Lisa has transformed HR practices at multiple organizations and is passionate about creating inclusive workplace cultures.",
            topics: ["Talent Development", "Team Building", "Employee Engagement", "HR Leadership", "Workplace Culture"],
            featured: false,
            linkedin: "https://linkedin.com/in/lisarodriguez",
            twitter: "https://twitter.com/lisarodriguez"
        },
        {
            id: 4,
            name: "James Wilson",
            position: "Former CEO",
            company: "Fortune 500 Company",
            bio: "Veteran executive with 25+ years of leadership experience, including navigating companies through major crises and transformations. James has served on multiple boards and is known for his strategic thinking and crisis management expertise.",
            topics: ["Crisis Leadership", "Strategic Planning", "Change Management", "Board Governance", "Executive Leadership"],
            featured: false,
            linkedin: "https://linkedin.com/in/jameswilson",
            website: "https://jameswilson.leadership"
        },
        {
            id: 5,
            name: "Dr. Maria Santos",
            position: "Sustainability Director",
            company: "EcoLeadership Institute",
            bio: "Pioneer in sustainable business practices and environmental leadership with a focus on long-term organizational success. Maria has helped over 100 companies implement sustainable practices while maintaining profitability.",
            topics: ["Sustainable Leadership", "Environmental Strategy", "Corporate Responsibility", "ESG", "Green Innovation"],
            featured: false,
            linkedin: "https://linkedin.com/in/mariasantos",
            twitter: "https://twitter.com/mariasantos"
        },
        {
            id: 6,
            name: "Dr. Jennifer Lee",
            position: "Communication Expert",
            company: "Leadership Communication Institute",
            bio: "Expert in leadership communication and organizational psychology with extensive research in effective communication strategies. Jennifer has published numerous papers on leadership communication and trains executives worldwide.",
            topics: ["Communication", "Leadership Psychology", "Organizational Behavior", "Executive Coaching", "Public Speaking"],
            featured: false,
            linkedin: "https://linkedin.com/in/jenniferlee",
            website: "https://jenniferlee.com"
        },
        {
            id: 7,
            name: "Robert Kim",
            position: "Chief Technology Officer",
            company: "Innovation Labs",
            bio: "Technology leader with deep expertise in AI, machine learning, and emerging technologies. Robert has led technology teams at major tech companies and is passionate about the intersection of technology and leadership.",
            topics: ["Technology Leadership", "AI", "Machine Learning", "Innovation", "Digital Strategy"],
            featured: false,
            linkedin: "https://linkedin.com/in/robertkim",
            twitter: "https://twitter.com/robertkim"
        },
        {
            id: 8,
            name: "Amanda Thompson",
            position: "Executive Coach",
            company: "Leadership Excellence Group",
            bio: "Executive coach and leadership development expert who has worked with C-suite executives across various industries. Amanda specializes in developing authentic leadership styles and building high-performing teams.",
            topics: ["Executive Coaching", "Leadership Development", "Authentic Leadership", "Team Performance", "Executive Presence"],
            featured: false,
            linkedin: "https://linkedin.com/in/amandathompson",
            website: "https://amandathompson.coaching"
        }
    ];

    /**
     * Main search function that filters speakers across multiple fields with smooth transitions
     * Implements requirements 2.1, 2.2, 3.2, 3.3, 4.2, 4.3, 4.4
     */
    function searchSpeakers() {
        console.log('🔍 [Speakers Search] Function called');
        console.time('searchSpeakers');

        try {
            // Get DOM elements with error handling
            const searchInput = document.getElementById('speakerSearchInput');
            const searchResults = document.getElementById('speakerSearchResults');
            const searchGrid = document.getElementById('searchSpeakersGrid');
            const clearBtn = document.getElementById('clearSpeakerBtn');
            const defaultSpeakersContainer = document.getElementById('defaultSpeakersGrid');

            // Element existence checks
            if (!searchInput) {
                console.error('❌ [Speakers Search] Search input not found');
                return;
            }

            if (!searchResults || !searchGrid || !defaultSpeakersContainer) {
                console.error('❌ [Speakers Search] Required DOM elements not found for speaker search');
                return;
            }

            // Get and process search term with validation
            let searchTerm = searchInput.value.trim();

            // Handle edge cases and validate input
            if (searchTerm.length > 100) {
                console.warn('⚠️ [Speakers Search] Search term too long, truncating');
                searchTerm = searchTerm.substring(0, 100);
                searchInput.value = searchTerm; // Update input to show truncated value
            }

            // Convert to lowercase for case-insensitive search
            const normalizedSearchTerm = searchTerm.toLowerCase();
            console.log('🔍 [Speakers Search] Search term:', `"${searchTerm}" (normalized: "${normalizedSearchTerm}")`);

            // Handle empty search - show default content with smooth transition
            if (normalizedSearchTerm === '' || normalizedSearchTerm.length < 1) {
                console.log('🔄 [Speakers Search] Empty or invalid search - transitioning to default content');
                hideSpeakerSearchResults(searchResults, defaultSpeakersContainer, clearBtn);
                console.timeEnd('searchSpeakers');
                return;
            }

            // Handle special characters and potential regex issues
            let safeSearchTerm;
            try {
                // Escape special regex characters for safe searching
                safeSearchTerm = normalizedSearchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            } catch (regexError) {
                console.warn('⚠️ [Speakers Search] Regex escape failed, using original term:', regexError);
                safeSearchTerm = normalizedSearchTerm;
            }

            // Filter speakers across multiple fields (case-insensitive, partial matches)
            const filteredSpeakers = speakersData.filter(speaker => {
                try {
                    const searchFields = [
                        speaker.name,
                        speaker.position,
                        speaker.company,
                        speaker.bio,
                        ...(speaker.topics || [])
                    ];

                    const matches = searchFields.some(field =>
                        field && field.toLowerCase().includes(normalizedSearchTerm)
                    );

                    if (matches) {
                        console.log(`✅ [Speakers Search] Match found: ${speaker.name}`);
                    }

                    return matches;
                } catch (filterError) {
                    console.warn('⚠️ [Speakers Search] Error filtering speaker:', speaker.name, filterError);
                    return false; // Exclude speakers that cause filtering errors
                }
            });

            console.log(`✅ [Speakers Search] Filtered ${filteredSpeakers.length} speakers from ${speakersData.length} total`);

            // Generate HTML for search results
            let speakersHTML = '';
            if (filteredSpeakers.length > 0) {
                filteredSpeakers.forEach((speaker, index) => {
                    console.log(`📝 [Speakers Search] Rendering speaker ${index + 1}: "${speaker.name}"`);

                    const topicTags = speaker.topics.map(topic =>
                        `<span class="topic-tag">${topic}</span>`
                    ).join('');

                    const socialLinks = [];
                    if (speaker.linkedin) {
                        socialLinks.push(`<a href="${speaker.linkedin}" class="social-link" target="_blank" aria-label="LinkedIn">
                            <i class="fab fa-linkedin-in" aria-hidden="true"></i>
                        </a>`);
                    }
                    if (speaker.twitter) {
                        socialLinks.push(`<a href="${speaker.twitter}" class="social-link" target="_blank" aria-label="Twitter">
                            <i class="fab fa-twitter" aria-hidden="true"></i>
                        </a>`);
                    }
                    if (speaker.website) {
                        socialLinks.push(`<a href="${speaker.website}" class="social-link" target="_blank" aria-label="Website">
                            <i class="fas fa-globe" aria-hidden="true"></i>
                        </a>`);
                    }

                    speakersHTML += `
                        <article class="speaker-card search-transition-enter">
                            <div class="speaker-image">
                                <i class="fas fa-user" aria-hidden="true"></i>
                                ${speaker.featured ? '<span class="speaker-badge">Keynote</span>' : ''}
                            </div>
                            <div class="speaker-content">
                                <h2 class="speaker-name">${speaker.name}</h2>
                                <p class="speaker-title">${speaker.position}</p>
                                <p class="speaker-company">${speaker.company}</p>
                                <p class="speaker-bio">${speaker.bio.length > 120 ? speaker.bio.substring(0, 120) + '...' : speaker.bio}</p>
                                <div class="speaker-topics">
                                    ${topicTags}
                                </div>
                                <div class="speaker-social">
                                    ${socialLinks.join('')}
                                </div>
                                <a href="/speakers/${speaker.id}" class="btn btn-primary">View Full Profile</a>
                            </div>
                        </article>
                    `;
                });
            } else {
                // No results found message (requirement 2.5)
                console.log('❌ [Speakers Search] No results found');

                // Sanitize search term for display to prevent XSS
                const sanitizedSearchTerm = searchTerm.replace(/[<>&"']/g, function(match) {
                    const escapeMap = {
                        '<': '&lt;',
                        '>': '&gt;',
                        '&': '&amp;',
                        '"': '&quot;',
                        "'": '&#x27;'
                    };
                    return escapeMap[match];
                });

                speakersHTML = `
                    <div class="no-speakers search-transition-enter" style="grid-column: 1 / -1;">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <h3>No speakers found</h3>
                        <p>We couldn't find any speakers matching "<strong>${sanitizedSearchTerm}</strong>".</p>
                        <p>Try searching for:</p>
                        <ul style="text-align: left; display: inline-block; margin-bottom: 2rem;">
                            <li>Speaker names (e.g., "Sarah", "Johnson")</li>
                            <li>Job titles (e.g., "CEO", "Director", "Founder")</li>
                            <li>Company names or industries</li>
                            <li>Expertise areas (e.g., "leadership", "innovation")</li>
                        </ul>
                        <div>
                            <button onclick="clearSpeakerSearch()" class="btn btn-primary me-2">View All Speakers</button>
                            <button onclick="document.getElementById('speakerSearchInput').focus()" class="btn btn-outline-primary">Try Another Search</button>
                        </div>
                    </div>
                `;
            }

            // Show search results with smooth transition
            showSpeakerSearchResults(searchResults, defaultSpeakersContainer, clearBtn, searchGrid, speakersHTML);

            console.log('✅ [Speakers Search] Search results displayed with transitions');
            console.timeEnd('searchSpeakers');

        } catch (error) {
            console.error('❌ [Speakers Search] Error in searchSpeakers function:', error);
            console.timeEnd('searchSpeakers');

            // Show error message to user
            const searchResults = document.getElementById('speakerSearchResults');
            const searchGrid = document.getElementById('searchSpeakersGrid');
            const defaultSpeakersContainer = document.getElementById('defaultSpeakersGrid');
            const clearBtn = document.getElementById('clearSpeakerBtn');

            if (searchResults && searchGrid) {
                const errorHTML = `
                    <div class="search-error" style="grid-column: 1 / -1;">
                        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                        <h3>Search Error</h3>
                        <p>We encountered an error while searching for speakers. Please try again or browse all speakers.</p>
                        <div>
                            <button onclick="clearSpeakerSearch()" class="btn btn-primary me-2">View All Speakers</button>
                            <button onclick="location.reload()" class="btn btn-outline-primary">Refresh Page</button>
                        </div>
                    </div>
                `;

                try {
                    searchGrid.innerHTML = errorHTML;
                    searchResults.style.display = 'block';
                    searchResults.classList.add('visible');

                    if (defaultSpeakersContainer) {
                        defaultSpeakersContainer.style.display = 'none';
                        defaultSpeakersContainer.classList.add('hidden');
                    }

                    if (clearBtn) {
                        clearBtn.style.display = 'inline-block';
                        clearBtn.classList.add('clear-btn-visible');
                    }
                } catch (fallbackError) {
                    console.error('❌ [Speakers Search] Fallback error handling failed:', fallbackError);
                    // Last resort: reload the page
                    if (confirm('Search functionality encountered an error. Would you like to refresh the page?')) {
                        location.reload();
                    }
                }
            } else {
                // Fallback: try to show default content if search fails
                if (defaultSpeakersContainer && searchResults) {
                    hideSpeakerSearchResults(searchResults, defaultSpeakersContainer, clearBtn);
                }
            }
        }
    }

    // Helper function to show speaker search results with smooth transitions
    function showSpeakerSearchResults(searchResults, defaultSpeakersContainer, clearBtn, searchGrid, speakersHTML) {
        console.log('🎬 [Speakers Search] Starting show transition');

        // First hide default content with fade out
        defaultSpeakersContainer.classList.add('hidden');

        setTimeout(() => {
            // Update search results content
            searchGrid.innerHTML = speakersHTML;

            // Show search results container
            searchResults.style.display = 'block';

            // Trigger fade in animation
            requestAnimationFrame(() => {
                searchResults.classList.add('visible');

                // Animate in search result cards
                const cards = searchGrid.querySelectorAll('.search-transition-enter');
                cards.forEach((card, index) => {
                    setTimeout(() => {
                        card.classList.add('search-transition-enter-active');
                        card.classList.remove('search-transition-enter');
                    }, index * 50); // Stagger animation
                });
            });

            // Hide default content completely
            defaultSpeakersContainer.style.display = 'none';

            // Show clear button with animation
            if (clearBtn) {
                clearBtn.style.display = 'inline-block';
                clearBtn.classList.remove('clear-btn-hidden');
                clearBtn.classList.add('clear-btn-visible');
            }

            console.log('✅ [Speakers Search] Show transition completed');
        }, 150); // Wait for fade out to complete
    }

    // Helper function to hide speaker search results with smooth transitions
    function hideSpeakerSearchResults(searchResults, defaultSpeakersContainer, clearBtn) {
        console.log('🎬 [Speakers Search] Starting hide transition');

        // Fade out search results
        searchResults.classList.remove('visible');

        // Hide clear button with animation
        if (clearBtn) {
            clearBtn.classList.remove('clear-btn-visible');
            clearBtn.classList.add('clear-btn-hidden');

            setTimeout(() => {
                clearBtn.style.display = 'none';
            }, 200);
        }

        setTimeout(() => {
            // Hide search results completely
            searchResults.style.display = 'none';

            // Show default content
            defaultSpeakersContainer.style.display = 'block';
            defaultSpeakersContainer.classList.remove('hidden');

            console.log('✅ [Speakers Search] Hide transition completed');
        }, 300); // Wait for fade out to complete
    }

    /**
     * Function to reset search state and show default content with smooth transitions
     * Implements requirements 2.2, 2.4, 4.2, 4.3, 4.4
     */
    function clearSpeakerSearch() {
        console.log('🧹 [Speakers Search] Clear function called');
        console.time('clearSpeakerSearch');

        try {
            // Get DOM elements with error handling
            const searchInput = document.getElementById('speakerSearchInput');
            const searchResults = document.getElementById('speakerSearchResults');
            const clearBtn = document.getElementById('clearSpeakerBtn');
            const defaultSpeakersContainer = document.getElementById('defaultSpeakersGrid');

            // Validate required elements exist
            if (!searchInput || !searchResults || !defaultSpeakersContainer) {
                console.error('❌ [Speakers Search] Required elements not found for clear function');
                return;
            }

            // Reset search input
            console.log('🔄 [Speakers Search] Clearing search input');
            searchInput.value = '';

            // Hide search results with smooth transition
            hideSpeakerSearchResults(searchResults, defaultSpeakersContainer, clearBtn);

            // Focus back on search input for better UX
            setTimeout(() => {
                searchInput.focus();
                console.log('🎯 [Speakers Search] Focus returned to search input');
            }, 350);

            console.log('✅ [Speakers Search] Search cleared - showing default content');
            console.timeEnd('clearSpeakerSearch');

        } catch (error) {
            console.error('❌ [Speakers Search] Error in clearSpeakerSearch function:', error);
            console.timeEnd('clearSpeakerSearch');

            // Fallback: basic clear without transitions
            if (searchInput) searchInput.value = '';
            if (searchResults) searchResults.style.display = 'none';
            if (defaultSpeakersContainer) defaultSpeakersContainer.style.display = 'block';
            if (clearBtn) clearBtn.style.display = 'none';
        }
    }

    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 [Speakers Search] Initializing search functionality');
        console.time('initializeSpeakersSearch');

        try {
            const searchInput = document.getElementById('speakerSearchInput');

            if (!searchInput) {
                console.error('❌ [Speakers Search] Search input not found during initialization');
                return;
            }

            // Verify all required elements exist
            const requiredElements = [
                'speakerSearchInput',
                'speakerSearchResults',
                'searchSpeakersGrid',
                'defaultSpeakersGrid',
                'clearSpeakerBtn'
            ];

            let allElementsExist = true;
            const missingElements = [];

            requiredElements.forEach(elementId => {
                const element = document.getElementById(elementId);
                if (!element) {
                    console.warn(`⚠️ [Speakers Search] Element with ID '${elementId}' not found`);
                    missingElements.push(elementId);
                    allElementsExist = false;
                } else {
                    console.log(`✅ [Speakers Search] Element '${elementId}' found`);
                }
            });

            if (!allElementsExist) {
                console.error(`❌ [Speakers Search] Missing elements: ${missingElements.join(', ')} - search may not work properly`);
            }

            // Initialize clear button state
            const clearBtn = document.getElementById('clearSpeakerBtn');
            if (clearBtn) {
                clearBtn.classList.add('clear-btn-hidden');
                console.log('🎯 [Speakers Search] Clear button initialized in hidden state');
            }

            // Enter key support (requirement 2.3)
            searchInput.addEventListener('keypress', function(e) {
                console.log(`⌨️ [Speakers Search] Keypress event: "${e.key}"`);

                if (e.key === 'Enter') {
                    e.preventDefault();
                    console.log('🔍 [Speakers Search] Enter key pressed - executing immediate search');
                    searchSpeakers();
                }
            });

            // Real-time search with 300ms debounce (requirement 2.1, 4.1)
            let searchTimeout;
            searchInput.addEventListener('input', function(event) {
                const inputValue = event.target.value;
                console.log(`⌨️ [Speakers Search] Input event triggered: "${inputValue}" (length: ${inputValue.length})`);

                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(searchSpeakers, 300);
            });

            // Add focus event for better UX and debugging
            searchInput.addEventListener('focus', function() {
                console.log('🎯 [Speakers Search] Search input focused');
                searchInput.setAttribute('aria-expanded', 'false');
            });

            // Add blur event for cleanup and debugging
            searchInput.addEventListener('blur', function() {
                console.log('👋 [Speakers Search] Search input blurred');
            });

            // Add clear button click handler
            if (clearBtn) {
                clearBtn.addEventListener('click', function() {
                    console.log('🧹 [Speakers Search] Clear button clicked');
                    clearSpeakerSearch();
                });
            }

            console.log('✅ [Speakers Search] All event listeners attached successfully');
            console.log(`📊 [Speakers Search] Sample data loaded: ${speakersData.length} speakers available`);
            console.timeEnd('initializeSpeakersSearch');

        } catch (error) {
            console.error('❌ [Speakers Search] Error initializing search functionality:', error);
            console.timeEnd('initializeSpeakersSearch');
        }

        // Stats animation (existing functionality)
        const observerOptions = {
            threshold: 0.5,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const statNumbers = entry.target.querySelectorAll('.stat-number');
                    statNumbers.forEach(stat => {
                        const finalValue = stat.textContent.includes('+') ?
                            parseInt(stat.textContent) :
                            stat.textContent.includes('%') ? 100 :
                            parseInt(stat.textContent);
                        const suffix = stat.textContent.replace(/[0-9]/g, '');
                        animateNumber(stat, 0, finalValue, 2000, suffix);
                    });
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        const statsSection = document.querySelector('.stats-section');
        if (statsSection) observer.observe(statsSection);

        function animateNumber(element, start, end, duration, suffix = '') {
            const startTime = performance.now();

            function updateNumber(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const current = Math.floor(start + (end - start) * progress);
                element.textContent = current + suffix;
                if (progress < 1) requestAnimationFrame(updateNumber);
            }
            requestAnimationFrame(updateNumber);
        }
    });
</script>
@endpush