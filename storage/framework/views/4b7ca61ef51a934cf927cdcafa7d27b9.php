<!doctype html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="profile" href="https://gmpg.org/xfn/11">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <!-- SEO Meta Tags -->
    <title><?php echo $__env->yieldContent('title', config('app.name', 'Leadership Summit')); ?></title>
    <meta name="description" content="<?php echo $__env->yieldContent('meta_description', 'Join us for the exclusive International Global Leadership Academy Summit in September 2025, Cypress.'); ?>">
    <meta name="keywords" content="<?php echo $__env->yieldContent('meta_keywords', 'leadership, summit, conference, global, academy, professional development'); ?>">
    <meta name="author" content="International Global Leadership Academy">

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo $__env->yieldContent('og_title', config('app.name', 'Leadership Summit')); ?>">
    <meta property="og:description" content="<?php echo $__env->yieldContent('og_description', 'Join us for the exclusive International Global Leadership Academy Summit in September 2025, Cypress.'); ?>">
    <meta property="og:image" content="<?php echo $__env->yieldContent('og_image', asset('images/og-image.jpg')); ?>">
    <meta property="og:url" content="<?php echo e(url()->current()); ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo e(config('app.name', 'Leadership Summit')); ?>">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $__env->yieldContent('twitter_title', config('app.name', 'Leadership Summit')); ?>">
    <meta name="twitter:description" content="<?php echo $__env->yieldContent('twitter_description', 'Join us for the exclusive International Global Leadership Academy Summit in September 2025, Cypress.'); ?>">
    <meta name="twitter:image" content="<?php echo $__env->yieldContent('twitter_image', asset('images/og-image.jpg')); ?>">

    <!-- Canonical URL -->
    <link rel="canonical" href="<?php echo e(url()->current()); ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo e(asset('images/favicon.svg')); ?>">
    <link rel="icon" type="image/png" href="<?php echo e(asset('images/favicon.png')); ?>">
    <link rel="apple-touch-icon" href="<?php echo e(asset('images/apple-touch-icon.png')); ?>">

    <!-- Preconnect to external domains -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer">

    <!-- Theme Color -->
    <meta name="theme-color" content="#0a2463">
    <meta name="msapplication-TileColor" content="#0a2463">

    <!-- Scripts -->
    <?php echo app('Illuminate\Foundation\Vite')(['resources/sass/app.scss', 'resources/js/app.js']); ?>

    <!-- Livewire Styles -->
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>


    <?php echo $__env->yieldPushContent('styles'); ?>

    <!-- Structured Data -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "<?php echo e(config('app.name', 'Leadership Summit')); ?>",
            "url": "<?php echo e(url('/')); ?>",
            "logo": "<?php echo e(asset('images/logo.svg')); ?>",
            "description": "International Global Leadership Academy Summit - Connect with global leaders and visionaries to shape the future of leadership.",
            "contactPoint": {
                "@type": "ContactPoint",
                "telephone": "+1-555-123-4567",
                "contactType": "customer service",
                "email": "info@leadershipacademy.org"
            }
        }
    </script>
</head>

<body class="<?php echo $__env->yieldContent('body_class'); ?>" data-bs-spy="scroll" data-bs-target="#navbar" data-bs-offset="70">
    <!-- Loading Spinner -->
    <div id="loading-spinner" class="loading-spinner" aria-hidden="true">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Skip Navigation Links -->
    <div class="skip-links">
        <a class="skip-link screen-reader-text" href="#primary">Skip to main content</a>
        <a class="skip-link screen-reader-text" href="#masthead">Skip to navigation</a>
        <a class="skip-link screen-reader-text" href="#colophon">Skip to footer</a>
    </div>

    <div id="page" class="site">
        <?php echo $__env->make('components.header', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <!-- Breadcrumb Navigation -->
        <?php if(!request()->is('/') && !request()->is('events/*')): ?>
        <nav aria-label="Breadcrumb" class="breadcrumb-nav">
            <div class="container">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="<?php echo e(url('/')); ?>">
                            <i class="fas fa-home" aria-hidden="true"></i>
                            <span class="visually-hidden">Home</span>
                        </a>
                    </li>
                    <?php echo $__env->yieldContent('breadcrumbs'); ?>
                </ol>
            </div>
        </nav>
        <?php endif; ?>

        <main id="primary" class="site-main" role="main">
            <!-- Flash Messages -->
            <?php if(session('success')): ?>
            <div class="container">
                <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                    <i class="fas fa-check-circle me-2" aria-hidden="true"></i>
                    <?php echo e(session('success')); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
            <?php endif; ?>

            <?php if(session('error')): ?>
            <div class="container">
                <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                    <i class="fas fa-exclamation-triangle me-2" aria-hidden="true"></i>
                    <?php echo e(session('error')); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
            <?php endif; ?>

            <?php if(session('warning')): ?>
            <div class="container">
                <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
                    <i class="fas fa-exclamation-circle me-2" aria-hidden="true"></i>
                    <?php echo e(session('warning')); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
            <?php endif; ?>

            <?php if(session('info')): ?>
            <div class="container">
                <div class="alert alert-info alert-dismissible fade show mt-3" role="alert">
                    <i class="fas fa-info-circle me-2" aria-hidden="true"></i>
                    <?php echo e(session('info')); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
            <?php endif; ?>

            <?php echo $__env->yieldContent('content'); ?>
        </main>

        <?php echo $__env->make('components.footer', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>

    <!-- Back to Top Button -->
    <button id="back-to-top" class="btn btn-primary back-to-top" aria-label="Back to top" title="Back to top">
        <i class="fas fa-chevron-up" aria-hidden="true"></i>
    </button>

    <?php echo $__env->yieldPushContent('scripts'); ?>

    <!-- Main JavaScript -->
    <script>
        // Loading spinner
        window.addEventListener('load', function() {
            const spinner = document.getElementById('loading-spinner');
            if (spinner) {
                spinner.style.display = 'none';
            }
        });

        // Back to top button
        const backToTopButton = document.getElementById('back-to-top');
        if (backToTopButton) {
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    backToTopButton.classList.add('show');
                } else {
                    backToTopButton.classList.remove('show');
                }
            });

            backToTopButton.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }

        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>

</html><?php /**PATH /Users/Apple/Desktop/dev_folder/Dev_project/test.kiro2/leadership-summit-laravel/resources/views/layouts/app.blade.php ENDPATH**/ ?>