<?php $__env->startSection('title', 'Events - Leadership Summit 2025'); ?>
<?php $__env->startSection('meta_description', 'Discover all events at the International Global Leadership Academy Summit 2025. Register for keynotes, workshops, and networking sessions.'); ?>

<?php $__env->startSection('breadcrumbs'); ?>
<li class="breadcrumb-item active" aria-current="page">Events</li>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .events-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, #1e3a8a 100%);
        color: white;
        padding: 4rem 0 3rem;
        text-align: center;
    }

    .events-header h1 {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .events-header p {
        font-size: 1.2rem;
        opacity: 0.9;
        max-width: 600px;
        margin: 0 auto;
    }

    .events-filters {
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

    .events-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
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
    #searchResults {
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    #searchResults.visible {
        opacity: 1;
    }

    #defaultEventsGrid {
        opacity: 1;
        transition: opacity 0.3s ease;
    }

    #defaultEventsGrid.hidden {
        opacity: 0;
    }

    .event-card {
        background: white;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border: 1px solid #f1f5f9;
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
        position: relative;
    }

    .event-badge {
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

    .event-content {
        padding: 2rem;
    }

    .event-meta {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
        font-size: 0.9rem;
        color: var(--dark-gray);
    }

    .event-meta span {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .event-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: var(--primary-color);
        line-height: 1.3;
    }

    .event-description {
        color: var(--dark-gray);
        margin-bottom: 1.5rem;
        line-height: 1.6;
    }

    .event-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 1rem;
        border-top: 1px solid #f1f5f9;
    }

    .event-price {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--primary-color);
    }

    .event-price.free {
        color: #10b981;
    }

    .no-events {
        text-align: center;
        padding: 4rem 0;
        color: var(--dark-gray);
        background: #f8fafc;
        border-radius: 1rem;
        margin: 2rem 0;
        border: 2px dashed #e2e8f0;
    }

    .no-events i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
        color: var(--primary-color);
    }

    .no-events h3 {
        color: var(--primary-color);
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .no-events p {
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
        .events-header h1 {
            font-size: 2.5rem;
        }

        .events-grid {
            grid-template-columns: 1fr;
            padding: 2rem 0;
        }

        .filter-tabs {
            justify-content: flex-start;
            overflow-x: auto;
            padding-bottom: 0.5rem;
        }

        .events-filters {
            position: static;
        }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<!-- Events Header -->
<section class="events-header">
    <div class="container">
        <h1>Summit Events</h1>
        <p>Explore our comprehensive lineup of keynotes, workshops, panels, and networking opportunities</p>
    </div>
</section>

<!-- Events Filters -->
<section class="events-filters">
    <div class="container">
        <div class="filter-tabs">
            <a href="<?php echo e(route('events.index')); ?>" class="filter-tab <?php echo e(!request('date') && !request('status') ? 'active' : ''); ?>">
                All Events
            </a>
            <a href="<?php echo e(route('events.index', ['date' => 'today'])); ?>" class="filter-tab <?php echo e(request('date') == 'today' ? 'active' : ''); ?>">
                Today
            </a>
            <a href="<?php echo e(route('events.index', ['date' => 'week'])); ?>" class="filter-tab <?php echo e(request('date') == 'week' ? 'active' : ''); ?>">
                This Week
            </a>
            <a href="<?php echo e(route('events.index', ['date' => 'month'])); ?>" class="filter-tab <?php echo e(request('date') == 'month' ? 'active' : ''); ?>">
                This Month
            </a>
            <a href="<?php echo e(route('events.index', ['status' => 'featured'])); ?>" class="filter-tab <?php echo e(request('status') == 'featured' ? 'active' : ''); ?>">
                Featured
            </a>
            <a href="<?php echo e(route('events.calendar')); ?>" class="filter-tab">
                <i class="fas fa-calendar-alt me-1" aria-hidden="true"></i>Calendar View
            </a>
        </div>

        <div class="search-bar">
            <div class="input-group">
                <input type="text" id="eventSearchInput" class="form-control" placeholder="Search events..."
                    aria-label="Search events">
                <button class="btn btn-primary" type="button" onclick="searchEvents()" aria-label="Search">
                    <i class="fas fa-search" aria-hidden="true"></i>
                </button>
                <button class="btn btn-outline-secondary" type="button" onclick="clearSearch()" title="Clear search" id="clearSearchBtn" style="display: none;">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Events Grid -->
<section class="events-content">
    <div class="container">
        <!-- Search Results Container -->
        <div id="searchResults" style="display: none;">
            <div class="events-grid" id="searchEventsGrid">
                <!-- Search results will be populated here -->
            </div>
        </div>

        <!-- Default Events Container -->
        <?php if(isset($events) && $events->count() > 0): ?>
        <div class="events-grid" id="defaultEventsGrid">
            <?php $__currentLoopData = $events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <article class="event-card">
                <div class="event-image">
                    <?php if($event->featured_image): ?>
                    <img src="<?php echo e(asset('storage/' . $event->featured_image)); ?>" alt="<?php echo e($event->title); ?>"
                        class="w-100 h-100" style="object-fit: cover;">
                    <?php else: ?>
                    <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                    <?php endif; ?>

                    <?php if($event->status === 'featured'): ?>
                    <span class="event-badge">Featured</span>
                    <?php elseif($event->tickets && $event->tickets->where('available', '>', 0)->count() === 0): ?>
                    <span class="event-badge" style="background: #ef4444;">Sold Out</span>
                    <?php endif; ?>
                </div>

                <div class="event-content">
                    <div class="event-meta">
                        <span>
                            <i class="fas fa-calendar" aria-hidden="true"></i>
                            <?php echo e($event->start_date->format('M d, Y')); ?>

                            <?php if($event->end_date && $event->end_date != $event->start_date): ?>
                            - <?php echo e($event->end_date->format('M d')); ?>

                            <?php endif; ?>
                        </span>
                        <span>
                            <i class="fas fa-clock" aria-hidden="true"></i>
                            <?php echo e($event->start_date->format('g:i A')); ?>

                        </span>
                        <?php if($event->location): ?>
                        <span>
                            <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                            <?php echo e(Str::limit($event->location, 20)); ?>

                        </span>
                        <?php endif; ?>
                    </div>

                    <h2 class="event-title">
                        <a href="<?php echo e(url('/events/' . $event->slug)); ?>" class="text-decoration-none">
                            <?php echo e($event->title); ?>

                        </a>
                    </h2>

                    <p class="event-description">
                        <?php echo e(Str::limit(strip_tags($event->description), 120)); ?>

                    </p>

                    <div class="event-footer">
                        <div class="event-price">
                            <?php if($event->tickets && $event->tickets->count() > 0): ?>
                            <?php
                            $minPrice = $event->tickets->min('price');
                            $maxPrice = $event->tickets->max('price');
                            ?>
                            <?php if($minPrice == 0): ?>
                            <span class="free">Free</span>
                            <?php elseif($minPrice == $maxPrice): ?>
                            $<?php echo e(number_format($minPrice, 2)); ?>

                            <?php else: ?>
                            $<?php echo e(number_format($minPrice, 2)); ?> - $<?php echo e(number_format($maxPrice, 2)); ?>

                            <?php endif; ?>
                            <?php else: ?>
                            <span class="free">Free</span>
                            <?php endif; ?>
                        </div>

                        <a href="<?php echo e(url('/events/' . $event->slug)); ?>" class="btn btn-primary">
                            View Details
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <?php if(method_exists($events, 'links')): ?>
        <div class="d-flex justify-content-center mt-4">
            <?php echo e($events->links()); ?>

        </div>
        <?php endif; ?>
        <?php else: ?>
        <!-- Placeholder events when no data is available -->
        <div class="events-grid" id="defaultEventsGrid">
            <article class="event-card">
                <div class="event-image">
                    <i class="fas fa-microphone" aria-hidden="true"></i>
                    <span class="event-badge">Featured</span>
                </div>
                <div class="event-content">
                    <div class="event-meta">
                        <span><i class="fas fa-calendar" aria-hidden="true"></i> Sep 15, 2025</span>
                        <span><i class="fas fa-clock" aria-hidden="true"></i> 9:00 AM</span>
                        <span><i class="fas fa-map-marker-alt" aria-hidden="true"></i> Main Hall</span>
                    </div>
                    <h2 class="event-title">Opening Keynote: The Future of Leadership</h2>
                    <p class="event-description">Join us for an inspiring opening keynote that sets the tone for the entire summit, exploring emerging trends in global leadership.</p>
                    <div class="event-footer">
                        <div class="event-price free">Included</div>
                        <a href="#" class="btn btn-primary">View Details</a>
                    </div>
                </div>
            </article>

            <article class="event-card">
                <div class="event-image">
                    <i class="fas fa-users" aria-hidden="true"></i>
                </div>
                <div class="event-content">
                    <div class="event-meta">
                        <span><i class="fas fa-calendar" aria-hidden="true"></i> Sep 15, 2025</span>
                        <span><i class="fas fa-clock" aria-hidden="true"></i> 2:00 PM</span>
                        <span><i class="fas fa-map-marker-alt" aria-hidden="true"></i> Workshop Room A</span>
                    </div>
                    <h2 class="event-title">Leadership in the Digital Age Workshop</h2>
                    <p class="event-description">An interactive workshop focusing on digital transformation and leading remote teams in the modern workplace.</p>
                    <div class="event-footer">
                        <div class="event-price">$150.00</div>
                        <a href="#" class="btn btn-primary">View Details</a>
                    </div>
                </div>
            </article>

            <article class="event-card">
                <div class="event-image">
                    <i class="fas fa-comments" aria-hidden="true"></i>
                </div>
                <div class="event-content">
                    <div class="event-meta">
                        <span><i class="fas fa-calendar" aria-hidden="true"></i> Sep 16, 2025</span>
                        <span><i class="fas fa-clock" aria-hidden="true"></i> 11:00 AM</span>
                        <span><i class="fas fa-map-marker-alt" aria-hidden="true"></i> Panel Room</span>
                    </div>
                    <h2 class="event-title">Women in Leadership Panel</h2>
                    <p class="event-description">A dynamic panel discussion featuring successful women leaders sharing insights on breaking barriers and driving change.</p>
                    <div class="event-footer">
                        <div class="event-price free">Free</div>
                        <a href="#" class="btn btn-primary">View Details</a>
                    </div>
                </div>
            </article>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    // Sample events data array with all necessary fields for search functionality
    const eventsData = [{
            id: 1,
            title: "Opening Keynote: The Future of Leadership",
            description: "Join us for an inspiring opening keynote that sets the tone for the entire summit, exploring emerging trends in global leadership and the skills needed to navigate tomorrow's challenges.",
            date: "Sep 15, 2025",
            time: "9:00 AM",
            location: "Main Hall",
            price: "Included",
            category: "keynote",
            featured: true,
            icon: "fas fa-microphone"
        },
        {
            id: 2,
            title: "Leadership in the Digital Age Workshop",
            description: "An interactive workshop focusing on digital transformation and leading remote teams in the modern workplace. Learn practical strategies for virtual leadership.",
            date: "Sep 15, 2025",
            time: "2:00 PM",
            location: "Workshop Room A",
            price: "$150.00",
            category: "workshop",
            featured: false,
            icon: "fas fa-users"
        },
        {
            id: 3,
            title: "Women in Leadership Panel",
            description: "A dynamic panel discussion featuring successful women leaders sharing insights on breaking barriers and driving change in male-dominated industries.",
            date: "Sep 16, 2025",
            time: "11:00 AM",
            location: "Panel Room",
            price: "Free",
            category: "panel",
            featured: false,
            icon: "fas fa-comments"
        },
        {
            id: 4,
            title: "Building High-Performance Teams",
            description: "Interactive session on team dynamics and performance optimization strategies for modern leaders. Includes case studies and practical exercises.",
            date: "Sep 15, 2025",
            time: "1:30 PM",
            location: "Workshop Room B",
            price: "$120.00",
            category: "workshop",
            featured: false,
            icon: "fas fa-users"
        },
        {
            id: 5,
            title: "Networking Reception",
            description: "Connect with fellow leaders over cocktails and hors d'oeuvres in a relaxed networking environment. Perfect for building professional relationships.",
            date: "Sep 15, 2025",
            time: "6:00 PM",
            location: "Grand Ballroom",
            price: "Included",
            category: "networking",
            featured: false,
            icon: "fas fa-glass-cheers"
        },
        {
            id: 6,
            title: "Innovation and Entrepreneurship Masterclass",
            description: "Learn from successful entrepreneurs about innovation strategies, startup culture, and scaling businesses in competitive markets.",
            date: "Sep 16, 2025",
            time: "2:30 PM",
            location: "Innovation Lab",
            price: "$200.00",
            category: "masterclass",
            featured: true,
            icon: "fas fa-lightbulb"
        },
        {
            id: 7,
            title: "Crisis Leadership: Managing Through Uncertainty",
            description: "Essential skills for leading during challenging times, including crisis communication, decision-making under pressure, and maintaining team morale.",
            date: "Sep 17, 2025",
            time: "10:00 AM",
            location: "Conference Room C",
            price: "$175.00",
            category: "workshop",
            featured: false,
            icon: "fas fa-shield-alt"
        },
        {
            id: 8,
            title: "Global Leadership Perspectives",
            description: "International leaders share insights on cross-cultural management, global business strategies, and leading diverse teams across continents.",
            date: "Sep 17, 2025",
            time: "3:00 PM",
            location: "Main Hall",
            price: "Free",
            category: "panel",
            featured: true,
            icon: "fas fa-globe"
        }
    ];

    // Debounce function to limit the frequency of search calls
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Search events function with comprehensive filtering logic and smooth transitions
    function searchEvents() {
        console.log('🔍 [Events Search] Function called');
        console.time('searchEvents');

        try {
            // Get DOM elements with existence checks
            const searchInput = document.getElementById('eventSearchInput');
            const searchResults = document.getElementById('searchResults');
            const searchGrid = document.getElementById('searchEventsGrid');
            const clearBtn = document.getElementById('clearSearchBtn');
            const defaultEventsGrid = document.getElementById('defaultEventsGrid');

            // Validate required elements exist
            if (!searchInput) {
                console.error('❌ [Events Search] Search input element not found');
                return;
            }

            if (!searchResults || !searchGrid || !defaultEventsGrid) {
                console.error('❌ [Events Search] Required DOM elements not found for search functionality');
                return;
            }

            // Get and process search term with validation
            let searchTerm = searchInput.value.trim();

            // Handle edge cases and validate input
            if (searchTerm.length > 100) {
                console.warn('⚠️ [Events Search] Search term too long, truncating');
                searchTerm = searchTerm.substring(0, 100);
                searchInput.value = searchTerm; // Update input to show truncated value
            }

            // Convert to lowercase for case-insensitive search
            const normalizedSearchTerm = searchTerm.toLowerCase();
            console.log('🔍 [Events Search] Search term:', `"${searchTerm}" (normalized: "${normalizedSearchTerm}")`);

            // Handle empty search - show default content with smooth transition
            if (normalizedSearchTerm === '' || normalizedSearchTerm.length < 1) {
                console.log('🔄 [Events Search] Empty or invalid search - transitioning to default content');
                hideSearchResults(searchResults, defaultEventsGrid, clearBtn);
                console.timeEnd('searchEvents');
                return;
            }

            // Handle special characters and potential regex issues
            let safeSearchTerm;
            try {
                // Escape special regex characters for safe searching
                safeSearchTerm = normalizedSearchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            } catch (regexError) {
                console.warn('⚠️ [Events Search] Regex escape failed, using original term:', regexError);
                safeSearchTerm = normalizedSearchTerm;
            }

            // Filter events based on multiple fields (title, description, location, category)
            const filteredEvents = eventsData.filter(event => {
                try {
                    const matchesTitle = event.title && event.title.toLowerCase().includes(normalizedSearchTerm);
                    const matchesDescription = event.description && event.description.toLowerCase().includes(normalizedSearchTerm);
                    const matchesLocation = event.location && event.location.toLowerCase().includes(normalizedSearchTerm);
                    const matchesCategory = event.category && event.category.toLowerCase().includes(normalizedSearchTerm);

                    return matchesTitle || matchesDescription || matchesLocation || matchesCategory;
                } catch (filterError) {
                    console.warn('⚠️ [Events Search] Error filtering event:', event.title, filterError);
                    return false; // Exclude events that cause filtering errors
                }
            });

            console.log(`✅ [Events Search] Filtered ${filteredEvents.length} events from ${eventsData.length} total`);

            // Generate HTML for search results
            let eventsHTML = '';

            if (filteredEvents.length > 0) {
                filteredEvents.forEach((event, index) => {
                    console.log(`📝 [Events Search] Rendering event ${index + 1}: "${event.title}"`);
                    eventsHTML += `
                    <article class="event-card search-transition-enter">
                        <div class="event-image">
                            <i class="${event.icon}" aria-hidden="true"></i>
                            ${event.featured ? '<span class="event-badge">Featured</span>' : ''}
                        </div>
                        <div class="event-content">
                            <div class="event-meta">
                                <span><i class="fas fa-calendar" aria-hidden="true"></i> ${event.date}</span>
                                <span><i class="fas fa-clock" aria-hidden="true"></i> ${event.time}</span>
                                <span><i class="fas fa-map-marker-alt" aria-hidden="true"></i> ${event.location}</span>
                            </div>
                            <h2 class="event-title">
                                <a href="#" class="text-decoration-none">${event.title}</a>
                            </h2>
                            <p class="event-description">${event.description}</p>
                            <div class="event-footer">
                                <div class="event-price ${(event.price === 'Free' || event.price === 'Included') ? 'free' : ''}">${event.price}</div>
                                <a href="#" class="btn btn-primary">View Details</a>
                            </div>
                        </div>
                    </article>
                `;
                });
            } else {
                // No results found message (requirement 1.5)
                console.log('❌ [Events Search] No results found');

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

                eventsHTML = `
                <div class="no-events search-transition-enter" style="grid-column: 1 / -1;">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <h3>No events found</h3>
                    <p>We couldn't find any events matching "<strong>${sanitizedSearchTerm}</strong>".</p>
                    <p>Try searching for:</p>
                    <ul style="text-align: left; display: inline-block; margin-bottom: 2rem;">
                        <li>Different keywords (e.g., "keynote", "workshop", "panel")</li>
                        <li>Speaker names or company names</li>
                        <li>Event locations or topics</li>
                        <li>Broader terms like "leadership" or "innovation"</li>
                    </ul>
                    <div>
                        <button onclick="clearSearch()" class="btn btn-primary me-2">View All Events</button>
                        <button onclick="document.getElementById('eventSearchInput').focus()" class="btn btn-outline-primary">Try Another Search</button>
                    </div>
                </div>
            `;
            }

            // Show search results with smooth transition
            showSearchResults(searchResults, defaultEventsGrid, clearBtn, searchGrid, eventsHTML);

            console.log('✅ [Events Search] Search results displayed with transitions');
            console.timeEnd('searchEvents');

        } catch (error) {
            console.error('❌ [Events Search] Error in searchEvents function:', error);
            console.timeEnd('searchEvents');

            // Show error message to user
            const searchResults = document.getElementById('searchResults');
            const searchGrid = document.getElementById('searchEventsGrid');
            const defaultEventsGrid = document.getElementById('defaultEventsGrid');
            const clearBtn = document.getElementById('clearSearchBtn');

            if (searchResults && searchGrid) {
                const errorHTML = `
                    <div class="search-error" style="grid-column: 1 / -1;">
                        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                        <h3>Search Error</h3>
                        <p>We encountered an error while searching. Please try again or browse all events.</p>
                        <div>
                            <button onclick="clearSearch()" class="btn btn-primary me-2">View All Events</button>
                            <button onclick="location.reload()" class="btn btn-outline-primary">Refresh Page</button>
                        </div>
                    </div>
                `;

                try {
                    searchGrid.innerHTML = errorHTML;
                    searchResults.style.display = 'block';
                    searchResults.classList.add('visible');

                    if (defaultEventsGrid) {
                        defaultEventsGrid.style.display = 'none';
                        defaultEventsGrid.classList.add('hidden');
                    }

                    if (clearBtn) {
                        clearBtn.style.display = 'inline-block';
                        clearBtn.classList.add('clear-btn-visible');
                    }
                } catch (fallbackError) {
                    console.error('❌ [Events Search] Fallback error handling failed:', fallbackError);
                    // Last resort: reload the page
                    if (confirm('Search functionality encountered an error. Would you like to refresh the page?')) {
                        location.reload();
                    }
                }
            } else {
                // Fallback: try to show default content if search fails
                if (defaultEventsGrid && searchResults) {
                    hideSearchResults(searchResults, defaultEventsGrid, clearBtn);
                }
            }
        }
    }

    // Helper function to show search results with smooth transitions
    function showSearchResults(searchResults, defaultEventsGrid, clearBtn, searchGrid, eventsHTML) {
        console.log('🎬 [Events Search] Starting show transition');

        // First hide default content with fade out
        defaultEventsGrid.classList.add('hidden');

        setTimeout(() => {
            // Update search results content
            searchGrid.innerHTML = eventsHTML;

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
            defaultEventsGrid.style.display = 'none';

            // Show clear button with animation
            if (clearBtn) {
                clearBtn.style.display = 'inline-block';
                clearBtn.classList.remove('clear-btn-hidden');
                clearBtn.classList.add('clear-btn-visible');
            }

            console.log('✅ [Events Search] Show transition completed');
        }, 150); // Wait for fade out to complete
    }

    // Helper function to hide search results with smooth transitions
    function hideSearchResults(searchResults, defaultEventsGrid, clearBtn) {
        console.log('🎬 [Events Search] Starting hide transition');

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
            defaultEventsGrid.style.display = 'block';
            defaultEventsGrid.classList.remove('hidden');

            console.log('✅ [Events Search] Hide transition completed');
        }, 300); // Wait for fade out to complete
    }

    // Clear search function to reset search state with smooth transitions
    function clearSearch() {
        console.log('🧹 [Events Search] Clear function called');
        console.time('clearSearch');

        try {
            // Get DOM elements with existence checks
            const searchInput = document.getElementById('eventSearchInput');
            const searchResults = document.getElementById('searchResults');
            const clearBtn = document.getElementById('clearSearchBtn');
            const defaultEventsGrid = document.getElementById('defaultEventsGrid');

            // Validate required elements exist
            if (!searchInput || !searchResults || !defaultEventsGrid) {
                console.error('❌ [Events Search] Required elements not found for clear function');
                return;
            }

            // Reset search input
            console.log('🔄 [Events Search] Clearing search input');
            searchInput.value = '';

            // Hide search results with smooth transition
            hideSearchResults(searchResults, defaultEventsGrid, clearBtn);

            // Focus back on search input for better UX
            setTimeout(() => {
                searchInput.focus();
                console.log('🎯 [Events Search] Focus returned to search input');
            }, 350);

            console.log('✅ [Events Search] Search cleared - showing default content');
            console.timeEnd('clearSearch');

        } catch (error) {
            console.error('❌ [Events Search] Error in clearSearch function:', error);
            console.timeEnd('clearSearch');

            // Fallback: basic clear without transitions
            if (searchInput) searchInput.value = '';
            if (searchResults) searchResults.style.display = 'none';
            if (defaultEventsGrid) defaultEventsGrid.style.display = 'block';
            if (clearBtn) clearBtn.style.display = 'none';
        }
    }

    // Initialize search functionality when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 [Events Search] Initializing search functionality');
        console.time('initializeEventsSearch');

        try {
            // Get search input element with error handling
            const searchInput = document.getElementById('eventSearchInput');

            if (!searchInput) {
                console.error('❌ [Events Search] Search input element not found - real-time search disabled');
                return;
            }

            // Verify all required elements exist
            const requiredElements = [
                'eventSearchInput',
                'searchResults',
                'searchEventsGrid',
                'defaultEventsGrid',
                'clearSearchBtn'
            ];

            let allElementsExist = true;
            const missingElements = [];

            requiredElements.forEach(elementId => {
                const element = document.getElementById(elementId);
                if (!element) {
                    console.warn(`⚠️ [Events Search] Element with ID '${elementId}' not found`);
                    missingElements.push(elementId);
                    allElementsExist = false;
                } else {
                    console.log(`✅ [Events Search] Element '${elementId}' found`);
                }
            });

            if (!allElementsExist) {
                console.error(`❌ [Events Search] Missing elements: ${missingElements.join(', ')} - search may not work properly`);
            }

            // Initialize clear button state
            const clearBtn = document.getElementById('clearSearchBtn');
            if (clearBtn) {
                clearBtn.classList.add('clear-btn-hidden');
                console.log('🎯 [Events Search] Clear button initialized in hidden state');
            }

            // Create debounced search function with 300ms delay (requirement 4.1)
            const debouncedSearch = debounce(searchEvents, 300);
            console.log('⏱️ [Events Search] Debounced search function created with 300ms delay');

            // Add input event listener for real-time filtering
            searchInput.addEventListener('input', function(event) {
                const inputValue = event.target.value;
                console.log(`⌨️ [Events Search] Input event triggered: "${inputValue}" (length: ${inputValue.length})`);

                try {
                    debouncedSearch();
                } catch (error) {
                    console.error('❌ [Events Search] Error during real-time search:', error);
                }
            });

            // Add keypress event listener for Enter key support
            searchInput.addEventListener('keypress', function(event) {
                console.log(`⌨️ [Events Search] Keypress event: "${event.key}"`);

                if (event.key === 'Enter') {
                    event.preventDefault(); // Prevent form submission
                    console.log('🔍 [Events Search] Enter key pressed - executing immediate search');

                    try {
                        // Clear any pending debounced calls and search immediately
                        searchEvents();
                    } catch (error) {
                        console.error('❌ [Events Search] Error during Enter key search:', error);
                    }
                }
            });

            // Add focus event for better UX and debugging
            searchInput.addEventListener('focus', function() {
                console.log('🎯 [Events Search] Search input focused');
                searchInput.setAttribute('aria-expanded', 'false');
            });

            // Add blur event for cleanup and debugging
            searchInput.addEventListener('blur', function() {
                console.log('👋 [Events Search] Search input blurred');
            });

            // Add clear button click handler
            if (clearBtn) {
                clearBtn.addEventListener('click', function() {
                    console.log('🧹 [Events Search] Clear button clicked');
                    clearSearch();
                });
            }

            console.log('✅ [Events Search] All event listeners added successfully');
            console.log(`📊 [Events Search] Sample data loaded: ${eventsData.length} events available`);
            console.timeEnd('initializeEventsSearch');

        } catch (error) {
            console.error('❌ [Events Search] Error initializing search functionality:', error);
            console.timeEnd('initializeEventsSearch');
        }
    });
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/Apple/Desktop/dev_folder/Dev_project/test.kiro2/leadership-summit-laravel/resources/views/events/index.blade.php ENDPATH**/ ?>