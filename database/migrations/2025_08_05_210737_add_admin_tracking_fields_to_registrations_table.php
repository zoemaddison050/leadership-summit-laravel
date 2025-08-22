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
            // Add admin tracking fields for payment management if missing
            if (!Schema::hasColumn('registrations', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('payment_confirmed_at');
            }
            if (!Schema::hasColumn('registrations', 'confirmed_by')) {
                $table->unsignedBigInteger('confirmed_by')->nullable()->after('confirmed_at');
            }
            if (!Schema::hasColumn('registrations', 'declined_by')) {
                $table->unsignedBigInteger('declined_by')->nullable()->after('declined_at');
            }

            // Add foreign key constraints (guarded via try/catch)
            try { $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('set null'); } catch (Throwable $e) {}
            try { $table->foreign('declined_by')->references('id')->on('users')->onDelete('set null'); } catch (Throwable $e) {}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            // Drop foreign key constraints first (guarded)
            try { $table->dropForeign(['confirmed_by']); } catch (Throwable $e) {}
            try { $table->dropForeign(['declined_by']); } catch (Throwable $e) {}

            // Drop columns if they exist
            foreach (['confirmed_at', 'confirmed_by', 'declined_by'] as $col) {
                if (Schema::hasColumn('registrations', $col)) {
                    try { $table->dropColumn($col); } catch (Throwable $e) {}
                }
            }
        });
    }
};
