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
            // Add registration status enum field
            $table->enum('registration_status', ['pending', 'confirmed', 'cancelled', 'declined'])
                ->default('pending')
                ->after('payment_status');

            // Add timestamp fields for tracking registration lifecycle
            $table->timestamp('marked_at')->nullable()->after('registration_status');
            $table->timestamp('payment_confirmed_at')->nullable()->after('marked_at');
            $table->timestamp('confirmed_at')->nullable()->after('payment_confirmed_at');
            $table->unsignedBigInteger('confirmed_by')->nullable()->after('confirmed_at');
            $table->timestamp('declined_at')->nullable()->after('confirmed_by');
            $table->unsignedBigInteger('declined_by')->nullable()->after('declined_at');
            $table->text('declined_reason')->nullable()->after('declined_by');

            // Add additional fields for enhanced registration
            $table->string('emergency_contact_name')->nullable()->after('emergency_contact');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            $table->json('ticket_selections')->nullable()->after('emergency_contact_phone');
            $table->decimal('total_amount', 10, 2)->nullable()->after('ticket_selections');
            $table->timestamp('terms_accepted_at')->nullable()->after('total_amount');

            // Add indexes for performance
            $table->index(['attendee_email', 'event_id'], 'idx_registrations_email_event');
            $table->index(['attendee_phone', 'event_id'], 'idx_registrations_phone_event');
            $table->index('registration_status');
            $table->index('marked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_registrations_email_event');
            $table->dropIndex('idx_registrations_phone_event');
            $table->dropIndex(['registration_status']);
            $table->dropIndex(['marked_at']);

            // Drop columns
            $table->dropColumn([
                'registration_status',
                'marked_at',
                'payment_confirmed_at',
                'confirmed_at',
                'confirmed_by',
                'declined_at',
                'declined_by',
                'declined_reason',
                'emergency_contact_name',
                'emergency_contact_phone',
                'ticket_selections',
                'total_amount',
                'terms_accepted_at'
            ]);
        });
    }
};
