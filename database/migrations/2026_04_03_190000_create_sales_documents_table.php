<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('document_type', 32);
            $table->string('document_number', 255);
            $table->date('document_date')->nullable();
            $table->date('due_date')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name', 255)->nullable();
            $table->string('file_name', 255);
            $table->string('pdf_disk', 32)->default('local');
            $table->string('pdf_path', 1024);
            $table->unsignedBigInteger('saved_by_user_id')->nullable();
            $table->json('payload');
            $table->timestamps();

            $table->unique(['document_type', 'document_number']);
            $table->index(['document_type', 'document_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_documents');
    }
};
