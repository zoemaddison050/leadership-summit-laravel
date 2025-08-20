<?php
$navItems = [
['url' => '/', 'label' => 'Home', 'icon' => 'fas fa-home', 'pattern' => '/'],
['url' => '/about', 'label' => 'About', 'icon' => 'fas fa-info-circle', 'pattern' => 'about*'],
['url' => '/speakers', 'label' => 'Speakers', 'icon' => 'fas fa-users', 'pattern' => 'speakers*'],
['url' => '/events', 'label' => 'Events', 'icon' => 'fas fa-calendar-alt', 'pattern' => 'events*'],
['url' => '/agenda', 'label' => 'Agenda', 'icon' => 'fas fa-list-ul', 'pattern' => 'agenda*'],
['url' => '/contact', 'label' => 'Contact', 'icon' => 'fas fa-envelope', 'pattern' => 'contact*'],
];
?>

<ul class="navbar-nav ms-auto mb-2 mb-lg-0">
    <?php $__currentLoopData = $navItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <li class="nav-item">
        <a class="nav-link <?php echo e(request()->is($item['pattern']) ? 'active' : ''); ?>"
            href="<?php echo e(url($item['url'])); ?>"
            <?php if(request()->is($item['pattern'])): ?> aria-current="page" <?php endif; ?>>
            <i class="<?php echo e($item['icon']); ?> d-lg-none me-2" aria-hidden="true"></i><?php echo e($item['label']); ?>

        </a>
    </li>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <?php if(auth()->guard()->check()): ?>
    <?php if(auth()->user()->role && auth()->user()->role->name === 'admin'): ?>
    <li class="nav-item">
        <a class="nav-link <?php echo e(request()->is('admin*') ? 'active' : ''); ?>"
            href="<?php echo e(url('/admin')); ?>"
            <?php if(request()->is('admin*')): ?> aria-current="page" <?php endif; ?>>
            <i class="fas fa-cog d-lg-none me-2" aria-hidden="true"></i>Admin
        </a>
    </li>
    <?php endif; ?>
    <li class="nav-item dropdown">
        <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-user d-lg-none me-2" aria-hidden="true"></i>
            <span class="d-none d-lg-inline"><?php echo e(Auth::user()->name); ?></span>
            <span class="d-lg-none">Account</span>
        </a>
        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
            <h6 class="dropdown-header"><?php echo e(Auth::user()->name); ?></h6>
            <a class="dropdown-item" href="<?php echo e(url('/dashboard')); ?>">
                <i class="fas fa-tachometer-alt me-2" aria-hidden="true"></i>Dashboard
            </a>
            <a class="dropdown-item" href="<?php echo e(route('profile.show')); ?>">
                <i class="fas fa-user-edit me-2" aria-hidden="true"></i>Profile
            </a>
            <a class="dropdown-item" href="<?php echo e(route('registrations.index')); ?>">
                <i class="fas fa-ticket-alt me-2" aria-hidden="true"></i>My Registrations
            </a>
            <a class="dropdown-item" href="<?php echo e(route('orders.index')); ?>">
                <i class="fas fa-shopping-cart me-2" aria-hidden="true"></i>My Orders
            </a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="<?php echo e(route('logout')); ?>"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="fas fa-sign-out-alt me-2" aria-hidden="true"></i>Logout
            </a>
            <form id="logout-form" action="<?php echo e(route('logout')); ?>" method="POST" class="d-none">
                <?php echo csrf_field(); ?>
            </form>
        </div>
    </li>
    <?php else: ?>
    <!-- No login/register links for regular users - they should use event registration -->
    <?php endif; ?>
</ul><?php /**PATH /Users/Apple/Desktop/dev_folder/Dev_project/test.kiro2/leadership-summit-laravel/resources/views/components/navigation.blade.php ENDPATH**/ ?>