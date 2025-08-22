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
        // Add the column only if it doesn't already exist. Avoid relying on a specific
        // column order (previously used ->after('location')), since 'location' may not exist
        // on some environments.
        if (!Schema::hasColumn('event_sessions', 'category')) {
            Schema::table('event_sessions', function (Blueprint $table) {
                $table->string('category')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('event_sessions', 'category')) {
            Schema::table('event_sessions', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }
};
