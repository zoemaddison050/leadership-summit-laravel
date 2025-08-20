<?php $__env->startSection('title', 'UniPayment Settings'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-credit-card mr-2"></i>
                        UniPayment Configuration
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-sm btn-info" id="test-connection-btn">
                            <i class="fas fa-plug mr-1"></i>
                            Test Connection
                        </button>
                        <div class="connection-status ml-2" id="connection-status">
                            <span class="badge badge-secondary">
                                <i class="fas fa-question-circle mr-1"></i>
                                Unknown
                            </span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="<?php echo e(route('admin.unipayment.update')); ?>" id="unipayment-form">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>

                    <div class="card-body">
                        <?php if(session('success')): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle mr-2"></i>
                            <?php echo e(session('success')); ?>

                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php endif; ?>

                        <?php if(session('error')): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <?php echo e(session('error')); ?>

                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php endif; ?>

                        <?php if($errors->any()): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Please correct the following errors:</strong>
                            <ul class="mb-0 mt-2">
                                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><?php echo e($error); ?></li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php endif; ?>

                        <!-- API Credentials Section -->
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-key mr-2"></i>
                                    API Credentials
                                </h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="app_id" class="form-label">
                                        App ID <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                        class="form-control <?php $__errorArgs = ['app_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        id="app_id"
                                        name="app_id"
                                        value="<?php echo e(old('app_id', $settings->app_id ?? '')); ?>"
                                        placeholder="Enter your UniPayment App ID"
                                        required>
                                    <?php $__errorArgs = ['app_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    <small class="form-text text-muted">
                                        Your UniPayment application identifier
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="api_key" class="form-label">
                                        API Key <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="password"
                                            class="form-control <?php $__errorArgs = ['api_key'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                            id="api_key"
                                            name="api_key"
                                            value="<?php echo e(old('api_key', $settings && $settings->api_key ? '••••••••••••••••' : '')); ?>"
                                            placeholder="Enter your UniPayment API Key"
                                            required>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary" id="toggle-api-key">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <?php $__errorArgs = ['api_key'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>
                                    <small class="form-text text-muted">
                                        Your UniPayment API secret key
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="environment" class="form-label">
                                        Environment <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control <?php $__errorArgs = ['environment'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        id="environment"
                                        name="environment"
                                        required>
                                        <option value="sandbox" <?php echo e(old('environment', $settings->environment ?? 'sandbox') === 'sandbox' ? 'selected' : ''); ?>>
                                            Sandbox (Testing)
                                        </option>
                                        <option value="production" <?php echo e(old('environment', $settings->environment ?? '') === 'production' ? 'selected' : ''); ?>>
                                            Production (Live)
                                        </option>
                                    </select>
                                    <?php $__errorArgs = ['environment'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    <small class="form-text text-muted">
                                        Use sandbox for testing, production for live payments
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="webhook_secret" class="form-label">
                                        Webhook Secret
                                    </label>
                                    <input type="text"
                                        class="form-control <?php $__errorArgs = ['webhook_secret'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        id="webhook_secret"
                                        name="webhook_secret"
                                        value="<?php echo e(old('webhook_secret', $settings->webhook_secret ?? '')); ?>"
                                        placeholder="Enter webhook secret (optional)">
                                    <?php $__errorArgs = ['webhook_secret'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    <small class="form-text text-muted">
                                        Used to verify webhook authenticity
                                    </small>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Webhook Configuration Section -->
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-link mr-2"></i>
                                    Webhook Configuration
                                </h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="webhook_url" class="form-label">
                                        Custom Webhook URL
                                    </label>
                                    <div class="input-group">
                                        <input type="url"
                                            class="form-control <?php $__errorArgs = ['webhook_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                            id="webhook_url"
                                            name="webhook_url"
                                            value="<?php echo e(old('webhook_url', $settings->webhook_url ?? '')); ?>"
                                            placeholder="Leave empty to use auto-generated URL">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary" id="generate-webhook-url-btn" title="Generate URL">
                                                <i class="fas fa-magic"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-info" id="test-webhook-btn" title="Test URL">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </div>
                                        <?php $__errorArgs = ['webhook_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>
                                    <small class="form-text text-muted">
                                        Custom webhook URL for UniPayment notifications. Leave empty to use auto-generated URL based on environment.
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="custom-control custom-switch mt-4">
                                        <input type="checkbox"
                                            class="custom-control-input"
                                            id="webhook_enabled"
                                            name="webhook_enabled"
                                            value="1"
                                            <?php echo e(old('webhook_enabled', $settings->webhook_enabled ?? true) ? 'checked' : ''); ?>>
                                        <label class="custom-control-label" for="webhook_enabled">
                                            <strong>Enable Webhooks</strong>
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Enable webhook notifications for payment updates
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Webhook Status Display -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card bg-light" id="webhook-status-card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6 class="card-title">Webhook Status</h6>
                                                <div id="webhook-status-display">
                                                    <span class="badge badge-secondary">
                                                        <i class="fas fa-question-circle mr-1"></i>
                                                        Unknown
                                                    </span>
                                                </div>
                                                <small class="text-muted d-block mt-1" id="webhook-last-test">
                                                    Last tested: Never
                                                </small>
                                            </div>
                                            <div class="col-md-6">
                                                <h6 class="card-title">Current Webhook URL</h6>
                                                <p class="card-text">
                                                    <code id="current-webhook-url"><?php echo e($settings && $settings->webhook_url ? $settings->webhook_url : route('payment.unipayment.webhook')); ?></code>
                                                </p>
                                                <small class="text-muted">
                                                    Configure this URL in your UniPayment dashboard
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Payment Settings Section -->
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-cog mr-2"></i>
                                    Payment Settings
                                </h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="processing_fee_percentage" class="form-label">
                                        Processing Fee (%)
                                    </label>
                                    <div class="input-group">
                                        <input type="number"
                                            class="form-control <?php $__errorArgs = ['processing_fee_percentage'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                            id="processing_fee_percentage"
                                            name="processing_fee_percentage"
                                            value="<?php echo e(old('processing_fee_percentage', $settings->processing_fee_percentage ?? '2.9')); ?>"
                                            min="0"
                                            max="100"
                                            step="0.1"
                                            placeholder="2.9">
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                        <?php $__errorArgs = ['processing_fee_percentage'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>
                                    <small class="form-text text-muted">
                                        Fee charged for card payments
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="minimum_amount" class="form-label">
                                        Minimum Amount ($)
                                    </label>
                                    <input type="number"
                                        class="form-control <?php $__errorArgs = ['minimum_amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        id="minimum_amount"
                                        name="minimum_amount"
                                        value="<?php echo e(old('minimum_amount', $settings->minimum_amount ?? '1.00')); ?>"
                                        min="0"
                                        step="0.01"
                                        placeholder="1.00">
                                    <?php $__errorArgs = ['minimum_amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    <small class="form-text text-muted">
                                        Minimum payment amount allowed
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="maximum_amount" class="form-label">
                                        Maximum Amount ($)
                                    </label>
                                    <input type="number"
                                        class="form-control <?php $__errorArgs = ['maximum_amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        id="maximum_amount"
                                        name="maximum_amount"
                                        value="<?php echo e(old('maximum_amount', $settings->maximum_amount ?? '10000.00')); ?>"
                                        min="0"
                                        step="0.01"
                                        placeholder="10000.00">
                                    <?php $__errorArgs = ['maximum_amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    <small class="form-text text-muted">
                                        Maximum payment amount allowed
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="supported_currencies" class="form-label">
                                        Supported Currencies
                                    </label>
                                    <input type="text"
                                        class="form-control <?php $__errorArgs = ['supported_currencies'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        id="supported_currencies"
                                        name="supported_currencies"
                                        value="<?php echo e(old('supported_currencies', $settings && $settings->supported_currencies ? implode(', ', $settings->supported_currencies) : 'USD')); ?>"
                                        placeholder="USD, EUR, GBP">
                                    <?php $__errorArgs = ['supported_currencies'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    <small class="form-text text-muted">
                                        Comma-separated list of currency codes (e.g., USD, EUR, GBP)
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <div class="custom-control custom-switch mt-4">
                                        <input type="checkbox"
                                            class="custom-control-input"
                                            id="is_enabled"
                                            name="is_enabled"
                                            value="1"
                                            <?php echo e(old('is_enabled', $settings->is_enabled ?? false) ? 'checked' : ''); ?>>
                                        <label class="custom-control-label" for="is_enabled">
                                            <strong>Enable Card Payments</strong>
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">
                                        When enabled, users will see card payment options during registration
                                    </small>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Connection Information -->
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Connection Information
                                </h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Webhook URL</h6>
                                        <p class="card-text">
                                            <code><?php echo e(route('payment.unipayment.webhook')); ?></code>
                                        </p>
                                        <small class="text-muted">
                                            Configure this URL in your UniPayment dashboard for payment notifications
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Callback URL</h6>
                                        <p class="card-text">
                                            <code><?php echo e(route('payment.unipayment.callback')); ?></code>
                                        </p>
                                        <small class="text-muted">
                                            Users will be redirected here after payment completion
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-2"></i>
                                    Save Settings
                                </button>
                                <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn btn-secondary ml-2">
                                    <i class="fas fa-arrow-left mr-2"></i>
                                    Back to Dashboard
                                </a>
                            </div>
                            <div class="col-md-6 text-right">
                                <?php if($settings && $settings->is_enabled): ?>
                                <span class="badge badge-success badge-lg">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    Card Payments Enabled
                                </span>
                                <?php else: ?>
                                <span class="badge badge-warning badge-lg">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    Card Payments Disabled
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Test Connection Modal -->
<div class="modal fade" id="test-connection-modal" tabindex="-1" role="dialog" aria-labelledby="test-connection-modal-label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="test-connection-modal-label">
                    <i class="fas fa-plug mr-2"></i>
                    Test UniPayment Connection
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="test-connection-loading" class="text-center" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Testing connection...</span>
                    </div>
                    <p class="mt-2">Testing connection to UniPayment API...</p>
                </div>
                <div id="test-connection-result"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Webhook Testing and Monitoring Section -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-tools mr-2"></i>
                    Webhook Testing & Monitoring
                </h3>
            </div>
            <div class="card-body">
                <!-- Webhook Health Status -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Webhook Health Status</h6>
                                <div id="webhook-health-display">
                                    <span class="badge badge-secondary">
                                        <i class="fas fa-spinner fa-spin mr-1"></i>
                                        Loading...
                                    </span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="refresh-health-btn">
                                    <i class="fas fa-sync mr-1"></i>
                                    Refresh Health Status
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Testing Tools -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    Accessibility Test
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Test if the webhook URL is accessible from external services.</p>
                                <button type="button" class="btn btn-primary btn-sm" id="test-accessibility-btn">
                                    <i class="fas fa-globe mr-1"></i>
                                    Test Accessibility
                                </button>
                                <div id="accessibility-result" class="mt-3"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-paper-plane mr-1"></i>
                                    Payload Test
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Send a test payload to the webhook endpoint.</p>
                                <button type="button" class="btn btn-success btn-sm" id="test-payload-btn">
                                    <i class="fas fa-play mr-1"></i>
                                    Send Test Payload
                                </button>
                                <div id="payload-result" class="mt-3"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Diagnostics -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-stethoscope mr-1"></i>
                                    Comprehensive Diagnostics
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Run a complete diagnostic check of webhook configuration and connectivity.</p>
                                <button type="button" class="btn btn-info btn-sm" id="run-diagnostics-btn">
                                    <i class="fas fa-search mr-1"></i>
                                    Run Diagnostics
                                </button>
                                <div id="diagnostics-result" class="mt-3"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monitoring Metrics -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-chart-line mr-1"></i>
                                    Processing Metrics (24h)
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="webhook-metrics-display">
                                    <div class="text-center">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                            <span class="sr-only">Loading metrics...</span>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="refresh-metrics-btn">
                                    <i class="fas fa-sync mr-1"></i>
                                    Refresh Metrics
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-chart-bar mr-1"></i>
                                    Processing Trends (7d)
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="webhook-trends-display">
                                    <div class="text-center">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                            <span class="sr-only">Loading trends...</span>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="refresh-trends-btn">
                                    <i class="fas fa-sync mr-1"></i>
                                    Refresh Trends
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Management Tools -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-cogs mr-1"></i>
                                    Management Tools
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-warning btn-sm" id="reset-counters-btn">
                                        <i class="fas fa-undo mr-1"></i>
                                        Reset Counters
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm" id="clear-cache-btn">
                                        <i class="fas fa-trash mr-1"></i>
                                        Clear Test Cache
                                    </button>
                                </div>
                                <div id="management-result" class="mt-3"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    $(document).ready(function() {
        // Toggle API key visibility
        $('#toggle-api-key').click(function() {
            const apiKeyInput = $('#api_key');
            const icon = $(this).find('i');

            if (apiKeyInput.attr('type') === 'password') {
                apiKeyInput.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                apiKeyInput.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        // Clear masked API key when user starts typing
        $('#api_key').on('input', function() {
            if ($(this).val() === '••••••••••••••••') {
                $(this).val('');
            }
        });

        // Test connection functionality
        $('#test-connection-btn').click(function() {
            const appId = $('#app_id').val();
            const apiKey = $('#api_key').val();
            const environment = $('#environment').val();

            if (!appId || !apiKey) {
                alert('Please enter App ID and API Key before testing connection.');
                return;
            }

            // Show modal and loading state
            $('#test-connection-modal').modal('show');
            $('#test-connection-loading').show();
            $('#test-connection-result').empty();

            // Make AJAX request to test connection
            $.ajax({
                url: '<?php echo e(route("admin.unipayment.test-connection")); ?>',
                method: 'POST',
                data: {
                    app_id: appId,
                    api_key: apiKey === '••••••••••••••••' ? '' : apiKey,
                    environment: environment,
                    _token: '<?php echo e(csrf_token()); ?>'
                },
                success: function(response) {
                    $('#test-connection-loading').hide();

                    if (response.success) {
                        $('#test-connection-result').html(`
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle mr-2"></i>
                            <strong>Connection Successful!</strong><br>
                            ${response.message}
                        </div>
                    `);
                        updateConnectionStatus('success', 'Connected');
                    } else {
                        $('#test-connection-result').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <strong>Connection Failed</strong><br>
                            ${response.message}
                        </div>
                    `);
                        updateConnectionStatus('error', 'Failed');
                    }
                },
                error: function(xhr) {
                    $('#test-connection-loading').hide();

                    let errorMessage = 'An error occurred while testing the connection.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    $('#test-connection-result').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <strong>Connection Error</strong><br>
                        ${errorMessage}
                    </div>
                `);
                    updateConnectionStatus('error', 'Error');
                }
            });
        });

        // Update connection status display
        function updateConnectionStatus(status, text) {
            const statusElement = $('#connection-status span');
            statusElement.removeClass('badge-secondary badge-success badge-danger badge-warning');

            switch (status) {
                case 'success':
                    statusElement.addClass('badge-success');
                    statusElement.html('<i class="fas fa-check-circle mr-1"></i>' + text);
                    break;
                case 'error':
                    statusElement.addClass('badge-danger');
                    statusElement.html('<i class="fas fa-times-circle mr-1"></i>' + text);
                    break;
                case 'warning':
                    statusElement.addClass('badge-warning');
                    statusElement.html('<i class="fas fa-exclamation-triangle mr-1"></i>' + text);
                    break;
                default:
                    statusElement.addClass('badge-secondary');
                    statusElement.html('<i class="fas fa-question-circle mr-1"></i>' + text);
            }
        }

        // Check connection status on page load
        function checkConnectionStatus() {
            $.ajax({
                url: '<?php echo e(route("admin.unipayment.connection-status")); ?>',
                method: 'GET',
                success: function(response) {
                    if (response.configured) {
                        if (response.success) {
                            updateConnectionStatus('success', 'Connected');
                        } else {
                            updateConnectionStatus('error', 'Failed');
                        }
                    } else {
                        updateConnectionStatus('warning', 'Not Configured');
                    }
                },
                error: function() {
                    updateConnectionStatus('error', 'Error');
                }
            });
        }

        // Check status on page load
        checkConnectionStatus();
        checkWebhookStatus();

        // Generate webhook URL functionality
        $('#generate-webhook-url-btn').click(function() {
            const button = $(this);
            const originalHtml = button.html();

            button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.ajax({
                url: '<?php echo e(route("admin.unipayment.generate-webhook-url")); ?>',
                method: 'GET',
                success: function(response) {
                    if (response.success && response.webhook_url) {
                        $('#webhook_url').val(response.webhook_url);
                        $('#current-webhook-url').text(response.webhook_url);

                        // Show success message
                        showWebhookMessage('success', 'Webhook URL generated successfully!');
                    } else {
                        showWebhookMessage('error', response.message || 'Failed to generate webhook URL');
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Failed to generate webhook URL';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    showWebhookMessage('error', errorMessage);
                },
                complete: function() {
                    button.prop('disabled', false).html(originalHtml);
                }
            });
        });

        // Test webhook URL functionality
        $('#test-webhook-btn').click(function() {
            const webhookUrl = $('#webhook_url').val();
            const button = $(this);
            const originalHtml = button.html();

            button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.ajax({
                url: '<?php echo e(route("admin.unipayment.test-webhook")); ?>',
                method: 'POST',
                data: {
                    webhook_url: webhookUrl,
                    _token: '<?php echo e(csrf_token()); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        updateWebhookStatus('success', 'Accessible');
                        showWebhookMessage('success', response.message);
                    } else {
                        updateWebhookStatus('error', 'Failed');
                        showWebhookMessage('error', response.message);
                    }

                    if (response.tested_at) {
                        $('#webhook-last-test').text('Last tested: ' + new Date(response.tested_at).toLocaleString());
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Webhook test failed';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    updateWebhookStatus('error', 'Error');
                    showWebhookMessage('error', errorMessage);
                },
                complete: function() {
                    button.prop('disabled', false).html(originalHtml);
                }
            });
        });

        // Update webhook URL display when input changes
        $('#webhook_url').on('input', function() {
            const url = $(this).val();
            if (url) {
                $('#current-webhook-url').text(url);
            } else {
                // Reset to default URL
                $('#current-webhook-url').text('<?php echo e(route("payment.unipayment.webhook")); ?>');
            }
        });

        // Check webhook status on page load
        function checkWebhookStatus() {
            $.ajax({
                url: '<?php echo e(route("admin.unipayment.webhook-status")); ?>',
                method: 'GET',
                success: function(response) {
                    if (response.success && response.status) {
                        const status = response.status;

                        if (status.enabled && status.configured) {
                            if (status.test_status === 'success') {
                                updateWebhookStatus('success', 'Configured');
                            } else if (status.test_status === 'failed') {
                                updateWebhookStatus('error', 'Failed');
                            } else {
                                updateWebhookStatus('warning', 'Untested');
                            }
                        } else if (!status.enabled) {
                            updateWebhookStatus('warning', 'Disabled');
                        } else {
                            updateWebhookStatus('warning', 'Not Configured');
                        }

                        if (status.last_test) {
                            $('#webhook-last-test').text('Last tested: ' + new Date(status.last_test).toLocaleString());
                        }

                        if (status.url) {
                            $('#current-webhook-url').text(status.url);
                        }
                    }
                },
                error: function() {
                    updateWebhookStatus('error', 'Error');
                }
            });
        }

        // Update webhook status display
        function updateWebhookStatus(status, text) {
            const statusElement = $('#webhook-status-display span');
            statusElement.removeClass('badge-secondary badge-success badge-danger badge-warning');

            switch (status) {
                case 'success':
                    statusElement.addClass('badge-success');
                    statusElement.html('<i class="fas fa-check-circle mr-1"></i>' + text);
                    break;
                case 'error':
                    statusElement.addClass('badge-danger');
                    statusElement.html('<i class="fas fa-times-circle mr-1"></i>' + text);
                    break;
                case 'warning':
                    statusElement.addClass('badge-warning');
                    statusElement.html('<i class="fas fa-exclamation-triangle mr-1"></i>' + text);
                    break;
                default:
                    statusElement.addClass('badge-secondary');
                    statusElement.html('<i class="fas fa-question-circle mr-1"></i>' + text);
            }
        }

        // Show webhook message
        function showWebhookMessage(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show mt-2" role="alert">
                    <i class="fas ${iconClass} mr-2"></i>
                    ${message}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            `;

            // Remove existing webhook messages
            $('#webhook-status-card').find('.alert').remove();

            // Add new message
            $('#webhook-status-card .card-body').append(alertHtml);

            // Auto-hide after 5 seconds
            setTimeout(function() {
                $('#webhook-status-card').find('.alert').fadeOut();
            }, 5000);
        }

        // Webhook Testing and Monitoring Functions

        // Load initial webhook health status
        loadWebhookHealth();
        loadWebhookMetrics();
        loadWebhookTrends();

        // Refresh health status
        $('#refresh-health-btn').click(function() {
            loadWebhookHealth();
        });

        // Test webhook accessibility
        $('#test-accessibility-btn').click(function() {
            const btn = $(this);
            const result = $('#accessibility-result');

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Testing...');
            result.empty();

            $.post('<?php echo e(route("admin.unipayment.test-webhook-accessibility")); ?>', {
                    _token: '<?php echo e(csrf_token()); ?>',
                    webhook_url: $('#webhook_url').val()
                })
                .done(function(response) {
                    const testResult = response.test_result;
                    const statusClass = testResult.accessible ? 'success' : 'danger';
                    const statusIcon = testResult.accessible ? 'check-circle' : 'times-circle';

                    result.html(`
                    <div class="alert alert-${statusClass}">
                        <i class="fas fa-${statusIcon} mr-2"></i>
                        <strong>URL:</strong> ${testResult.url}<br>
                        <strong>Status:</strong> ${testResult.accessible ? 'Accessible' : 'Not Accessible'}<br>
                        <strong>Response Time:</strong> ${testResult.response_time || 'N/A'}ms<br>
                        <strong>Status Code:</strong> ${testResult.status_code || 'N/A'}<br>
                        ${testResult.error ? `<strong>Error:</strong> ${testResult.error}` : ''}
                    </div>
                `);
                })
                .fail(function(xhr) {
                    result.html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        Test failed: ${xhr.responseJSON?.message || 'Unknown error'}
                    </div>
                `);
                })
                .always(function() {
                    btn.prop('disabled', false).html('<i class="fas fa-globe mr-1"></i> Test Accessibility');
                });
        });

        // Test webhook with payload
        $('#test-payload-btn').click(function() {
            const btn = $(this);
            const result = $('#payload-result');

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Sending...');
            result.empty();

            $.post('<?php echo e(route("admin.unipayment.test-webhook-payload")); ?>', {
                    _token: '<?php echo e(csrf_token()); ?>',
                    webhook_url: $('#webhook_url').val()
                })
                .done(function(response) {
                    const testResult = response.test_result;
                    const statusClass = testResult.success ? 'success' : 'warning';
                    const statusIcon = testResult.success ? 'check-circle' : 'exclamation-triangle';

                    result.html(`
                    <div class="alert alert-${statusClass}">
                        <i class="fas fa-${statusIcon} mr-2"></i>
                        <strong>URL:</strong> ${testResult.url}<br>
                        <strong>Success:</strong> ${testResult.success ? 'Yes' : 'No'}<br>
                        <strong>Response Time:</strong> ${testResult.response_time || 'N/A'}ms<br>
                        <strong>Status Code:</strong> ${testResult.status_code || 'N/A'}<br>
                        ${testResult.error ? `<strong>Error:</strong> ${testResult.error}` : ''}
                    </div>
                `);
                })
                .fail(function(xhr) {
                    result.html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        Test failed: ${xhr.responseJSON?.message || 'Unknown error'}
                    </div>
                `);
                })
                .always(function() {
                    btn.prop('disabled', false).html('<i class="fas fa-play mr-1"></i> Send Test Payload');
                });
        });

        // Run comprehensive diagnostics
        $('#run-diagnostics-btn').click(function() {
            const btn = $(this);
            const result = $('#diagnostics-result');

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Running...');
            result.empty();

            $.get('<?php echo e(route("admin.unipayment.webhook-diagnostics")); ?>')
                .done(function(response) {
                    const diagnostics = response.diagnostics;
                    let html = '<div class="card"><div class="card-body">';

                    html += `<h6>Environment: ${diagnostics.environment}</h6>`;
                    html += `<p><strong>App URL:</strong> ${diagnostics.app_url}</p>`;
                    html += `<p><strong>Webhook URL:</strong> ${diagnostics.webhook_url || 'Not generated'}</p>`;

                    if (diagnostics.url_accessibility) {
                        const accessible = diagnostics.url_accessibility.accessible;
                        html += `<p><strong>URL Accessible:</strong> <span class="badge badge-${accessible ? 'success' : 'danger'}">${accessible ? 'Yes' : 'No'}</span></p>`;
                    }

                    if (diagnostics.recommendations && diagnostics.recommendations.length > 0) {
                        html += '<h6>Recommendations:</h6><ul>';
                        diagnostics.recommendations.forEach(rec => {
                            html += `<li class="text-warning">${rec}</li>`;
                        });
                        html += '</ul>';
                    }

                    html += '</div></div>';
                    result.html(html);
                })
                .fail(function(xhr) {
                    result.html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        Diagnostics failed: ${xhr.responseJSON?.message || 'Unknown error'}
                    </div>
                `);
                })
                .always(function() {
                    btn.prop('disabled', false).html('<i class="fas fa-search mr-1"></i> Run Diagnostics');
                });
        });

        // Refresh metrics
        $('#refresh-metrics-btn').click(function() {
            loadWebhookMetrics();
        });

        // Refresh trends
        $('#refresh-trends-btn').click(function() {
            loadWebhookTrends();
        });

        // Reset counters
        $('#reset-counters-btn').click(function() {
            if (!confirm('Are you sure you want to reset all webhook monitoring counters?')) {
                return;
            }

            const btn = $(this);
            const result = $('#management-result');

            btn.prop('disabled', true);

            $.post('<?php echo e(route("admin.unipayment.reset-webhook-counters")); ?>', {
                    _token: '<?php echo e(csrf_token()); ?>'
                })
                .done(function(response) {
                    result.html(`
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i>
                        ${response.message}
                    </div>
                `);
                    loadWebhookMetrics();
                    loadWebhookTrends();
                })
                .fail(function(xhr) {
                    result.html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        ${xhr.responseJSON?.message || 'Reset failed'}
                    </div>
                `);
                })
                .always(function() {
                    btn.prop('disabled', false);
                });
        });

        // Clear test cache
        $('#clear-cache-btn').click(function() {
            const btn = $(this);
            const result = $('#management-result');

            btn.prop('disabled', true);

            $.post('<?php echo e(route("admin.unipayment.clear-webhook-test-cache")); ?>', {
                    _token: '<?php echo e(csrf_token()); ?>'
                })
                .done(function(response) {
                    result.html(`
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i>
                        ${response.message}
                    </div>
                `);
                })
                .fail(function(xhr) {
                    result.html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        ${xhr.responseJSON?.message || 'Clear cache failed'}
                    </div>
                `);
                })
                .always(function() {
                    btn.prop('disabled', false);
                });
        });

        // Helper functions
        function loadWebhookHealth() {
            $.get('<?php echo e(route("admin.unipayment.webhook-health")); ?>')
                .done(function(response) {
                    const health = response.health;
                    const statusClass = health.status === 'healthy' ? 'success' :
                        health.status === 'warning' ? 'warning' : 'danger';
                    const statusIcon = health.status === 'healthy' ? 'check-circle' :
                        health.status === 'warning' ? 'exclamation-triangle' : 'times-circle';

                    let html = `<span class="badge badge-${statusClass}">
                    <i class="fas fa-${statusIcon} mr-1"></i>
                    ${health.status.toUpperCase()}
                </span>`;

                    if (health.issues && health.issues.length > 0) {
                        html += '<ul class="mt-2 mb-0">';
                        health.issues.forEach(issue => {
                            html += `<li class="text-${statusClass}">${issue}</li>`;
                        });
                        html += '</ul>';
                    }

                    $('#webhook-health-display').html(html);
                })
                .fail(function() {
                    $('#webhook-health-display').html(`
                    <span class="badge badge-danger">
                        <i class="fas fa-times-circle mr-1"></i>
                        ERROR
                    </span>
                `);
                });
        }

        function loadWebhookMetrics() {
            $.get('<?php echo e(route("admin.unipayment.webhook-metrics")); ?>')
                .done(function(response) {
                    const metrics = response.metrics;

                    let html = `
                    <div class="row text-center">
                        <div class="col-4">
                            <h4 class="text-primary">${metrics.total_events}</h4>
                            <small>Total Events</small>
                        </div>
                        <div class="col-4">
                            <h4 class="text-success">${metrics.successful_events}</h4>
                            <small>Successful</small>
                        </div>
                        <div class="col-4">
                            <h4 class="text-danger">${metrics.failed_events}</h4>
                            <small>Failed</small>
                        </div>
                    </div>
                    <hr>
                    <p><strong>Error Rate:</strong> ${metrics.error_rate}%</p>
                    <p><strong>Avg Processing Time:</strong> ${metrics.average_processing_time}ms</p>
                    ${metrics.last_event_at ? `<p><strong>Last Event:</strong> ${new Date(metrics.last_event_at).toLocaleString()}</p>` : ''}
                `;

                    $('#webhook-metrics-display').html(html);
                })
                .fail(function() {
                    $('#webhook-metrics-display').html('<p class="text-danger">Failed to load metrics</p>');
                });
        }

        function loadWebhookTrends() {
            $.get('<?php echo e(route("admin.unipayment.webhook-trends")); ?>')
                .done(function(response) {
                    const trends = response.trends;

                    let html = '<div class="text-center">';
                    html += `<p><strong>Period:</strong> ${trends.period_days} days</p>`;

                    if (trends.daily_counts && Object.keys(trends.daily_counts).length > 0) {
                        html += '<small>Daily Activity:</small><br>';
                        Object.entries(trends.daily_counts).forEach(([date, counts]) => {
                            html += `<span class="badge badge-light mr-1">${date}: ${counts.total}</span>`;
                        });
                    } else {
                        html += '<p class="text-muted">No trend data available</p>';
                    }

                    html += '</div>';
                    $('#webhook-trends-display').html(html);
                })
                .fail(function() {
                    $('#webhook-trends-display').html('<p class="text-danger">Failed to load trends</p>');
                });
        }
    });
</script>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .badge-lg {
        font-size: 0.9em;
        padding: 0.5em 0.75em;
    }

    .connection-status {
        display: inline-block;
    }

    .card-tools {
        display: flex;
        align-items: center;
    }

    .form-label {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .bg-light .card-body {
        padding: 1rem;
    }

    .bg-light .card-title {
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .bg-light .card-text code {
        font-size: 0.85rem;
        word-break: break-all;
    }

    hr {
        border-top: 1px solid #dee2e6;
    }

    .text-primary {
        color: #007bff !important;
    }

    .custom-control-label {
        font-weight: 500;
    }
</style>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/Apple/Desktop/dev_folder/Dev_project/test.kiro2/leadership-summit-laravel/resources/views/admin/unipayment/index.blade.php ENDPATH**/ ?>