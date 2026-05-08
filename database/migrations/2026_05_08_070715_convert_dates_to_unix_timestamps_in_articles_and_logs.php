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
        // 1. Articles Table
        Schema::table('articles', function (Blueprint $table) {
            $table->bigInteger('published_at_new')->nullable()->after('published_at');
            $table->bigInteger('notified_at_new')->nullable()->after('notified_at');
            $table->bigInteger('created_at_new')->nullable()->after('created_at');
            $table->bigInteger('updated_at_new')->nullable()->after('updated_at');
        });

        DB::statement("UPDATE articles SET 
            published_at_new = UNIX_TIMESTAMP(published_at),
            notified_at_new = UNIX_TIMESTAMP(notified_at),
            created_at_new = UNIX_TIMESTAMP(created_at),
            updated_at_new = UNIX_TIMESTAMP(updated_at)");

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['published_at', 'notified_at', 'created_at', 'updated_at']);
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->bigInteger('published_at')->nullable()->after('is_line_broadcasted');
            $table->bigInteger('notified_at')->nullable()->after('view_count');
            $table->bigInteger('created_at')->nullable();
            $table->bigInteger('updated_at')->nullable();
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

            DB::statement("UPDATE line_notification_logs SET 
                sent_at_new = UNIX_TIMESTAMP(sent_at),
                failed_at_new = UNIX_TIMESTAMP(failed_at),
                created_at_new = UNIX_TIMESTAMP(created_at),
                updated_at_new = UNIX_TIMESTAMP(updated_at)");

            Schema::table('line_notification_logs', function (Blueprint $table) {
                $table->dropColumn(['sent_at', 'failed_at', 'created_at', 'updated_at']);
            });

            Schema::table('line_notification_logs', function (Blueprint $table) {
                $table->bigInteger('sent_at')->nullable();
                $table->bigInteger('failed_at')->nullable();
                $table->bigInteger('created_at')->nullable();
                $table->bigInteger('updated_at')->nullable();
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
