<?php $__env->startSection('title', 'Events Management'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Events Management</h1>
        <a href="<?php echo e(route('admin.events.create')); ?>" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Create Event
        </a>
    </div>

    <?php if(session('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo e(session('success')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo e(session('error')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Default</th>
                            <th>Registrations</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $events; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $event): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td>
                                <strong><?php echo e($event->title); ?></strong>
                                <br>
                                <small class="text-muted"><?php echo e($event->slug); ?></small>
                            </td>
                            <td>
                                <?php echo e($event->start_date->format('M d, Y')); ?>

                                <?php if($event->end_date && $event->end_date != $event->start_date): ?>
                                <br><small class="text-muted">to <?php echo e($event->end_date->format('M d, Y')); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($event->location); ?></td>
                            <td>
                                <span class="badge bg-<?php echo e($event->status === 'published' ? 'success' : ($event->status === 'draft' ? 'warning' : 'danger')); ?>">
                                    <?php echo e(ucfirst($event->status)); ?>

                                </span>
                            </td>
                            <td>
                                <?php if($event->is_default): ?>
                                <span class="badge bg-primary">Default</span>
                                <?php else: ?>
                                <form method="POST" action="<?php echo e(route('admin.events.set-default', $event)); ?>" class="d-inline">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Set Default</button>
                                </form>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($event->registrations_count); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="<?php echo e(route('admin.events.show', $event)); ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?php echo e(route('admin.events.edit', $event)); ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if($event->registrations_count == 0): ?>
                                    <form method="POST" action="<?php echo e(route('admin.events.destroy', $event)); ?>" class="d-inline"
                                        onsubmit="return confirm('Are you sure you want to delete this event?')">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="text-center">No events found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php echo e($events->links()); ?>

        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/Apple/Desktop/dev_folder/Dev_project/test.kiro2/leadership-summit-laravel/resources/views/admin/events/index.blade.php ENDPATH**/ ?>