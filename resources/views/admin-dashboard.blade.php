@extends('layouts.app')

@section('title', 'Admin Dashboard - Leadership Summit')
@section('meta_description', 'Admin dashboard for the Leadership Summit. Manage events, speakers, registrations, and system operations.')

@push('styles')
<style>
    :root {
        --admin-primary: #1e40af;
        --admin-secondary: #dc2626;
        --admin-success: #059669;
        --admin-warning: #d97706;
        --admin-info: #0284c7;
        --admin-dark: #374151;
        --admin-light: #f8fafc;
        --admin-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        --admin-border-radius: 1rem;
        --admin-transition: all 0.3s ease;
    }

    .admin-hero {
        background: linear-gradient(135deg, var(--admin-primary) 0%, #1e3a8a 100%);
        color: white;
        padding: 3rem 0 2rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .admin-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="admin-grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23admin-grain)"/></svg>');
        opacity: 0.3;
    }

    .admin-welcome {
        position: relative;
        z-index: 2;
    }

    .admin-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
    }

    .admin-subtitle {
        font-size: 1.1rem;
        opacity: 0.9;
        margin-bottom: 2rem;
    }

    .admin-stats-row {
        display: flex;
        justify-content: center;
        gap: 2rem;
        flex-wrap: wrap;
        margin-top: 2rem;
    }

    .admin-hero-stat {
        text-align: center;
        min-width: 120px;
    }

    .admin-hero-stat-number {
        display: block;
        font-size: 2rem;
        font-weight: 700;
        color: #fbbf24;
    }

    .admin-hero-stat-label {
        font-size: 0.9rem;
        opacity: 0.8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .admin-content {
        padding: 3rem 0;
        background: var(--admin-light);
    }

    .admin-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 3rem;
    }

    .admin-stat-card {
        background: white;
        border-radius: var(--admin-border-radius);
        padding: 2rem;
        box-shadow: var(--admin-shadow);
        transition: var(--admin-transition);
        cursor: pointer;
        border-left: 4px solid var(--admin-primary);
    }

    .admin-stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    }

    .admin-stat-card.success {
        border-left-color: var(--admin-success);
    }

    .admin-stat-card.warning {
        border-left-color: var(--admin-warning);
    }

    .admin-stat-card.info {
        border-left-color: var(--admin-info);
    }

    .admin-stat-card.danger {
        border-left-color: var(--admin-secondary);
    }

    .admin-stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1rem;
        background: var(--admin-primary);
        color: white;
    }

    .admin-stat-card.success .admin-stat-icon {
        background: var(--admin-success);
    }

    .admin-stat-card.warning .admin-stat-icon {
        background: var(--admin-warning);
    }

    .admin-stat-card.info .admin-stat-icon {
        background: var(--admin-info);
    }

    .admin-stat-card.danger .admin-stat-icon {
        background: var(--admin-secondary);
    }

    .admin-stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--admin-dark);
        margin-bottom: 0.5rem;
        display: block;
    }

    .admin-stat-label {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--admin-dark);
        margin-bottom: 0.5rem;
    }

    .admin-stat-description {
        font-size: 0.9rem;
        color: #6b7280;
    }

    .admin-card {
        background: white;
        border-radius: var(--admin-border-radius);
        padding: 2rem;
        box-shadow: var(--admin-shadow);
        margin-bottom: 2rem;
    }

    .admin-card h3 {
        color: var(--admin-primary);
        font-weight: 700;
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .admin-quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }

    .admin-quick-action {
        display: flex;
        align-items: center;
        padding: 1rem;
        background: #f8fafc;
        border-radius: 0.5rem;
        text-decoration: none;
        color: var(--admin-dark);
        transition: var(--admin-transition);
        border: 1px solid #e5e7eb;
    }

    .admin-quick-action:hover {
        background: var(--admin-primary);
        color: white;
        transform: translateX(5px);
    }

    .admin-quick-action-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--admin-primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        transition: var(--admin-transition);
    }

    .admin-quick-action:hover .admin-quick-action-icon {
        background: white;
        color: var(--admin-primary);
    }

    .admin-quick-action h4 {
        font-size: 1rem;
        font-weight: 600;
        margin: 0 0 0.25rem 0;
    }

    .admin-quick-action p {
        font-size: 0.85rem;
        margin: 0;
        opacity: 0.8;
    }

    .admin-recent-activity {
        max-height: 400px;
        overflow-y: auto;
    }

    .admin-activity-item {
        display: flex;
        align-items: flex-start;
        padding: 1rem 0;
        border-bottom: 1px solid #e5e7eb;
    }

    .admin-activity-item:last-child {
        border-bottom: none;
    }

    .admin-activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--admin-info);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        flex-shrink: 0;
    }

    .admin-activity-content h5 {
        font-size: 0.95rem;
        font-weight: 600;
        margin: 0 0 0.25rem 0;
        color: var(--admin-dark);
    }

    .admin-activity-content p {
        font-size: 0.85rem;
        color: #6b7280;
        margin: 0 0 0.25rem 0;
    }

    .admin-activity-time {
        font-size: 0.75rem;
        color: #9ca3af;
    }

    @media (max-width: 768px) {
        .admin-title {
            font-size: 2rem;
        }

        .admin-stats-row {
            gap: 1rem;
        }

        .admin-stats-grid {
            grid-template-columns: 1fr;
        }

        .admin-quick-actions {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

<!-- Admin Hero Section -->
<section class="admin-hero">
    <div class="container">
        <div class="admin-welcome">
            <h1 class="admin-title">
                <i class="fas fa-crown" aria-hidden="true"></i>
                Welcome back, {{ $user->name }}!
            </h1>
            <p class="admin-subtitle">Admin • Ready to manage your leadership summit?</p>

            <div class="admin-stats-row">
                <div class="admin-hero-stat">
                    <span class="admin-hero-stat-number" data-count="{{ $dashboardStats['total_events'] }}">0</span>
                    <span class="admin-hero-stat-label">Total Events</span>
                </div>
                <div class="admin-hero-stat">
                    <span class="admin-hero-stat-number" data-count="{{ $dashboardStats['total_registrations'] }}">0</span>
                    <span class="admin-hero-stat-label">Registrations</span>
                </div>
                <div class="admin-hero-stat">
                    <span class="admin-hero-stat-number" data-count="{{ $dashboardStats['admin_since_days'] }}">0</span>
                    <span class="admin-hero-stat-label">Days as Admin</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Admin Dashboard Content -->
<section class="admin-content">
    <div class="container">
        <!-- Admin Statistics Dashboard -->
        <div class="admin-stats-grid">
            <div class="admin-stat-card success" onclick="navigateTo('{{ route('admin.events.index') }}')">
                <div class="admin-stat-icon">
                    <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                </div>
                <span class="admin-stat-number" data-count="{{ $dashboardStats['total_events'] }}">0</span>
                <div class="admin-stat-label">Total Events</div>
                <div class="admin-stat-description">Events in the system</div>
            </div>

            <div class="admin-stat-card info" onclick="navigateTo('{{ route('admin.registrations.index') }}')">
                <div class="admin-stat-icon">
                    <i class="fas fa-ticket-alt" aria-hidden="true"></i>
                </div>
                <span class="admin-stat-number" data-count="{{ $dashboardStats['total_registrations'] }}">0</span>
                <div class="admin-stat-label">Total Registrations</div>
                <div class="admin-stat-description">All event registrations</div>
            </div>

            <div class="admin-stat-card warning" onclick="navigateTo('{{ route('admin.speakers.index') }}')">
                <div class="admin-stat-icon">
                    <i class="fas fa-users" aria-hidden="true"></i>
                </div>
                <span class="admin-stat-number" data-count="{{ $dashboardStats['total_speakers'] }}">0</span>
                <div class="admin-stat-label">Total Speakers</div>
                <div class="admin-stat-description">Registered speakers</div>
            </div>

            <div class="admin-stat-card success" onclick="navigateTo('{{ route('admin.payments.pending') }}')">
                <div class="admin-stat-icon">
                    <i class="fas fa-dollar-sign" aria-hidden="true"></i>
                </div>
                <span class="admin-stat-number">${{ number_format($dashboardStats['total_revenue'], 0) }}</span>
                <div class="admin-stat-label">Total Revenue</div>
                <div class="admin-stat-description">Completed payments</div>
            </div>

            @if($dashboardStats['pending_payments'] > 0)
            <div class="admin-stat-card danger" onclick="navigateTo('{{ route('admin.payments.pending') }}')">
                <div class="admin-stat-icon">
                    <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                </div>
                <span class="admin-stat-number" data-count="{{ $dashboardStats['pending_payments'] }}">0</span>
                <div class="admin-stat-label">Pending Payments</div>
                <div class="admin-stat-description">Require attention</div>
            </div>
            @endif

            <div class="admin-stat-card info" onclick="navigateTo('{{ route('events.index') }}')">
                <div class="admin-stat-icon">
                    <i class="fas fa-calendar-check" aria-hidden="true"></i>
                </div>
                <span class="admin-stat-number" data-count="{{ $dashboardStats['upcoming_events'] }}">0</span>
                <div class="admin-stat-label">Upcoming Events</div>
                <div class="admin-stat-description">Events available for registration</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="admin-card">
            <h3>
                <i class="fas fa-bolt" aria-hidden="true"></i>
                Quick Actions
            </h3>

            <div class="admin-quick-actions">
                @foreach($quickActions as $action)
                <a href="{{ $action['url'] }}" class="admin-quick-action">
                    <div class="admin-quick-action-icon">
                        <i class="{{ $action['icon'] }}" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h4>{{ $action['title'] }}</h4>
                        <p>{{ $action['description'] }}</p>
                    </div>
                </a>
                @endforeach
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Recent Registrations -->
                <div class="admin-card">
                    <h3>
                        <i class="fas fa-clock" aria-hidden="true"></i>
                        Recent Registrations
                    </h3>

                    @if($recentRegistrations->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Participant</th>
                                    <th>Event</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentRegistrations as $registration)
                                <tr onclick="navigateTo('{{ route('admin.registrations.show', $registration) }}')" style="cursor: pointer;">
                                    <td>
                                        <strong>
                                            {{ $registration->user ? $registration->user->name : $registration->first_name . ' ' . $registration->last_name }}
                                        </strong>
                                        <br>
                                        <small class="text-muted">{{ $registration->user ? $registration->user->email : $registration->email }}</small>
                                    </td>
                                    <td>{{ $registration->event->title }}</td>
                                    <td>
                                        <span class="badge bg-{{ $registration->payment_status === 'completed' ? 'success' : ($registration->payment_status === 'pending' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($registration->payment_status) }}
                                        </span>
                                    </td>
                                    <td>{{ $registration->created_at->format('M d, Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted">No recent registrations found.</p>
                    @endif
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Recent Activity -->
                <div class="admin-card">
                    <h3>
                        <i class="fas fa-history" aria-hidden="true"></i>
                        Recent Activity
                    </h3>

                    <div class="admin-recent-activity">
                        @if(count($recentActivity) > 0)
                        @foreach($recentActivity as $activity)
                        <div class="admin-activity-item">
                            <div class="admin-activity-icon">
                                <i class="{{ $activity['icon'] }}" aria-hidden="true"></i>
                            </div>
                            <div class="admin-activity-content">
                                <h5>{{ $activity['title'] }}</h5>
                                <p>{{ $activity['description'] }}</p>
                                <div class="admin-activity-time">{{ $activity['date']->diffForHumans() }}</div>
                            </div>
                        </div>
                        @endforeach
                        @else
                        <p class="text-muted">No recent activity found.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animate numbers
        const numbers = document.querySelectorAll('[data-count]');
        numbers.forEach(number => {
            const target = parseInt(number.getAttribute('data-count'));
            const duration = 2000;
            const step = target / (duration / 16);
            let current = 0;

            const timer = setInterval(() => {
                current += step;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                number.textContent = Math.floor(current);
            }, 16);
        });
    });

    // Navigation function
    function navigateTo(url) {
        window.location.href = url;
    }
</script>
@endpush