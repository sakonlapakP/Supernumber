<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facebook_imported_posts', function (Blueprint $table): void {
            $table->string('status', 20)->default('pending')->after('last_synced_at');
            $table->unsignedBigInteger('article_id')->nullable()->after('status');
            $table->foreign('article_id')->references('id')->on('articles')->nullOnDelete();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('facebook_imported_posts', function (Blueprint $table): void {
            $table->dropForeign(['article_id']);
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'article_id']);
        });
    }
};
