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
            if (!Schema::hasColumn('registrations', 'order_id')) {
                $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
                try { $table->index('order_id'); } catch (Throwable $e) {}
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            try { $table->dropForeign(['order_id']); } catch (Throwable $e) {}
            if (Schema::hasColumn('registrations', 'order_id')) {
                try { $table->dropColumn('order_id'); } catch (Throwable $e) {}
            }
        });
    }
};
