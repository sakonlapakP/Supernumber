<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('customer_orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('tier_level'); // 1, 2, or 3
            $table->decimal('percentage_applied', 5, 2);
            $table->decimal('calculated_amount', 10, 2);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('period')->nullable(); // e.g. '2026-05' for monthly batch
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'period']);
            $table->index(['order_id']);
            $table->index(['status', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
