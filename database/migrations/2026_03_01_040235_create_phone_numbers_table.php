<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 10)->unique();
            $table->string('display_number', 12)->nullable();
            $table->string('network_code', 20)->default('true_dtac');
            $table->string('plan_name')->nullable();
            $table->string('price_text')->nullable();
            $table->unsignedInteger('sale_price')->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->index(['network_code', 'status']);
            $table->index('sale_price');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_numbers');
    }
};
