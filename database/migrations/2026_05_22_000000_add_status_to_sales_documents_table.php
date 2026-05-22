<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_documents', function (Blueprint $table): void {
            // Add status column after document_type
            $table->string('status', 32)->default('draft')->after('document_type');

            // Add source_quotation_id for invoices created from quotations
            $table->unsignedBigInteger('source_quotation_id')->nullable()->after('document_number');

            // Add indexes for querying by status
            $table->index(['document_type', 'status']);
            $table->index(['status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::table('sales_documents', function (Blueprint $table): void {
            $table->dropIndex(['document_type', 'status']);
            $table->dropIndex(['status', 'updated_at']);
            $table->dropColumn(['status', 'source_quotation_id']);
        });
    }
};
