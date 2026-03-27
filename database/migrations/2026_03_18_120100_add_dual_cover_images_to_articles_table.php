<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->string('cover_image_landscape_path')->nullable()->after('cover_image_path');
            $table->string('cover_image_square_path')->nullable()->after('cover_image_landscape_path');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropColumn(['cover_image_landscape_path', 'cover_image_square_path']);
        });
    }
};
