<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PhoneNumber;
use App\Models\PhonePackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PhoneNumberImportController extends Controller
{
    public function index()
    {
        return view('admin.import-numbers');
    }

    public function downloadSample()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sample_numbers.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($file, ['เบอร์', 'ยอดจ่ายก้อนแรก', 'แพ็กเกจ', 'เครือข่าย', 'สถานะ']);
            fputcsv($file, ['0812345678', '999', '-', 'true', 'active']);
            fputcsv($file, ['0912345678', '999', 'TRUE-SV-1499', 'true', 'active']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function store(Request $request)
    {
        $request->validate([
            'prepaid_file' => ['required', 'file', 'mimes:csv,txt'],
            'postpaid_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $prepaidData = $this->parseCsv($request->file('prepaid_file')->getRealPath(), PhoneNumber::SERVICE_TYPE_PREPAID);
        $postpaidData = $this->parseCsv($request->file('postpaid_file')->getRealPath(), PhoneNumber::SERVICE_TYPE_POSTPAID);
        $crossFileErrors = $this->findCrossFileDuplicates($prepaidData['records'], $postpaidData['records']);

        $validationErrors = [];
        if (count($prepaidData['errors']) > 0) {
            $validationErrors['prepaid_file'] = $prepaidData['errors'];
        }
        if (count($postpaidData['errors']) > 0) {
            $validationErrors['postpaid_file'] = $postpaidData['errors'];
        }
        if (count($crossFileErrors) > 0) {
            $validationErrors['error'] = $crossFileErrors;
        }
        if (count($prepaidData['records']) + count($postpaidData['records']) === 0) {
            $validationErrors['error'] = array_merge($validationErrors['error'] ?? [], [
                'ไม่พบข้อมูลเบอร์ที่พร้อมนำเข้าในไฟล์ CSV',
            ]);
        }

        if (
            count($prepaidData['errors']) > 0
            || count($postpaidData['errors']) > 0
            || count($crossFileErrors) > 0
            || (count($prepaidData['records']) + count($postpaidData['records']) === 0)
        ) {
            return back()->withErrors($validationErrors)->withInput();
        }

        try {
            $stats = DB::transaction(function () use ($prepaidData, $postpaidData) {
                $importedPhones = array_values(array_unique(array_merge(
                    array_column($prepaidData['records'], 'phone_number'),
                    array_column($postpaidData['records'], 'phone_number')
                )));

                $retiredActive = PhoneNumber::query()
                    ->whereNotIn('phone_number', $importedPhones)
                    ->where('status', PhoneNumber::STATUS_ACTIVE)
                    ->update([
                        'status' => PhoneNumber::STATUS_SOLD,
                        'updated_at' => now(),
                    ]);

                $prepaidStats = $this->upsertRecords($prepaidData['records'], PhoneNumber::SERVICE_TYPE_PREPAID);
                $postpaidStats = $this->upsertRecords($postpaidData['records'], PhoneNumber::SERVICE_TYPE_POSTPAID);

                return [
                    'created' => $prepaidStats['created'] + $postpaidStats['created'],
                    'updated' => $prepaidStats['updated'] + $postpaidStats['updated'],
                    'unchanged' => $prepaidStats['unchanged'] + $postpaidStats['unchanged'],
                    'retired_active' => $retiredActive,
                ];
            });

            return redirect()->route('admin.numbers')->with(
                'status_message',
                sprintf(
                    'นำเข้าข้อมูลเบอร์เรียบร้อยแล้ว: เพิ่มใหม่ %s, อัปเดต %s, ไม่เปลี่ยนแปลง %s, ปิดเบอร์เดิมที่ไม่อยู่ในไฟล์ %s',
                    number_format($stats['created']),
                    number_format($stats['updated']),
                    number_format($stats['unchanged']),
                    number_format($stats['retired_active'])
                )
            );
        } catch (\Throwable $e) {
            Log::error('Import failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล: ' . $e->getMessage()])->withInput();
        }
    }

    private function parseCsv(string $path, string $serviceType): array
    {
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle);
        
        if (!$headers) {
            fclose($handle);
            return ['records' => [], 'errors' => ['ไม่สามารถอ่าน Header ของไฟล์ได้']];
        }

        // Clean BOM if present
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);

        $headerMap = $this->matchHeaders($headers);
        if (!isset($headerMap['phone']) || !isset($headerMap['initial_payment'])) {
            return ['records' => [], 'errors' => ['ไฟล์ CSV ต้องมีคอลัมน์ "เบอร์" และ "ยอดจ่ายก้อนแรก"']];
        }

        $records = [];
        $errors = [];
        $seenPhones = [];
        $rowCount = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowCount++;
            
            // Skip empty rows
            if (count($row) < 2 || trim(implode('', $row)) === '') {
                continue;
            }

            $phone = $this->normalizePhoneNumber($row[$headerMap['phone']] ?? '');
            $initialPaymentPrice = $this->normalizeInteger($row[$headerMap['initial_payment']] ?? '');
            $package = isset($headerMap['package']) ? trim($row[$headerMap['package']] ?? '') : null;
            $status = isset($headerMap['status']) ? trim($row[$headerMap['status']] ?? '') : 'active';
            $resolvedStatus = $this->resolveStatus($status);
            $network = isset($headerMap['network']) ? $this->normalizeNetwork($row[$headerMap['network']] ?? '') : 'true_dtac';
            $phonePackage = null;

            if (!$phone) {
                $errors[] = "แถวที่ {$rowCount}: เบอร์โทรศัพท์ไม่ถูกต้อง (ต้องมี 10 หลัก)";
                continue;
            }

            if (isset($seenPhones[$phone])) {
                $errors[] = "แถวที่ {$rowCount}: เบอร์ {$phone} ซ้ำกับแถวที่ {$seenPhones[$phone]}";
                continue;
            }

            if ($initialPaymentPrice === null) {
                $errors[] = "แถวที่ {$rowCount}: ยอดจ่ายก้อนแรกไม่ถูกต้อง";
                continue;
            }

            if ($network === null) {
                $errors[] = "แถวที่ {$rowCount}: เครือข่ายไม่ถูกต้อง (รองรับ true, true_dtac, dtac, ais)";
                continue;
            }

            if ($resolvedStatus === null) {
                $errors[] = "แถวที่ {$rowCount}: สถานะไม่ถูกต้อง (รองรับ active, hold, sold)";
                continue;
            }

            if ($serviceType === PhoneNumber::SERVICE_TYPE_PREPAID) {
                if ($package !== null && trim($package) !== '' && trim($package) !== '-') {
                    $errors[] = "แถวที่ {$rowCount}: เบอร์เติมเงินไม่ต้องระบุรหัสแพ็กเกจ";
                    continue;
                }
            } else {
                $packageCode = strtoupper(trim((string) $package));
                if ($packageCode === '' || $packageCode === '-') {
                    $errors[] = "แถวที่ {$rowCount}: เบอร์รายเดือนต้องระบุรหัสแพ็กเกจ";
                    continue;
                }

                $phonePackage = PhonePackage::query()
                    ->where('code', $packageCode)
                    ->first();

                if (! $phonePackage) {
                    $errors[] = "แถวที่ {$rowCount}: ไม่พบรหัสแพ็กเกจ {$packageCode}";
                    continue;
                }

                if (! $phonePackage->is_active) {
                    $errors[] = "แถวที่ {$rowCount}: แพ็กเกจ {$packageCode} ถูกปิดใช้งาน";
                    continue;
                }

                if ($phonePackage->service_type !== PhoneNumber::SERVICE_TYPE_POSTPAID) {
                    $errors[] = "แถวที่ {$rowCount}: แพ็กเกจ {$packageCode} ไม่ใช่แพ็กเกจรายเดือน";
                    continue;
                }

                if ($phonePackage->network_code !== $network) {
                    $errors[] = "แถวที่ {$rowCount}: เครือข่ายของแพ็กเกจ {$packageCode} ไม่ตรงกับไฟล์";
                    continue;
                }
            }

            $seenPhones[$phone] = $rowCount;

            $records[] = [
                'phone_number' => $phone,
                'row' => $rowCount,
                'initial_payment_price' => $initialPaymentPrice,
                'sale_price' => $initialPaymentPrice,
                'package_id' => $phonePackage?->id,
                'plan_name' => $phonePackage?->name ?? ($serviceType === PhoneNumber::SERVICE_TYPE_PREPAID ? 'เติมเงิน' : $package),
                'status' => $resolvedStatus,
                'network_code' => $network,
                'number_sum' => PhoneNumber::calculateNumberSum($phone),
            ];
        }

        fclose($handle);

        return [
            'records' => $records,
            'errors' => $errors,
        ];
    }

    private function matchHeaders(array $headers): array
    {
        $map = [];
        foreach ($headers as $index => $label) {
            $label = mb_strtolower(trim($label));
            if (in_array($label, ['เบอร์', 'เลขหมาย', 'phone', 'phone_number'], true)) {
                $map['phone'] = $index;
            } elseif (in_array($label, ['ยอดจ่ายก้อนแรก', 'ยอดชำระก้อนแรก', 'ยอดชำระแรก', 'ราคา', 'price', 'initial_payment_price'], true)) {
                $map['initial_payment'] = $index;
            } elseif (in_array($label, ['แพ็กเกจ', 'แพคเกจ', 'โปร', 'package', 'plan', 'โปรโมชั่น'], true)) {
                $map['package'] = $index;
            } elseif (in_array($label, ['สถานะ', 'status'], true)) {
                $map['status'] = $index;
            } elseif (in_array($label, ['เครือข่าย', 'network', 'network_code'], true)) {
                $map['network'] = $index;
            }
        }
        return $map;
    }

    private function normalizePhoneNumber(string $value): ?string
    {
        $digits = preg_replace('/\D/', '', $value);
        if (strlen($digits) === 10) return $digits;
        if (strlen($digits) === 9) return '0' . $digits;
        return null;
    }

    private function normalizeInteger(string $value): ?int
    {
        $normalized = str_replace([',', 'บาท', ' '], '', trim($value));

        if (preg_match('/^\d+(?:\.\d+)?$/', $normalized) !== 1) {
            return null;
        }

        return (int) round((float) $normalized);
    }

    private function normalizeNetwork(string $value): ?string
    {
        $value = strtolower(trim($value));
        $value = str_replace([' ', '-'], '_', $value);

        if ($value === '') return 'true_dtac';

        return match ($value) {
            'ais' => PhoneNumber::NETWORK_AIS,
            'dtac' => PhoneNumber::NETWORK_DTAC,
            'true' => PhoneNumber::NETWORK_TRUE,
            'true_dtac', 'truedtac', 'true_move_dtac', 'true_dtac_network' => PhoneNumber::NETWORK_TRUE_DTAC,
            default => null,
        };
    }

    private function resolveStatus(string $status): ?string
    {
        $status = strtolower(trim($status));
        if ($status === '') return PhoneNumber::STATUS_ACTIVE;
        if (in_array($status, ['active', 'available', 'พร้อมขาย', 'ว่าง'], true)) return PhoneNumber::STATUS_ACTIVE;
        if (in_array($status, ['sold', 'ขายแล้ว', 'ขาย'], true)) return PhoneNumber::STATUS_SOLD;
        if (in_array($status, ['hold', 'held', 'จอง', 'พัก'], true)) return PhoneNumber::STATUS_HOLD;
        return null;
    }

    private function findCrossFileDuplicates(array $prepaidRecords, array $postpaidRecords): array
    {
        $prepaidRowsByPhone = [];
        foreach ($prepaidRecords as $record) {
            $prepaidRowsByPhone[$record['phone_number']] = $record['row'];
        }

        $errors = [];
        foreach ($postpaidRecords as $record) {
            $phone = $record['phone_number'];
            if (isset($prepaidRowsByPhone[$phone])) {
                $errors[] = sprintf(
                    'เบอร์ %s ซ้ำระหว่างไฟล์เติมเงินแถวที่ %s และไฟล์รายเดือนแถวที่ %s',
                    $phone,
                    $prepaidRowsByPhone[$phone],
                    $record['row']
                );
            }
        }

        return $errors;
    }

    private function upsertRecords(array $records, string $serviceType): array
    {
        $stats = [
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
        ];

        $phones = array_column($records, 'phone_number');
        $existingNumbers = PhoneNumber::query()
            ->whereIn('phone_number', $phones)
            ->get()
            ->keyBy('phone_number');

        foreach ($records as $record) {
            $defaultPlanName = $serviceType === PhoneNumber::SERVICE_TYPE_PREPAID ? 'เติมเงิน' : 'รายเดือน';
            $phoneNumber = $existingNumbers->get($record['phone_number']) ?? new PhoneNumber([
                'phone_number' => $record['phone_number'],
            ]);

            $phoneNumber->fill([
                'number_sum' => $record['number_sum'],
                'service_type' => $serviceType,
                'network_code' => $record['network_code'],
                'package_id' => $record['package_id'],
                'plan_name' => $record['plan_name'] ?: $defaultPlanName,
                'initial_payment_price' => $record['initial_payment_price'],
                'sale_price' => $record['sale_price'],
                'status' => $record['status'],
            ]);

            if (! $phoneNumber->exists) {
                $phoneNumber->save();
                $stats['created']++;
                continue;
            }

            if ($phoneNumber->isDirty()) {
                $phoneNumber->save();
                $stats['updated']++;
                continue;
            }

            $stats['unchanged']++;
        }

        return $stats;
    }
}
