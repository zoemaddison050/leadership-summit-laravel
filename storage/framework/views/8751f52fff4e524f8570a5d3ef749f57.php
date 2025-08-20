<?php $__env->startSection('title', 'Speakers Management'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Speakers Management</h1>
                <a href="<?php echo e(route('admin.speakers.create')); ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Speaker
                </a>
            </div>

            <?php if(session('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo e(session('success')); ?>

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0">All Speakers (<?php echo e($speakers->total()); ?>)</h5>
                        </div>
                        <div class="col-auto">
                            <form method="POST" action="<?php echo e(route('admin.speakers.bulk-action')); ?>" id="bulk-action-form">
                                <?php echo csrf_field(); ?>
                                <div class="input-group">
                                    <select name="action" class="form-select" required>
                                        <option value="">Bulk Actions</option>
                                        <option value="delete">Delete Selected</option>
                                    </select>
                                    <button type="submit" class="btn btn-outline-secondary" onclick="return confirm('Are you sure you want to perform this action?')">
                                        Apply
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if($speakers->count() > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" id="select-all" class="form-check-input">
                                    </th>
                                    <th width="80">Photo</th>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>Company</th>
                                    <th>Sessions</th>
                                    <th>Created</th>
                                    <th width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $speakers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $speaker): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="speakers[]" value="<?php echo e($speaker->id); ?>"
                                            class="form-check-input speaker-checkbox" form="bulk-action-form">
                                    </td>
                                    <td>
                                        <?php if($speaker->photo): ?>
                                        <img src="<?php echo e(asset('storage/' . $speaker->photo)); ?>"
                                            alt="<?php echo e($speaker->name); ?>"
                                            class="rounded-circle"
                                            width="50" height="50"
                                            style="object-fit: cover;">
                                        <?php else: ?>
                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center"
                                            style="width: 50px; height: 50px;">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo e($speaker->name); ?></strong>
                                    </td>
                                    <td><?php echo e($speaker->position ?: '-'); ?></td>
                                    <td><?php echo e($speaker->company ?: '-'); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo e($speaker->sessions_count); ?> sessions</span>
                                    </td>
                                    <td><?php echo e($speaker->created_at->format('M j, Y')); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?php echo e(route('admin.speakers.show', $speaker)); ?>"
                                                class="btn btn-sm btn-outline-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo e(route('admin.speakers.edit', $speaker)); ?>"
                                                class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" action="<?php echo e(route('admin.speakers.destroy', $speaker)); ?>"
                                                class="d-inline" onsubmit="return confirm('Are you sure you want to delete this speaker?')">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-microphone-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No speakers found</h5>
                        <p class="text-muted">Get started by adding your first speaker.</p>
                        <a href="<?php echo e(route('admin.speakers.create')); ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Speaker
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if($speakers->hasPages()): ?>
                <div class="card-footer">
                    <?php echo e($speakers->links()); ?>

                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle select all checkbox
        const selectAllCheckbox = document.getElementById('select-all');
        const speakerCheckboxes = document.querySelectorAll('.speaker-checkbox');

        selectAllCheckbox.addEventListener('change', function() {
            speakerCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Update select all checkbox when individual checkboxes change
        speakerCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const checkedCount = document.querySelectorAll('.speaker-checkbox:checked').length;
                selectAllCheckbox.checked = checkedCount === speakerCheckboxes.length;
                selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < speakerCheckboxes.length;
            });
        });
    });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/Apple/Desktop/dev_folder/Dev_project/test.kiro2/leadership-summit-laravel/resources/views/admin/speakers/index.blade.php ENDPATH**/ ?>