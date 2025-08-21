<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'billing_details',
        'notes',
        'completed_at',
        'cancelled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'billing_details' => 'array',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Order status constants.
     */
    const STATUS_PROCESSING = 'processing';
    const STATUS_REFUNDED = 'refunded';

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });
    }

    /**
     * Generate a unique order number.
     */
    public static function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'LS-' . date('Y') . '-' . strtoupper(Str::random(6));
        } while (static::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Get the user that owns the order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the registrations for the order.
     */
    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * Get the payments for the order.
     */
    public function payments()
    {
        return $this->hasManyThrough(Payment::class, Registration::class);
    }

    /**
     * Check if order is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if order is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if order is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Mark order as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark order as cancelled.
     */
    public function markAsCancelled(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'notes' => $reason,
        ]);
    }

    /**
     * Calculate order totals.
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->registrations->sum(function ($registration) {
            return $registration->ticket->price;
        });

        $taxAmount = $subtotal * 0.08; // 8% tax rate (configurable)
        $totalAmount = $subtotal + $taxAmount - $this->discount_amount;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ]);
    }

    /**
     * Get order status badge class.
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'bg-success',
            self::STATUS_PROCESSING => 'bg-primary',
            self::STATUS_CANCELLED => 'bg-danger',
            self::STATUS_REFUNDED => 'bg-warning',
            default => 'bg-secondary',
        };
    }

    /**
     * Get formatted order number.
     */
    public function getFormattedOrderNumber(): string
    {
        return $this->order_number;
    }
}
