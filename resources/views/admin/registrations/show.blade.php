@extends('layouts.admin')

@section('title', 'Registration Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Registration Details</h1>
                <a href="{{ route('admin.registrations.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Registrations
                </a>
            </div>

            <div class="row">
                <!-- Registration Information -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Registration Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Registration ID:</strong></td>
                                    <td>{{ $registration->id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $registration->registration_status === 'confirmed' ? 'success' : ($registration->registration_status === 'pending' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($registration->registration_status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Event:</strong></td>
                                    <td>{{ $registration->event->title ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Attendee Name:</strong></td>
                                    <td>{{ $registration->attendee_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>{{ $registration->attendee_email }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td>{{ $registration->attendee_phone }}</td>
                                </tr>
                                @if($registration->emergency_contact_name)
                                <tr>
                                    <td><strong>Emergency Contact:</strong></td>
                                    <td>
                                        {{ $registration->emergency_contact_name }}
                                        @if($registration->emergency_contact_phone)
                                        <br><small class="text-muted">{{ $registration->emergency_contact_phone }}</small>
                                        @endif
                                    </td>
                                </tr>
                                @endif
                                <tr>
                                    <td><strong>Registration Date:</strong></td>
                                    <td>{{ $registration->created_at->format('M d, Y H:i') }}</td>
                                </tr>
                                @if($registration->confirmed_at)
                                <tr>
                                    <td><strong>Confirmed At:</strong></td>
                                    <td>
                                        {{ $registration->confirmed_at->format('M d, Y H:i') }}
                                        @if($registration->confirmedBy)
                                        <br><small class="text-muted">by {{ $registration->confirmedBy->name }}</small>
                                        @endif
                                    </td>
                                </tr>
                                @endif
                                @if($registration->declined_at)
                                <tr>
                                    <td><strong>Declined At:</strong></td>
                                    <td>
                                        {{ $registration->declined_at->format('M d, Y H:i') }}
                                        @if($registration->declinedBy)
                                        <br><small class="text-muted">by {{ $registration->declinedBy->name }}</small>
                                        @endif
                                        @if($registration->declined_reason)
                                        <br><small class="text-muted">Reason: {{ $registration->declined_reason }}</small>
                                        @endif
                                    </td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Payment Information</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Payment Status:</strong></td>
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
                                    </td>
                                </tr>
                                @if($registration->payment_method)
                                <tr>
                                    <td><strong>Payment Method:</strong></td>
                                    <td>{{ $registration->getPaymentMethodDisplayName() }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Payment Provider:</strong></td>
                                    <td>{{ $registration->getPaymentProviderDisplayName() }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td><strong>Total Amount:</strong></td>
                                    <td>${{ number_format($registration->total_amount ?? 0, 2) }}</td>
                                </tr>
                                @if($registration->payment_amount)
                                <tr>
                                    <td><strong>Amount Paid:</strong></td>
                                    <td>${{ number_format($registration->payment_amount, 2) }}</td>
                                </tr>
                                @endif
                                @if($registration->payment_fee)
                                <tr>
                                    <td><strong>Processing Fee:</strong></td>
                                    <td>${{ number_format($registration->payment_fee, 2) }}</td>
                                </tr>
                                @endif
                                @if($registration->payment_currency)
                                <tr>
                                    <td><strong>Currency:</strong></td>
                                    <td>{{ strtoupper($registration->payment_currency) }}</td>
                                </tr>
                                @endif
                                @if($registration->transaction_id)
                                <tr>
                                    <td><strong>Transaction ID:</strong></td>
                                    <td><code>{{ $registration->transaction_id }}</code></td>
                                </tr>
                                @endif
                                @if($registration->payment_completed_at)
                                <tr>
                                    <td><strong>Payment Completed:</strong></td>
                                    <td>{{ $registration->payment_completed_at->format('M d, Y H:i') }}</td>
                                </tr>
                                @endif
                                @if($registration->isRefunded())
                                <tr>
                                    <td><strong>Refund Amount:</strong></td>
                                    <td>${{ number_format($registration->refund_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Refunded At:</strong></td>
                                    <td>{{ $registration->refunded_at->format('M d, Y H:i') }}</td>
                                </tr>
                                @if($registration->refund_reason)
                                <tr>
                                    <td><strong>Refund Reason:</strong></td>
                                    <td>{{ $registration->refund_reason }}</td>
                                </tr>
                                @endif
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Transactions -->
            @if($registration->paymentTransactions->count() > 0)
            <div class="row">
                <div class="col-12">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Payment Transactions</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Transaction ID</th>
                                            <th>Provider</th>
                                            <th>Method</th>
                                            <th>Amount</th>
                                            <th>Fee</th>
                                            <th>Status</th>
                                            <th>Processed At</th>
                                            <th>Created At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($registration->paymentTransactions as $transaction)
                                        <tr>
                                            <td><code>{{ $transaction->transaction_id }}</code></td>
                                            <td>{{ ucfirst($transaction->provider) }}</td>
                                            <td>{{ ucfirst($transaction->payment_method) }}</td>
                                            <td>${{ number_format($transaction->amount, 2) }} {{ strtoupper($transaction->currency) }}</td>
                                            <td>${{ number_format($transaction->fee, 2) }}</td>
                                            <td>
                                                @php
                                                $statusColor = match($transaction->status) {
                                                'completed' => 'success',
                                                'pending' => 'warning',
                                                'failed' => 'danger',
                                                'refunded' => 'info',
                                                default => 'secondary'
                                                };
                                                @endphp
                                                <span class="badge bg-{{ $statusColor }}">
                                                    {{ ucfirst($transaction->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($transaction->processed_at)
                                                {{ $transaction->processed_at->format('M d, Y H:i') }}
                                                @else
                                                <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>{{ $transaction->created_at->format('M d, Y H:i') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Ticket Selections -->
            @if($registration->ticket_selections && count($registration->ticket_selections) > 0)
            <div class="row">
                <div class="col-12">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Ticket Selections</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Ticket Type</th>
                                            <th>Quantity</th>
                                            <th>Price</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $total = 0; @endphp
                                        @foreach($registration->ticket_selections as $ticketId => $quantity)
                                        @if($quantity > 0)
                                        @php
                                        $ticket = \App\Models\Ticket::find($ticketId);
                                        $subtotal = $ticket ? $ticket->price * $quantity : 0;
                                        $total += $subtotal;
                                        @endphp
                                        <tr>
                                            <td>{{ $ticket->name ?? 'Unknown Ticket' }}</td>
                                            <td>{{ $quantity }}</td>
                                            <td>${{ number_format($ticket->price ?? 0, 2) }}</td>
                                            <td>${{ number_format($subtotal, 2) }}</td>
                                        </tr>
                                        @endif
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-active">
                                            <th colspan="3">Total</th>
                                            <th>${{ number_format($total, 2) }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Actions -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="btn-group" role="group">
                                @if($registration->registration_status === 'pending')
                                <form method="POST" action="{{ route('admin.registrations.updateStatus', $registration) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="confirmed">
                                    <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to confirm this registration?')">
                                        <i class="fas fa-check"></i> Confirm Registration
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.registrations.updateStatus', $registration) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="declined">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to decline this registration?')">
                                        <i class="fas fa-times"></i> Decline Registration
                                    </button>
                                </form>
                                @endif

                                @if($registration->registration_status === 'confirmed')
                                <form method="POST" action="{{ route('admin.registrations.updateStatus', $registration) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="cancelled">
                                    <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to cancel this registration?')">
                                        <i class="fas fa-ban"></i> Cancel Registration
                                    </button>
                                </form>
                                @endif

                                <form method="POST" action="{{ route('admin.registrations.destroy', $registration) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete this registration? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i> Delete Registration
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection