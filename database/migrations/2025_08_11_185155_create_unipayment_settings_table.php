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
        Schema::create('unipayment_settings', function (Blueprint $table) {
            $table->id();

            // API credentials
            $table->string('app_id')->nullable();
            $table->text('api_key')->nullable(); // Encrypted storage
            $table->enum('environment', ['sandbox', 'production'])->default('sandbox');
            $table->string('webhook_secret')->nullable();

            // Configuration settings
            $table->boolean('is_enabled')->default(false);
            $table->json('supported_currencies')->nullable();
            $table->decimal('processing_fee_percentage', 5, 2)->default(0.00);
            $table->decimal('minimum_amount', 10, 2)->default(1.00);
            $table->decimal('maximum_amount', 10, 2)->default(10000.00);

            $table->timestamps();

            // Add indexes
            $table->index('is_enabled');
            $table->index('environment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unipayment_settings');
    }
};
