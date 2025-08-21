<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ticket_selections' => 'array',
        'marked_at' => 'datetime',
        'payment_confirmed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'declined_at' => 'datetime',
        'terms_accepted_at' => 'datetime',
        'total_amount' => 'decimal:2',
        // Payment tracking casts
        'payment_amount' => 'decimal:2',
        'payment_fee' => 'decimal:2',
        'payment_completed_at' => 'datetime',
        'refund_amount' => 'decimal:2',
        'refunded_at' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'event_id',
        'ticket_id',
        'order_id',
        'status',
        'payment_status',
        'attendee_name',
        'attendee_email',
        'attendee_phone',
        'emergency_contact',
        'registration_status',
        'marked_at',
        'payment_confirmed_at',
        'confirmed_at',
        'confirmed_by',
        'declined_at',
        'declined_by',
        'declined_reason',
        'emergency_contact_name',
        'emergency_contact_phone',
        'ticket_selections',
        'total_amount',
        'terms_accepted_at',
        // Payment tracking fields
        'payment_method',
        'payment_provider',
        'transaction_id',
        'payment_amount',
        'payment_currency',
        'payment_fee',
        'payment_completed_at',
        'refund_amount',
        'refund_reason',
        'refunded_at',
    ];

    /**
     * Get the user that owns the registration.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the event that owns the registration.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the ticket that owns the registration.
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the payments for the registration.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the latest payment for the registration.
     */
    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latest();
    }

    /**
     * Get the order that owns the registration.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the payment transactions for the registration.
     */
    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * Get the latest payment transaction for the registration.
     */
    public function latestPaymentTransaction()
    {
        return $this->hasOne(PaymentTransaction::class)->latest();
    }

    /**
     * Get the admin user who confirmed the registration.
     */
    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Get the admin user who declined the registration.
     */
    public function declinedBy()
    {
        return $this->belongsTo(User::class, 'declined_by');
    }

    /**
     * Check if the registration is confirmed.
     *
     * @return bool
     */
    public function isConfirmed()
    {
        return $this->registration_status === 'confirmed';
    }

    /**
     * Check if the registration is pending.
     *
     * @return bool
     */
    public function isPending()
    {
        return $this->registration_status === 'pending';
    }

    /**
     * Check if the registration is declined.
     *
     * @return bool
     */
    public function isDeclined()
    {
        return $this->registration_status === 'declined';
    }

    /**
     * Check if the registration is cancelled.
     *
     * @return bool
     */
    public function isCancelled()
    {
        return $this->registration_status === 'cancelled';
    }

    /**
     * Check if the registration uses card payment.
     *
     * @return bool
     */
    public function isCardPayment()
    {
        return $this->payment_method === 'card';
    }

    /**
     * Check if the registration uses crypto payment.
     *
     * @return bool
     */
    public function isCryptoPayment()
    {
        return $this->payment_method === 'crypto';
    }

    /**
     * Check if the registration has been refunded.
     *
     * @return bool
     */
    public function isRefunded()
    {
        return !is_null($this->refunded_at);
    }

    /**
     * Check if payment is completed.
     *
     * @return bool
     */
    public function isPaymentCompleted()
    {
        return !is_null($this->payment_completed_at);
    }

    /**
     * Get the payment method display name.
     *
     * @return string
     */
    public function getPaymentMethodDisplayName()
    {
        return match ($this->payment_method) {
            'card' => 'Credit/Debit Card',
            'crypto' => 'Cryptocurrency',
            default => 'Unknown'
        };
    }

    /**
     * Get the payment provider display name.
     *
     * @return string
     */
    public function getPaymentProviderDisplayName()
    {
        return match ($this->payment_provider) {
            'unipayment' => 'UniPayment',
            'crypto' => 'Cryptocurrency',
            default => $this->payment_provider ?? 'Unknown'
        };
    }

    /**
     * Get validation rules for registration data.
     *
     * @return array
     */
    public static function getValidationRules()
    {
        return [
            'attendee_name' => 'required|string|max:255',
            'attendee_email' => 'required|email|max:255',
            'attendee_phone' => 'required|string|max:20',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'ticket_selections' => 'required|array',
            'ticket_selections.*' => 'integer|min:0',
            'total_amount' => 'required|numeric|min:0',
            'terms_accepted_at' => 'required|date',
            'registration_status' => 'in:pending,confirmed,cancelled,declined',
        ];
    }

    /**
     * Get validation rules for direct registration form.
     *
     * @param int $eventId
     * @return array
     */
    public static function getDirectRegistrationRules($eventId)
    {
        return [
            'attendee_name' => 'required|string|max:255',
            'attendee_email' => [
                'required',
                'email',
                'max:255',
                'unique:registrations,attendee_email,NULL,id,event_id,' . $eventId
            ],
            'attendee_phone' => [
                'required',
                'string',
                'max:20',
                'unique:registrations,attendee_phone,NULL,id,event_id,' . $eventId
            ],
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'ticket_selections' => 'required|array|min:1',
            'ticket_selections.*' => 'integer|min:1',
            'terms_accepted' => 'required|accepted',
        ];
    }

    /**
     * Get the payment status based on payment completion and refund status.
     *
     * @return string
     */
    public function getPaymentStatus()
    {
        if ($this->isRefunded()) {
            return 'refunded';
        }

        if ($this->isPaymentCompleted()) {
            return 'completed';
        }

        if ($this->payment_method && $this->transaction_id) {
            return 'pending';
        }

        return 'not_started';
    }

    /**
     * Get the payment status display name.
     *
     * @return string
     */
    public function getPaymentStatusDisplayName()
    {
        return match ($this->getPaymentStatus()) {
            'completed' => 'Payment Completed',
            'pending' => 'Payment Pending',
            'refunded' => 'Refunded',
            'not_started' => 'Payment Not Started',
            default => 'Unknown'
        };
    }

    /**
     * Check if the registration has a successful payment transaction.
     *
     * @return bool
     */
    public function hasSuccessfulPayment()
    {
        return $this->paymentTransactions()
            ->where('status', 'completed')
            ->exists();
    }

    /**
     * Check if the registration has any failed payment attempts.
     *
     * @return bool
     */
    public function hasFailedPayments()
    {
        return $this->paymentTransactions()
            ->where('status', 'failed')
            ->exists();
    }

    /**
     * Get the total amount paid including fees.
     *
     * @return float
     */
    public function getTotalAmountPaid()
    {
        return ($this->payment_amount ?? 0) + ($this->payment_fee ?? 0);
    }

    /**
     * Get the net amount after refunds.
     *
     * @return float
     */
    public function getNetAmount()
    {
        return ($this->payment_amount ?? 0) - ($this->refund_amount ?? 0);
    }

    /**
     * Mark payment as completed.
     *
     * @param array $paymentData
     * @return bool
     */
    public function markPaymentCompleted(array $paymentData = [])
    {
        $this->payment_completed_at = now();

        if (isset($paymentData['transaction_id'])) {
            $this->transaction_id = $paymentData['transaction_id'];
        }

        if (isset($paymentData['amount'])) {
            $this->payment_amount = $paymentData['amount'];
        }

        if (isset($paymentData['currency'])) {
            $this->payment_currency = $paymentData['currency'];
        }

        if (isset($paymentData['fee'])) {
            $this->payment_fee = $paymentData['fee'];
        }

        // Auto-confirm registration if payment is completed
        if ($this->registration_status === 'pending') {
            $this->registration_status = 'confirmed';
            $this->confirmed_at = now();
        }

        return $this->save();
    }

    /**
     * Process refund for the registration.
     *
     * @param float $amount
     * @param string $reason
     * @return bool
     */
    public function processRefund(float $amount, string $reason = '')
    {
        $this->refund_amount = $amount;
        $this->refund_reason = $reason;
        $this->refunded_at = now();

        return $this->save();
    }

    /**
     * Get the latest completed payment transaction.
     *
     * @return PaymentTransaction|null
     */
    public function getLatestCompletedTransaction()
    {
        return $this->paymentTransactions()
            ->where('status', 'completed')
            ->latest()
            ->first();
    }

    /**
     * Get all failed payment transactions.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFailedTransactions()
    {
        return $this->paymentTransactions()
            ->where('status', 'failed')
            ->get();
    }

    /**
     * Scope for filtering by payment method.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $method
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope for filtering by payment status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPaymentStatus($query, $status)
    {
        return match ($status) {
            'completed' => $query->whereNotNull('payment_completed_at'),
            'pending' => $query->whereNull('payment_completed_at')
                ->whereNotNull('payment_method'),
            'refunded' => $query->whereNotNull('refunded_at'),
            'not_started' => $query->whereNull('payment_method'),
            default => $query
        };
    }

    /**
     * Scope for filtering by payment provider.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $provider
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPaymentProvider($query, $provider)
    {
        return $query->where('payment_provider', $provider);
    }
}
