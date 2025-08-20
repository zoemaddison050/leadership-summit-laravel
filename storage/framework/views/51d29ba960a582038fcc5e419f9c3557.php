<?php $__env->startSection('title', 'Tickets Management'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Tickets Management</h1>
                <a href="<?php echo e(route('admin.tickets.create')); ?>" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add Ticket
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Event</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $tickets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticket): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td><?php echo e($ticket->id); ?></td>
                                    <td><?php echo e($ticket->name); ?></td>
                                    <td><?php echo e($ticket->event->title ?? 'N/A'); ?></td>
                                    <td>$<?php echo e(number_format($ticket->price, 2)); ?></td>
                                    <td><?php echo e($ticket->quantity); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo e($ticket->is_active ? 'success' : 'danger'); ?>">
                                            <?php echo e($ticket->is_active ? 'Active' : 'Inactive'); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?php echo e(route('admin.tickets.show', $ticket)); ?>" class="btn btn-sm btn-outline-primary">View</a>
                                            <a href="<?php echo e(route('admin.tickets.edit', $ticket)); ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                                            <form method="POST" action="<?php echo e(route('admin.tickets.destroy', $ticket)); ?>" class="d-inline">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Are you sure?')">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">No tickets found.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php echo e($tickets->links()); ?>

                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/Apple/Desktop/dev_folder/Dev_project/test.kiro2/leadership-summit-laravel/resources/views/admin/tickets/index.blade.php ENDPATH**/ ?>