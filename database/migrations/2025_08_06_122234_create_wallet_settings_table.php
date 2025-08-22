<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('wallet_settings')) {
            Schema::create('wallet_settings', function (Blueprint $table) {
            $table->id();
            $table->string('cryptocurrency')->unique();
            $table->string('wallet_address');
            $table->string('currency_name');
            $table->string('currency_symbol');
            $table->string('currency_code');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            });
        }

        // Insert default wallet addresses if not present
        $defaults = [
            [
                'cryptocurrency' => 'bitcoin',
                'wallet_address' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa',
                'currency_name' => 'Bitcoin',
                'currency_symbol' => '₿',
                'currency_code' => 'BTC',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'cryptocurrency' => 'ethereum',
                'wallet_address' => '0x742d35Cc6634C0532925a3b8D4C9db96590b4c8d',
                'currency_name' => 'Ethereum',
                'currency_symbol' => 'Ξ',
                'currency_code' => 'ETH',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'cryptocurrency' => 'usdt',
                'wallet_address' => '0x742d35Cc6634C0532925a3b8D4C9db96590b4c8d',
                'currency_name' => 'USDT (ERC-20)',
                'currency_symbol' => '₮',
                'currency_code' => 'USDT',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($defaults as $row) {
            if (!DB::table('wallet_settings')->where('cryptocurrency', $row['cryptocurrency'])->exists()) {
                DB::table('wallet_settings')->insert($row);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_settings');
    }
};
