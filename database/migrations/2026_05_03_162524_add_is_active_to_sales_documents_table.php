<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_documents', function (Blueprint $blueprint) {
            $blueprint->boolean('is_active')->default(true)->after('payload');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_documents', function (Blueprint $blueprint) {
            $blueprint->dropColumn('is_active');
        });
    }
};
