<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phone_number_status_logs', function (Blueprint $table) {
            $table->foreignId('order_id')
                ->nullable()
                ->after('user_id')
                ->constrained('customer_orders')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('phone_number_status_logs', function (Blueprint $table) {
            $table->dropForeignIdFor('customer_orders');
            $table->dropColumn('order_id');
        });
    }
};
