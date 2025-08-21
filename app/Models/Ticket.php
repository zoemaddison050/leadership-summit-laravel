<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id',
        'name',
        'description',
        'price',
        'quantity',
        'available',
        'max_per_order',
        'sale_start',
        'sale_end',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'sale_start' => 'datetime',
        'sale_end' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the event that owns the ticket.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the registrations for the ticket.
     */
    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * Boot method to set default values
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            // Set available to quantity if not set
            if (is_null($ticket->available)) {
                $ticket->available = $ticket->quantity;
            }
        });

        static::updating(function ($ticket) {
            // Update available when quantity changes
            if ($ticket->isDirty('quantity') && is_null($ticket->available)) {
                $ticket->available = $ticket->quantity;
            }
        });
    }

    /**
     * Check if ticket is currently available for purchase
     */
    public function isAvailable()
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->available !== null && $this->available <= 0) {
            return false;
        }

        if ($this->sale_start && $this->sale_start > now()) {
            return false;
        }

        if ($this->sale_end && $this->sale_end < now()) {
            return false;
        }

        return true;
    }
}
