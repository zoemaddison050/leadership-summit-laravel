<?php $__env->startSection('title', 'Registrations Management'); ?>

<?php
use Illuminate\Support\Str;
?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Registrations Management</h1>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo e(request('status') === 'pending' ? 'selected' : ''); ?>>Pending</option>
                                <option value="confirmed" <?php echo e(request('status') === 'confirmed' ? 'selected' : ''); ?>>Confirmed</option>
                                <option value="cancelled" <?php echo e(request('status') === 'cancelled' ? 'selected' : ''); ?>>Cancelled</option>
                                <option value="declined" <?php echo e(request('status') === 'declined' ? 'selected' : ''); ?>>Declined</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="payment_status" class="form-label">Payment Status</label>
                            <select name="payment_status" id="payment_status" class="form-select">
                                <option value="">All Payment Statuses</option>
                                <option value="not_started" <?php echo e(request('payment_status') === 'not_started' ? 'selected' : ''); ?>>Not Started</option>
                                <option value="pending" <?php echo e(request('payment_status') === 'pending' ? 'selected' : ''); ?>>Pending</option>
                                <option value="completed" <?php echo e(request('payment_status') === 'completed' ? 'selected' : ''); ?>>Completed</option>
                                <option value="refunded" <?php echo e(request('payment_status') === 'refunded' ? 'selected' : ''); ?>>Refunded</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select name="payment_method" id="payment_method" class="form-select">
                                <option value="">All Methods</option>
                                <option value="card" <?php echo e(request('payment_method') === 'card' ? 'selected' : ''); ?>>Card</option>
                                <option value="crypto" <?php echo e(request('payment_method') === 'crypto' ? 'selected' : ''); ?>>Crypto</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="payment_provider" class="form-label">Payment Provider</label>
                            <select name="payment_provider" id="payment_provider" class="form-select">
                                <option value="">All Providers</option>
                                <option value="unipayment" <?php echo e(request('payment_provider') === 'unipayment' ? 'selected' : ''); ?>>UniPayment</option>
                                <option value="crypto" <?php echo e(request('payment_provider') === 'crypto' ? 'selected' : ''); ?>>Crypto</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="Name, email, transaction..." value="<?php echo e(request('search')); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Registrations Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Attendee</th>
                                    <th>Event</th>
                                    <th>Status</th>
                                    <th>Payment Status</th>
                                    <th>Payment Method</th>
                                    <th>Amount</th>
                                    <th>Transaction ID</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $registrations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $registration): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td><?php echo e($registration->id); ?></td>
                                    <td>
                                        <div><?php echo e($registration->attendee_name); ?></div>
                                        <small class="text-muted"><?php echo e($registration->attendee_email); ?></small>
                                    </td>
                                    <td><?php echo e($registration->event->title ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo e($registration->registration_status === 'confirmed' ? 'success' : ($registration->registration_status === 'pending' ? 'warning' : 'danger')); ?>">
                                            <?php echo e(ucfirst($registration->registration_status)); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $paymentStatus = $registration->getPaymentStatus();
                                        $badgeColor = match($paymentStatus) {
                                        'completed' => 'success',
                                        'pending' => 'warning',
                                        'refunded' => 'info',
                                        'not_started' => 'secondary',
                                        default => 'danger'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo e($badgeColor); ?>">
                                            <?php echo e($registration->getPaymentStatusDisplayName()); ?>

                                        </span>
                                        <?php if($registration->isRefunded()): ?>
                                        <br><small class="text-muted">Refunded: $<?php echo e(number_format($registration->refund_amount, 2)); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($registration->payment_method): ?>
                                        <div><?php echo e($registration->getPaymentMethodDisplayName()); ?></div>
                                        <small class="text-muted"><?php echo e($registration->getPaymentProviderDisplayName()); ?></small>
                                        <?php else: ?>
                                        <span class="text-muted">Not selected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div>$<?php echo e(number_format($registration->total_amount ?? 0, 2)); ?></div>
                                        <?php if($registration->payment_amount && $registration->payment_amount != $registration->total_amount): ?>
                                        <small class="text-muted">Paid: $<?php echo e(number_format($registration->payment_amount, 2)); ?></small>
                                        <?php endif; ?>
                                        <?php if($registration->payment_fee): ?>
                                        <small class="text-muted">Fee: $<?php echo e(number_format($registration->payment_fee, 2)); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($registration->transaction_id): ?>
                                        <code class="small"><?php echo e(Str::limit($registration->transaction_id, 15)); ?></code>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?php echo e($registration->created_at->format('M d, Y')); ?></div>
                                        <?php if($registration->payment_completed_at): ?>
                                        <small class="text-muted">Paid: <?php echo e($registration->payment_completed_at->format('M d, Y')); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?php echo e(route('admin.registrations.show', $registration)); ?>" class="btn btn-sm btn-outline-primary">View</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="10" class="text-center py-4">No registrations found.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php echo e($registrations->links()); ?>

                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/Apple/Desktop/dev_folder/Dev_project/test.kiro2/leadership-summit-laravel/resources/views/admin/registrations/index.blade.php ENDPATH**/ ?>