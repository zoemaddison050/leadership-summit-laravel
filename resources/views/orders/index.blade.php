@extends('layouts.app')

@section('title', 'My Orders')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>My Orders</h2>
                <a href="{{ route('events.index') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>
                    Browse Events
                </a>
            </div>

            @if($orders->count() > 0)
            <div class="row">
                @foreach($orders as $order)
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5 class="mb-0">Order {{ $order->order_number }}</h5>
                                    <small class="text-muted">
                                        Placed on {{ $order->created_at->format('M d, Y g:i A') }}
                                    </small>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <span class="badge {{ $order->getStatusBadgeClass() }} me-2">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                    <strong>${{ number_format($order->total_amount, 2) }}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Order Items -->
                            <div class="mb-3">
                                <h6>Items:</h6>
                                @foreach($order->registrations as $registration)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong>{{ $registration->event->title }}</strong><br>
                                        <small class="text-muted">{{ $registration->ticket->name }}</small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-{{ $registration->status === 'confirmed' ? 'success' : ($registration->status === 'cancelled' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($registration->status) }}
                                        </span><br>
                                        <small>${{ number_format($registration->ticket->price, 2) }}</small>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <!-- Order Summary -->
                            <div class="row">
                                <div class="col-md-8">
                                    @if($order->completed_at)
                                    <small class="text-success">
                                        <i class="fas fa-check-circle me-1"></i>
                                        Completed on {{ $order->completed_at->format('M d, Y g:i A') }}
                                    </small>
                                    @elseif($order->cancelled_at)
                                    <small class="text-danger">
                                        <i class="fas fa-times-circle me-1"></i>
                                        Cancelled on {{ $order->cancelled_at->format('M d, Y g:i A') }}
                                    </small>
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    <div class="text-end">
                                        <small class="text-muted">Subtotal: ${{ number_format($order->subtotal, 2) }}</small><br>
                                        <small class="text-muted">Tax: ${{ number_format($order->tax_amount, 2) }}</small><br>
                                        @if($order->discount_amount > 0)
                                        <small class="text-success">Discount: -${{ number_format($order->discount_amount, 2) }}</small><br>
                                        @endif
                                        <strong>Total: ${{ number_format($order->total_amount, 2) }}</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="mt-3 d-flex gap-2">
                                <a href="{{ route('orders.show', $order) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i>
                                    View Details
                                </a>

                                @if($order->isCompleted())
                                <a href="{{ route('orders.receipt', $order) }}" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-download me-1"></i>
                                    Download Receipt
                                </a>
                                @endif

                                @if($order->isPending() && !$order->isCancelled())
                                <form method="POST"
                                    action="{{ route('orders.cancel', $order) }}"
                                    class="d-inline"
                                    onsubmit="return confirm('Are you sure you want to cancel this order?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="fas fa-times me-1"></i>
                                        Cancel Order
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {{ $orders->links() }}
            </div>
            @else
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-shopping-cart fa-4x text-muted"></i>
                </div>
                <h4>No Orders Yet</h4>
                <p class="text-muted">You haven't placed any orders yet.</p>
                <a href="{{ route('events.index') }}" class="btn btn-primary">
                    <i class="fas fa-search me-1"></i>
                    Browse Events
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection