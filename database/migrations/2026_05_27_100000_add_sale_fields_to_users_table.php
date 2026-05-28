<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('national_id')->nullable()->unique()->after('phone');
            $table->string('bank_name')->nullable()->after('national_id');
            $table->string('bank_account_number')->nullable()->after('bank_name');
            $table->string('bank_account_name')->nullable()->after('bank_account_number');
            $table->unsignedBigInteger('parent_id')->nullable()->after('bank_account_name');
            $table->string('referral_code', 10)->nullable()->unique()->after('parent_id');
            $table->unsignedInteger('quota_tokens')->default(0)->after('referral_code');
            $table->enum('sale_status', ['pending', 'approved', 'rejected'])->nullable()->after('quota_tokens');

            $table->foreign('parent_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropUnique(['national_id']);
            $table->dropUnique(['referral_code']);
            $table->dropColumn([
                'phone', 'national_id', 'bank_name', 'bank_account_number',
                'bank_account_name', 'parent_id', 'referral_code',
                'quota_tokens', 'sale_status',
            ]);
        });
    }
};
