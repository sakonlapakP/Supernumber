<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class ConvertToTimestamps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:convert-to-timestamps';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert all bigInteger Unix timestamps to MySQL TIMESTAMP type';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tables = [
            'users' => ['email_verified_at', 'created_at', 'updated_at'],
            'phone_numbers' => ['created_at', 'updated_at'],
            'customer_orders' => ['created_at', 'updated_at'],
            'article_comments' => ['approved_at', 'created_at', 'updated_at'],
            'line_webhook_events' => ['received_at', 'created_at', 'updated_at'],
            'lottery_results' => ['fetched_at', 'created_at', 'updated_at'],
            'estimate_leads' => ['submitted_at', 'created_at', 'updated_at'],
            'contact_messages' => ['submitted_at', 'created_at', 'updated_at'],
            'customers' => ['created_at', 'updated_at'],
            'sales_documents' => ['document_date', 'due_date', 'created_at', 'updated_at'],
            'pair_meanings' => ['created_at', 'updated_at'],
            'phone_number_status_logs' => ['created_at', 'updated_at'],
            'articles' => ['published_at', 'notified_at', 'created_at', 'updated_at'],
            'line_notification_logs' => ['sent_at', 'failed_at', 'created_at', 'updated_at'],
        ];

        if (Schema::hasTable('personal_access_tokens')) {
            $tables['personal_access_tokens'] = ['last_used_at', 'expires_at', 'created_at', 'updated_at'];
        }

        foreach ($tables as $table => $columns) {
            $this->info("Processing table: {$table}");
            $this->convertTable($table, $columns);
        }

        $this->success("All tables processed successfully!");
    }

    private function convertTable(string $table, array $columns): void
    {
        $driver = DB::getDriverName();
        $columnsToProcess = [];

        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                $type = Schema::getColumnType($table, $column);
                if ($type !== 'timestamp' && $type !== 'datetime') {
                    $columnsToProcess[] = $column;
                }
            }
        }

        if (empty($columnsToProcess)) {
            $this->line("  No columns to process for {$table}. Skipping.");
            return;
        }

        $this->line("  Converting columns: " . implode(', ', $columnsToProcess));

        // 1. Add temporary columns
        Schema::table($table, function (Blueprint $t) use ($table, $columnsToProcess) {
            foreach ($columnsToProcess as $column) {
                if (!Schema::hasColumn($table, $column . '_ts')) {
                    $t->timestamp($column . '_ts')->nullable()->after($column);
                }
            }
        });

        // 2. Copy and convert data
        foreach ($columnsToProcess as $column) {
            if ($driver === 'sqlite') {
                DB::statement("UPDATE {$table} SET {$column}_ts = datetime({$column}, 'unixepoch') WHERE {$column} IS NOT NULL");
            } else {
                DB::statement("UPDATE {$table} SET {$column}_ts = FROM_UNIXTIME({$column}) WHERE {$column} IS NOT NULL");
            }
        }

        // 3. Handle Indexes
        $allIndexes = Schema::getIndexes($table);
        foreach ($allIndexes as $index) {
            if (array_intersect($index['columns'], $columnsToProcess)) {
                Schema::table($table, function (Blueprint $t) use ($index) {
                    try {
                        $t->dropIndex($index['name']);
                    } catch (\Exception $e) {}
                });
            }
        }

        // 4. Drop old columns
        Schema::table($table, function (Blueprint $t) use ($table, $columnsToProcess) {
            foreach ($columnsToProcess as $column) {
                $t->dropColumn($column);
            }
        });

        // 5. Recreate columns as TIMESTAMP
        Schema::table($table, function (Blueprint $t) use ($columnsToProcess) {
            foreach ($columnsToProcess as $column) {
                $t->timestamp($column)->nullable();
            }
        });

        // Restore indexes
        $currentIndexes = array_column(Schema::getIndexes($table), 'name');
        if ($table === 'articles' && !in_array('articles_is_published_published_at_index', $currentIndexes)) {
            Schema::table($table, function (Blueprint $t) {
                $t->index(['is_published', 'published_at'], 'articles_is_published_published_at_index');
            });
        }
        if ($table === 'line_notification_logs' && !in_array('line_notification_logs_destination_key_created_at_index', $currentIndexes)) {
            Schema::table($table, function (Blueprint $t) {
                $t->index(['destination_key', 'created_at'], 'line_notification_logs_destination_key_created_at_index');
            });
        }

        // 6. Restore data
        foreach ($columnsToProcess as $column) {
            DB::statement("UPDATE {$table} SET {$column} = {$column}_ts");
        }

        // 7. Cleanup
        Schema::table($table, function (Blueprint $t) use ($table, $columnsToProcess) {
            foreach ($columnsToProcess as $column) {
                $t->dropColumn($column . '_ts');
            }
        });
    }

    private function success(string $message): void
    {
        $this->output->writeln("<info>{$message}</info>");
    }
}
