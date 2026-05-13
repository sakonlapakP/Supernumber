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

        $header = fgetcsv($handle);
        $columns = $this->matchHeaderColumns(is_array($header) ? $header : []);

        if ($columns === null) {
            fclose($handle);
            $this->error('CSV header must include เบอร์ and ยอดจ่ายก้อนแรก/ราคา columns.');

            return self::FAILURE;
        }
        
        $stats = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'skipped' => 0,
        ];

        $records = [];
        while (($row = fgetcsv($handle)) !== false) {
            $phoneNumber = $this->normalizePhoneNumber($row[$columns['phone']] ?? null);
            $salePrice = $this->normalizeInteger($row[$columns['price']] ?? null);
            $sum = isset($columns['number_sum'])
                ? $this->normalizeInteger($row[$columns['number_sum']] ?? null)
                : null;
            $status = isset($columns['status'])
                ? trim((string) ($row[$columns['status']] ?? ''))
                : '';

            if ($this->shouldSkipStatus($status)) {
                $stats['skipped']++;
                continue;
            }

            if ($phoneNumber === null || $salePrice === null) {
                if (implode('', $row) !== '') {
                    $stats['skipped']++;
                }
                continue;
            }

            $records[$phoneNumber] = [
                'number_sum' => PhoneNumber::calculateNumberSum($phoneNumber),
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
                    'number_sum' => $data['number_sum'],
                    'service_type' => PhoneNumber::SERVICE_TYPE_PREPAID,
                    'network_code' => 'true_dtac',
                    'plan_name' => 'เติมเงิน',
                    'sale_price' => $data['sale_price'],
                    'initial_payment_price' => $data['sale_price'],
                    'package_id' => null,
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

    /**
     * @param  array<int, string|null>  $header
     * @return array{phone:int,price:int,status?:int,number_sum?:int}|null
     */
    private function matchHeaderColumns(array $header): ?array
    {
        $matched = [];

        foreach ($header as $index => $value) {
            $label = trim((string) $value);

            if ($label === 'เบอร์') {
                $matched['phone'] = $index;
            }

            if (in_array($label, ['ยอดจ่ายก้อนแรก', 'ยอดชำระก้อนแรก', 'ยอดชำระแรก', 'ราคา'], true)) {
                $matched['price'] = $index;
            }

            if ($label === 'สถานะ') {
                $matched['status'] = $index;
            }

            if ($label === 'ผลรวม') {
                $matched['number_sum'] = $index;
            }
        }

        if (! isset($matched['phone'], $matched['price'])) {
            return null;
        }

        return $matched;
    }

    private function normalizePhoneNumber(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if (strlen($digits) === PhoneNumber::PHONE_NUMBER_LENGTH) {
            return $digits;
        }

        if (strlen($digits) === PhoneNumber::PHONE_NUMBER_LENGTH - 1) {
            return '0' . $digits;
        }

        return null;
    }

    private function normalizeInteger(mixed $value): ?int
    {
        $normalized = str_replace([',', 'บาท', ' '], '', trim((string) $value));

        if ($normalized === '' || ! is_numeric($normalized)) {
            return null;
        }

        return (int) round((float) $normalized);
    }

    private function shouldSkipStatus(string $status): bool
    {
        $status = trim(mb_strtolower($status));

        return in_array($status, ['ขาย', 'sold'], true);
    }
}
