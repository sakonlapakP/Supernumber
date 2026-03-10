<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_orders', function (Blueprint $table) {
            $table->id();
            $table->string('ordered_number', 20);
            $table->unsignedInteger('selected_package');
            $table->string('title_prefix', 50)->nullable();
            $table->string('first_name', 120)->nullable();
            $table->string('last_name', 120)->nullable();
            $table->string('email')->nullable();
            $table->string('current_phone', 20)->nullable();
            $table->string('shipping_address_line')->nullable();
            $table->string('district', 120)->nullable();
            $table->string('amphoe', 120)->nullable();
            $table->string('province', 120)->nullable();
            $table->string('zipcode', 10)->nullable();
            $table->date('appointment_date')->nullable();
            $table->string('appointment_time_slot', 40)->nullable();
            $table->string('payment_slip_path');
            $table->string('status', 30)->default('submitted');
            $table->timestamps();

            $table->index(['ordered_number', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_orders');
    }
};

