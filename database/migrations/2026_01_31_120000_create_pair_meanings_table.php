<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pair_meanings', function (Blueprint $table) {
            $table->id();
            $table->string('pair', 2)->unique();
            $table->string('status', 20);
            $table->text('short_meaning');
            $table->text('long_meaning')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pair_meanings');
    }
};
