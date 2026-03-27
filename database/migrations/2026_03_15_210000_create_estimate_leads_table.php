<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimate_leads', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 120);
            $table->string('last_name', 120);
            $table->string('gender', 20)->nullable();
            $table->date('birthday')->nullable();
            $table->string('work_type', 80)->nullable();
            $table->string('current_phone', 20)->nullable();
            $table->string('main_phone', 20);
            $table->string('email', 255);
            $table->string('goal', 120)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimate_leads');
    }
};
