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
            // Add columns only if missing
            if (!Schema::hasColumn('registrations', 'registration_status')) {
                $table->enum('registration_status', ['pending', 'confirmed', 'cancelled', 'declined'])
                    ->default('pending')
                    ->after('payment_status');
            }
            if (!Schema::hasColumn('registrations', 'marked_at')) {
                $table->timestamp('marked_at')->nullable()->after('registration_status');
            }
            if (!Schema::hasColumn('registrations', 'payment_confirmed_at')) {
                $table->timestamp('payment_confirmed_at')->nullable()->after('marked_at');
            }
            if (!Schema::hasColumn('registrations', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('payment_confirmed_at');
            }
            if (!Schema::hasColumn('registrations', 'confirmed_by')) {
                $table->unsignedBigInteger('confirmed_by')->nullable()->after('confirmed_at');
            }
            if (!Schema::hasColumn('registrations', 'declined_at')) {
                $table->timestamp('declined_at')->nullable()->after('confirmed_by');
            }
            if (!Schema::hasColumn('registrations', 'declined_by')) {
                $table->unsignedBigInteger('declined_by')->nullable()->after('declined_at');
            }
            if (!Schema::hasColumn('registrations', 'declined_reason')) {
                $table->text('declined_reason')->nullable()->after('declined_by');
            }
            if (!Schema::hasColumn('registrations', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable()->after('emergency_contact');
            }
            if (!Schema::hasColumn('registrations', 'emergency_contact_phone')) {
                $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            }
            if (!Schema::hasColumn('registrations', 'ticket_selections')) {
                $table->json('ticket_selections')->nullable()->after('emergency_contact_phone');
            }
            if (!Schema::hasColumn('registrations', 'total_amount')) {
                $table->decimal('total_amount', 10, 2)->nullable()->after('ticket_selections');
            }
            if (!Schema::hasColumn('registrations', 'terms_accepted_at')) {
                $table->timestamp('terms_accepted_at')->nullable()->after('total_amount');
            }

            // Add indexes for performance (guarded)
            try { $table->index(['attendee_email', 'event_id'], 'idx_registrations_email_event'); } catch (Throwable $e) {}
            try { $table->index(['attendee_phone', 'event_id'], 'idx_registrations_phone_event'); } catch (Throwable $e) {}
            try { $table->index('registration_status'); } catch (Throwable $e) {}
            try { $table->index('marked_at'); } catch (Throwable $e) {}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            // Drop indexes first (guarded)
            try { $table->dropIndex('idx_registrations_email_event'); } catch (Throwable $e) {}
            try { $table->dropIndex('idx_registrations_phone_event'); } catch (Throwable $e) {}
            try { $table->dropIndex(['registration_status']); } catch (Throwable $e) {}
            try { $table->dropIndex(['marked_at']); } catch (Throwable $e) {}

            // Drop columns if they exist
            $cols = [
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
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('registrations', $col)) {
                    try { $table->dropColumn($col); } catch (Throwable $e) {}
                }
            }
        });
    }
};
