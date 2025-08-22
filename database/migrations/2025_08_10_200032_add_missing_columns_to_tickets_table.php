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
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'max_per_order')) {
                $table->integer('max_per_order')->nullable()->after('quantity');
            }
            if (!Schema::hasColumn('tickets', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('sale_end');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            foreach (['max_per_order', 'is_active'] as $col) {
                if (Schema::hasColumn('tickets', $col)) {
                    try { $table->dropColumn($col); } catch (Throwable $e) {}
                }
            }
        });
    }
};
