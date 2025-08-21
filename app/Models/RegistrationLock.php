<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class RegistrationLock extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'phone',
        'event_id',
        'locked_at',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'locked_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the event that this lock belongs to.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Check if the lock has expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at < now();
    }

    /**
     * Create a new registration lock with 30-minute expiration.
     *
     * @param string $email
     * @param string $phone
     * @param int $eventId
     * @return static
     */
    public static function createLock(string $email, string $phone, int $eventId): static
    {
        return static::create([
            'email' => $email,
            'phone' => $phone,
            'event_id' => $eventId,
            'locked_at' => now(),
            'expires_at' => now()->addMinutes(30),
        ]);
    }

    /**
     * Check if an email/phone combination is currently locked for an event.
     *
     * @param string $email
     * @param string $phone
     * @param int $eventId
     * @return bool
     */
    public static function isLocked(string $email, string $phone, int $eventId): bool
    {
        try {
            return static::where('email', $email)
                ->where('phone', $phone)
                ->where('event_id', $eventId)
                ->where('expires_at', '>', now())
                ->exists();
        } catch (\Exception $e) {
            Log::error('Error checking registration lock status', [
                'email' => $email,
                'phone' => $phone,
                'event_id' => $eventId,
                'error' => $e->getMessage()
            ]);

            // Return false to allow registration attempt if we can't check locks
            return false;
        }
    }

    /**
     * Remove expired locks.
     *
     * @return int Number of expired locks removed
     */
    public static function cleanupExpiredLocks(): int
    {
        try {
            $expiredCount = static::where('expires_at', '<', now())->count();
            $deletedCount = static::where('expires_at', '<', now())->delete();

            if ($deletedCount > 0) {
                Log::info('Cleaned up expired registration locks', [
                    'expired_locks_found' => $expiredCount,
                    'locks_deleted' => $deletedCount
                ]);
            }

            return $deletedCount;
        } catch (\Exception $e) {
            Log::error('Error cleaning up expired registration locks', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 0;
        }
    }

    /**
     * Get information about a specific lock.
     *
     * @param string $email
     * @param string $phone
     * @param int $eventId
     * @return array|null Lock information or null if not found
     */
    public static function getLockInfo(string $email, string $phone, int $eventId): ?array
    {
        try {
            $lock = static::where('email', $email)
                ->where('phone', $phone)
                ->where('event_id', $eventId)
                ->first();

            if (!$lock) {
                return null;
            }

            return [
                'id' => $lock->id,
                'locked_at' => $lock->locked_at,
                'expires_at' => $lock->expires_at,
                'is_expired' => $lock->isExpired(),
                'minutes_remaining' => $lock->isExpired() ? 0 : now()->diffInMinutes($lock->expires_at),
                'minutes_since_locked' => now()->diffInMinutes($lock->locked_at)
            ];
        } catch (\Exception $e) {
            Log::error('Error getting registration lock info', [
                'email' => $email,
                'phone' => $phone,
                'event_id' => $eventId,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Find an active (non-expired) lock for email/phone/event combination.
     *
     * @param string $email
     * @param string $phone
     * @param int $eventId
     * @return static|null
     */
    public static function findActiveLock(string $email, string $phone, int $eventId): ?static
    {
        return static::where('email', $email)
            ->where('phone', $phone)
            ->where('event_id', $eventId)
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Release (delete) all locks for a specific email/phone/event combination.
     *
     * @param string $email
     * @param string $phone
     * @param int $eventId
     * @return int Number of locks released
     */
    public static function releaseLocks(string $email, string $phone, int $eventId): int
    {
        return static::where('email', $email)
            ->where('phone', $phone)
            ->where('event_id', $eventId)
            ->delete();
    }
}
