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
        Schema::table('unipayment_settings', function (Blueprint $table) {
            // Webhook configuration fields
            $table->string('webhook_url')->nullable()->after('webhook_secret');
            $table->boolean('webhook_enabled')->default(true)->after('webhook_url');
            $table->timestamp('last_webhook_test')->nullable()->after('webhook_enabled');
            $table->enum('webhook_test_status', ['success', 'failed', 'pending'])->nullable()->after('last_webhook_test');
            $table->text('webhook_test_response')->nullable()->after('webhook_test_status');
            $table->integer('webhook_retry_count')->default(0)->after('webhook_test_response');
            $table->timestamp('last_webhook_received')->nullable()->after('webhook_retry_count');

            // Add indexes for webhook fields
            $table->index('webhook_enabled');
            $table->index('webhook_test_status');
            $table->index('last_webhook_test');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unipayment_settings', function (Blueprint $table) {
            $table->dropIndex(['webhook_enabled']);
            $table->dropIndex(['webhook_test_status']);
            $table->dropIndex(['last_webhook_test']);

            $table->dropColumn([
                'webhook_url',
                'webhook_enabled',
                'last_webhook_test',
                'webhook_test_status',
                'webhook_test_response',
                'webhook_retry_count',
                'last_webhook_received'
            ]);
        });
    }
};
