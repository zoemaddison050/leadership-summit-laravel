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
        // Add column only if missing; avoid strict column ordering to be resilient
        if (!Schema::hasColumn('events', 'is_default')) {
            Schema::table('events', function (Blueprint $table) {
                $table->boolean('is_default')->default(false);
            });
        }

        // Add index if not present. On some MySQL versions, the default index name will be
        // `events_is_default_index`.
        $indexName = 'events_is_default_index';
        $hasIndex = false;
        // Try to detect index presence in a DB-agnostic way via Doctrine schema manager when available
        try {
            $schemaManager = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $schemaManager->listTableIndexes('events');
            $hasIndex = array_key_exists($indexName, $indexes) || array_key_exists('is_default', $indexes);
        } catch (Throwable $e) {
            // Fallback: attempt to create index; if it already exists, DB will error, which we'll ignore via try/catch
        }

        if (!$hasIndex) {
            try {
                Schema::table('events', function (Blueprint $table) {
                    $table->index('is_default');
                });
            } catch (Throwable $e) {
                // Ignore if index already exists
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop index if present, then the column
        try {
            Schema::table('events', function (Blueprint $table) {
                $table->dropIndex(['is_default']);
            });
        } catch (Throwable $e) {
            // Ignore if index doesn't exist
        }

        if (Schema::hasColumn('events', 'is_default')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('is_default');
            });
        }
    }
};
