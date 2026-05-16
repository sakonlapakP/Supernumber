<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_plans', function (Blueprint $table): void {
            $table->string('status')->default('todo')->after('is_lottery'); // todo|in_progress|done|blocked|cancelled
            $table->foreignId('assigned_to')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable()->after('assigned_to');
            $table->text('blocked_reason')->nullable()->after('due_date');
            $table->text('notes')->nullable()->after('blocked_reason');
            $table->foreignId('article_id')->nullable()->after('notes')->constrained('articles')->nullOnDelete();
            $table->timestamp('last_refreshed_at')->nullable()->after('article_id');
            $table->string('refresh_status')->nullable()->after('last_refreshed_at'); // draft|created|refreshed|done

            $table->index('publish_date');
            $table->index('status');
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::table('article_plans', function (Blueprint $table): void {
            $table->dropIndex(['publish_date']);
            $table->dropIndex(['status']);
            $table->dropIndex(['assigned_to']);

            $table->dropForeign(['assigned_to']);
            $table->dropForeign(['article_id']);

            $table->dropColumn([
                'status',
                'assigned_to',
                'due_date',
                'blocked_reason',
                'notes',
                'article_id',
                'last_refreshed_at',
                'refresh_status',
            ]);
        });
    }
};
