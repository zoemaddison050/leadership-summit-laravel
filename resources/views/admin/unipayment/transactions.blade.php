@extends('layouts.admin')

@section('title', 'UniPayment Transactions')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-list mr-2"></i>
                        Payment Transactions
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.unipayment.index') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-cog mr-1"></i>
                            Settings
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <select name="payment_method" class="form-control">
                                    <option value="">All Payment Methods</option>
                                    @foreach($paymentMethods as $method)
                                    <option value="{{ $method }}" {{ request('payment_method') == $method ? 'selected' : '' }}>
                                        {{ ucfirst($method) }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-control">
                                    <option value="">All Statuses</option>
                                    @foreach($statuses as $status)
                                    <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                        {{ ucfirst($status) }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From Date">
                            </div>
                            <div class="col-md-2">
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To Date">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="{{ route('admin.unipayment.transactions') }}" class="btn btn-secondary">Clear</a>
                            </div>
                        </div>
                    </form>

                    @if($transactions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>Registration</th>
                                    <th>Payment Method</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $transaction)
                                <tr>
                                    <td>
                                        <code>{{ $transaction->transaction_id }}</code>
                                    </td>
                                    <td>
                                        @if($transaction->registration)
                                        <a href="{{ route('admin.registrations.show', $transaction->registration) }}">
                                            {{ $transaction->registration->attendee_name }}
                                        </a>
                                        <br>
                                        <small class="text-muted">{{ $transaction->registration->attendee_email }}</small>
                                        @else
                                        <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ ucfirst($transaction->payment_method) }}</span>
                                    </td>
                                    <td>
                                        <strong>${{ number_format($transaction->amount, 2) }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $transaction->currency }}</small>
                                    </td>
                                    <td>
                                        @if($transaction->status === 'completed')
                                        <span class="badge badge-success">{{ ucfirst($transaction->status) }}</span>
                                        @elseif($transaction->status === 'pending')
                                        <span class="badge badge-warning">{{ ucfirst($transaction->status) }}</span>
                                        @elseif($transaction->status === 'failed')
                                        <span class="badge badge-danger">{{ ucfirst($transaction->status) }}</span>
                                        @else
                                        <span class="badge badge-secondary">{{ ucfirst($transaction->status) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $transaction->created_at->format('M j, Y') }}
                                        <br>
                                        <small class="text-muted">{{ $transaction->created_at->format('g:i A') }}</small>
                                    </td>
                                    <td>
                                        @if($transaction->registration)
                                        <a href="{{ route('admin.registrations.show', $transaction->registration) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $transactions->appends(request()->query())->links() }}
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Transactions Found</h5>
                        <p class="text-muted">
                            @if(request()->hasAny(['payment_method', 'status', 'date_from', 'date_to']))
                            No transactions match your current filters.
                            @else
                            No UniPayment transactions have been processed yet.
                            @endif
                        </p>
                    </div>
                    @endif
                </div>

                <div class="card-footer">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection