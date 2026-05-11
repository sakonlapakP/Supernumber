<?php

namespace App\Console\Commands;

use App\Models\PhoneNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportPostpaidSnapshotCommand extends Command
{
    protected $signature = 'numbers:import-postpaid-snapshot
        {file : Path to a SQL snapshot containing phone/price value pairs}
        {--dry-run : Parse the snapshot and show the summary without writing to the database}';

    protected $description = 'Import postpaid number stock from a SQL snapshot value list';

    public function handle(): int
    {
        $path = (string) $this->argument('file');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            $this->error("Unable to read file: {$path}");

            return self::FAILURE;
        }

        $parsed = $this->parseRecords($contents);
        if ($parsed['record_count'] === 0) {
            $this->warn('No importable postpaid records were found in the snapshot.');

            return self::SUCCESS;
        }

        $phoneNumbers = array_keys($parsed['records']);
        $existingNumbers = PhoneNumber::query()
            ->whereIn('phone_number', $phoneNumbers)
            ->get()
            ->keyBy('phone_number');

        $this->line('ไฟล์: ' . $path);
        $this->line('อ่านได้ทั้งหมด: ' . number_format($parsed['row_count']) . ' รายการ');
        $this->line('พร้อม import: ' . number_format($parsed['record_count']) . ' เบอร์');
        $this->line('พบซ้ำใน snapshot: ' . number_format($parsed['duplicate_count']) . ' เบอร์');
        $this->line('มีอยู่ในระบบแล้ว: ' . number_format($existingNumbers->count()) . ' เบอร์');

        if ($this->option('dry-run')) {
            $this->info('Dry run complete. No database changes were made.');

            return self::SUCCESS;
        }

        $stats = [
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'preserved_status' => 0,
            'skipped_prepaid' => 0,
        ];

        DB::transaction(function () use ($parsed, $existingNumbers, &$stats): void {
            foreach ($parsed['records'] as $phoneNumber => $price) {
                /** @var PhoneNumber|null $existing */
                $existing = $existingNumbers->get($phoneNumber);

                if ($existing?->is_prepaid) {
                    $stats['skipped_prepaid']++;
                    continue;
                }

                $status = $this->resolveImportedStatus($existing);

                if ($existing && $status !== PhoneNumber::STATUS_ACTIVE) {
                    $stats['preserved_status']++;
                }

                $payload = [
                    'number_sum' => PhoneNumber::calculateNumberSum($phoneNumber),
                    'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
                    'network_code' => PhoneNumber::NETWORK_TRUE_DTAC,
                    'plan_name' => PhoneNumber::PACKAGE_NAME,
                    'sale_price' => $price,
                    'status' => $status,
                ];

                if ($existing) {
                    $existing->fill($payload);

                    if ($existing->isDirty()) {
                        $existing->save();
                        $stats['updated']++;
                    } else {
                        $stats['unchanged']++;
                    }

                    continue;
                }

                PhoneNumber::query()->create([
                    'phone_number' => $phoneNumber,
                    ...$payload,
                ]);

                $stats['created']++;
            }
        });

        $this->info('นำเข้าข้อมูลรายเดือนเรียบร้อยแล้ว');
        $this->line('สร้างใหม่: ' . number_format($stats['created']) . ' เบอร์');
        $this->line('อัปเดตข้อมูลเดิม: ' . number_format($stats['updated']) . ' เบอร์');
        $this->line('ไม่เปลี่ยนแปลง: ' . number_format($stats['unchanged']) . ' เบอร์');
        $this->line('คงสถานะเดิมที่ไม่ใช่ active: ' . number_format($stats['preserved_status']) . ' เบอร์');
        $this->line('ข้ามเบอร์ที่เป็นเติมเงินอยู่แล้ว: ' . number_format($stats['skipped_prepaid']) . ' เบอร์');

        return self::SUCCESS;
    }

    /**
     * @return array{row_count:int,record_count:int,duplicate_count:int,records:array<string, int>}
     */
    private function parseRecords(string $contents): array
    {
        preg_match_all(
            "/\\(\\s*'(?<phone>\\d{9,10})'\\s*,\\s*(?<price>\\d+)\\s*\\)/",
            $contents,
            $matches,
            PREG_SET_ORDER
        );

        $records = [];
        $duplicateNumbers = [];

        foreach ($matches as $match) {
            $phoneNumber = $this->normalizePhoneNumber($match['phone'] ?? '');
            $price = (int) ($match['price'] ?? 0);

            if ($phoneNumber === null || $price < 1) {
                continue;
            }

            if (isset($records[$phoneNumber])) {
                $duplicateNumbers[$phoneNumber] = true;
            }

            $records[$phoneNumber] = $price;
        }

        return [
            'row_count' => count($matches),
            'record_count' => count($records),
            'duplicate_count' => count($duplicateNumbers),
            'records' => $records,
        ];
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

    private function resolveImportedStatus(?PhoneNumber $existing): string
    {
        $status = strtolower(trim((string) ($existing?->status ?? '')));

        return in_array($status, [PhoneNumber::STATUS_HOLD, PhoneNumber::STATUS_SOLD], true)
            ? $status
            : PhoneNumber::STATUS_ACTIVE;
    }
}
