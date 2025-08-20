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
            // Make user_id nullable for direct registrations
            $table->foreignId('user_id')->nullable()->change();

            // Add direct registration fields
            $table->string('attendee_name')->nullable();
            $table->string('attendee_email')->nullable();
            $table->string('attendee_phone')->nullable();
            $table->string('emergency_contact')->nullable();

            // Add order_id if it doesn't exist
            if (!Schema::hasColumn('registrations', 'order_id')) {
                $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn([
                'attendee_name',
                'attendee_email',
                'attendee_phone',
                'emergency_contact'
            ]);

            // Make user_id required again
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
