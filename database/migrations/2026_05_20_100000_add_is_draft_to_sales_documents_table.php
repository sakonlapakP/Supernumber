<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_documents', function (Blueprint $table): void {
            $table->boolean('is_draft')->default(false)->after('is_active');
            $table->index(['is_draft', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::table('sales_documents', function (Blueprint $table): void {
            $table->dropIndex(['is_draft', 'updated_at']);
            $table->dropColumn('is_draft');
        });
    }
};
