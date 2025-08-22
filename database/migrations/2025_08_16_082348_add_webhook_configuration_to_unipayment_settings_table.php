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
            // Webhook configuration fields (add only if missing)
            if (!Schema::hasColumn('unipayment_settings', 'webhook_url')) {
                $table->string('webhook_url')->nullable()->after('webhook_secret');
            }
            if (!Schema::hasColumn('unipayment_settings', 'webhook_enabled')) {
                $table->boolean('webhook_enabled')->default(true)->after('webhook_url');
            }
            if (!Schema::hasColumn('unipayment_settings', 'last_webhook_test')) {
                $table->timestamp('last_webhook_test')->nullable()->after('webhook_enabled');
            }
            if (!Schema::hasColumn('unipayment_settings', 'webhook_test_status')) {
                $table->enum('webhook_test_status', ['success', 'failed', 'pending'])->nullable()->after('last_webhook_test');
            }
            if (!Schema::hasColumn('unipayment_settings', 'webhook_test_response')) {
                $table->text('webhook_test_response')->nullable()->after('webhook_test_status');
            }
            if (!Schema::hasColumn('unipayment_settings', 'webhook_retry_count')) {
                $table->integer('webhook_retry_count')->default(0)->after('webhook_test_response');
            }
            if (!Schema::hasColumn('unipayment_settings', 'last_webhook_received')) {
                $table->timestamp('last_webhook_received')->nullable()->after('webhook_retry_count');
            }

            // Add indexes for webhook fields (guarded)
            try { $table->index('webhook_enabled'); } catch (Throwable $e) {}
            try { $table->index('webhook_test_status'); } catch (Throwable $e) {}
            try { $table->index('last_webhook_test'); } catch (Throwable $e) {}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unipayment_settings', function (Blueprint $table) {
            try { $table->dropIndex(['webhook_enabled']); } catch (Throwable $e) {}
            try { $table->dropIndex(['webhook_test_status']); } catch (Throwable $e) {}
            try { $table->dropIndex(['last_webhook_test']); } catch (Throwable $e) {}

            $columns = [
                'webhook_url',
                'webhook_enabled',
                'last_webhook_test',
                'webhook_test_status',
                'webhook_test_response',
                'webhook_retry_count',
                'last_webhook_received'
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('unipayment_settings', $col)) {
                    try { $table->dropColumn($col); } catch (Throwable $e) {}
                }
            }
        });
    }
};
