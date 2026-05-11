<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('article_plans', function (Blueprint $table) {
            $table->id();
            $table->date('publish_date');
            $table->string('publish_time')->default('09:00');
            $table->string('type')->nullable(); // หวย, วันสำคัญ, Evergreen, etc.
            $table->string('topic');
            $table->boolean('is_lottery')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_plans');
    }
};
