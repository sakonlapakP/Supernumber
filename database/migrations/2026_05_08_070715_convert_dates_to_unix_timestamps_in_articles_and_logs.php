<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        $unixTimestamp = $driver === 'sqlite' ? "strftime('%%s', %s)" : "UNIX_TIMESTAMP(%s)";

        // 1. Articles Table
        Schema::table('articles', function (Blueprint $table) {
            $table->bigInteger('published_at_new')->nullable()->after('published_at');
            $table->bigInteger('notified_at_new')->nullable()->after('notified_at');
            $table->bigInteger('created_at_new')->nullable()->after('created_at');
            $table->bigInteger('updated_at_new')->nullable()->after('updated_at');
        });

        DB::statement(sprintf(
            "UPDATE articles SET 
            published_at_new = $unixTimestamp,
            notified_at_new = $unixTimestamp,
            created_at_new = $unixTimestamp,
            updated_at_new = $unixTimestamp",
            'published_at', 'notified_at', 'created_at', 'updated_at'
        ));

        Schema::table('articles', function (Blueprint $table) {
            // Drop index that references published_at before dropping the column (Required for SQLite)
            $table->dropIndex('articles_is_published_published_at_index');
            $table->dropColumn(['published_at', 'notified_at', 'created_at', 'updated_at']);
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->bigInteger('published_at')->nullable()->after('is_line_broadcasted');
            $table->bigInteger('notified_at')->nullable()->after('view_count');
            $table->bigInteger('created_at')->nullable();
            $table->bigInteger('updated_at')->nullable();
            
            // Re-create the index after column is restored
            $table->index(['is_published', 'published_at']);
        });

        DB::statement("UPDATE articles SET 
            published_at = published_at_new,
            notified_at = notified_at_new,
            created_at = created_at_new,
            updated_at = updated_at_new");

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['published_at_new', 'notified_at_new', 'created_at_new', 'updated_at_new']);
        });

        // 2. Line Notification Logs Table
        if (Schema::hasTable('line_notification_logs')) {
            Schema::table('line_notification_logs', function (Blueprint $table) {
                $table->bigInteger('sent_at_new')->nullable()->after('sent_at');
                $table->bigInteger('failed_at_new')->nullable()->after('failed_at');
                $table->bigInteger('created_at_new')->nullable()->after('created_at');
                $table->bigInteger('updated_at_new')->nullable()->after('updated_at');
            });

            DB::statement(sprintf(
                "UPDATE line_notification_logs SET 
                sent_at_new = $unixTimestamp,
                failed_at_new = $unixTimestamp,
                created_at_new = $unixTimestamp,
                updated_at_new = $unixTimestamp",
                'sent_at', 'failed_at', 'created_at', 'updated_at'
            ));

            Schema::table('line_notification_logs', function (Blueprint $table) {
                // Drop index that references created_at before dropping the column (Required for SQLite)
                $table->dropIndex('line_notification_logs_destination_key_created_at_index');
                $table->dropColumn(['sent_at', 'failed_at', 'created_at', 'updated_at']);
            });

            Schema::table('line_notification_logs', function (Blueprint $table) {
                $table->bigInteger('sent_at')->nullable();
                $table->bigInteger('failed_at')->nullable();
                $table->bigInteger('created_at')->nullable();
                $table->bigInteger('updated_at')->nullable();
                
                // Re-create the index after column is restored
                $table->index(['destination_key', 'created_at']);
            });

            DB::statement("UPDATE line_notification_logs SET 
                sent_at = sent_at_new,
                failed_at = failed_at_new,
                created_at = created_at_new,
                updated_at = updated_at_new");

            Schema::table('line_notification_logs', function (Blueprint $table) {
                $table->dropColumn(['sent_at_new', 'failed_at_new', 'created_at_new', 'updated_at_new']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse logic would go here if needed, but for now we focus on forward
    }
};
