@extends('layouts.app')

@section('title', 'Order Details - ' . $order->order_number)

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Order Details</h2>
                <div>
                    <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to Orders
                    </a>
                    @if($order->isCompleted())
                    <a href="{{ route('orders.receipt', $order) }}" class="btn btn-outline-success">
                        <i class="fas fa-download me-1"></i>
                        Download Receipt
                    </a>
                    @endif
                    <button class="btn btn-outline-primary" onclick="window.print()">
                        <i class="fas fa-print me-1"></i>
                        Print
                    </button>
                </div>
            </div>

            <div class="row">
                <!-- Order Information -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Order Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Order Number:</strong></td>
                                            <td>{{ $order->order_number }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>
                                                <span class="badge {{ $order->getStatusBadgeClass() }}">
                                                    {{ ucfirst($order->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Order Date:</strong></td>
                                            <td>{{ $order->created_at->format('M d, Y g:i A') }}</td>
                                        </tr>
                                        @if($order->completed_at)
                                        <tr>
                                            <td><strong>Completed:</strong></td>
                                            <td>{{ $order->completed_at->format('M d, Y g:i A') }}</td>
                                        </tr>
                                        @endif
                                        @if($order->cancelled_at)
                                        <tr>
                                            <td><strong>Cancelled:</strong></td>
                                            <td>{{ $order->cancelled_at->format('M d, Y g:i A') }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Customer:</strong></td>
                                            <td>{{ $order->user->name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Email:</strong></td>
                                            <td>{{ $order->user->email }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Currency:</strong></td>
                                            <td>{{ strtoupper($order->currency) }}</td>
                                        </tr>
                                        @if($order->notes)
                                        <tr>
                                            <td><strong>Notes:</strong></td>
                                            <td>{{ $order->notes }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Order Items</h5>
                        </div>
                        <div class="card-body">
                            @foreach($orderSummary['registrations'] as $registration)
                            <div class="row align-items-center mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                                <div class="col-md-8">
                                    <h6>{{ $registration['event_title'] }}</h6>
                                    <p class="text-muted mb-1">{{ $registration['ticket_name'] }}</p>
                                    <div>
                                        <span class="badge bg-{{ $registration['status'] === 'confirmed' ? 'success' : ($registration['status'] === 'cancelled' ? 'danger' : 'warning') }} me-2">
                                            Registration: {{ ucfirst($registration['status']) }}
                                        </span>
                                        <span class="badge bg-{{ $registration['payment_status'] === 'completed' ? 'success' : 'warning' }}">
                                            Payment: {{ ucfirst($registration['payment_status']) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="h5">${{ number_format($registration['ticket_price'], 2) }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Billing Details -->
                    @if($order->billing_details)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Billing Information</h5>
                        </div>
                        <div class="card-body">
                            @foreach($order->billing_details as $key => $value)
                            @if($value)
                            <p><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}</p>
                            @endif
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Order Summary Sidebar -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span>${{ number_format($order->subtotal, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Tax:</span>
                                <span>${{ number_format($order->tax_amount, 2) }}</span>
                            </div>
                            @if($order->discount_amount > 0)
                            <div class="d-flex justify-content-between mb-2 text-success">
                                <span>Discount:</span>
                                <span>-${{ number_format($order->discount_amount, 2) }}</span>
                            </div>
                            @endif
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>Total:</strong>
                                <strong>${{ number_format($order->total_amount, 2) }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="mb-0">Actions</h6>
                        </div>
                        <div class="card-body">
                            @if($order->isCompleted())
                            <a href="{{ route('orders.receipt', $order) }}" class="btn btn-success w-100 mb-2">
                                <i class="fas fa-download me-1"></i>
                                Download Receipt
                            </a>
                            @endif

                            @if($order->isPending() && !$order->isCancelled())
                            <form method="POST"
                                action="{{ route('orders.cancel', $order) }}"
                                onsubmit="return confirm('Are you sure you want to cancel this order? This action cannot be undone.')">
                                @csrf
                                @method('PATCH')
                                <div class="mb-3">
                                    <label for="reason" class="form-label">Cancellation Reason (Optional)</label>
                                    <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="Please provide a reason for cancellation..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="fas fa-times me-1"></i>
                                    Cancel Order
                                </button>
                            </form>
                            @endif

                            <button class="btn btn-outline-secondary w-100 mt-2" onclick="window.print()">
                                <i class="fas fa-print me-1"></i>
                                Print Order
                            </button>
                        </div>
                    </div>

                    <!-- Order Timeline -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="mb-0">Order Timeline</h6>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <div class="timeline-item">
                                    <i class="fas fa-plus-circle text-primary"></i>
                                    <div class="timeline-content">
                                        <strong>Order Created</strong><br>
                                        <small>{{ $order->created_at->format('M d, Y g:i A') }}</small>
                                    </div>
                                </div>

                                @if($order->completed_at)
                                <div class="timeline-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <div class="timeline-content">
                                        <strong>Order Completed</strong><br>
                                        <small>{{ $order->completed_at->format('M d, Y g:i A') }}</small>
                                    </div>
                                </div>
                                @endif

                                @if($order->cancelled_at)
                                <div class="timeline-item">
                                    <i class="fas fa-times-circle text-danger"></i>
                                    <div class="timeline-content">
                                        <strong>Order Cancelled</strong><br>
                                        <small>{{ $order->cancelled_at->format('M d, Y g:i A') }}</small>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .timeline {
        position: relative;
        padding-left: 30px;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }

    .timeline-item i {
        position: absolute;
        left: -35px;
        top: 2px;
        font-size: 16px;
    }

    .timeline-item:not(:last-child)::before {
        content: '';
        position: absolute;
        left: -27px;
        top: 20px;
        width: 2px;
        height: calc(100% + 10px);
        background-color: #dee2e6;
    }

    @media print {

        .btn,
        .card-header {
            display: none !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
        }

        .col-md-4:last-child .card:not(:first-child) {
            display: none !important;
        }
    }
</style>
@endsection