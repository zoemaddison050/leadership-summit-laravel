@extends('layouts.app')

@section('title', 'Payment Failed - ' . ($event->title ?? 'Event Registration'))

@push('styles')
<style>
    .payment-failed-container {
        min-height: 100vh;
        background: linear-gradient(135deg, #f8fafc 0%, #e3f2fd 100%);
        padding: 2rem 0;
        display: flex;
        align-items: center;
    }

    .payment-failed-card {
        background: white;
        border-radius: 1.5rem;
        padding: 3rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border: 2px solid #e5e7eb;
        max-width: 700px;
        margin: 0 auto;
        text-align: center;
    }

    .payment-failed-icon {
        font-size: 4rem;
        color: #ef4444;
        margin-bottom: 1.5rem;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.7;
        }
    }

    .payment-failed-title {
        color: var(--primary-color);
        font-weight: 700;
        font-size: 2rem;
        margin-bottom: 1rem;
    }

    .payment-failed-message {
        color: #6b7280;
        font-size: 1.1rem;
        line-height: 1.6;
        margin-bottom: 2rem;
    }

    .error-details {
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 1rem;
        padding: 1.5rem;
        margin: 2rem 0;
        text-align: left;
    }

    .error-details h4 {
        color: #dc2626;
        font-weight: 600;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .error-reason {
        color: #991b1b;
        font-weight: 500;
        margin-bottom: 1rem;
    }

    .retry-options {
        background: #f0f9ff;
        border: 1px solid #bfdbfe;
        border-radius: 1rem;
        padding: 1.5rem;
        margin: 2rem 0;
        text-align: left;
    }

    .retry-options h4 {
        color: var(--primary-color);
        font-weight: 600;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .retry-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .retry-list li {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.5rem 0;
        color: #374151;
    }

    .retry-list li i {
        color: var(--primary-color);
        margin-top: 0.25rem;
        flex-shrink: 0;
    }

    .action-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
        margin-top: 2rem;
    }

    .btn-action {
        padding: 1rem 2rem;
        border-radius: 0.75rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        font-size: 1rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        text-decoration: none;
    }

    .btn-primary-action {
        background: var(--primary-color);
        color: white;
    }

    .btn-primary-action:hover {
        background: #1e3a8a;
        color: white;
    }

    .btn-secondary-action {
        background: #10b981;
        color: white;
    }

    .btn-secondary-action:hover {
        background: #059669;
        color: white;
    }

    .btn-outline-action {
        background: transparent;
        color: var(--primary-color);
        border: 2px solid var(--primary-color);
    }

    .btn-outline-action:hover {
        background: var(--primary-color);
        color: white;
    }

    .loading-state {
        opacity: 0.7;
        pointer-events: none;
    }

    .loading-state .btn-action {
        background: #9ca3af;
        cursor: not-allowed;
    }

    .support-section {
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid #e5e7eb;
    }

    .support-section h5 {
        color: var(--primary-color);
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .support-section p {
        color: #6b7280;
        margin-bottom: 0.5rem;
    }

    .support-contact {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .support-contact:hover {
        color: #1e3a8a;
        text-decoration: none;
    }

    @media (max-width: 768px) {
        .payment-failed-container {
            padding: 1rem;
        }

        .payment-failed-card {
            padding: 2rem;
        }

        .action-buttons {
            flex-direction: column;
        }

        .btn-action {
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endpush

@section('content')
<div class="payment-failed-container">
    <div class="container">
        <div class="payment-failed-card">
            <div class="payment-failed-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>

            <h1 class="payment-failed-title">Payment Failed</h1>

            <p class="payment-failed-message">
                We were unable to process your payment. Don't worry - your registration information is still saved and you can try again.
            </p>

            @if(isset($errorMessage) && $errorMessage)
            <div class="error-details">
                <h4>
                    <i class="fas fa-info-circle"></i>
                    Error Details
                </h4>
                <div class="error-reason">{{ $errorMessage }}</div>
            </div>
            @endif

            <div class="retry-options">
                <h4>
                    <i class="fas fa-lightbulb"></i>
                    What you can try:
                </h4>
                <ul class="retry-list">
                    @if(isset($suggestedMethod) && $suggestedMethod)
                    <li>
                        <i class="fas fa-exchange-alt"></i>
                        <div>
                            <strong>Try {{ $suggestedMethod === 'card' ? 'card payment' : 'cryptocurrency payment' }}:</strong>
                            @if($suggestedMethod === 'card')
                            Card payments offer instant confirmation and are often more reliable.
                            @else
                            Cryptocurrency payments have no processing fees and work independently of banking systems.
                            @endif
                        </div>
                    </li>
                    @endif

                    @php
                    $retryCount = $registrationData['retry_count'] ?? 0;
                    $maxRetries = 3;
                    $canRetry = $retryCount < $maxRetries;
                        @endphp

                        @if($canRetry)
                        <li>
                        <i class="fas fa-sync-alt"></i>
                        <div>
                            <strong>Retry the same method:</strong> Sometimes temporary issues resolve quickly. You have {{ $maxRetries - $retryCount }} attempts remaining.
                        </div>
                        </li>
                        @endif

                        @if(($paymentMethod ?? '') === 'card')
                        <li>
                            <i class="fas fa-shield-alt"></i>
                            <div>
                                <strong>Check your card details:</strong> Ensure your card information is correct, has sufficient funds, and is enabled for online transactions.
                            </div>
                        </li>
                        @endif

                        <li>
                            <i class="fas fa-globe"></i>
                            <div>
                                <strong>Check your internet connection:</strong> A stable connection is required for payment processing.
                            </div>
                        </li>

                        <li>
                            <i class="fas fa-clock"></i>
                            <div>
                                <strong>Your registration is preserved:</strong> Your registration information is safely stored and won't be lost while you resolve the payment issue.
                            </div>
                        </li>
                </ul>
            </div>

            <div class="action-buttons" id="actionButtons">
                @if(isset($event))
                @php
                $retryCount = $registrationData['retry_count'] ?? 0;
                $maxRetries = 3;
                $canRetry = $retryCount < $maxRetries;
                    @endphp

                    @if($canRetry)
                    <button type="button" class="btn-action btn-primary-action" onclick="retryPayment('{{ $paymentMethod ?? 'card' }}')">
                    <i class="fas fa-redo"></i>
                    Try Again ({{ $maxRetries - $retryCount }} attempts left)
                    </button>
                    @endif

                    @if(isset($suggestedMethod) && $suggestedMethod)
                    <button type="button" class="btn-action btn-secondary-action" onclick="switchToAlternativeMethod('{{ $suggestedMethod }}')">
                        <i class="fas fa-exchange-alt"></i>
                        Try {{ $suggestedMethod === 'card' ? 'Card Payment' : 'Cryptocurrency' }}
                    </button>
                    @endif

                    <a href="{{ route('payment.selection', $event) }}" class="btn-action btn-outline-action">
                        <i class="fas fa-credit-card"></i>
                        Choose Payment Method
                    </a>

                    <a href="{{ route('events.show', $event) }}" class="btn-action btn-outline-action">
                        <i class="fas fa-arrow-left"></i>
                        Back to Event
                    </a>

                    @if(!$canRetry)
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i>
                            Maximum retry attempts reached. Please try a different payment method or contact support.
                        </small>
                    </div>
                    @endif
                    @else
                    <a href="{{ route('events.index') }}" class="btn-action btn-primary-action">
                        <i class="fas fa-calendar"></i>
                        Browse Events
                    </a>

                    <a href="{{ route('home') }}" class="btn-action btn-outline-action">
                        <i class="fas fa-home"></i>
                        Go Home
                    </a>
                    @endif
            </div>

            <div class="support-section">
                <h5>Still having trouble?</h5>
                <p>Our support team is here to help you complete your registration.</p>
                <p>
                    <a href="mailto:support@leadershipsummit.com" class="support-contact">
                        <i class="fas fa-envelope"></i>
                        support@leadershipsummit.com
                    </a>
                </p>
                <p>
                    <a href="tel:+1-555-123-4567" class="support-contact">
                        <i class="fas fa-phone"></i>
                        +1 (555) 123-4567
                    </a>
                </p>
                @if(isset($transactionId))
                <p class="mt-2">
                    <small class="text-muted">
                        Reference ID: {{ $transactionId }}
                    </small>
                </p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function retryPayment(method) {
        const actionButtons = document.getElementById('actionButtons');
        actionButtons.classList.add('loading-state');

        // Update button text to show loading
        const retryButton = event.target;
        const originalText = retryButton.innerHTML;
        retryButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Retrying...';

        // Create and submit retry form
        const retryForm = document.createElement('form');
        retryForm.method = 'POST';
        retryForm.action = '{{ route("payment.retry", $event ?? "") }}';
        retryForm.style.display = 'none';

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';

        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = 'method';
        methodInput.value = method;

        retryForm.appendChild(csrfToken);
        retryForm.appendChild(methodInput);
        document.body.appendChild(retryForm);

        // Submit after a brief delay to show loading state
        setTimeout(() => {
            retryForm.submit();
        }, 500);
    }

    function switchToAlternativeMethod(method) {
        const actionButtons = document.getElementById('actionButtons');
        actionButtons.classList.add('loading-state');

        // Update button text to show loading
        const switchButton = event.target;
        const originalText = switchButton.innerHTML;
        switchButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Switching...';

        // Create and submit switch form
        const switchForm = document.createElement('form');
        switchForm.method = 'POST';
        switchForm.action = '{{ route("payment.switch", $event ?? "") }}';
        switchForm.style.display = 'none';

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';

        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = 'method';
        methodInput.value = method;

        switchForm.appendChild(csrfToken);
        switchForm.appendChild(methodInput);
        document.body.appendChild(switchForm);

        // Submit after a brief delay to show loading state
        setTimeout(() => {
            switchForm.submit();
        }, 500);
    }

    function showError(message) {
        // Create or update error alert
        let errorAlert = document.getElementById('payment-error-alert');
        if (!errorAlert) {
            errorAlert = document.createElement('div');
            errorAlert.id = 'payment-error-alert';
            errorAlert.className = 'alert alert-danger';
            errorAlert.style.cssText = `
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                z-index: 9999;
                max-width: 500px;
                border-radius: 0.75rem;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            `;
            document.body.appendChild(errorAlert);
        }

        errorAlert.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <span>${message}</span>
                <button type="button" class="btn-close ms-auto" onclick="hideError()"></button>
            </div>
        `;

        errorAlert.style.display = 'block';

        // Auto-hide after 5 seconds
        setTimeout(hideError, 5000);
    }

    function hideError() {
        const errorAlert = document.getElementById('payment-error-alert');
        if (errorAlert) {
            errorAlert.style.display = 'none';
        }
    }

    // Add keyboard support for action buttons
    document.addEventListener('DOMContentLoaded', function() {
        const actionButtons = document.querySelectorAll('.btn-action[onclick]');
        actionButtons.forEach(button => {
            button.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        });

        // Configuration from server
        const failureConfig = {
            hasRegistrationData: {
                {
                    isset($registrationData) ? 'true' : 'false'
                }
            },
            registrationExpiresAt: {
                {
                    isset($registrationData['expires_at']) ? "'".$registrationData['expires_at'].
                    "'": 'null'
                }
            },
            eventUrl: '{{ isset($event) ? route("events.show", $event) : "" }}',
            eventId: {
                {
                    isset($event) ? $event - > id : 'null'
                }
            },
            errorMessage: '{{ addslashes($errorMessage ?? "") }}',
            transactionId: '{{ $transactionId ?? "" }}',
            paymentMethod: '{{ $paymentMethod ?? "" }}',
            retryCount: {
                {
                    isset($registrationData['retry_count']) ? $registrationData['retry_count'] : 0
                }
            }
        };

        // Check if registration session is still valid
        if (failureConfig.hasRegistrationData && failureConfig.registrationExpiresAt) {
            checkRegistrationValidity();
        }
    });

    function checkRegistrationValidity() {
        const expiresAt = new Date(failureConfig.registrationExpiresAt);
        const now = new Date();

        if (now >= expiresAt) {
            showError('Your registration session has expired. Redirecting to event page...');
            setTimeout(() => {
                window.location.href = failureConfig.eventUrl;
            }, 3000);
        }
    }

    // Log payment failure for analytics
    console.info('Payment failure page loaded', {
        timestamp: new Date().toISOString(),
        eventId: failureConfig.eventId,
        errorMessage: failureConfig.errorMessage,
        transactionId: failureConfig.transactionId,
        paymentMethod: failureConfig.paymentMethod,
        retryCount: failureConfig.retryCount,
        userAgent: navigator.userAgent,
        referrer: document.referrer
    });
</script>
@endpush