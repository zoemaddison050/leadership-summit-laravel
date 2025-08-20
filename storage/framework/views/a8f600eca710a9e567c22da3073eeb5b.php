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
    <title><?php echo $__env->yieldContent('title', 'Admin Dashboard'); ?> - <?php echo e(config('app.name', 'Leadership Summit')); ?></title>
    <meta name="description" content="Admin dashboard for <?php echo e(config('app.name', 'Leadership Summit')); ?>">
    <meta name="robots" content="noindex, nofollow">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo e(asset('images/favicon.svg')); ?>">
    <link rel="icon" type="image/png" href="<?php echo e(asset('images/favicon.png')); ?>">

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

    <!-- Scripts -->
    <?php echo app('Illuminate\Foundation\Vite')(['resources/sass/app.scss', 'resources/js/app.js']); ?>

    <?php echo $__env->yieldPushContent('styles'); ?>

    <style>
        /* Admin-specific styles */
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        .admin-sidebar {
            width: 280px;
            background: var(--primary-color);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .admin-sidebar.collapsed {
            transform: translateX(-100%);
        }

        .admin-main {
            flex: 1;
            margin-left: 280px;
            background: #f8fafc;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        .admin-main.expanded {
            margin-left: 0;
        }

        .admin-header {
            background: white;
            padding: 1rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .admin-content {
            padding: 2rem;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-brand {
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-brand:hover {
            color: var(--secondary-color);
            text-decoration: none;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-section {
            margin-bottom: 2rem;
        }

        .nav-section-title {
            padding: 0.5rem 1.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 0.5rem;
        }

        .nav-item {
            margin-bottom: 0.25rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-link:hover,
        .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            text-decoration: none;
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--secondary-color);
        }

        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
            font-size: 1rem;
        }

        .nav-badge {
            background: var(--secondary-color);
            color: var(--primary-color);
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 50px;
            margin-left: auto;
            font-weight: 600;
        }

        .mobile-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--primary-color);
            font-size: 1.5rem;
            cursor: pointer;
        }

        .admin-breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 1rem;
        }

        .admin-breadcrumb .breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
        }

        .admin-breadcrumb .breadcrumb-item {
            font-size: 0.9rem;
        }

        .admin-breadcrumb .breadcrumb-item+.breadcrumb-item::before {
            content: ">";
            color: #6b7280;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }

        .page-actions {
            display: flex;
            gap: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--primary-color);
        }

        .stat-card.success {
            border-left-color: #10b981;
        }

        .stat-card.warning {
            border-left-color: #f59e0b;
        }

        .stat-card.danger {
            border-left-color: #ef4444;
        }

        .stat-card.info {
            border-left-color: #3b82f6;
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .stat-title {
            font-size: 0.9rem;
            color: #6b7280;
            font-weight: 500;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }

        .stat-icon.success {
            background: #10b981;
        }

        .stat-icon.warning {
            background: #f59e0b;
        }

        .stat-icon.danger {
            background: #ef4444;
        }

        .stat-icon.info {
            background: #3b82f6;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }

        .stat-change {
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .stat-change.positive {
            color: #10b981;
        }

        .stat-change.negative {
            color: #ef4444;
        }

        .stat-change.neutral {
            color: #6b7280;
        }

        .admin-card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .admin-card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }

        .admin-card-body {
            padding: 1.5rem;
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }

            .admin-sidebar.show {
                transform: translateX(0);
            }

            .admin-main {
                margin-left: 0;
            }

            .mobile-toggle {
                display: block;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="admin-body">
    <div class="admin-layout">
        <!-- Admin Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-header">
                <a href="<?php echo e(url('/admin')); ?>" class="sidebar-brand">
                    <i class="fas fa-cog" aria-hidden="true"></i>
                    Admin Panel
                </a>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <div class="nav-item">
                        <a href="<?php echo e(url('/admin')); ?>" class="nav-link <?php echo e(request()->is('admin') ? 'active' : ''); ?>">
                            <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
                            Dashboard
                        </a>
                    </div>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Events</div>
                    <div class="nav-item">
                        <a href="<?php echo e(url('/admin/events')); ?>" class="nav-link <?php echo e(request()->is('admin/events*') ? 'active' : ''); ?>">
                            <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                            All Events
                            <?php if(isset($pendingEventsCount) && $pendingEventsCount > 0): ?>
                            <span class="nav-badge"><?php echo e($pendingEventsCount); ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="<?php echo e(url('/admin/events/create')); ?>" class="nav-link <?php echo e(request()->is('admin/events/create') ? 'active' : ''); ?>">
                            <i class="fas fa-plus" aria-hidden="true"></i>
                            Add Event
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="<?php echo e(url('/admin/tickets')); ?>" class="nav-link <?php echo e(request()->is('admin/tickets*') ? 'active' : ''); ?>">
                            <i class="fas fa-ticket-alt" aria-hidden="true"></i>
                            Tickets
                        </a>
                    </div>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Speakers</div>
                    <div class="nav-item">
                        <a href="<?php echo e(url('/admin/speakers')); ?>" class="nav-link <?php echo e(request()->is('admin/speakers*') ? 'active' : ''); ?>">
                            <i class="fas fa-users" aria-hidden="true"></i>
                            All Speakers
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="<?php echo e(url('/admin/speakers/create')); ?>" class="nav-link <?php echo e(request()->is('admin/speakers/create') ? 'active' : ''); ?>">
                            <i class="fas fa-user-plus" aria-hidden="true"></i>
                            Add Speaker
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="<?php echo e(url('/admin/sessions')); ?>" class="nav-link <?php echo e(request()->is('admin/sessions*') ? 'active' : ''); ?>">
                            <i class="fas fa-presentation" aria-hidden="true"></i>
                            Sessions
                        </a>
                    </div>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Users</div>
                    <div class="nav-item">
                        <a href="<?php echo e(url('/admin/users')); ?>" class="nav-link <?php echo e(request()->is('admin/users*') ? 'active' : ''); ?>">
                            <i class="fas fa-users-cog" aria-hidden="true"></i>
                            All Users
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="<?php echo e(url('/admin/registrations')); ?>" class="nav-link <?php echo e(request()->is('admin/registrations*') ? 'active' : ''); ?>">
                            <i class="fas fa-clipboard-list" aria-hidden="true"></i>
                            Registrations
                            <?php if(isset($newRegistrationsCount) && $newRegistrationsCount > 0): ?>
                            <span class="nav-badge"><?php echo e($newRegistrationsCount); ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="<?php echo e(url('/admin/payments/pending')); ?>" class="nav-link <?php echo e(request()->is('admin/payments*') ? 'active' : ''); ?>">
                            <i class="fas fa-credit-card" aria-hidden="true"></i>
                            Payment Review
                            <?php if(isset($pendingPaymentsCount) && $pendingPaymentsCount > 0): ?>
                            <span class="nav-badge"><?php echo e($pendingPaymentsCount); ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="<?php echo e(url('/admin/roles')); ?>" class="nav-link <?php echo e(request()->is('admin/roles*') ? 'active' : ''); ?>">
                            <i class="fas fa-user-shield" aria-hidden="true"></i>
                            Roles & Permissions
                        </a>
                    </div>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Content</div>
                    <div class="nav-item">
                        <a href="<?php echo e(url('/admin/pages')); ?>" class="nav-link <?php echo e(request()->is('admin/pages*') ? 'active' : ''); ?>">
                            <i class="fas fa-file-alt" aria-hidden="true"></i>
                            Pages
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="<?php echo e(url('/admin/media')); ?>" class="nav-link <?php echo e(request()->is('admin/media*') ? 'active' : ''); ?>">
                            <i class="fas fa-images" aria-hidden="true"></i>
                            Media Library
                        </a>
                    </div>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">System</div>
                    <div class="nav-item">
                        <a href="<?php echo e(url('/admin/settings')); ?>" class="nav-link <?php echo e(request()->is('admin/settings*') ? 'active' : ''); ?>">
                            <i class="fas fa-cogs" aria-hidden="true"></i>
                            Settings
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="<?php echo e(url('/admin/unipayment')); ?>" class="nav-link <?php echo e(request()->is('admin/unipayment*') ? 'active' : ''); ?>">
                            <i class="fas fa-credit-card" aria-hidden="true"></i>
                            Card Payments
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="<?php echo e(url('/admin/wallet-settings')); ?>" class="nav-link <?php echo e(request()->is('admin/wallet-settings*') ? 'active' : ''); ?>">
                            <i class="fas fa-wallet" aria-hidden="true"></i>
                            Crypto Wallets
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="<?php echo e(url('/admin/reports')); ?>" class="nav-link <?php echo e(request()->is('admin/reports*') ? 'active' : ''); ?>">
                            <i class="fas fa-chart-bar" aria-hidden="true"></i>
                            Reports
                        </a>
                    </div>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Account</div>
                    <div class="nav-item">
                        <a href="<?php echo e(url('/')); ?>" class="nav-link" target="_blank">
                            <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                            View Site
                        </a>
                    </div>
                    <div class="nav-item">
                        <a href="<?php echo e(route('logout')); ?>" class="nav-link"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                            Logout
                        </a>
                        <form id="logout-form" action="<?php echo e(route('logout')); ?>" method="POST" class="d-none">
                            <?php echo csrf_field(); ?>
                        </form>
                    </div>
                </div>
            </nav>
        </aside>

        <!-- Admin Main Content -->
        <main class="admin-main" id="adminMain">
            <!-- Admin Header -->
            <header class="admin-header">
                <div class="d-flex align-items-center">
                    <button class="mobile-toggle me-3" id="sidebarToggle" aria-label="Toggle sidebar">
                        <i class="fas fa-bars" aria-hidden="true"></i>
                    </button>

                    <?php if(!request()->is('admin')): ?>
                    <nav class="admin-breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="<?php echo e(url('/admin')); ?>">Dashboard</a>
                            </li>
                            <?php echo $__env->yieldContent('breadcrumbs'); ?>
                        </ol>
                    </nav>
                    <?php endif; ?>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-2" aria-hidden="true"></i>
                            <?php echo e(auth()->user()->name); ?>

                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <h6 class="dropdown-header"><?php echo e(auth()->user()->name); ?></h6>
                            </li>
                            <li><a class="dropdown-item" href="<?php echo e(url('/profile')); ?>">
                                    <i class="fas fa-user-edit me-2" aria-hidden="true"></i>Profile
                                </a></li>
                            <li><a class="dropdown-item" href="<?php echo e(url('/admin/settings')); ?>">
                                    <i class="fas fa-cogs me-2" aria-hidden="true"></i>Settings
                                </a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="<?php echo e(route('logout')); ?>"
                                    onclick="event.preventDefault(); document.getElementById('logout-form-header').submit();">
                                    <i class="fas fa-sign-out-alt me-2" aria-hidden="true"></i>Logout
                                </a></li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Admin Content -->
            <div class="admin-content">
                <!-- Flash Messages -->
                <?php if(session('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2" aria-hidden="true"></i>
                    <?php echo e(session('success')); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if(session('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2" aria-hidden="true"></i>
                    <?php echo e(session('error')); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if(session('warning')): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2" aria-hidden="true"></i>
                    <?php echo e(session('warning')); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php echo $__env->yieldContent('content'); ?>
            </div>
        </main>
    </div>

    <?php echo $__env->yieldPushContent('scripts'); ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('adminSidebar');
            const main = document.getElementById('adminMain');

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    sidebar.classList.toggle('collapsed');
                    main.classList.toggle('expanded');
                });
            }

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768 &&
                    !sidebar.contains(e.target) &&
                    !sidebarToggle.contains(e.target) &&
                    sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                }
            });

            // Auto-dismiss alerts
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>

    <form id="logout-form-header" action="<?php echo e(route('logout')); ?>" method="POST" class="d-none">
        <?php echo csrf_field(); ?>
    </form>
</body>

</html><?php /**PATH /Users/Apple/Desktop/dev_folder/Dev_project/test.kiro2/leadership-summit-laravel/resources/views/layouts/admin.blade.php ENDPATH**/ ?>