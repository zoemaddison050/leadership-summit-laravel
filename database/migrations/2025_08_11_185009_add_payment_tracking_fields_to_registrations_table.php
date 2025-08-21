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
        Schema::table('registrations', function (Blueprint $table) {
            // Payment method and provider tracking
            $table->string('payment_method', 20)->default('crypto')->after('status');
            $table->string('payment_provider', 50)->nullable()->after('payment_method');
            $table->string('transaction_id')->nullable()->after('payment_provider');

            // Payment amount and currency tracking
            $table->decimal('payment_amount', 10, 2)->nullable()->after('transaction_id');
            $table->string('payment_currency', 10)->nullable()->after('payment_amount');
            $table->decimal('payment_fee', 10, 2)->nullable()->after('payment_currency');

            // Payment completion timestamp
            $table->timestamp('payment_completed_at')->nullable()->after('payment_fee');

            // Refund tracking fields
            $table->decimal('refund_amount', 10, 2)->nullable()->after('payment_completed_at');
            $table->text('refund_reason')->nullable()->after('refund_amount');
            $table->timestamp('refunded_at')->nullable()->after('refund_reason');

            // Add indexes for payment-related queries
            $table->index('payment_method');
            $table->index('payment_provider');
            $table->index('transaction_id');
            $table->index('payment_completed_at');
            $table->index(['payment_method', 'payment_provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['registrations_payment_method_index']);
            $table->dropIndex(['registrations_payment_provider_index']);
            $table->dropIndex(['registrations_transaction_id_index']);
            $table->dropIndex(['registrations_payment_completed_at_index']);
            $table->dropIndex(['registrations_payment_method_payment_provider_index']);

            // Drop columns
            $table->dropColumn([
                'payment_method',
                'payment_provider',
                'transaction_id',
                'payment_amount',
                'payment_currency',
                'payment_fee',
                'payment_completed_at',
                'refund_amount',
                'refund_reason',
                'refunded_at'
            ]);
        });
    }
};
