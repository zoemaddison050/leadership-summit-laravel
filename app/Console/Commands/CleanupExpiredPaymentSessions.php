<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupExpiredPaymentSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:cleanup-sessions {--dry-run : Show what would be cleaned up without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired payment sessions and related data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('Starting payment session cleanup...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No data will be deleted');
        }

        // Clean up expired payment transactions that are still pending
        $expiredTransactions = DB::table('payment_transactions')
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subHours(3))
            ->get();

        $this->info("Found {$expiredTransactions->count()} expired pending payment transactions");

        if (!$dryRun && $expiredTransactions->count() > 0) {
            $deletedTransactions = DB::table('payment_transactions')
                ->where('status', 'pending')
                ->where('created_at', '<', now()->subHours(3))
                ->update([
                    'status' => 'expired',
                    'updated_at' => now()
                ]);

            $this->info("Updated {$deletedTransactions} expired payment transactions to 'expired' status");

            Log::info('Payment session cleanup completed', [
                'expired_transactions' => $deletedTransactions,
                'dry_run' => false
            ]);
        }

        // Clean up registration locks that are expired
        $expiredLocks = DB::table('registration_locks')
            ->where('expires_at', '<', now())
            ->get();

        $this->info("Found {$expiredLocks->count()} expired registration locks");

        if (!$dryRun && $expiredLocks->count() > 0) {
            $deletedLocks = DB::table('registration_locks')
                ->where('expires_at', '<', now())
                ->delete();

            $this->info("Deleted {$deletedLocks} expired registration locks");

            Log::info('Registration locks cleanup completed', [
                'deleted_locks' => $deletedLocks,
                'dry_run' => false
            ]);
        }

        // Note: Session data cleanup is handled by Laravel's session garbage collection
        // We can't directly clean up session data from the command line as it's stored
        // in files or database depending on the session driver configuration

        $this->info('Payment session cleanup completed successfully');

        if ($dryRun) {
            $this->warn('This was a dry run. Use without --dry-run to actually clean up data.');
        }

        return 0;
    }
}
