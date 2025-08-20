@extends('layouts.admin')

@section('title', 'Registrations Management')

@php
use Illuminate\Support\Str;
@endphp

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Registrations Management</h1>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                <option value="declined" {{ request('status') === 'declined' ? 'selected' : '' }}>Declined</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="payment_status" class="form-label">Payment Status</label>
                            <select name="payment_status" id="payment_status" class="form-select">
                                <option value="">All Payment Statuses</option>
                                <option value="not_started" {{ request('payment_status') === 'not_started' ? 'selected' : '' }}>Not Started</option>
                                <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="completed" {{ request('payment_status') === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="refunded" {{ request('payment_status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select name="payment_method" id="payment_method" class="form-select">
                                <option value="">All Methods</option>
                                <option value="card" {{ request('payment_method') === 'card' ? 'selected' : '' }}>Card</option>
                                <option value="crypto" {{ request('payment_method') === 'crypto' ? 'selected' : '' }}>Crypto</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="payment_provider" class="form-label">Payment Provider</label>
                            <select name="payment_provider" id="payment_provider" class="form-select">
                                <option value="">All Providers</option>
                                <option value="unipayment" {{ request('payment_provider') === 'unipayment' ? 'selected' : '' }}>UniPayment</option>
                                <option value="crypto" {{ request('payment_provider') === 'crypto' ? 'selected' : '' }}>Crypto</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="Name, email, transaction..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Registrations Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Attendee</th>
                                    <th>Event</th>
                                    <th>Status</th>
                                    <th>Payment Status</th>
                                    <th>Payment Method</th>
                                    <th>Amount</th>
                                    <th>Transaction ID</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($registrations as $registration)
                                <tr>
                                    <td>{{ $registration->id }}</td>
                                    <td>
                                        <div>{{ $registration->attendee_name }}</div>
                                        <small class="text-muted">{{ $registration->attendee_email }}</small>
                                    </td>
                                    <td>{{ $registration->event->title ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $registration->registration_status === 'confirmed' ? 'success' : ($registration->registration_status === 'pending' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($registration->registration_status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                        $paymentStatus = $registration->getPaymentStatus();
                                        $badgeColor = match($paymentStatus) {
                                        'completed' => 'success',
                                        'pending' => 'warning',
                                        'refunded' => 'info',
                                        'not_started' => 'secondary',
                                        default => 'danger'
                                        };
                                        @endphp
                                        <span class="badge bg-{{ $badgeColor }}">
                                            {{ $registration->getPaymentStatusDisplayName() }}
                                        </span>
                                        @if($registration->isRefunded())
                                        <br><small class="text-muted">Refunded: ${{ number_format($registration->refund_amount, 2) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($registration->payment_method)
                                        <div>{{ $registration->getPaymentMethodDisplayName() }}</div>
                                        <small class="text-muted">{{ $registration->getPaymentProviderDisplayName() }}</small>
                                        @else
                                        <span class="text-muted">Not selected</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>${{ number_format($registration->total_amount ?? 0, 2) }}</div>
                                        @if($registration->payment_amount && $registration->payment_amount != $registration->total_amount)
                                        <small class="text-muted">Paid: ${{ number_format($registration->payment_amount, 2) }}</small>
                                        @endif
                                        @if($registration->payment_fee)
                                        <small class="text-muted">Fee: ${{ number_format($registration->payment_fee, 2) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($registration->transaction_id)
                                        <code class="small">{{ Str::limit($registration->transaction_id, 15) }}</code>
                                        @else
                                        <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $registration->created_at->format('M d, Y') }}</div>
                                        @if($registration->payment_completed_at)
                                        <small class="text-muted">Paid: {{ $registration->payment_completed_at->format('M d, Y') }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.registrations.show', $registration) }}" class="btn btn-sm btn-outline-primary">View</a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center py-4">No registrations found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $registrations->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection