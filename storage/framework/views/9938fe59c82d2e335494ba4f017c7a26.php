<?php $__env->startSection('title', 'Payment Review'); ?>

<?php $__env->startSection('breadcrumbs'); ?>
<li class="breadcrumb-item active">Payment Review</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <h1 class="page-title">Payment Review</h1>
    <div class="page-actions">
        <div class="btn-group">
            <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-filter me-2" aria-hidden="true"></i>Filter
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?php echo e(route('admin.payments.pending')); ?>">All Pending</a></li>
                <li><a class="dropdown-item" href="<?php echo e(route('admin.payments.pending', ['filter' => 'today'])); ?>">Today Only</a></li>
                <li><a class="dropdown-item" href="<?php echo e(route('admin.payments.pending', ['filter' => 'week'])); ?>">This Week</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid mb-4">
    <div class="stat-card warning">
        <div class="stat-header">
            <span class="stat-title">Pending Review</span>
            <div class="stat-icon warning">
                <i class="fas fa-clock" aria-hidden="true"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo e($stats['total_pending']); ?></div>
        <div class="stat-change">
            <span><?php echo e($stats['pending_today']); ?> today</span>
        </div>
    </div>

    <div class="stat-card success">
        <div class="stat-header">
            <span class="stat-title">Confirmed</span>
            <div class="stat-icon success">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo e($stats['total_confirmed']); ?></div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up" aria-hidden="true"></i>
            <span>All time</span>
        </div>
    </div>

    <div class="stat-card danger">
        <div class="stat-header">
            <span class="stat-title">Declined</span>
            <div class="stat-icon danger">
                <i class="fas fa-times-circle" aria-hidden="true"></i>
            </div>
        </div>
        <div class="stat-value"><?php echo e($stats['total_declined']); ?></div>
        <div class="stat-change">
            <span>All time</span>
        </div>
    </div>
</div>

<!-- Pending Payments Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="admin-card-title">Pending Payment Confirmations</h3>
        <div class="d-flex gap-2">
            <span class="badge bg-warning"><?php echo e($pendingRegistrations->total()); ?> pending</span>
        </div>
    </div>
    <div class="admin-card-body p-0">
        <?php if($pendingRegistrations->count() > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Attendee</th>
                        <th>Event</th>
                        <th>Amount</th>
                        <th>Payment Confirmed</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $pendingRegistrations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $registration): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-user text-white" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?php echo e($registration->attendee_name); ?></div>
                                    <small class="text-muted"><?php echo e($registration->attendee_email); ?></small>
                                    <br>
                                    <small class="text-muted"><?php echo e($registration->attendee_phone); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>
                                <div class="fw-semibold"><?php echo e($registration->event->title); ?></div>
                                <small class="text-muted"><?php echo e($registration->event->start_date->format('M d, Y g:i A')); ?></small>
                            </div>
                        </td>
                        <td>
                            <span class="fw-semibold">$<?php echo e(number_format($registration->total_amount, 2)); ?></span>
                            <?php if($registration->ticket_selections): ?>
                            <br>
                            <small class="text-muted">
                                <?php $__currentLoopData = $registration->ticket_selections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticketId => $quantity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php echo e($quantity); ?>x tickets
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div>
                                <span class="fw-semibold"><?php echo e($registration->payment_confirmed_at->format('M d, Y')); ?></span>
                                <br>
                                <small class="text-muted"><?php echo e($registration->payment_confirmed_at->format('g:i A')); ?></small>
                                <br>
                                <small class="text-muted"><?php echo e($registration->payment_confirmed_at->diffForHumans()); ?></small>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-warning">
                                <i class="fas fa-clock me-1" aria-hidden="true"></i>
                                Pending Review
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-success"
                                    onclick="approvePayment(<?php echo e($registration->id); ?>)"
                                    title="Approve Payment">
                                    <i class="fas fa-check" aria-hidden="true"></i>
                                    Approve
                                </button>
                                <button type="button" class="btn btn-sm btn-danger"
                                    onclick="showDeclineModal(<?php echo e($registration->id); ?>, '<?php echo e($registration->attendee_name); ?>')"
                                    title="Decline Payment">
                                    <i class="fas fa-times" aria-hidden="true"></i>
                                    Decline
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split"
                                    data-bs-toggle="dropdown">
                                    <span class="visually-hidden">Toggle Dropdown</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="viewRegistrationDetails(<?php echo e($registration->id); ?>)">
                                            <i class="fas fa-eye me-2" aria-hidden="true"></i>View Details
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="mailto:<?php echo e($registration->attendee_email); ?>">
                                            <i class="fas fa-envelope me-2" aria-hidden="true"></i>Contact Attendee
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if($pendingRegistrations->hasPages()): ?>
        <div class="d-flex justify-content-between align-items-center p-3 border-top">
            <div class="text-muted">
                Showing <?php echo e($pendingRegistrations->firstItem()); ?> to <?php echo e($pendingRegistrations->lastItem()); ?>

                of <?php echo e($pendingRegistrations->total()); ?> results
            </div>
            <?php echo e($pendingRegistrations->links()); ?>

        </div>
        <?php endif; ?>
        <?php else: ?>
        <div class="text-center py-5">
            <div class="mb-3">
                <i class="fas fa-check-circle text-success" style="font-size: 3rem;" aria-hidden="true"></i>
            </div>
            <h5>No Pending Payments</h5>
            <p class="text-muted">All payments have been reviewed. Great job!</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Decline Payment Modal -->
