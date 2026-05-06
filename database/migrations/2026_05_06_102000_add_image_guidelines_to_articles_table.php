<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            if (! Schema::hasColumn('articles', 'image_guidelines')) {
                $table->json('image_guidelines')->nullable()->after('cover_image_square_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            if (Schema::hasColumn('articles', 'image_guidelines')) {
                $table->dropColumn('image_guidelines');
            }
        });
    }
};
