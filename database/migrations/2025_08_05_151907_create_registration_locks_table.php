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
        Schema::create('registration_locks', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('phone');
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->timestamp('locked_at');
            $table->timestamp('expires_at');
            $table->timestamps();

            // Add indexes for performance on email, phone, and event_id combinations
            $table->index(['email', 'event_id'], 'idx_locks_email_event');
            $table->index(['phone', 'event_id'], 'idx_locks_phone_event');
            $table->index(['email', 'phone', 'event_id'], 'idx_locks_email_phone_event');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registration_locks');
    }
};
