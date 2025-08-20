@extends('layouts.app')

@section('title', 'Processing Card Payment - ' . $event->title)

@push('styles')
<style>
    .card-processing-container {
        min-height: 100vh;
        background: linear-gradient(135deg, #f8fafc 0%, #e3f2fd 100%);
        padding: 2rem 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .card-processing-card {
        background: white;
        border-radius: 1.5rem;
        padding: 3rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border: 2px solid #e5e7eb;
        max-width: 500px;
        text-align: center;
    }

    .processing-steps {
        margin: 2rem 0;
    }

    .step {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        padding: 1rem 0;
        opacity: 0.3;
        transition: all 0.5s ease;
    }

    .step.active {
        opacity: 1;
        color: var(--primary-color);
    }

    .step.completed {
        opacity: 1;
        color: #10b981;
    }

    .step-icon {
        font-size: 1.5rem;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid currentColor;
        flex-shrink: 0;
    }

    .step.active .step-icon {
        background: var(--primary-color);
        color: white;
        animation: pulse 2s infinite;
    }

    .step.completed .step-icon {
        background: #10b981;
        color: white;
        border-color: #10b981;
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
            opacity: 1;
        }

        50% {
            transform: scale(1.05);
            opacity: 0.8;
        }
    }

    .step-text {
        font-weight: 500;
        text-align: left;
    }

    .step-description {
        font-size: 0.9rem;
        color: #6b7280;
        margin-top: 0.25rem;
    }

    .progress-bar {
        width: 100%;
        height: 6px;
        background: #e5e7eb;
        border-radius: 3px;
        margin: 2rem 0;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary-color), #3b82f6);
        border-radius: 3px;
        width: 0%;
        transition: width 0.5s ease;
        animation: shimmer 2s infinite;
    }

    @keyframes shimmer {
        0% {
            background-position: -200px 0;
        }

        100% {
            background-position: 200px 0;
        }
    }

    .processing-icon i {
        animation: spin 2s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .processing-title {
        color: var(--primary-color);
        font-weight: 700;
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }

    .processing-message {
        color: #6b7280;
        font-size: 1.1rem;
        margin-bottom: 2rem;
        line-height: 1.6;
    }

    .security-notice {
        background: #f0f9ff;
        border: 1px solid #bfdbfe;
        border-radius: 0.75rem;
        padding: 1rem;
        margin-top: 2rem;
        font-size: 0.9rem;
        color: #1e40af;
    }

    .security-notice i {
        color: var(--primary-color);
        margin-right: 0.5rem;
    }

    .timeout-warning {
        background: #fef3c7;
        border: 1px solid #f59e0b;
        border-radius: 0.75rem;
        padding: 1rem;
        margin-top: 1rem;
        font-size: 0.9rem;
        color: #92400e;
        display: none;
    }

    .timeout-warning.show {
        display: block;
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .manual-check-btn {
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: 0.75rem;
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        margin-top: 1rem;
    }

    .manual-check-btn:hover {
        background: #1e3a8a;
        transform: translateY(-1px);
        color: white;
        text-decoration: none;
    }

    @media (max-width: 768px) {
        .card-processing-card {
            margin: 1rem;
            padding: 2rem;
        }

        .step {
            flex-direction: column;
            text-align: center;
            gap: 0.5rem;
        }

        .step-text {
            text-align: center;
        }
    }
</style>
@endpush

@section('content')
<div class="card-processing-container">
    <div class="card-processing-card">
        <div class="processing-icon">
            <i class="fas fa-credit-card"></i>
        </div>

        <h2 class="processing-title">Processing Card Payment</h2>

        <p class="processing-message">
            Your payment is being securely processed. Please do not close this window or navigate away.
        </p>

        <div class="progress-bar">
            <div class="progress-fill" id="progressFill"></div>
        </div>

        <div class="processing-steps">
            <div class="step active" id="step1">
                <div class="step-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="step-text">
                    Verifying Payment Details
                    <div class="step-description">Checking card information and security</div>
                </div>
            </div>

            <div class="step" id="step2">
                <div class="step-icon">
                    <i class="fas fa-university"></i>
                </div>
                <div class="step-text">
                    Processing with Bank
                    <div class="step-description">Authorizing payment with your financial institution</div>
                </div>
            </div>

            <div class="step" id="step3">
                <div class="step-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="step-text">
                    Confirming Registration
                    <div class="step-description">Finalizing your event registration</div>
                </div>
            </div>
        </div>

        <div class="security-notice">
            <i class="fas fa-lock"></i>
            <strong>Secure Processing:</strong> Your payment is protected by bank-level encryption and security measures.
        </div>

        <div class="timeout-warning" id="timeoutWarning">
            <i class="fas fa-clock"></i>
            <strong>Taking longer than expected?</strong> Payment processing can sometimes take a few extra moments.
            If this continues, you can check your payment status manually.
            <br>
            <button type="button" class="manual-check-btn" onclick="checkPaymentStatus()">
                <i class="fas fa-search me-2"></i>Check Payment Status
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentStep = 1;
    let progressInterval;
    let stepInterval;
    let timeoutWarningTimer;

    document.addEventListener('DOMContentLoaded', function() {
        startProcessingAnimation();
        startPaymentStatusCheck();

        // Show timeout warning after 30 seconds
        timeoutWarningTimer = setTimeout(() => {
            document.getElementById('timeoutWarning').classList.add('show');
        }, 30000);
    });

    function startProcessingAnimation() {
        const progressFill = document.getElementById('progressFill');
        let progress = 0;

        // Animate progress bar
        progressInterval = setInterval(() => {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90; // Don't complete until payment is confirmed
            progressFill.style.width = progress + '%';
        }, 1000);

        // Animate steps
        stepInterval = setInterval(() => {
            if (currentStep < 3) {
                // Mark current step as completed
                const currentStepEl = document.getElementById(`step${currentStep}`);
                currentStepEl.classList.remove('active');
                currentStepEl.classList.add('completed');

                // Activate next step
                currentStep++;
                const nextStepEl = document.getElementById(`step${currentStep}`);
                nextStepEl.classList.add('active');
            }
        }, 8000);
    }

    function startPaymentStatusCheck() {
        // Check payment status every 5 seconds
        const statusCheckInterval = setInterval(() => {
            fetch('{{ route("payment.status.check", $event ?? "unknown") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.status === 'completed') {
                            // Payment successful
                            completeProcessing(true);
                            clearInterval(statusCheckInterval);
                            window.location.href = data.redirect_url || '{{ route("registrations.success") }}';
                        } else if (data.status === 'failed') {
                            // Payment failed
                            completeProcessing(false);
                            clearInterval(statusCheckInterval);
                            window.location.href = data.redirect_url || '{{ route("payment.failed", $event ?? "unknown") }}';
                        }
                        // If status is still 'pending', continue checking
                    }
                })
                .catch(error => {
                    console.error('Payment status check error:', error);
                    // Continue checking - don't stop on network errors
                });
        }, 5000);

        // Stop checking after 10 minutes
        setTimeout(() => {
            clearInterval(statusCheckInterval);
            // Redirect to manual status check page
            window.location.href = '{{ route("payment.status", $event ?? "unknown") }}';
        }, 600000);
    }

    function completeProcessing(success) {
        // Clear intervals
        if (progressInterval) clearInterval(progressInterval);
        if (stepInterval) clearInterval(stepInterval);
        if (timeoutWarningTimer) clearTimeout(timeoutWarningTimer);

        // Complete progress bar
        const progressFill = document.getElementById('progressFill');
        progressFill.style.width = '100%';

        // Mark all steps as completed
        for (let i = 1; i <= 3; i++) {
            const stepEl = document.getElementById(`step${i}`);
            stepEl.classList.remove('active');
            stepEl.classList.add('completed');
        }

        // Update UI based on success/failure
        const title = document.querySelector('.processing-title');
        const message = document.querySelector('.processing-message');
        const icon = document.querySelector('.processing-icon i');

        if (success) {
            title.textContent = 'Payment Successful!';
            message.textContent = 'Your payment has been processed successfully. Redirecting to confirmation...';
            icon.className = 'fas fa-check-circle';
            icon.style.color = '#10b981';
            icon.style.animation = 'none';
        } else {
            title.textContent = 'Payment Failed';
            message.textContent = 'There was an issue processing your payment. Redirecting to retry options...';
            icon.className = 'fas fa-exclamation-triangle';
            icon.style.color = '#ef4444';
            icon.style.animation = 'none';
        }
    }

    function checkPaymentStatus() {
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Checking...';
        button.disabled = true;

        fetch('{{ route("payment.status.check", $event ?? "unknown") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.status === 'completed') {
                        window.location.href = data.redirect_url || '{{ route("registrations.success") }}';
                    } else if (data.status === 'failed') {
                        window.location.href = data.redirect_url || '{{ route("payment.failed", $event ?? "unknown") }}';
                    } else {
                        // Still pending
                        button.innerHTML = originalText;
                        button.disabled = false;
                        alert('Payment is still being processed. Please wait a moment longer.');
                    }
                } else {
                    button.innerHTML = originalText;
                    button.disabled = false;
                    alert('Unable to check payment status. Please contact support if this continues.');
                }
            })
            .catch(error => {
                console.error('Manual status check error:', error);
                button.innerHTML = originalText;
                button.disabled = false;
                alert('Unable to check payment status. Please contact support.');
            });
    }

    // Prevent accidental navigation away
    window.addEventListener('beforeunload', function(e) {
        e.preventDefault();
        e.returnValue = 'Your payment is being processed. Are you sure you want to leave?';
        return e.returnValue;
    });

    // Clean up intervals when page is unloaded
    window.addEventListener('beforeunload', function() {
        if (progressInterval) clearInterval(progressInterval);
        if (stepInterval) clearInterval(stepInterval);
        if (timeoutWarningTimer) clearTimeout(timeoutWarningTimer);
    });
</script>
@endpush .processing-icon {
font-size: 4rem;
color: var(--primary-color);
margin-bottom: 1.5rem;