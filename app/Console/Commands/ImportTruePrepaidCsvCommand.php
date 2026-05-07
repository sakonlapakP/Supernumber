<?php

namespace App\Console\Commands;

use App\Models\PhoneNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportTruePrepaidCsvCommand extends Command
{
    protected $signature = 'numbers:import-true-csv
        {file : Path to the TRUE prepaid CSV file}
        {--dry-run : Parse the CSV and show the summary without writing to the database}';

    protected $description = 'Import prepaid numbers from a CSV file';

    public function handle(): int
    {
        $path = $this->argument('file');

        if (!is_file($path)) {
            $this->error("File not found: {$path}");
            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->error("Unable to open file: {$path}");
            return self::FAILURE;
        }

        // Skip header
        $header = fgetcsv($handle);
        
        $stats = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'skipped' => 0,
        ];

        $records = [];
        while (($row = fgetcsv($handle)) !== false) {
            // Check if row has enough columns
            if (count($row) < 6) continue;

            // Column 1 is the phone number with dashes
            $displayNumber = trim($row[1]);
            $phoneNumber = preg_replace('/\D/', '', $displayNumber);
            
            if (strlen($phoneNumber) !== 10) {
                if ($phoneNumber !== '') {
                    $stats['skipped']++;
                }
                continue;
            }

            // Column 2 is the sum
            $sum = (int) $row[2];
            
            // Column 5 is the price text e.g. "12,900"
            $priceText = $row[5];
            $salePrice = (int) preg_replace('/\D/', '', $priceText);

            $records[$phoneNumber] = [
                'display_number' => $displayNumber,
                'number_sum' => $sum ?: PhoneNumber::calculateNumberSum($phoneNumber),
                'sale_price' => $salePrice,
            ];
            $stats['total']++;
        }
        fclose($handle);

        $this->info("Read total " . count($records) . " unique records from CSV.");

        if ($this->option('dry-run')) {
            $this->info("Dry run complete.");
            return self::SUCCESS;
        }

        $existingNumbers = PhoneNumber::query()
            ->whereIn('phone_number', array_keys($records))
            ->get()
            ->keyBy('phone_number');

        DB::transaction(function () use ($records, $existingNumbers, &$stats) {
            foreach ($records as $phone => $data) {
                $existing = $existingNumbers->get($phone);
                
                $payload = [
                    'display_number' => $data['display_number'],
                    'number_sum' => $data['number_sum'],
                    'service_type' => PhoneNumber::SERVICE_TYPE_PREPAID,
                    'network_code' => 'true_dtac',
                    'plan_name' => 'เติมเงิน',
                    'price_text' => number_format($data['sale_price']),
                    'sale_price' => $data['sale_price'],
                    'status' => PhoneNumber::STATUS_ACTIVE,
                ];

                if ($existing) {
                    $existing->fill($payload);
                    if ($existing->isDirty()) {
                        $existing->save();
                        $stats['updated']++;
                    } else {
                        $stats['unchanged']++;
                    }
                } else {
                    PhoneNumber::create([
                        'phone_number' => $phone,
                        ...$payload
                    ]);
                    $stats['created']++;
                }
            }
        });

        $this->info("Import complete.");
        $this->line("Created: " . number_format($stats['created']));
        $this->line("Updated: " . number_format($stats['updated']));
        $this->line("Unchanged: " . number_format($stats['unchanged']));
        $this->line("Skipped: " . number_format($stats['skipped']));

        return self::SUCCESS;
    }
}