<div class="modal fade" id="declineModal" tabindex="-1" aria-labelledby="declineModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="declineModalLabel">Decline Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="declineForm" method="POST">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2" aria-hidden="true"></i>
                        You are about to decline the payment for <strong id="attendeeName"></strong>.
                        This will unlock their email and phone for re-registration.
                    </div>

                    <div class="mb-3">
                        <label for="decline_reason" class="form-label">Reason for Decline <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="decline_reason" name="decline_reason" rows="4"
                            placeholder="Please provide a clear reason for declining this payment..." required></textarea>
                        <div class="form-text">This reason will be logged for audit purposes and may be sent to the attendee.</div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirmDecline" required>
                            <label class="form-check-label" for="confirmDecline">
                                I understand that declining this payment will allow the attendee to re-register with the same email and phone number.
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-2" aria-hidden="true"></i>Decline Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Registration Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">Registration Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="detailsContent">
                <!-- Content will be loaded via AJAX -->
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    function approvePayment(registrationId) {
        if (confirm('Are you sure you want to approve this payment? This will confirm the registration.')) {
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/payments/${registrationId}/approve`;

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '<?php echo e(csrf_token()); ?>';

            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }
    }

    function showDeclineModal(registrationId, attendeeName) {
        document.getElementById('attendeeName').textContent = attendeeName;
        document.getElementById('declineForm').action = `/admin/payments/${registrationId}/decline`;
        document.getElementById('decline_reason').value = '';
        document.getElementById('confirmDecline').checked = false;

        const modal = new bootstrap.Modal(document.getElementById('declineModal'));
        modal.show();
    }

    function viewRegistrationDetails(registrationId) {
        const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
        const content = document.getElementById('detailsContent');

        // Show loading spinner
        content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;

        modal.show();

        // TODO: Load registration details via AJAX
        // For now, show placeholder content
        setTimeout(() => {
            content.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2" aria-hidden="true"></i>
                Registration details view will be implemented in a future update.
            </div>
        `;
        }, 1000);
    }

    // Auto-refresh page every 30 seconds to show new pending payments
    setInterval(() => {
        // Only refresh if no modals are open
        if (!document.querySelector('.modal.show')) {
            window.location.reload();
        }
    }, 30000);
</script>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .avatar-sm {
        width: 40px;
        height: 40px;
        font-size: 0.875rem;
    }

    .table td {
        vertical-align: middle;
    }

    .badge {
        font-size: 0.75rem;
    }

    .btn-group .btn {
        border-radius: 0.375rem;
    }

    .btn-group .btn:not(:last-child) {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    .btn-group .dropdown-toggle-split {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }

    @media (max-width: 768px) {
        .btn-group {
            flex-direction: column;
        }

        .btn-group .btn {
            border-radius: 0.375rem;
            margin-bottom: 0.25rem;
        }

        .table-responsive {
            font-size: 0.875rem;
        }
    }
</style>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/Apple/Desktop/dev_folder/Dev_project/test.kiro2/leadership-summit-laravel/resources/views/admin/payments/pending.blade.php ENDPATH**/ ?>