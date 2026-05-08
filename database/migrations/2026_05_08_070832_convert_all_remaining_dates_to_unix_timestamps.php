<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->convertTable('users', ['email_verified_at', 'created_at', 'updated_at']);
        $this->convertTable('phone_numbers', ['created_at', 'updated_at']);
        $this->convertTable('customer_orders', ['created_at', 'updated_at']);
        $this->convertTable('article_comments', ['approved_at', 'created_at', 'updated_at']);
        $this->convertTable('line_webhook_events', ['received_at', 'created_at', 'updated_at']);
        $this->convertTable('lottery_results', ['fetched_at', 'created_at', 'updated_at']);
        $this->convertTable('estimate_leads', ['submitted_at', 'created_at', 'updated_at']);
        $this->convertTable('contact_messages', ['submitted_at', 'created_at', 'updated_at']);
        $this->convertTable('customers', ['created_at', 'updated_at']);
        $this->convertTable('sales_documents', ['document_date', 'due_date', 'created_at', 'updated_at']);
        $this->convertTable('pair_meanings', ['created_at', 'updated_at']);
        $this->convertTable('phone_number_status_logs', ['created_at', 'updated_at']);
        
        if (Schema::hasTable('personal_access_tokens')) {
            $this->convertTable('personal_access_tokens', ['last_used_at', 'expires_at', 'created_at', 'updated_at']);
        }
    }

    private function convertTable(string $table, array $columns): void
    {
        Schema::table($table, function (Blueprint $t) use ($columns) {
            foreach ($columns as $column) {
                if (Schema::hasColumn($t->getTable(), $column)) {
                    $t->bigInteger($column . '_new')->nullable()->after($column);
                }
            }
        });

        $updates = [];
        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                $updates[] = "{$column}_new = UNIX_TIMESTAMP({$column})";
            }
        }
        
        if (!empty($updates)) {
            $updateStr = implode(', ', $updates);
            DB::statement("UPDATE {$table} SET {$updateStr}");
        }

        Schema::table($table, function (Blueprint $t) use ($columns) {
            foreach ($columns as $column) {
                if (Schema::hasColumn($t->getTable(), $column)) {
                    $t->dropColumn($column);
                }
            }
        });

        Schema::table($table, function (Blueprint $t) use ($columns) {
            foreach ($columns as $column) {
                $t->bigInteger($column)->nullable();
            }
        });

        $updates = [];
        foreach ($columns as $column) {
            $updates[] = "{$column} = {$column}_new";
        }
        $updateStr = implode(', ', $updates);
        DB::statement("UPDATE {$table} SET {$updateStr}");

        Schema::table($table, function (Blueprint $t) use ($columns) {
            foreach ($columns as $column) {
                $t->dropColumn($column . '_new');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
