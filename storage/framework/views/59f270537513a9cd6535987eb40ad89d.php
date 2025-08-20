<?php $__env->startSection('title', 'Admin Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <div class="page-actions">
        <a href="<?php echo e(url('/admin/events/create')); ?>" class="btn btn-primary">
            <i class="fas fa-plus me-2" aria-hidden="true"></i>Add Event
        </a>
        <a href="<?php echo e(url('/admin/speakers/create')); ?>" class="btn btn-outline-primary">
            <i class="fas fa-user-plus me-2" aria-hidden="true"></i>Add Speaker
        </a>
    </div>
</div>

<!-- Welcome Message -->
<div class="admin-card mb-4">
    <div class="admin-card-body">
        <h4 class="mb-2">Welcome back, <?php echo e(auth()->user()->name); ?>!</h4>
        <p class="text-muted mb-0">Here's what's happening with your leadership summit today.</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-title">Total Events</span>
            <div class="stat-icon info">
                <i class="fas fa-calendar-alt" aria-hidden="true"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo e($stats['total_events'] ?? 0); ?></div>
        <div class="stat-change <?php echo e(($growth['events_this_month'] ?? 0) > 0 ? 'positive' : 'neutral'); ?>">
            <i class="fas fa-arrow-up" aria-hidden="true"></i>
            <span>+<?php echo e($growth['events_this_month'] ?? 0); ?> this month</span>
        </div>
    </div>

    <div class="stat-card success">
        <div class="stat-header">
            <span class="stat-title">Total Registrations</span>
            <div class="stat-icon success">
                <i class="fas fa-users" aria-hidden="true"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo e($stats['total_registrations'] ?? 0); ?></div>
        <div class="stat-change <?php echo e(($growth['registrations_this_week'] ?? 0) > 0 ? 'positive' : 'neutral'); ?>">
            <i class="fas fa-arrow-up" aria-hidden="true"></i>
            <span>+<?php echo e($growth['registrations_this_week'] ?? 0); ?> this week</span>
        </div>
    </div>

    <div class="stat-card warning">
        <div class="stat-header">
            <span class="stat-title">Active Speakers</span>
            <div class="stat-icon warning">
                <i class="fas fa-microphone" aria-hidden="true"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo e($stats['total_speakers'] ?? 0); ?></div>
        <div class="stat-change neutral">
            <i class="fas fa-microphone" aria-hidden="true"></i>
            <span>Active speakers</span>
        </div>
    </div>

    <div class="stat-card danger">
        <div class="stat-header">
            <span class="stat-title">Revenue</span>
            <div class="stat-icon danger">
                <i class="fas fa-dollar-sign" aria-hidden="true"></i>
            </div>
        </div>
        <div class="stat-value">$<?php echo e(number_format($stats['total_revenue'] ?? 0, 0)); ?></div>
        <div class="stat-change <?php echo e(($growth['revenue_growth'] ?? 0) > 0 ? 'positive' : (($growth['revenue_growth'] ?? 0) < 0 ? 'negative' : 'neutral')); ?>">
            <i class="fas fa-<?php echo e(($growth['revenue_growth'] ?? 0) > 0 ? 'arrow-up' : (($growth['revenue_growth'] ?? 0) < 0 ? 'arrow-down' : 'minus')); ?>" aria-hidden="true"></i>
            <span><?php echo e(($growth['revenue_growth'] ?? 0) > 0 ? '+' : ''); ?><?php echo e($growth['revenue_growth'] ?? 0); ?>% vs last month</span>
        </div>
    </div>
</div>

<!-- Quick Actions and Recent Activity -->
<div class="row">
    <div class="col-lg-8">
        <!-- Recent Registrations -->
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h3 class="admin-card-title">Recent Registrations</h3>
                <a href="<?php echo e(url('/admin/registrations')); ?>" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="admin-card-body">
                <?php if(isset($recentRegistrations) && $recentRegistrations->count() > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Event</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $recentRegistrations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $registration): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-2">
                                            <i class="fas fa-user text-white" aria-hidden="true"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold"><?php echo e($registration->attendee_name); ?></div>
                                            <small class="text-muted"><?php echo e($registration->attendee_email); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo e($registration->event->title); ?></td>
                                <td><?php echo e($registration->created_at->format('M d, Y')); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo e($registration->registration_status === 'confirmed' ? 'success' : ($registration->registration_status === 'pending' ? 'warning' : 'secondary')); ?>">
                                        <?php echo e(ucfirst($registration->registration_status)); ?>

                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <!-- No registrations state -->
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-users fa-3x text-muted"></i>
                    </div>
                    <h5 class="text-muted">No Registrations Yet</h5>
                    <p class="text-muted mb-3">When people register for your events, they'll appear here.</p>
                    <a href="<?php echo e(route('events.show', \App\Models\Event::first()->slug ?? '#')); ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-eye me-1"></i>View Event Page
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming Events -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">Upcoming Events</h3>
                <a href="<?php echo e(url('/admin/events')); ?>" class="btn btn-sm btn-outline-primary">Manage Events</a>
            </div>
            <div class="admin-card-body">
                <?php if(isset($upcomingEvents) && $upcomingEvents->count() > 0): ?>
                <?php $__currentLoopData = $upcomingEvents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="d-flex align-items-center p-3 border rounded mb-3">
                    <div class="flex-shrink-0">
                        <div class="bg-primary text-white rounded p-2 text-center" style="min-width: 60px;">
                            <div class="fw-bold"><?php echo e($event->start_date->format('d')); ?></div>
                            <small><?php echo e($event->start_date->format('M')); ?></small>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-1"><?php echo e($event->title); ?></h6>
                        <p class="text-muted mb-1"><?php echo e($event->start_date->format('F d, Y g:i A')); ?></p>
                        <small class="text-muted">
                            <i class="fas fa-users me-1" aria-hidden="true"></i>
                            <?php echo e($event->registrations_count ?? 0); ?> registered
                        </small>
                    </div>
                    <div class="flex-shrink-0">
                        <a href="<?php echo e(url('/admin/events/' . $event->id)); ?>" class="btn btn-sm btn-outline-primary">
                            Manage
                        </a>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php else: ?>
                <!-- No upcoming events state -->
                <div class="text-center py-4">
                    <div class="mb-3">
                        <i class="fas fa-calendar-plus fa-3x text-muted"></i>
                    </div>
                    <h6 class="text-muted">No Upcoming Events</h6>
                    <p class="text-muted mb-3">Create your first event to get started.</p>
                    <a href="<?php echo e(route('admin.events.create')); ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i>Create Event
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Quick Actions -->
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h3 class="admin-card-title">Quick Actions</h3>
            </div>
            <div class="admin-card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo e(route('admin.events.create')); ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-2" aria-hidden="true"></i>Create New Event
                    </a>
                    <a href="<?php echo e(route('admin.events.index')); ?>" class="btn btn-outline-primary">
                        <i class="fas fa-calendar me-2" aria-hidden="true"></i>Manage Events
                    </a>
                    <a href="<?php echo e(route('admin.speakers.index')); ?>" class="btn btn-outline-primary">
                        <i class="fas fa-users me-2" aria-hidden="true"></i>Manage Speakers
                    </a>
                    <a href="<?php echo e(route('admin.wallet-settings.index')); ?>" class="btn btn-outline-success">
                        <i class="fas fa-wallet me-2" aria-hidden="true"></i>Crypto Wallets
                    </a>
                    <a href="<?php echo e(route('admin.payments.pending')); ?>" class="btn btn-outline-warning">
                        <i class="fas fa-credit-card me-2" aria-hidden="true"></i>Review Payments
                    </a>
                    <hr>
                    <a href="<?php echo e(route('admin.registrations.index')); ?>" class="btn btn-outline-info">
                        <i class="fas fa-clipboard-list me-2" aria-hidden="true"></i>Registrations
                    </a>
                    <a href="<?php echo e(route('admin.tickets.index')); ?>" class="btn btn-outline-info">
                        <i class="fas fa-ticket-alt me-2" aria-hidden="true"></i>Tickets
                    </a>
                    <a href="<?php echo e(route('admin.users.index')); ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-user-cog me-2" aria-hidden="true"></i>Users
                    </a>
                    <a href="<?php echo e(route('admin.roles.index')); ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-user-shield me-2" aria-hidden="true"></i>Roles
                    </a>
                    <a href="<?php echo e(route('admin.reports.index')); ?>" class="btn btn-outline-dark">
                        <i class="fas fa-chart-bar me-2" aria-hidden="true"></i>Reports
                    </a>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">System Status</h3>
            </div>
            <div class="admin-card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted">Server Status</span>
                        <span class="badge bg-success">Online</span>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted">Database</span>
                        <span class="badge bg-success">Connected</span>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted">Email Service</span>
                        <span class="badge bg-success">Active</span>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted">Storage</span>
                        <span class="text-success">78% used</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" style="width: 78%"></div>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <a href="<?php echo e(route('admin.settings.index')); ?>" class="btn btn-sm btn-outline-primary">
                        System Settings
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .avatar-sm {
        width: 32px;
        height: 32px;
    }

    .progress {
        background-color: #e9ecef;
    }
</style>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/Apple/Desktop/dev_folder/Dev_project/test.kiro2/leadership-summit-laravel/resources/views/admin/dashboard.blade.php ENDPATH**/ ?>