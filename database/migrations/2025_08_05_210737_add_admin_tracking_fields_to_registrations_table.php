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
            // Add admin tracking fields for payment management
            $table->timestamp('confirmed_at')->nullable()->after('payment_confirmed_at');
            $table->unsignedBigInteger('confirmed_by')->nullable()->after('confirmed_at');
            $table->unsignedBigInteger('declined_by')->nullable()->after('declined_at');

            // Add foreign key constraints
            $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('declined_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            // Drop foreign key constraints first
            $table->dropForeign(['confirmed_by']);
            $table->dropForeign(['declined_by']);

            // Drop columns
            $table->dropColumn(['confirmed_at', 'confirmed_by', 'declined_by']);
        });
    }
};
