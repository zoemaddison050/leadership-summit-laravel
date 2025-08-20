<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'start_date',
        'end_date',
        'location',
        'featured_image',
        'status',
        'is_default',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_default' => 'boolean',
    ];

    /**
     * Get the tickets for the event.
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get the sessions for the event.
     */
    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    /**
     * Get the registrations for the event.
     */
    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Get the default event.
     */
    public static function getDefaultEvent()
    {
        return self::where('is_default', true)->first() ?? self::first();
    }

    /**
     * Set this event as the default event.
     */
    public function setAsDefault()
    {
        // Remove default status from all other events
        self::where('is_default', true)->update(['is_default' => false]);

        // Set this event as default
        $this->update(['is_default' => true]);
    }
}
