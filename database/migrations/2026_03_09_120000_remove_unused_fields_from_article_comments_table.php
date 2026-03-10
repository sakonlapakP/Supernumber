<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('article_comments')) {
            return;
        }

        $drop = [];
        if (Schema::hasColumn('article_comments', 'commenter_email')) {
            $drop[] = 'commenter_email';
        }
        if (Schema::hasColumn('article_comments', 'commenter_phone')) {
            $drop[] = 'commenter_phone';
        }

        if ($drop !== []) {
            Schema::table('article_comments', function (Blueprint $table) use ($drop): void {
                $table->dropColumn($drop);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('article_comments')) {
            return;
        }

        Schema::table('article_comments', function (Blueprint $table): void {
            if (! Schema::hasColumn('article_comments', 'commenter_email')) {
                $table->string('commenter_email', 190)->nullable()->after('commenter_name');
            }
            if (! Schema::hasColumn('article_comments', 'commenter_phone')) {
                $table->string('commenter_phone', 40)->nullable()->after('commenter_email');
            }
        });
    }
};
