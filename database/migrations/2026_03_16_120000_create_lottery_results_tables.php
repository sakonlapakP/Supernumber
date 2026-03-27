<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lottery_results', function (Blueprint $table): void {
            $table->id();
            $table->date('draw_date')->unique();
            $table->date('source_draw_date')->nullable();
            $table->string('source_draw_date_text')->nullable();
            $table->boolean('is_complete')->default(false);
            $table->timestamp('fetched_at')->nullable();
            $table->json('source_payload')->nullable();
            $table->timestamps();

            $table->index(['is_complete', 'draw_date']);
        });

        Schema::create('lottery_result_prizes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lottery_result_id')->constrained('lottery_results')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('prize_name');
            $table->string('prize_number', 20);
            $table->timestamps();

            $table->unique(['lottery_result_id', 'prize_name', 'prize_number'], 'lottery_result_prize_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_result_prizes');
        Schema::dropIfExists('lottery_results');
    }
};
