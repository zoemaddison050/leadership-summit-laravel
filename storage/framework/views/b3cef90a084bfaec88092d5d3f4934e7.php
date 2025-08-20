<header id="masthead" class="site-header" role="banner">
    <nav class="navbar navbar-expand-lg navbar-light" role="navigation" aria-label="Main Navigation">
        <div class="container">
            <div class="site-branding navbar-brand">
                <div class="logo-container">
                    <i class="fas fa-crown text-primary" style="font-size: 2rem;" aria-label="Leadership Summit Logo"></i>
                </div>
            </div>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
                <span class="sr-only">Menu</span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <?php echo $__env->make('components.navigation', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <div class="header-cta d-none d-lg-flex ms-3">
                    <?php
                    $defaultEvent = \App\Models\Event::where('is_default', true)->first() ?? \App\Models\Event::first();
                    ?>
                    <?php if($defaultEvent): ?>
                    <a href="<?php echo e(route('events.show', $defaultEvent->slug)); ?>" class="btn btn-primary registration-btn">
                        <i class="fas fa-ticket-alt me-2" aria-hidden="true"></i>Register Now
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile CTA Button (visible only on mobile) -->
    <div class="container">
        <div class="mobile-cta d-lg-none mt-2 mb-2">
            <?php
            $defaultEvent = \App\Models\Event::where('is_default', true)->first() ?? \App\Models\Event::first();
            ?>
            <?php if($defaultEvent): ?>
            <a href="<?php echo e(route('events.show', $defaultEvent->slug)); ?>" class="btn btn-primary registration-btn w-100">
                <i class="fas fa-ticket-alt me-2" aria-hidden="true"></i>Register Now
            </a>
            <?php endif; ?>
        </div>
    </div>
</header><?php /**PATH /Users/Apple/Desktop/dev_folder/Dev_project/test.kiro2/leadership-summit-laravel/resources/views/components/header.blade.php ENDPATH**/ ?>