<?php $__env->startSection('title', 'Crypto Payment - ' . $event->title); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .payment-container {
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
        max-width: 600px;
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

    .crypto-options {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .crypto-option {
        border: 2px solid #e5e7eb;
        border-radius: 1rem;
        padding: 1.5rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .crypto-option:hover {
        border-color: var(--primary-color);
        transform: translateY(-2px);
    }

    .crypto-option.selected {
        border-color: var(--primary-color);
        background: linear-gradient(135deg, rgba(10, 36, 99, 0.05), rgba(59, 130, 246, 0.05));
    }

    .crypto-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }



    .payment-details {
        background: #f8fafc;
        border-radius: 1rem;
        padding: 1.5rem;
        margin-top: 2rem;
        display: none;
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .payment-details.show {
        display: block;
    }

    .qr-code {
        text-align: center;
        margin: 1.5rem 0;
    }

    .crypto-address {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 1rem;
        font-family: monospace;
        word-break: break-all;
        margin: 1rem 0;
    }

    .copy-button {
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: 0.5rem;
        padding: 0.5rem 1rem;
        cursor: pointer;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }

    .copy-button:hover {
        background: #1e3a8a;
        transform: translateY(-1px);
    }

    .crypto-amount-display {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--primary-color);
        font-family: monospace;
    }

    .status-indicator {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-top: 1rem;
    }

    .status-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .status-completed {
        background: #d1fae5;
        color: #065f46;
    }

    .status-failed {
        background: #fee2e2;
        color: #991b1b;
    }

    @media (max-width: 768px) {
        .payment-card {
            margin: 1rem;
            padding: 1.5rem;
        }

        .crypto-options {
            grid-template-columns: 1fr;
        }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="payment-container">
    <div class="container">
        <div class="payment-card">
            <div class="payment-header">
                <h2>Complete Your Registration</h2>
                <p class="text-muted"><?php echo e($event->title); ?></p>
            </div>

            <!-- Registration Summary -->
            <div class="order-summary">
                <h4 class="mb-3">Registration Summary</h4>

                <?php if(isset($registrationData)): ?>
                <!-- New direct registration flow -->
                <div class="summary-item">
                    <div>
                        <div><strong>Attendee:</strong> <?php echo e($registrationData['attendee_name']); ?></div>
                        <small class="text-muted"><?php echo e($registrationData['attendee_email']); ?></small>
                    </div>
                </div>

                <?php if(!empty($registrationData['attendee_phone'])): ?>
                <div class="summary-item">
                    <div>
                        <div><strong>Phone:</strong> <?php echo e($registrationData['attendee_phone']); ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(!empty($registrationData['emergency_contact_name'])): ?>
                <div class="summary-item">
                    <div>
                        <div><strong>Emergency Contact:</strong> <?php echo e($registrationData['emergency_contact_name']); ?></div>
                        <?php if(!empty($registrationData['emergency_contact_phone'])): ?>
                        <small class="text-muted"><?php echo e($registrationData['emergency_contact_phone']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php $__currentLoopData = $registrationData['ticket_selections']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticketData): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="summary-item">
                    <div>
                        <div><?php echo e($ticketData['ticket_name']); ?></div>
                        <small class="text-muted">Qty: <?php echo e($ticketData['quantity']); ?> × $<?php echo e(number_format($ticketData['price'], 2)); ?></small>
                    </div>
                    <span>$<?php echo e(number_format($ticketData['subtotal'], 2)); ?></span>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                <div class="summary-item">
                    <span>Total:</span>
                    <span>$<?php echo e(number_format($registrationData['total_amount'], 2)); ?></span>
                </div>
                <?php else: ?>
                <!-- Fallback to old ticket selection flow -->
                <?php $__currentLoopData = $ticketSelection['tickets']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ticketData): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="summary-item">
                    <div>
                        <div><?php echo e($ticketData['ticket']->name); ?></div>
                        <small class="text-muted">Qty: <?php echo e($ticketData['quantity']); ?> × $<?php echo e(number_format($ticketData['ticket']->price, 2)); ?></small>
                    </div>
                    <span>$<?php echo e(number_format($ticketData['subtotal'], 2)); ?></span>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <div class="summary-item">
                    <span>Total:</span>
                    <span>$<?php echo e(number_format($ticketSelection['total_amount'], 2)); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Crypto Payment Options -->
            <h4 class="mb-3">Select Cryptocurrency</h4>

            <div class="crypto-options" id="cryptoOptions">
                <div class="crypto-option" data-crypto="bitcoin">
                    <div class="crypto-icon">₿</div>
                    <div>Bitcoin</div>
                    <small class="text-muted">BTC</small>
                </div>
                <div class="crypto-option" data-crypto="ethereum">
                    <div class="crypto-icon">Ξ</div>
                    <div>Ethereum</div>
                    <small class="text-muted">ETH</small>
                </div>
                <div class="crypto-option" data-crypto="usdt">
                    <div class="crypto-icon">₮</div>
                    <div>USDT (ERC-20)</div>
                    <small class="text-muted">USDT</small>
                </div>
            </div>

            <?php if(isset($registrationData)): ?>
            <!-- New direct registration flow - Instructions -->
            <div class="payment-instructions mb-4">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Payment Instructions:</strong><br>
                    Select a cryptocurrency above to see payment details with current exchange rates and QR code.
                </div>
            </div>
            <?php else: ?>
            <!-- Old flow - Initialize payment button -->
            <button id="initPaymentBtn" class="btn btn-primary w-100" disabled>
                <i class="fas fa-lock me-2"></i>Initialize Secure Payment
            </button>
            <?php endif; ?>

            <!-- Payment Details (shown after cryptocurrency selection) -->
            <div id="paymentDetails" class="payment-details">
                <div class="text-center mb-4">
                    <h4><i class="fas fa-coins me-2"></i>Payment Instructions</h4>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Send exactly <strong id="cryptoAmount"></strong> to the address below
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="qr-code">
                            <h5 class="text-center mb-3">Scan QR Code</h5>
                            <img id="qrCode" src="" alt="QR Code" style="max-width: 250px; width: 100%;" class="border rounded">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5>Payment Details</h5>
                        <div class="mb-3">
                            <label class="form-label"><strong>Amount:</strong></label>
                            <div class="crypto-amount-display" id="cryptoAmountDisplay"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><strong>Current Rate:</strong></label>
                            <div id="cryptoRate"></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><strong>Wallet Address:</strong></label>
                            <div class="crypto-address" id="cryptoAddress"></div>
                            <button class="copy-button mt-2" onclick="copyAddress()">
                                <i class="fas fa-copy me-1"></i>Copy Address
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Important:</strong> Send only <span id="cryptoCurrencyName"></span> to this address.
                        Sending other cryptocurrencies may result in permanent loss.
                    </div>
                </div>

                <?php if(isset($registrationData)): ?>
                <div class="text-center mt-4">
                    <button id="confirmPaymentBtn" class="btn btn-success btn-lg">
                        <i class="fas fa-check me-2"></i>I Have Sent the Payment
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    // Configuration variables from server
    window.cryptoPaymentConfig = {
        hasRegistrationData: <?php echo e(isset($registrationData) ? 'true' : 'false'); ?>,        csrfToken: '<?php echo e(csrf_token()); ?>',
        detailsUrl: '<?php echo e(route("payment.crypto.details", $event)); ?>',
        confirmUrl: '<?php echo e(route("payment.confirm", $event)); ?>',
        <?php if(isset($registrationData) && array_key_exists('expires_at', $registrationData)): ?>
        registrationExpiresAt: '<?php echo e($registrationData["expires_at"]); ?>'
        <?php else: ?>
        registrationExpiresAt: null
        <?php endif; ?>
    };
</script>
<script>
    let selectedCrypto = null;
    let currentPaymentData = null;

    document.addEventListener('DOMContentLoaded', function() {
        // Set up crypto option selection
        document.querySelectorAll('.crypto-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove previous selection
                document.querySelectorAll('.crypto-option').forEach(opt => opt.classList.remove('selected'));

                // Add selection to clicked option
                this.classList.add('selected');
                selectedCrypto = this.dataset.crypto;

                // Load payment details for selected cryptocurrency
                loadPaymentDetails(selectedCrypto);
            });
        });

        // Handle payment confirmation button (will be added dynamically)
        document.addEventListener('click', function(e) {
            if (e.target && e.target.id === 'confirmPaymentBtn') {
                e.target.disabled = true;
                e.target.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';

                if (window.cryptoPaymentConfig.hasRegistrationData) {
                    // Create a form and submit it to handle the redirect properly
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = window.cryptoPaymentConfig.confirmUrl;

                    // Add CSRF token
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = window.cryptoPaymentConfig.csrfToken;
                    form.appendChild(csrfInput);

                    // Add form to page and submit
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        });
    });



    function loadPaymentDetails(cryptocurrency) {
        // Show loading state
        const paymentDetails = document.getElementById('paymentDetails');
        paymentDetails.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin me-2"></i>Loading payment details...</div>';
        paymentDetails.classList.add('show');

        console.log('Loading payment details for:', cryptocurrency);
        console.log('CSRF Token:', window.cryptoPaymentConfig.csrfToken);
        console.log('Details URL:', window.cryptoPaymentConfig.detailsUrl);

        // Get CSRF token from config or meta tag
        const csrfToken = window.cryptoPaymentConfig.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!csrfToken) {
            console.error('CSRF token not found');
            showError('Security token not found. Please refresh the page and try again.');
            return;
        }

        fetch(window.cryptoPaymentConfig.detailsUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    cryptocurrency: cryptocurrency
                })
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);

                if (response.status === 419) {
                    throw new Error('CSRF token expired. Please refresh the page and try again.');
                }

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    showPaymentDetails(data.data);
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                if (error.message.includes('CSRF')) {
                    showError(error.message);
                } else {
                    showError('Failed to load payment details. Please try again.');
                }
            });
    }

    function showPaymentDetails(data) {
        currentPaymentData = data;

        const paymentDetails = document.getElementById('paymentDetails');
        paymentDetails.innerHTML = `
            <div class="text-center mb-4">
                <h4><i class="fas fa-coins me-2"></i>Payment Instructions</h4>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Send exactly <strong>${data.formatted_amount}</strong> to the address below
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="qr-code text-center">
                        <h5 class="mb-3">Scan QR Code</h5>
                        <img src="${data.qr_code}" alt="QR Code" style="max-width: 250px; width: 100%;" class="border rounded">
                    </div>
                </div>
                <div class="col-md-6">
                    <h5>Payment Details</h5>
                    <div class="mb-3">
                        <label class="form-label"><strong>Amount:</strong></label>
                        <div class="crypto-amount-display">${data.formatted_amount}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><strong>Current Rate:</strong></label>
                        <div>1 ${data.currency_code} = $${data.crypto_price_usd.toLocaleString()}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><strong>Wallet Address:</strong></label>
                        <div class="crypto-address">${data.wallet_address}</div>
                        <button class="copy-button mt-2" onclick="copyAddress()">
                            <i class="fas fa-copy me-1"></i>Copy Address
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Important:</strong> Send only ${data.currency_name} to this address. 
                    Sending other cryptocurrencies may result in permanent loss.
                </div>
            </div>
        `;

        if (window.cryptoPaymentConfig.hasRegistrationData) {
            paymentDetails.innerHTML += `
                <div class="text-center mt-4">
                    <button id="confirmPaymentBtn" class="btn btn-success btn-lg">
                        <i class="fas fa-check me-2"></i>I Have Sent the Payment
                    </button>
                </div>
            `;
        }
    }

    function showError(message) {
        const paymentDetails = document.getElementById('paymentDetails');
        paymentDetails.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                ${message}
            </div>
        `;
    }

    function copyAddress() {
        if (!currentPaymentData) return;

        const address = currentPaymentData.wallet_address;
        navigator.clipboard.writeText(address).then(() => {
            const button = event.target.closest('.copy-button');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
            button.classList.add('btn-success');
            button.classList.remove('copy-button');

            setTimeout(() => {
                button.innerHTML = originalText;
                button.classList.remove('btn-success');
                button.classList.add('copy-button');
            }, 2000);
        }).catch(err => {
            console.error('Failed to copy address:', err);
            alert('Failed to copy address. Please copy manually.');
        });
    }
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/Apple/Desktop/dev_folder/Dev_project/test.kiro2/leadership-summit-laravel/resources/views/payments/crypto.blade.php ENDPATH**/ ?>