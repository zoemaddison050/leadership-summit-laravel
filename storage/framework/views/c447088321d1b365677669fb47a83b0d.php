<?php $__env->startSection('title', 'Processing Payment - ' . $event->title); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .processing-container {
        min-height: 100vh;
        background: linear-gradient(135deg, #f8fafc 0%, #e3f2fd 100%);
        padding: 2rem 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .processing-card {
        background: white;
        border-radius: 1.5rem;
        padding: 3rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border: 2px solid #e5e7eb;
        max-width: 500px;
        text-align: center;
    }

    .processing-icon {
        font-size: 4rem;
        color: var(--primary-color);
        margin-bottom: 1.5rem;
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

    .countdown-section {
        background: #f8fafc;
        border-radius: 1rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .countdown-timer {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 0.5rem;
    }

    .countdown-text {
        color: #6b7280;
        font-size: 0.9rem;
    }

    .manual-redirect-btn {
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
    }

    .manual-redirect-btn:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
        color: white;
        text-decoration: none;
    }

    .event-info {
        background: #f0f9ff;
        border-radius: 0.75rem;
        padding: 1rem;
        margin-bottom: 2rem;
        border-left: 4px solid var(--primary-color);
    }

    .event-info h4 {
        color: var(--primary-color);
        margin-bottom: 0.5rem;
        font-size: 1rem;
    }

    .event-info p {
        color: #6b7280;
        margin: 0;
        font-size: 0.9rem;
    }

    @media (max-width: 768px) {
        .processing-card {
            margin: 1rem;
            padding: 2rem;
        }

        .countdown-timer {
            font-size: 1.5rem;
        }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="processing-container">
    <div class="processing-card">
        <div class="processing-icon">
            <i class="fas fa-cog"></i>
        </div>

        <h2 class="processing-title">Processing Your Payment</h2>

        <p class="processing-message">
            <?php if(isset($registrationData['payment_method']) && $registrationData['payment_method'] === 'card'): ?>
            Your card payment is being processed. This usually takes just a few moments.
            <?php else: ?>
            Processing your payment... This will take a few minutes. We will send you an invite once it's done.
            <?php endif; ?>
        </p>

        <div class="event-info">
            <h4><?php echo e($event->title); ?></h4>
            <p>Total Amount: $<?php echo e(number_format($registrationData['total_amount'], 2)); ?></p>
            <p>Email: <?php echo e($registrationData['attendee_email']); ?></p>
            <?php if(isset($registrationData['payment_method'])): ?>
            <p>
                Payment Method:
                <?php if($registrationData['payment_method'] === 'card'): ?>
                <i class="fas fa-credit-card me-1"></i>Credit/Debit Card
                <?php elseif($registrationData['payment_method'] === 'crypto'): ?>
                <i class="fas fa-coins me-1"></i>Cryptocurrency
                <?php else: ?>
                <?php echo e(ucfirst($registrationData['payment_method'])); ?>

                <?php endif; ?>
            </p>
            <?php endif; ?>
        </div>

        <div class="countdown-section">
            <div class="countdown-timer" id="countdownTimer">10</div>
            <div class="countdown-text">Redirecting to homepage in <span id="countdownText">10</span> seconds...</div>
        </div>

        <a href="<?php echo e(route('home')); ?>" class="manual-redirect-btn" id="manualRedirectBtn">
            <i class="fas fa-home me-2"></i>Go to Homepage Now
        </a>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    let countdownSeconds = 10;
    let countdownInterval;

    document.addEventListener('DOMContentLoaded', function() {
        startCountdown();
        processPaymentConfirmation();
    });

    function startCountdown() {
        const timerElement = document.getElementById('countdownTimer');
        const textElement = document.getElementById('countdownText');

        countdownInterval = setInterval(() => {
            countdownSeconds--;

            timerElement.textContent = countdownSeconds;
            textElement.textContent = countdownSeconds;

            if (countdownSeconds <= 0) {
                clearInterval(countdownInterval);
                redirectToHomepage();
            }
        }, 1000);
    }

    function processPaymentConfirmation() {
        // Call the backend to process the payment confirmation
        fetch('<?php echo e(route("payment.process", $event)); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                console.log('Payment confirmation processed:', data);
                // Continue with countdown regardless of backend response
                // The registration will be created in the background
            })
            .catch(error => {
                console.error('Payment confirmation processing error:', error);
                // Continue with countdown even if there's an error
                // User will be redirected and can contact support if needed
            });
    }

    function redirectToHomepage() {
        window.location.href = '<?php echo e(route("home")); ?>';
    }

    // Handle manual redirect button
    document.getElementById('manualRedirectBtn').addEventListener('click', function(e) {
        e.preventDefault();
        clearInterval(countdownInterval);
        redirectToHomepage();
    });

    // Clean up interval when page is unloaded
    window.addEventListener('beforeunload', function() {
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }
    });
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/Apple/Desktop/dev_folder/Dev_project/test.kiro2/leadership-summit-laravel/resources/views/payments/processing.blade.php ENDPATH**/ ?>