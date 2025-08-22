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
        if (Schema::hasTable('payment_transactions')) {
            return;
        }
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();

            // Registration relationship
            $table->foreignId('registration_id')->constrained('registrations')->onDelete('cascade');

            // Transaction details
            $table->string('provider', 50); // 'unipayment', 'crypto', etc.
            $table->string('transaction_id'); // External transaction ID
            $table->string('payment_method', 20); // 'card', 'crypto'

            // Amount and currency
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10);
            $table->decimal('fee', 10, 2)->default(0.00);

            // Status tracking
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');

            // Provider response data
            $table->json('provider_response')->nullable();
            $table->json('callback_data')->nullable();

            // Processing timestamp
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            // Add indexes for efficient queries
            $table->index('registration_id');
            $table->index('transaction_id');
            $table->index('provider');
            $table->index('status');
            $table->index('processed_at');
            $table->index(['provider', 'status']);
            $table->index(['registration_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
