@php
$navItems = [
['url' => '/about', 'label' => 'About', 'icon' => 'fas fa-info-circle', 'pattern' => 'about*'],
['url' => '/speakers', 'label' => 'Speakers', 'icon' => 'fas fa-users', 'pattern' => 'speakers*'],
['url' => '/events', 'label' => 'Events', 'icon' => 'fas fa-calendar-alt', 'pattern' => 'events*'],
['url' => '/agenda', 'label' => 'Agenda', 'icon' => 'fas fa-list-ul', 'pattern' => 'agenda*'],
['url' => '/contact', 'label' => 'Contact', 'icon' => 'fas fa-envelope', 'pattern' => 'contact*'],
];
@endphp

<ul class="navbar-nav ms-auto mb-2 mb-lg-0 nav-spaced">
    @foreach($navItems as $item)
    <li class="nav-item">
        <a class="nav-link {{ request()->is($item['pattern']) ? 'active' : '' }}"
            href="{{ url($item['url']) }}"
            @if(request()->is($item['pattern'])) aria-current="page" @endif>
            <i class="{{ $item['icon'] }} d-lg-none me-2" aria-hidden="true"></i>{{ $item['label'] }}
        </a>
    </li>
    @endforeach

    @auth
    @if(auth()->user()->role && auth()->user()->role->name === 'admin')
    <li class="nav-item dropdown">
        <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-user-shield d-lg-none me-2" aria-hidden="true"></i>
            <span class="d-none d-lg-inline">Admin User</span>
            <span class="d-lg-none">Admin</span>
        </a>
        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
            <h6 class="dropdown-header text-primary">
                <i class="fas fa-crown me-1"></i>{{ Auth::user()->name }}
            </h6>
            <div class="dropdown-divider"></div>

            <!-- Admin Dashboard Section -->
            <h6 class="dropdown-header text-muted small">ADMIN DASHBOARD</h6>
            <a class="dropdown-item" href="{{ url('/admin') }}">
                <i class="fas fa-tachometer-alt me-2 text-primary" aria-hidden="true"></i>Dashboard
            </a>
            <a class="dropdown-item" href="{{ route('admin.events.create') }}">
                <i class="fas fa-plus-circle me-2 text-success" aria-hidden="true"></i>Add Event
            </a>
            <a class="dropdown-item" href="{{ route('admin.speakers.create') }}">
                <i class="fas fa-user-plus me-2 text-info" aria-hidden="true"></i>Add Speaker
            </a>
            <div class="dropdown-divider"></div>

            <!-- Admin Management Section -->
            <h6 class="dropdown-header text-muted small">MANAGEMENT</h6>
            <a class="dropdown-item" href="{{ route('admin.events.index') }}">
                <i class="fas fa-calendar-alt me-2" aria-hidden="true"></i>All Events
            </a>
            <a class="dropdown-item" href="{{ route('admin.speakers.index') }}">
                <i class="fas fa-users me-2" aria-hidden="true"></i>All Speakers
            </a>
            <a class="dropdown-item" href="{{ route('admin.registrations.index') }}">
                <i class="fas fa-ticket-alt me-2" aria-hidden="true"></i>Registrations
            </a>
            <a class="dropdown-item" href="{{ route('admin.payments.pending') }}">
                <i class="fas fa-credit-card me-2" aria-hidden="true"></i>Payments
            </a>
            <a class="dropdown-item" href="{{ route('admin.users.index') }}">
                <i class="fas fa-users-cog me-2" aria-hidden="true"></i>Users
            </a>
            <div class="dropdown-divider"></div>

            <!-- Personal Section -->
            <h6 class="dropdown-header text-muted small">PERSONAL</h6>
            <a class="dropdown-item" href="{{ route('profile.show') }}">
                <i class="fas fa-user-edit me-2" aria-hidden="true"></i>Profile
            </a>
            <div class="dropdown-divider"></div>

            <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                <i class="fas fa-sign-out-alt me-2" aria-hidden="true"></i>Logout
            </a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>
        </div>
    </li>
    @endif
    @endauth
</ul>