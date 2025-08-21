<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Speaker extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'bio',
        'photo',
        'position',
        'company',
    ];

    /**
     * Get the sessions for the speaker.
     */
    public function sessions()
    {
        return $this->belongsToMany(Session::class, 'session_speakers');
    }
}
