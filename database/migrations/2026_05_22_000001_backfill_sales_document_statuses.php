<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('sales_documents')
            ->where('document_type', 'quotation')
            ->whereIn('status', ['draft', ''])
            ->update(['status' => 'quotation_draft']);

        DB::table('sales_documents')
            ->where('document_type', 'invoice')
            ->whereIn('status', ['draft', ''])
            ->update(['status' => 'invoice_draft']);

        Schema::table('sales_documents', function (Blueprint $table): void {
            $table->unique('source_quotation_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales_documents', function (Blueprint $table): void {
            $table->dropUnique(['source_quotation_id']);
        });
    }
};
