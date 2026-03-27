<?php

use App\Models\PhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->string('service_type', 20)
                ->default(PhoneNumber::SERVICE_TYPE_POSTPAID)
                ->after('display_number');
            $table->index(['service_type', 'status']);
        });

        Schema::table('customer_orders', function (Blueprint $table) {
            $table->string('service_type', 20)
                ->default(PhoneNumber::SERVICE_TYPE_POSTPAID)
                ->after('ordered_number');
            $table->index(['service_type', 'status']);
        });

        DB::table('phone_numbers')->update([
            'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
        ]);

        DB::table('customer_orders')->update([
            'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
        ]);
    }

    public function down(): void
    {
        Schema::table('customer_orders', function (Blueprint $table) {
            $table->dropIndex(['service_type', 'status']);
            $table->dropColumn('service_type');
        });

        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->dropIndex(['service_type', 'status']);
            $table->dropColumn('service_type');
        });
    }
};
