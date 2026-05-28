<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            $table->string('referral_code_used', 10)->nullable()->after('status');
            $table->foreignId('seller_user_id')->nullable()->after('referral_code_used')
                ->constrained('users')->nullOnDelete();
            $table->decimal('discount_applied', 8, 2)->default(0)->after('seller_user_id');
            $table->decimal('net_amount', 10, 2)->nullable()->after('discount_applied');
            $table->decimal('sale_price', 10, 2)->nullable()->after('net_amount');

            $table->index('referral_code_used');
            $table->index('seller_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            $table->dropForeign(['seller_user_id']);
            $table->dropIndex(['referral_code_used']);
            $table->dropIndex(['seller_user_id']);
            $table->dropColumn([
                'referral_code_used', 'seller_user_id',
                'discount_applied', 'net_amount', 'sale_price',
            ]);
        });
    }
};
