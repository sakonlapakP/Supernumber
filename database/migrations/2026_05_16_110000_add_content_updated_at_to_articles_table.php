<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->bigInteger('content_updated_at')->nullable()->after('published_at');
        });

        // Backfill: seed content_updated_at as Unix timestamp from published_at for existing rows
        DB::statement('UPDATE articles SET content_updated_at = UNIX_TIMESTAMP(published_at) WHERE published_at IS NOT NULL AND content_updated_at IS NULL');

        Schema::table('articles', function (Blueprint $table): void {
            $table->index('content_updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropIndex(['content_updated_at']);
            $table->dropColumn('content_updated_at');
        });
    }
};
