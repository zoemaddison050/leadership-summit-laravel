<?php $__env->startSection('title', 'Wallet Settings'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Cryptocurrency Wallet Settings</h1>
        <a href="<?php echo e(route('admin.wallet-settings.create')); ?>" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add Wallet
        </a>
    </div>

    <?php if(session('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo e(session('success')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Cryptocurrency</th>
                            <th>Name</th>
                            <th>Symbol</th>
                            <th>Code</th>
                            <th>Wallet Address</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $wallets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $wallet): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td>
                                <span class="badge bg-primary"><?php echo e(ucfirst($wallet->cryptocurrency)); ?></span>
                            </td>
                            <td><?php echo e($wallet->currency_name); ?></td>
                            <td>
                                <span style="font-size: 1.2em;"><?php echo e($wallet->currency_symbol); ?></span>
                            </td>
                            <td><?php echo e($wallet->currency_code); ?></td>
                            <td>
                                <code class="small"><?php echo e(Str::limit($wallet->wallet_address, 30)); ?></code>
                                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyToClipboard('<?php echo e($wallet->wallet_address); ?>')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </td>
                            <td>
                                <form method="POST" action="<?php echo e(route('admin.wallet-settings.toggle', $wallet)); ?>" class="d-inline">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-sm btn-<?php echo e($wallet->is_active ? 'success' : 'secondary'); ?>">
                                        <?php echo e($wallet->is_active ? 'Active' : 'Inactive'); ?>

                                    </button>
                                </form>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="<?php echo e(route('admin.wallet-settings.edit', $wallet)); ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="<?php echo e(route('admin.wallet-settings.destroy', $wallet)); ?>" class="d-inline"
                                        onsubmit="return confirm('Are you sure you want to delete this wallet setting?')">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="text-center">No wallet settings found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            // Show success message
            const toast = document.createElement('div');
            toast.className = 'toast align-items-center text-white bg-success border-0 position-fixed top-0 end-0 m-3';
            toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    Wallet address copied to clipboard!
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
            document.body.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            setTimeout(() => toast.remove(), 3000);
        });
    }
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/Apple/Desktop/dev_folder/Dev_project/test.kiro2/leadership-summit-laravel/resources/views/admin/wallet-settings/index.blade.php ENDPATH**/ ?>