@extends('layouts.app')

@section('title', 'Registration Session Expired')

@push('styles')
<style>
    .error-container {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #f8fafc 0%, #e3f2fd 100%);
    }

    .error-card {
        background: white;
        border-radius: 1.5rem;
        padding: 3rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border: 2px solid #e5e7eb;
        max-width: 600px;
        text-align: center;
    }

    .error-icon {
        font-size: 4rem;
        color: #f59e0b;
        margin-bottom: 1.5rem;
    }

    .error-title {
        color: var(--primary-color);
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .error-message {
        color: #6b7280;
        font-size: 1.1rem;
        line-height: 1.6;
        margin-bottom: 2rem;
    }

    .error-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn-primary-custom {
        background: var(--primary-color);
        border: none;
        border-radius: 0.75rem;
        padding: 0.875rem 2rem;
        font-weight: 600;
        color: white;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .btn-primary-custom:hover {
        background: #1e40af;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(10, 36, 99, 0.3);
        color: white;
        text-decoration: none;
    }

    .btn-secondary-custom {
        background: #6b7280;
        border: none;
        border-radius: 0.75rem;
        padding: 0.875rem 2rem;
        font-weight: 600;
        color: white;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .btn-secondary-custom:hover {
        background: #4b5563;
        transform: translateY(-2px);
        color: white;
        text-decoration: none;
    }

    .help-section {
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid #e5e7eb;
    }

    .help-section h6 {
        color: var(--primary-color);
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .help-section p {
        color: #6b7280;
        margin-bottom: 0.5rem;
    }

    @media (max-width: 768px) {
        .error-card {
            padding: 2rem;
            margin: 1rem;
        }

        .error-actions {
            flex-direction: column;
        }
    }
</style>
@endpush

@section('content')
<div class="error-container">
    <div class="error-card">
        <div class="error-icon">
            <i class="fas fa-clock"></i>
        </div>

        <h2 class="error-title">Registration Session Expired</h2>

        <div class="error-message">
            <p>Your registration session has expired for security reasons. This happens after 30 minutes of inactivity to protect your personal information.</p>
            <p>Don't worry - you can start a new registration right away, and it only takes a few minutes to complete.</p>
        </div>

        <div class="error-actions">
            @if(isset($event))
            <a href="{{ route('events.register.form', $event) }}" class="btn-primary-custom">
                <i class="fas fa-redo me-2"></i>
                Start New Registration
            </a>
            <a href="{{ route('events.show', $event) }}" class="btn-secondary-custom">
                <i class="fas fa-arrow-left me-2"></i>
                Back to Event
            </a>
            @else
            <a href="{{ route('events.index') }}" class="btn-primary-custom">
                <i class="fas fa-calendar me-2"></i>
                View All Events
            </a>
            <a href="{{ route('home') }}" class="btn-secondary-custom">
                <i class="fas fa-home me-2"></i>
                Go Home
            </a>
            @endif
        </div>

        <div class="help-section">
            <h6>Why did this happen?</h6>
            <p><strong>Security:</strong> We automatically clear registration data after 30 minutes to protect your personal information.</p>
            <p><strong>Browser issues:</strong> Sometimes browser settings or extensions can interfere with session storage.</p>
            <p><strong>Network problems:</strong> Temporary connectivity issues may have interrupted your session.</p>

            <h6 class="mt-3">Need help?</h6>
            <p>If you continue to experience issues, please contact our support team:</p>
            <p>
                <i class="fas fa-envelope me-2"></i>
                <a href="mailto:support@example.com">support@example.com</a>
            </p>
            <p>
                <i class="fas fa-phone me-2"></i>
                <a href="tel:+1234567890">+1 (234) 567-8900</a>
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Clear any remaining session data
    if (typeof sessionStorage !== 'undefined') {
        sessionStorage.removeItem('registrationFormState');
    }

    // Log the error for analytics
    console.info('Registration session expired', {
        timestamp: new Date().toISOString(),
        userAgent: navigator.userAgent,
        referrer: document.referrer
    });
</script>
@endpush