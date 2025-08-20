<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo UniPayment Checkout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .checkout-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
        }

        .checkout-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }

        .demo-badge {
            background: #fbbf24;
            color: #92400e;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: inline-block;
        }

        .amount-display {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 1rem 0;
        }

        .checkout-body {
            padding: 2rem;
        }

        .payment-info {
            background: #f8fafc;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .btn-complete {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            padding: 1rem 2rem;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-complete:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
            color: white;
        }

        .security-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
            color: #6b7280;
            font-size: 0.875rem;
        }
    </style>
</head>

<body>
    <div class="checkout-card">
        <div class="checkout-header">
            <div class="demo-badge">
                <i class="fas fa-flask"></i> DEMO MODE
            </div>
            <h2><i class="fas fa-credit-card"></i> UniPayment Checkout</h2>
            <div class="amount-display">${{ number_format($amount, 2) }} {{ $currency }}</div>
        </div>

        <div class="checkout-body">
            <div class="payment-info">
                <h5><i class="fas fa-info-circle text-primary"></i> Demo Payment Information</h5>
                <p class="mb-2"><strong>Invoice ID:</strong> {{ $invoiceId }}</p>
                <p class="mb-2"><strong>Amount:</strong> ${{ number_format($amount, 2) }} {{ $currency }}</p>
                <p class="mb-0"><strong>Status:</strong> <span class="badge bg-warning">Pending Payment</span></p>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-lightbulb"></i>
                <strong>Demo Mode:</strong> This is a simulated UniPayment checkout for testing purposes.
                No real payment will be processed.
            </div>

            <form method="POST" action="{{ route('demo.unipayment.complete', $invoiceId) }}">
                @csrf
                <button type="submit" class="btn btn-complete">
                    <i class="fas fa-check-circle"></i> Complete Demo Payment
                </button>
            </form>

            <div class="security-info">
                <i class="fas fa-shield-alt"></i>
                <span>Secure Demo Environment</span>
                <i class="fas fa-lock"></i>
                <span>SSL Protected</span>
            </div>

            <div class="text-center mt-3">
                <small class="text-muted">
                    In production, this would redirect to the actual UniPayment checkout page.
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>