<?php $__env->startSection('title', 'Registration Details'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Registration Details</h1>
                <a href="<?php echo e(route('admin.registrations.index')); ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Registrations
                </a>
            </div>

            <div class="row">
                <!-- Registration Information -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Registration Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Registration ID:</strong></td>
                                    <td><?php echo e($registration->id); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge bg-<?php echo e($registration->registration_status === 'confirmed' ? 'success' : ($registration->registration_status === 'pending' ? 'warning' : 'danger')); ?>">
                                            <?php echo e(ucfirst($registration->registration_status)); ?>

                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Event:</strong></td>
                                    <td><?php echo e($registration->event->title ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Attendee Name:</strong></td>
                                    <td><?php echo e($registration->attendee_name); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?php echo e($registration->attendee_email); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td><?php echo e($registration->attendee_phone); ?></td>
                                </tr>
                                <?php if($registration->emergency_contact_name): ?>
                                <tr>
                                    <td><strong>Emergency Contact:</strong></td>
                                    <td>
                                        <?php echo e($registration->emergency_contact_name); ?>

                                        <?php if($registration->emergency_contact_phone): ?>
                                        <br><small class="text-muted"><?php echo e($registration->emergency_contact_phone); ?></small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td><strong>Registration Date:</strong></td>
                                    <td><?php echo e($registration->created_at->format('M d, Y H:i')); ?></td>
                                </tr>
                                <?php if($registration->confirmed_at): ?>
                                <tr>
                                    <td><strong>Confirmed At:</strong></td>
                                    <td>
                                        <?php echo e($registration->confirmed_at->format('M d, Y H:i')); ?>

                                        <?php if($registration->confirmedBy): ?>
                                        <br><small class="text-muted">by <?php echo e($registration->confirmedBy->name); ?></small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <?php if($registration->declined_at): ?>
                                <tr>
                                    <td><strong>Declined At:</strong></td>
                                    <td>
                                        <?php echo e($registration->declined_at->format('M d, Y H:i')); ?>

                                        <?php if($registration->declinedBy): ?>
                                        <br><small class="text-muted">by <?php echo e($registration->declinedBy->name); ?></small>
                                        <?php endif; ?>
                                        <?php if($registration->declined_reason): ?>
                                        <br><small class="text-muted">Reason: <?php echo e($registration->declined_reason); ?></small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Payment Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Payment Status:</strong></td>
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
                                    </td>
                                </tr>
                                <?php if($registration->payment_method): ?>
                                <tr>
                                    <td><strong>Payment Method:</strong></td>
                                    <td><?php echo e($registration->getPaymentMethodDisplayName()); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Payment Provider:</strong></td>
                                    <td><?php echo e($registration->getPaymentProviderDisplayName()); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td><strong>Total Amount:</strong></td>
                                    <td>$<?php echo e(number_format($registration->total_amount ?? 0, 2)); ?></td>
                                </tr>
                                <?php if($registration->payment_amount): ?>
                                <tr>
                                    <td><strong>Amount Paid:</strong></td>
                                    <td>$<?php echo e(number_format($registration->payment_amount, 2)); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if($registration->payment_fee): ?>
                                <tr>
                                    <td><strong>Processing Fee:</strong></td>
                                    <td>$<?php echo e(number_format($registration->payment_fee, 2)); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if($registration->payment_currency): ?>
                                <tr>
                                    <td><strong>Currency:</strong></td>
                                    <td><?php echo e(strtoupper($registration->payment_currency)); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if($registration->transaction_id): ?>
                                <tr>
                                    <td><strong>Transaction ID:</strong></td>
                                    <td><code><?php echo e($registration->transaction_id); ?></code></td>
                                </tr>
                                <?php endif; ?>
                                <?php if($registration->payment_completed_at): ?>
                                <tr>
                                    <td><strong>Payment Completed:</strong></td>
                                    <td><?php echo e($registration->payment_completed_at->format('M d, Y H:i')); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if($registration->isRefunded()): ?>
                                <tr>
                                    <td><strong>Refund Amount:</strong></td>
                                    <td>$<?php echo e(number_format($registration->refund_amount, 2)); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Refunded At:</strong></td>
                                    <td><?php echo e($registration->refunded_at->format('M d, Y H:i')); ?></td>
                                </tr>
                                <?php if($registration->refund_reason): ?>
                                <tr>
                                    <td><strong>Refund Reason:</strong></td>
                                    <td><?php echo e($registration->refund_reason); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Transactions -->
            <?php if($registration->paymentTransactions->count() > 0): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Payment Transactions</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Transaction ID</th>
                                            <th>Provider</th>
                                            <th>Method</th>
                                            <th>Amount</th>
                                            <th>Fee</th>
                                            <th>Status</th>
                                            <th>Processed At</th>
                                            <th>Created At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $__currentLoopData = $registration->paymentTransactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td><code><?php echo e($transaction->transaction_id); ?></code></td>
                                            <td><?php echo e(ucfirst($transaction->provider)); ?></td>
                                            <td><?php echo e(ucfirst($transaction->payment_method)); ?></td>
                                            <td>$<?php echo e(number_format($transaction->amount, 2)); ?> <?php echo e(strtoupper($transaction->currency)); ?></td>
                                            <td>$<?php echo e(number_format($transaction->fee, 2)); ?></td>
                                            <td>
                                                <?php
                                                $statusColor = match($transaction->status) {
                                                'completed' => 'success',
                                                'pending' => 'warning',
                                                'failed' => 'danger',
                                                'refunded' => 'info',
                                                default => 'secondary'
                                                };
                                                ?>
                                                <span class="badge bg-<?php echo e($statusColor); ?>">
                                                    <?php echo e(ucfirst($transaction->status)); ?>

                                                </span>
                                            </td>
                                            <td>
                                                <?php if($transaction->processed_at): ?>
                                                <?php echo e($transaction->processed_at->format('M d, Y H:i')); ?>

                                                <?php else: ?>
                                                <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo e($transaction->created_at->format('M d, Y H:i')); ?></td>
                                        </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Ticket Selections -->
            <?php if($registration->ticket_selections && count($registration->ticket_selections) > 0): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Ticket Selections</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Ticket Type</th>
                                            <th>Quantity</th>
                                            <th>Price</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $total = 0; ?>
                                        <?php $__currentLoopData = $registration->ticket_selections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticketId => $quantity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($quantity > 0): ?>
                                        <?php
                                        $ticket = \App\Models\Ticket::find($ticketId);
                                        $subtotal = $ticket ? $ticket->price * $quantity : 0;
                                        $total += $subtotal;
                                        ?>
                                        <tr>
                                            <td><?php echo e($ticket->name ?? 'Unknown Ticket'); ?></td>
                                            <td><?php echo e($quantity); ?></td>
                                            <td>$<?php echo e(number_format($ticket->price ?? 0, 2)); ?></td>
                                            <td>$<?php echo e(number_format($subtotal, 2)); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-active">
                                            <th colspan="3">Total</th>
                                            <th>$<?php echo e(number_format($total, 2)); ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="btn-group" role="group">
                                <?php if($registration->registration_status === 'pending'): ?>
                                <form method="POST" action="<?php echo e(route('admin.registrations.updateStatus', $registration)); ?>" class="d-inline">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <input type="hidden" name="status" value="confirmed">
                                    <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to confirm this registration?')">
                                        <i class="fas fa-check"></i> Confirm Registration
                                    </button>
                                </form>
                                <form method="POST" action="<?php echo e(route('admin.registrations.updateStatus', $registration)); ?>" class="d-inline">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <input type="hidden" name="status" value="declined">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to decline this registration?')">
                                        <i class="fas fa-times"></i> Decline Registration
                                    </button>
                                </form>
                                <?php endif; ?>

                                <?php if($registration->registration_status === 'confirmed'): ?>
                                <form method="POST" action="<?php echo e(route('admin.registrations.updateStatus', $registration)); ?>" class="d-inline">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <input type="hidden" name="status" value="cancelled">
                                    <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to cancel this registration?')">
                                        <i class="fas fa-ban"></i> Cancel Registration
                                    </button>
                                </form>
                                <?php endif; ?>

                                <form method="POST" action="<?php echo e(route('admin.registrations.destroy', $registration)); ?>" class="d-inline">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this registration? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i> Delete Registration
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/Apple/Desktop/dev_folder/Dev_project/test.kiro2/leadership-summit-laravel/resources/views/admin/registrations/show.blade.php ENDPATH**/ ?>