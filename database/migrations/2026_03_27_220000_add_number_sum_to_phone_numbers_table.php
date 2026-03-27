<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->unsignedSmallInteger('number_sum')
                ->nullable()
                ->after('display_number');
            $table->index('number_sum');
        });
    }

    public function down(): void
    {
        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->dropIndex(['number_sum']);
            $table->dropColumn('number_sum');
        });
    }
};
