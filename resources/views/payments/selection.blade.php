@extends('layouts.app')

@section('title', 'Choose Payment Method - ' . $event->title)

@push('styles')
<style>
    .payment-selection-container {
        min-height: 100vh;
        background: linear-gradient(135deg, #f8fafc 0%, #e3f2fd 100%);
        padding: 2rem 0;
    }

    .payment-card {
        background: white;
        border-radius: 1.5rem;
        padding: 2.5rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border: 2px solid #e5e7eb;
        max-width: 800px;
        margin: 0 auto;
    }

    .payment-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .payment-header h2 {
        color: var(--primary-color);
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .order-summary {
        background: #f8fafc;
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .summary-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid #e5e7eb;
    }

    .summary-item:last-child {
        border-bottom: none;
        font-weight: 700;
        font-size: 1.2rem;
        color: var(--primary-color);
    }

    .payment-methods {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .payment-method {
        border: 2px solid #e5e7eb;
        border-radius: 1.5rem;
        padding: 2rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        background: white;
    }

    .payment-method:hover {
        border-color: var(--primary-color);
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(10, 36, 99, 0.15);
    }

    .payment-method.available:hover {
        background: linear-gradient(135deg, rgba(10, 36, 99, 0.03), rgba(59, 130, 246, 0.03));
    }

    .payment-method.unavailable {
        opacity: 0.6;
        cursor: not-allowed;
        background: #f9fafb;
    }

    .payment-method.unavailable:hover {
        transform: none;
        box-shadow: none;
        border-color: #e5e7eb;
    }

    .payment-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        display: block;
    }

    .payment-method.card .payment-icon {
        color: #1e40af;
    }

    .payment-method.crypto .payment-icon {
        color: #f59e0b;
    }

    .payment-method.unavailable .payment-icon {
        color: #9ca3af;
    }

    .payment-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--primary-color);
    }

    .payment-method.unavailable .payment-title {
        color: #6b7280;
    }

    .payment-description {
        color: #6b7280;
        margin-bottom: 1rem;
        line-height: 1.5;
    }

    .payment-features {
        list-style: none;
        padding: 0;
        margin: 1rem 0;
    }

    .payment-features li {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.25rem 0;
        color: #6b7280;
        font-size: 0.9rem;
    }

    .payment-features li i {
        color: #10b981;
        font-size: 0.8rem;
    }

    .payment-method.unavailable .payment-features li i {
        color: #ef4444;
    }

    .payment-button {
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: 0.75rem;
        padding: 1rem 2rem;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
        margin-top: 1rem;
    }

    .payment-button:hover:not(:disabled) {
        background: #1e3a8a;
        transform: translateY(-1px);
    }

    .payment-button:disabled {
        background: #9ca3af;
        cursor: not-allowed;
        transform: none;
    }

    .fee-info {
        background: #fef3c7;
        border: 1px solid #f59e0b;
        border-radius: 0.5rem;
        padding: 0.75rem;
        margin-top: 1rem;
        font-size: 0.9rem;
        color: #92400e;
    }

    .fee-info.no-fee {
        background: #d1fae5;
        border-color: #10b981;
        color: #065f46;
    }

    .unavailable-notice {
        background: #fee2e2;
        border: 1px solid #ef4444;
        border-radius: 0.5rem;
        padding: 0.75rem;
        margin-top: 1rem;
        font-size: 0.9rem;
        color: #991b1b;
    }

    .security-notice {
        text-align: center;
        margin-top: 2rem;
        padding: 1rem;
        background: #f0f9ff;
        border-radius: 0.75rem;
        border-left: 4px solid var(--primary-color);
    }

    .security-notice i {
        color: var(--primary-color);
        margin-right: 0.5rem;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--primary-color);
        text-decoration: none;
        margin-bottom: 2rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .back-link:hover {
        color: #1e3a8a;
        text-decoration: none;
        transform: translateX(-2px);
    }

    @media (max-width: 768px) {
        .payment-card {
            margin: 1rem;
            padding: 1.5rem;
        }

        .payment-methods {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .payment-method {
            padding: 1.5rem;
        }

        .payment-icon {
            font-size: 2.5rem;
        }

        .payment-title {
            font-size: 1.25rem;
        }
    }
</style>
@endpush

@section('content')
<div class="payment-selection-container">
    <div class="container">
        <div class="payment-card">
            <a href="{{ route('events.show', $event) }}" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Event Details
            </a>

            <div class="payment-header">
                <h2>Choose Payment Method</h2>
                <p class="text-muted">{{ $event->title }}</p>
            </div>

            <!-- Registration Summary -->
            <div class="order-summary">
                <h4 class="mb-3">Registration Summary</h4>

                <div class="summary-item">
                    <div>
                        <div><strong>Attendee:</strong> {{ $registrationData['attendee_name'] }}</div>
                        <small class="text-muted">{{ $registrationData['attendee_email'] }}</small>
                    </div>
                </div>

                @if($registrationData['attendee_phone'])
                <div class="summary-item">
                    <div>
                        <div><strong>Phone:</strong> {{ $registrationData['attendee_phone'] }}</div>
                    </div>
                </div>
                @endif

                @if(isset($registrationData['emergency_contact_name']) && $registrationData['emergency_contact_name'])
                <div class="summary-item">
                    <div>
                        <div><strong>Emergency Contact:</strong> {{ $registrationData['emergency_contact_name'] }}</div>
                        @if(isset($registrationData['emergency_contact_phone']) && $registrationData['emergency_contact_phone'])
                        <small class="text-muted">{{ $registrationData['emergency_contact_phone'] }}</small>
                        @endif
                    </div>
                </div>
                @endif

                @if(isset($registrationData['ticket_selections']))
                @foreach($registrationData['ticket_selections'] as $ticketData)
                <div class="summary-item">
                    <div>
                        <div>{{ $ticketData['ticket_name'] }}</div>
                        <small class="text-muted">Qty: {{ $ticketData['quantity'] }} × ${{ number_format($ticketData['price'], 2) }}</small>
                    </div>
                    <span>${{ number_format($ticketData['subtotal'], 2) }}</span>
                </div>
                @endforeach
                @endif

                <div class="summary-item">
                    <span>Total:</span>
                    <span>${{ number_format($registrationData['total_amount'], 2) }}</span>
                </div>
            </div>

            <!-- Payment Method Options -->
            <div class="payment-methods">
                <!-- Card Payment Option -->
                <div class="payment-method card {{ $paymentOptions['card']['available'] ? 'available' : 'unavailable' }}">
                    <i class="payment-icon fas fa-credit-card"></i>
                    <h3 class="payment-title">{{ $paymentOptions['card']['name'] }}</h3>
                    <p class="payment-description">{{ $paymentOptions['card']['description'] }}</p>

                    <ul class="payment-features">
                        <li>
                            <i class="fas {{ $paymentOptions['card']['available'] ? 'fa-check' : 'fa-times' }}"></i>
                            {{ $paymentOptions['card']['processing_time'] }}
                        </li>
                        @if($paymentOptions['card']['available'])
                        <li>
                            <i class="fas fa-shield-alt"></i>
                            Secure checkout with SSL encryption
                        </li>
                        <li>
                            <i class="fas fa-mobile-alt"></i>
                            Mobile-friendly payment process
                        </li>
                        @endif
                    </ul>

                    @if($paymentOptions['card']['available'])
                    @if(isset($paymentOptions['card']['fee_info']) && $paymentOptions['card']['fee_info'] !== 'No processing fees')
                    <div class="fee-info">
                        <i class="fas fa-info-circle"></i>
                        {{ $paymentOptions['card']['fee_info'] }}
                    </div>
                    @else
                    <div class="fee-info no-fee">
                        <i class="fas fa-check-circle"></i>
                        No processing fees
                    </div>
                    @endif

                    <form method="POST" action="{{ route('payment.card', $event) }}">
                        @csrf
                        <button type="submit" class="payment-button" id="card-payment-btn">
                            <i class="fas fa-credit-card me-2"></i>
                            Pay with Card
                        </button>
                    </form>
                    @else
                    <div class="unavailable-notice">
                        <i class="fas fa-exclamation-triangle"></i>
                        {{ $paymentOptions['card']['fee_info'] }}
                    </div>
                    <button type="button" class="payment-button" disabled>
                        <i class="fas fa-times me-2"></i>
                        Temporarily Unavailable
                    </button>
                    @endif
                </div>

                <!-- Crypto Payment Option -->
                <div class="payment-method crypto {{ $paymentOptions['crypto']['available'] ? 'available' : 'unavailable' }}">
                    <i class="payment-icon fas fa-coins"></i>
                    <h3 class="payment-title">{{ $paymentOptions['crypto']['name'] }}</h3>
                    <p class="payment-description">{{ $paymentOptions['crypto']['description'] }}</p>

                    <ul class="payment-features">
                        <li>
                            <i class="fas {{ $paymentOptions['crypto']['available'] ? 'fa-check' : 'fa-times' }}"></i>
                            {{ $paymentOptions['crypto']['processing_time'] }}
                        </li>
                        @if($paymentOptions['crypto']['available'])
                        <li>
                            <i class="fas fa-globe"></i>
                            Decentralized payment method
                        </li>
                        <li>
                            <i class="fas fa-qrcode"></i>
                            QR code for easy mobile payments
                        </li>
                        @endif
                    </ul>

                    @if($paymentOptions['crypto']['available'])
                    <div class="fee-info no-fee">
                        <i class="fas fa-check-circle"></i>
                        {{ $paymentOptions['crypto']['fee_info'] }}
                    </div>

                    <a href="{{ route('payment.crypto', $event) }}" class="payment-button" style="display: inline-block; text-decoration: none; text-align: center;">
                        <i class="fas fa-coins me-2"></i>
                        Pay with Crypto
                    </a>
                    @else
                    <div class="unavailable-notice">
                        <i class="fas fa-exclamation-triangle"></i>
                        Cryptocurrency payment is temporarily unavailable
                    </div>
                    <button type="button" class="payment-button" disabled>
                        <i class="fas fa-times me-2"></i>
                        Temporarily Unavailable
                    </button>
                    @endif
                </div>
            </div>

            <div class="security-notice">
                <i class="fas fa-shield-alt"></i>
                <strong>Secure Payment Processing:</strong> All payment methods use industry-standard security measures to protect your financial information.
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let registrationExpiresAt = @json($registrationData['expires_at'] ?? null);

    // Add simple loading state for card payment form
    document.addEventListener('DOMContentLoaded', function() {
        const cardForm = document.querySelector('form[action*="payment/card"]');
        if (cardForm) {
            cardForm.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                }
            });
        }
    });

    function switchPaymentMethod(newMethod) {
        const switchForm = document.createElement('form');
        switchForm.method = 'POST';
        switchForm.action = '{{ route("payment.switch", $event) }}';
        switchForm.style.display = 'none';

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';

        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = 'method';
        methodInput.value = newMethod;

        switchForm.appendChild(csrfToken);
        switchForm.appendChild(methodInput);
        document.body.appendChild(switchForm);

        // Show loading state
        const buttons = document.querySelectorAll('.payment-button:not(:disabled)');
        buttons.forEach(btn => {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Switching...';
        });

        switchForm.submit();
    }

    function resetPaymentButtons() {
        const cardButton = document.querySelector('.payment-method.card .payment-button');
        const cryptoButton = document.querySelector('.payment-method.crypto .payment-button');

        if (cardButton && !cardButton.hasAttribute('data-original-disabled')) {
            cardButton.disabled = false;
            cardButton.innerHTML = '<i class="fas fa-credit-card me-2"></i>Pay with Card';
        }

        if (cryptoButton && !cryptoButton.hasAttribute('data-original-disabled')) {
            cryptoButton.disabled = false;
            cryptoButton.innerHTML = '<i class="fas fa-coins me-2"></i>Pay with Crypto';
        }
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

    function startRegistrationExpiryCountdown() {
        if (!registrationExpiresAt) return;

        const expiryTime = new Date(registrationExpiresAt);

        const countdownInterval = setInterval(() => {
            const now = new Date();
            const timeLeft = expiryTime - now;

            if (timeLeft <= 0) {
                clearInterval(countdownInterval);
                showError('Your registration session has expired. Redirecting to event page...');
                setTimeout(() => {
                    window.location.href = '{{ route("events.show", $event) }}';
                }, 2000);
                return;
            }

            // Show warning when 5 minutes left
            if (timeLeft <= 5 * 60 * 1000 && timeLeft > 4 * 60 * 1000) {
                showWarning('Your registration session will expire in 5 minutes. Please complete your payment soon.');
            }
        }, 30000); // Check every 30 seconds
    }

    function showWarning(message) {
        // Create or update warning alert
        let warningAlert = document.getElementById('payment-warning-alert');
        if (!warningAlert) {
            warningAlert = document.createElement('div');
            warningAlert.id = 'payment-warning-alert';
            warningAlert.className = 'alert alert-warning';
            warningAlert.style.cssText = `
                position: fixed;
                top: 80px;
                left: 50%;
                transform: translateX(-50%);
                z-index: 9998;
                max-width: 500px;
                border-radius: 0.75rem;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            `;
            document.body.appendChild(warningAlert);
        }

        warningAlert.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-clock me-2"></i>
                <span>${message}</span>
                <button type="button" class="btn-close ms-auto" onclick="hideWarning()"></button>
            </div>
        `;

        warningAlert.style.display = 'block';

        // Auto-hide after 10 seconds
        setTimeout(hideWarning, 10000);
    }

    function hideWarning() {
        const warningAlert = document.getElementById('payment-warning-alert');
        if (warningAlert) {
            warningAlert.style.display = 'none';
        }
    }

    // Add keyboard support for payment method selection
    document.addEventListener('DOMContentLoaded', function() {
        const paymentMethods = document.querySelectorAll('.payment-method.available');

        paymentMethods.forEach(method => {
            method.setAttribute('tabindex', '0');
            method.setAttribute('role', 'button');

            method.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        });

        // Add visual feedback for keyboard navigation
        paymentMethods.forEach(method => {
            method.addEventListener('focus', function() {
                this.style.outline = '2px solid var(--primary-color)';
                this.style.outlineOffset = '2px';
            });

            method.addEventListener('blur', function() {
                this.style.outline = 'none';
            });
        });

        // Start registration expiry countdown
        startRegistrationExpiryCountdown();

        // Mark originally disabled buttons
        document.querySelectorAll('.payment-button:disabled').forEach(btn => {
            btn.setAttribute('data-original-disabled', 'true');
        });
    });

    // Removed beforeunload handler to prevent "stay on page" dialog

    // Clean up intervals when leaving page
    window.addEventListener('beforeunload', function() {
        if (paymentAvailabilityCheckInterval) {
            clearInterval(paymentAvailabilityCheckInterval);
        }
    });
</script>
@endpush