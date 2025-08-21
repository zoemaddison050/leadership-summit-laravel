@extends('layouts.app')

@section('title', 'Sign In - Leadership Summit')

@push('styles')
<style>
    .auth-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        background: linear-gradient(135deg, #f8fafc 0%, #e3f2fd 100%);
        padding: 1rem 0;
    }

    .auth-card {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        width: 100%;
        max-width: 500px;
        margin: 0 auto;
    }

    .auth-header {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 2rem 1.5rem;
        text-align: center;
    }

    .auth-header h4 {
        margin: 0;
        font-weight: 600;
        font-size: 1.5rem;
    }

    .auth-header p {
        margin: 0.5rem 0 0 0;
        opacity: 0.9;
        font-size: 0.95rem;
    }

    .auth-body {
        padding: 2rem 1.5rem;
        background: white;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        display: block;
    }

    .form-control {
        border-radius: 0.5rem;
        border: 2px solid #e5e7eb;
        padding: 0.875rem 1rem;
        font-size: 1rem;
        width: 100%;
        transition: all 0.2s ease;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.2rem rgba(10, 36, 99, 0.15);
        outline: none;
    }

    .form-control.is-invalid {
        border-color: #dc3545;
    }

    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875rem;
        color: #dc3545;
    }

    .form-check {
        margin: 1rem 0;
    }

    .form-check-input {
        margin-right: 0.5rem;
    }

    .btn-auth {
        border-radius: 0.5rem;
        padding: 0.875rem 2rem;
        font-weight: 600;
        font-size: 1rem;
        width: 100%;
        margin-bottom: 1rem;
        transition: all 0.2s ease;
    }

    .btn-auth:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(10, 36, 99, 0.3);
    }

    .btn-link {
        color: var(--primary-color);
        text-decoration: none;
        font-size: 0.9rem;
    }

    .btn-link:hover {
        color: var(--primary-color);
        text-decoration: underline;
    }

    .auth-divider {
        margin: 1.5rem 0;
        text-align: center;
        position: relative;
    }

    .auth-divider::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background: #e5e7eb;
    }

    .auth-divider span {
        background: white;
        padding: 0 1rem;
        color: #6b7280;
        font-size: 0.9rem;
    }

    .auth-footer {
        text-align: center;
        padding-top: 1rem;
    }

    .btn-outline-auth {
        border: 2px solid var(--primary-color);
        color: var(--primary-color);
        background: transparent;
        border-radius: 0.5rem;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        transition: all 0.2s ease;
    }

    .btn-outline-auth:hover {
        background: var(--primary-color);
        color: white;
        text-decoration: none;
    }

    /* Mobile Styles */
    @media (max-width: 576px) {
        .auth-container {
            padding: 0.5rem;
            min-height: 100vh;
        }

        .auth-card {
            margin: 0;
            border-radius: 0.75rem;
        }

        .auth-header {
            padding: 1.5rem 1rem;
        }

        .auth-header h4 {
            font-size: 1.25rem;
        }

        .auth-body {
            padding: 1.5rem 1rem;
        }

        .form-control {
            padding: 0.75rem;
            font-size: 16px;
            /* Prevents zoom on iOS */
        }

        .btn-auth {
            padding: 0.875rem 1rem;
            font-size: 1rem;
        }

        .btn-outline-auth {
            padding: 0.625rem 1rem;
            font-size: 0.9rem;
        }
    }

    /* Tablet Styles */
    @media (min-width: 577px) and (max-width: 768px) {
        .auth-container {
            padding: 1rem;
        }

        .auth-card {
            max-width: 450px;
        }

        .auth-header {
            padding: 1.75rem 1.25rem;
        }

        .auth-body {
            padding: 1.75rem 1.25rem;
        }
    }

    /* Desktop Styles */
    @media (min-width: 769px) {
        .auth-container {
            padding: 2rem 0;
        }

        .auth-card {
            max-width: 500px;
        }
    }

    /* Large Desktop Styles */
    @media (min-width: 1200px) {
        .auth-card {
            max-width: 550px;
        }

        .auth-header {
            padding: 2.5rem 2rem;
        }

        .auth-body {
            padding: 2.5rem 2rem;
        }
    }
</style>
@endpush

@section('content')
<div class="auth-container">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
                <div class="card auth-card">
                    <div class="auth-header">
                        <h4>{{ __('Admin Login') }}</h4>
                        <p>Access the Leadership Summit admin panel</p>
                    </div>

                    <div class="auth-body">
                        @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <div class="form-group">
                                <label for="email" class="form-label">{{ __('Email Address') }}</label>
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                    name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                                    placeholder="Enter your email address">
                                @error('email')
                                <div class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="password" class="form-label">{{ __('Password') }}</label>
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                                    name="password" required autocomplete="current-password"
                                    placeholder="Enter your password">
                                @error('password')
                                <div class="invalid-feedback">
                                    <strong>{{ $message }}</strong>
                                </div>
                                @enderror
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label" for="remember">
                                    {{ __('Remember Me') }}
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary btn-auth">
                                <i class="fas fa-sign-in-alt me-2"></i>{{ __('Sign In') }}
                            </button>

                            @if (Route::has('password.request'))
                            <div class="text-center">
                                <a class="btn-link" href="{{ route('password.request') }}">
                                    {{ __('Forgot Your Password?') }}
                                </a>
                            </div>
                            @endif

                            <div class="auth-footer">
                                <p class="mb-2 text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Admin access only
                                </p>
                                <a href="{{ url('/') }}" class="btn-outline-auth">
                                    <i class="fas fa-home me-2"></i>Back to Website
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection