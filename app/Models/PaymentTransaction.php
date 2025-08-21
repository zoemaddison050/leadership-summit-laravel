<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_id',
        'provider',
        'transaction_id',
        'payment_method',
        'amount',
        'currency',
        'fee',
        'status',
        'provider_response',
        'callback_data',
        'processed_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'provider_response' => 'array',
        'callback_data' => 'array',
        'processed_at' => 'datetime'
    ];

    /**
     * Get the registration that owns this transaction
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if transaction failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if transaction is refunded
     */
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /**
     * Mark transaction as completed
     */
    public function markAsCompleted(array $providerResponse = null): bool
    {
        $this->status = 'completed';
        $this->processed_at = now();

        if ($providerResponse) {
            $this->provider_response = $providerResponse;
        }

        return $this->save();
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed(array $providerResponse = null): bool
    {
        $this->status = 'failed';
        $this->processed_at = now();

        if ($providerResponse) {
            $this->provider_response = $providerResponse;
        }

        return $this->save();
    }

    /**
     * Mark transaction as refunded
     */
    public function markAsRefunded(array $providerResponse = null): bool
    {
        $this->status = 'refunded';

        if ($providerResponse) {
            $this->provider_response = $providerResponse;
        }

        return $this->save();
    }

    /**
     * Update transaction status
     */
    public function updateStatus(string $status, array $providerResponse = null): bool
    {
        $validStatuses = ['pending', 'completed', 'failed', 'refunded'];

        if (!in_array($status, $validStatuses)) {
            return false;
        }

        $this->status = $status;

        if (in_array($status, ['completed', 'failed'])) {
            $this->processed_at = now();
        }

        if ($providerResponse) {
            $this->provider_response = $providerResponse;
        }

        return $this->save();
    }

    /**
     * Store callback data from payment provider
     */
    public function storeCallbackData(array $callbackData): bool
    {
        $this->callback_data = $callbackData;
        return $this->save();
    }

    /**
     * Get total amount including fees
     */
    public function getTotalAmount(): float
    {
        return $this->amount + $this->fee;
    }

    /**
     * Scope for filtering by provider
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by payment method
     */
    public function scopeByPaymentMethod($query, string $paymentMethod)
    {
        return $query->where('payment_method', $paymentMethod);
    }

    /**
     * Scope for completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
