<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('form_type', 40);
            $table->string('name')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->json('payload')->nullable();
            $table->boolean('consent_dev')->default(false);
            $table->boolean('consent_marketing')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['form_type', 'submitted_at']);
            $table->index('phone');
            $table->index('email');
            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_submissions');
    }
};
