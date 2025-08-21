@extends('layouts.app')

@section('title', 'Edit Profile - Leadership Summit')
@section('meta_description', 'Update your profile information and account settings for the Leadership Summit.')

@section('breadcrumbs')
<li class="breadcrumb-item">
    <a href="{{ route('profile.show') }}">Profile</a>
</li>
<li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@push('styles')
<style>
    .edit-profile-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, #1e3a8a 100%);
        color: white;
        padding: 3rem 0;
        text-align: center;
    }

    .edit-profile-content {
        padding: 3rem 0;
    }

    .form-card {
        background: white;
        border-radius: 1rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        padding: 2rem;
        margin-bottom: 2rem;
        border: 1px solid #f1f5f9;
    }

    .form-card h3 {
        color: var(--primary-color);
        font-weight: 600;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #f1f5f9;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        font-weight: 600;
        color: var(--dark-gray);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-control {
        border: 2px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
    }

    .btn-save {
        background: var(--primary-color);
        border: none;
        padding: 0.75rem 2rem;
        border-radius: 0.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-save:hover {
        background: #1d4ed8;
        transform: translateY(-2px);
    }

    .btn-cancel {
        background: #6b7280;
        border: none;
        padding: 0.75rem 2rem;
        border-radius: 0.5rem;
        font-weight: 600;
        color: white;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
    }

    .btn-cancel:hover {
        background: #4b5563;
        color: white;
        transform: translateY(-2px);
    }

    .password-requirements {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-top: 0.5rem;
        font-size: 0.9rem;
    }

    .password-requirements ul {
        margin: 0;
        padding-left: 1.5rem;
    }

    .password-requirements li {
        margin-bottom: 0.25rem;
        color: var(--dark-gray);
    }

    @media (max-width: 768px) {
        .edit-profile-header {
            padding: 2rem 0;
        }

        .form-card {
            padding: 1.5rem;
        }
    }
</style>
@endpush

@section('content')
<!-- Edit Profile Header -->
<section class="edit-profile-header">
    <div class="container">
        <h1>Edit Profile</h1>
        <p class="mb-0">Update your account information and settings</p>
    </div>
</section>

<!-- Edit Profile Content -->
<section class="edit-profile-content">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Profile Information Form -->
                <div class="form-card">
                    <h3>Profile Information</h3>

                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PATCH')

                        <div class="form-group">
                            <label for="name" class="form-label">
                                <i class="fas fa-user" aria-hidden="true"></i>
                                Full Name
                            </label>
                            <input id="name" type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                name="name"
                                value="{{ old('name', $user->name) }}"
                                required
                                autocomplete="name"
                                autofocus>
                            @error('name')
                            <div class="invalid-feedback">
                                <strong>{{ $message }}</strong>
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope" aria-hidden="true"></i>
                                Email Address
                            </label>
                            <input id="email" type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                name="email"
                                value="{{ old('email', $user->email) }}"
                                required
                                autocomplete="email">
                            @error('email')
                            <div class="invalid-feedback">
                                <strong>{{ $message }}</strong>
                            </div>
                            @enderror
                        </div>

                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-primary btn-save">
                                <i class="fas fa-save me-2" aria-hidden="true"></i>Save Changes
                            </button>
                            <a href="{{ route('profile.show') }}" class="btn-cancel">
                                <i class="fas fa-times me-2" aria-hidden="true"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Change Password Form -->
                <div class="form-card">
                    <h3>Change Password</h3>

                    <form method="POST" action="{{ route('profile.password') }}">
                        @csrf
                        @method('PATCH')

                        <div class="form-group">
                            <label for="current_password" class="form-label">
                                <i class="fas fa-lock" aria-hidden="true"></i>
                                Current Password
                            </label>
                            <input id="current_password" type="password"
                                class="form-control @error('current_password') is-invalid @enderror"
                                name="current_password"
                                required
                                autocomplete="current-password">
                            @error('current_password')
                            <div class="invalid-feedback">
                                <strong>{{ $message }}</strong>
                            </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password" class="form-label">
                                <i class="fas fa-key" aria-hidden="true"></i>
                                New Password
                            </label>
                            <input id="password" type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                name="password"
                                required
                                autocomplete="new-password">
                            @error('password')
                            <div class="invalid-feedback">
                                <strong>{{ $message }}</strong>
                            </div>
                            @enderror
                            <div class="password-requirements">
                                <strong>Password Requirements:</strong>
                                <ul>
                                    <li>At least 8 characters long</li>
                                    <li>Contains at least one uppercase letter</li>
                                    <li>Contains at least one lowercase letter</li>
                                    <li>Contains at least one number</li>
                                </ul>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation" class="form-label">
                                <i class="fas fa-key" aria-hidden="true"></i>
                                Confirm New Password
                            </label>
                            <input id="password_confirmation" type="password"
                                class="form-control"
                                name="password_confirmation"
                                required
                                autocomplete="new-password">
                        </div>

                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-primary btn-save">
                                <i class="fas fa-key me-2" aria-hidden="true"></i>Update Password
                            </button>
                            <a href="{{ route('profile.show') }}" class="btn-cancel">
                                <i class="fas fa-times me-2" aria-hidden="true"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Account Information -->
                <div class="form-card">
                    <h3>Account Information</h3>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <strong>Member Since:</strong><br>
                                <span class="text-muted">{{ $user->created_at->format('F d, Y') }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <strong>Last Updated:</strong><br>
                                <span class="text-muted">{{ $user->updated_at->format('F d, Y g:i A') }}</span>
                            </div>
                        </div>
                    </div>

                    @if($user->role)
                    <div class="mt-3">
                        <strong>Account Role:</strong><br>
                        <span class="badge bg-primary">{{ ucfirst($user->role->name) }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    // Password strength indicator
    document.getElementById('password').addEventListener('input', function() {
        const password = this.value;
        const requirements = document.querySelectorAll('.password-requirements li');

        // Check each requirement
        const checks = [
            password.length >= 8,
            /[A-Z]/.test(password),
            /[a-z]/.test(password),
            /\d/.test(password)
        ];

        requirements.forEach((req, index) => {
            if (checks[index]) {
                req.style.color = '#10b981';
                req.innerHTML = '✓ ' + req.textContent.replace('✓ ', '');
            } else {
                req.style.color = '#6b7280';
                req.innerHTML = req.textContent.replace('✓ ', '');
            }
        });
    });
</script>
@endpush