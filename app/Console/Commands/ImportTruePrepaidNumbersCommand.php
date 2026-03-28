<?php

namespace App\Console\Commands;

use App\Models\PhoneNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use SimpleXMLElement;
use ZipArchive;

class ImportTruePrepaidNumbersCommand extends Command
{
    protected $signature = 'numbers:import-true-prepaid
        {file : Path to the TRUE prepaid spreadsheet (.xlsx)}
        {--dry-run : Parse the spreadsheet and show the summary without writing to the database}';

    protected $description = 'Import prepaid stock spreadsheet using number, optional sum, price, and status columns';

    private const XML_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    public function handle(): int
    {
        $path = $this->resolveImportPath((string) $this->argument('file'));

        if ($path === null || ! is_file($path)) {
            $this->error('Spreadsheet file was not found.');

            return self::FAILURE;
        }

        try {
            $parsed = $this->parseWorkbook($path);
        } catch (\Throwable $exception) {
            $this->error('Unable to parse spreadsheet: ' . $exception->getMessage());

            return self::FAILURE;
        }

        if ($parsed['record_count'] === 0) {
            $this->warn('No importable rows were found in the spreadsheet.');

            return self::SUCCESS;
        }

        $phoneNumbers = array_keys($parsed['records']);
        $existingNumbers = PhoneNumber::query()
            ->whereIn('phone_number', $phoneNumbers)
            ->get()
            ->keyBy('phone_number');

        $this->line('ไฟล์: ' . $path);
        $this->line('อ่านได้ทั้งหมด: ' . number_format($parsed['row_count']) . ' แถว');
        $this->line('พร้อม import: ' . number_format($parsed['record_count']) . ' เบอร์');
        $this->line('พบซ้ำในไฟล์: ' . number_format($parsed['duplicate_count']) . ' เบอร์');
        $this->line('ข้ามสถานะขาย: ' . number_format($parsed['skipped_sold_count']) . ' เบอร์');
        $this->line('มีอยู่ในระบบแล้ว: ' . number_format($existingNumbers->count()) . ' เบอร์');

        if ($parsed['duplicate_count'] > 0) {
            foreach ($parsed['duplicates'] as $phoneNumber => $rows) {
                $this->warn(sprintf(
                    'ข้ามเบอร์ซ้ำ %s จากแถว %s',
                    $phoneNumber,
                    implode(', ', $rows)
                ));
            }
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run complete. No database changes were made.');

            return self::SUCCESS;
        }

        $stats = [
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'preserved_status' => 0,
            'converted_to_prepaid' => 0,
        ];

        DB::transaction(function () use ($parsed, $existingNumbers, &$stats): void {
            foreach ($parsed['records'] as $phoneNumber => $row) {
                /** @var PhoneNumber|null $existing */
                $existing = $existingNumbers->get($phoneNumber);
                $status = $this->resolveImportedStatus($existing);

                if ($existing && $status !== PhoneNumber::STATUS_ACTIVE) {
                    $stats['preserved_status']++;
                }

                if ($existing && $existing->service_type !== PhoneNumber::SERVICE_TYPE_PREPAID) {
                    $stats['converted_to_prepaid']++;
                }

                $payload = [
                    'display_number' => $this->formatDisplayNumber($phoneNumber),
                    'number_sum' => $row['number_sum'] ?? $existing?->number_sum,
                    'service_type' => PhoneNumber::SERVICE_TYPE_PREPAID,
                    'network_code' => 'true_dtac',
                    'plan_name' => 'เติมเงิน',
                    'price_text' => (string) $row['sale_price'],
                    'sale_price' => $row['sale_price'],
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

        $this->info('นำเข้าข้อมูล TRUE เติมเงินเรียบร้อยแล้ว');
        $this->line('สร้างใหม่: ' . number_format($stats['created']) . ' เบอร์');
        $this->line('อัปเดตข้อมูลเดิม: ' . number_format($stats['updated']) . ' เบอร์');
        $this->line('ไม่เปลี่ยนแปลง: ' . number_format($stats['unchanged']) . ' เบอร์');
        $this->line('คงสถานะเดิมที่ไม่ใช่ active: ' . number_format($stats['preserved_status']) . ' เบอร์');
        $this->line('เปลี่ยนจากรายเดือนเป็นเติมเงิน: ' . number_format($stats['converted_to_prepaid']) . ' เบอร์');

        return self::SUCCESS;
    }

    /**
     * @return array{
     *     row_count:int,
     *     record_count:int,
     *     duplicate_count:int,
     *     skipped_sold_count:int,
     *     duplicates:array<string, array<int, string>>,
     *     records:array<string, array{row:string, number_sum:int|null, sale_price:int}>
     * }
     */
    private function parseWorkbook(string $path): array
    {
        $archive = new ZipArchive();

        if ($archive->open($path) !== true) {
            throw new \RuntimeException('Unable to open xlsx archive.');
        }

        $sharedStringsXml = $archive->getFromName('xl/sharedStrings.xml');
        $workbookXml = $archive->getFromName('xl/workbook.xml');
        $workbookRelsXml = $archive->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $workbookRelsXml === false) {
            $archive->close();

            throw new \RuntimeException('Workbook metadata is missing from xlsx archive.');
        }

        $sharedStrings = $this->parseSharedStrings($sharedStringsXml !== false ? $sharedStringsXml : null);
        $sheetDefinitions = $this->parseWorkbookSheets($workbookXml, $workbookRelsXml);

        $records = [];
        $duplicates = [];
        $rowCount = 0;
        $skippedSoldCount = 0;

        foreach ($sheetDefinitions as $sheetDefinition) {
            $sheetXml = $archive->getFromName($sheetDefinition['path']);

            if ($sheetXml === false) {
                continue;
            }

            $sheetResult = $this->parseWorksheet(
                $sheetXml,
                $sharedStrings,
                $sheetDefinition['name']
            );

            $rowCount += $sheetResult['row_count'];
            $skippedSoldCount += $sheetResult['skipped_sold_count'];
            foreach ($sheetResult['duplicates'] as $phoneNumber => $rows) {
                $duplicates[$phoneNumber] ??= [];
                $duplicates[$phoneNumber] = array_values(array_unique([
                    ...$duplicates[$phoneNumber],
                    ...$rows,
                ]));
            }

            foreach ($sheetResult['records'] as $phoneNumber => $record) {
                if (isset($records[$phoneNumber])) {
                    $duplicates[$phoneNumber] ??= [$records[$phoneNumber]['row']];
                    $duplicates[$phoneNumber][] = $record['row'];

                    continue;
                }

                $records[$phoneNumber] = $record;
            }
        }

        $archive->close();

        return [
            'row_count' => $rowCount,
            'record_count' => count($records),
            'duplicate_count' => count($duplicates),
            'skipped_sold_count' => $skippedSoldCount,
            'duplicates' => $duplicates,
            'records' => $records,
        ];
    }

    /**
     * @return array<int, array{name:string,path:string}>
     */
    private function parseWorkbookSheets(string $workbookXml, string $workbookRelsXml): array
    {
        $workbook = $this->loadXml($workbookXml);
        $relationships = $this->loadRelationshipsXml($workbookRelsXml);
        $relationMap = [];

        foreach ($relationships->Relationship as $relationship) {
            $relationMap[(string) $relationship['Id']] = 'xl/' . ltrim((string) $relationship['Target'], '/');
        }

        $sheets = [];

        foreach ($workbook->xpath('//a:sheets/a:sheet') ?: [] as $sheet) {
            $sheetId = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            $sheetPath = $relationMap[$sheetId] ?? null;

            if ($sheetPath === null) {
                continue;
            }

            $sheets[] = [
                'name' => trim((string) ($sheet['name'] ?? 'Sheet')),
                'path' => $sheetPath,
            ];
        }

        return $sheets;
    }

    /**
     * @param  array<int, string>  $sharedStrings
     * @return array{
     *     row_count:int,
     *     skipped_sold_count:int,
     *     duplicates:array<string, array<int, string>>,
     *     records:array<string, array{row:string, number_sum:int|null, sale_price:int}>
     * }
     */
    private function parseWorksheet(string $sheetXml, array $sharedStrings, string $sheetName): array
    {
        $sheet = $this->loadXml($sheetXml);
        $rows = $sheet->xpath('//a:sheetData/a:row') ?: [];
        $headerColumns = null;
        $headerRow = null;
        $rowCount = 0;
        $skippedSoldCount = 0;
        $records = [];
        $duplicates = [];

        foreach ($rows as $row) {
            $rowIndex = (int) ($row['r'] ?? 0);
            $row->registerXPathNamespace('a', self::XML_NS);
            $cells = [];

            foreach ($row->xpath('a:c') ?: [] as $cell) {
                $column = $this->extractColumnName((string) ($cell['r'] ?? ''));

                if ($column === '') {
                    continue;
                }

                $cells[$column] = $this->extractCellValue($cell, $sharedStrings);
            }

            if ($headerColumns === null) {
                $headerColumns = $this->matchHeaderColumns($cells);

                if ($headerColumns !== null) {
                    $headerRow = $rowIndex;
                }

                continue;
            }

            if ($headerRow !== null && $rowIndex <= $headerRow) {
                continue;
            }

            $rowCount++;
            $phoneNumber = $this->normalizePhoneNumber($cells[$headerColumns['phone']] ?? null);
            $salePrice = $this->normalizeInteger($cells[$headerColumns['price']] ?? null);
            $status = isset($headerColumns['status'])
                ? trim((string) ($cells[$headerColumns['status']] ?? ''))
                : '';
            $numberSum = isset($headerColumns['number_sum'])
                ? $this->normalizeInteger($cells[$headerColumns['number_sum']] ?? null)
                : null;

            if ($this->shouldSkipStatus($status)) {
                $skippedSoldCount++;

                continue;
            }

            if ($phoneNumber === null || $salePrice === null) {
                continue;
            }

            if (isset($records[$phoneNumber])) {
                $duplicates[$phoneNumber] ??= [$records[$phoneNumber]['row']];
                $duplicates[$phoneNumber][] = $sheetName . ':' . $rowIndex;

                continue;
            }

            $records[$phoneNumber] = [
                'row' => $sheetName . ':' . $rowIndex,
                'number_sum' => $numberSum,
                'sale_price' => $salePrice,
            ];
        }

        return [
            'row_count' => $rowCount,
            'skipped_sold_count' => $skippedSoldCount,
            'duplicates' => $duplicates,
            'records' => $records,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function parseSharedStrings(?string $xml): array
    {
        if ($xml === null || trim($xml) === '') {
            return [];
        }

        $root = $this->loadXml($xml);
        $strings = [];

        foreach ($root->xpath('//a:si') ?: [] as $sharedString) {
            $sharedString->registerXPathNamespace('a', self::XML_NS);
            $texts = [];

            foreach ($sharedString->xpath('.//a:t') ?: [] as $textNode) {
                $texts[] = (string) $textNode;
            }

            $strings[] = implode('', $texts);
        }

        return $strings;
    }

    private function loadXml(string $xml): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        $parsed = simplexml_load_string($xml);
        libxml_use_internal_errors($previous);

        if (! $parsed instanceof SimpleXMLElement) {
            throw new \RuntimeException('Invalid XML inside xlsx archive.');
        }

        $parsed->registerXPathNamespace('a', self::XML_NS);

        return $parsed;
    }

    private function loadRelationshipsXml(string $xml): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        $parsed = simplexml_load_string($xml);
        libxml_use_internal_errors($previous);

        if (! $parsed instanceof SimpleXMLElement) {
            throw new \RuntimeException('Invalid relationship XML inside xlsx archive.');
        }

        return $parsed;
    }

    private function extractCellValue(SimpleXMLElement $cell, array $sharedStrings): ?string
    {
        $type = (string) ($cell['t'] ?? '');
        $cell->registerXPathNamespace('a', self::XML_NS);
        $cellChildren = $cell->children(self::XML_NS);

        if ($type === 'inlineStr') {
            $texts = [];

            foreach ($cell->xpath('.//a:t') ?: [] as $textNode) {
                $texts[] = (string) $textNode;
            }

            return implode('', $texts);
        }

        $valueNode = $cellChildren->v;

        if (! $valueNode) {
            return null;
        }

        $value = (string) $valueNode;

        if ($type === 's') {
            return $sharedStrings[(int) $value] ?? null;
        }

        return $value;
    }

    private function extractColumnName(string $reference): string
    {
        if (preg_match('/^[A-Z]+/', strtoupper($reference), $matches) !== 1) {
            return '';
        }

        return $matches[0];
    }

    private function normalizeInteger(mixed $value): ?int
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if ($digits === '') {
            return null;
        }

        return (int) $digits;
    }

    /**
     * @param  array<string, string|null>  $cells
     * @return array{phone:string,price:string,status?:string,number_sum?:string}|null
     */
    private function matchHeaderColumns(array $cells): ?array
    {
        $matched = [];

        foreach ($cells as $column => $value) {
            $label = trim((string) $value);

            if ($label === 'เบอร์') {
                $matched['phone'] = $column;
            }

            if ($label === 'ราคา') {
                $matched['price'] = $column;
            }

            if ($label === 'สถานะ') {
                $matched['status'] = $column;
            }

            if ($label === 'ผลรวม') {
                $matched['number_sum'] = $column;
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

    private function shouldSkipStatus(string $status): bool
    {
        $status = trim(mb_strtolower($status));

        return in_array($status, ['ขาย', 'sold'], true);
    }

    private function resolveImportedStatus(?PhoneNumber $phoneNumber): string
    {
        $status = trim((string) $phoneNumber?->status);

        return $status !== '' ? $status : PhoneNumber::STATUS_ACTIVE;
    }

    private function formatDisplayNumber(string $phoneNumber): string
    {
        return substr($phoneNumber, 0, 3)
            . '-'
            . substr($phoneNumber, 3, 3)
            . '-'
            . substr($phoneNumber, 6, 4);
    }

    private function resolveImportPath(string $path): ?string
    {
        $path = trim($path);

        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, '~/')) {
            $home = getenv('HOME') ?: null;

            if ($home) {
                $path = $home . substr($path, 1);
            }
        }

        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        $candidate = base_path($path);

        if (is_file($candidate)) {
            return $candidate;
        }

        return $path;
    }
}
