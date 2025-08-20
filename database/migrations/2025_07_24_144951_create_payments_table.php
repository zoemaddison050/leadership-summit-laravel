<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->onDelete('cascade');
            $table->string('payment_method'); // 'card', 'crypto', 'paypal', etc.
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('pending'); // pending, processing, completed, failed, refunded
            $table->string('transaction_id')->nullable();
            $table->string('gateway_reference')->nullable(); // Reference from payment gateway
            $table->json('gateway_response')->nullable(); // Store gateway response
            $table->string('crypto_currency')->nullable(); // For crypto payments
            $table->string('crypto_address')->nullable(); // Crypto wallet address
            $table->decimal('crypto_amount', 20, 8)->nullable(); // Crypto amount
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'payment_method']);
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
