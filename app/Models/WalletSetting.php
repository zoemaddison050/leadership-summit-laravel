<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'cryptocurrency',
        'wallet_address',
        'currency_name',
        'currency_symbol',
        'currency_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get active wallet settings
     */
    public static function getActiveWallets()
    {
        return self::where('is_active', true)->get()->keyBy('cryptocurrency');
    }

    /**
     * Get wallet address for a specific cryptocurrency
     */
    public static function getWalletAddress(string $cryptocurrency): ?string
    {
        $wallet = self::where('cryptocurrency', $cryptocurrency)
            ->where('is_active', true)
            ->first();

        return $wallet ? $wallet->wallet_address : null;
    }
}
