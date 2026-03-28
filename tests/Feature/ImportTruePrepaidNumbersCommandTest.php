<?php

namespace Tests\Feature;

use App\Models\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class ImportTruePrepaidNumbersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_true_prepaid_numbers_and_preserves_existing_status(): void
    {
        PhoneNumber::query()->create([
            'phone_number' => '0951415156',
            'display_number' => '095-141-5156',
            'number_sum' => 11,
            'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
            'network_code' => 'true_dtac',
            'plan_name' => PhoneNumber::PACKAGE_NAME,
            'price_text' => '699',
            'sale_price' => 699,
            'status' => PhoneNumber::STATUS_SOLD,
        ]);

        $path = storage_path('app/testing/import-true-prepaid.xlsx');
        $this->createWorkbook($path, [
            [
                'name' => 'Sheet1',
                'header' => [
                    'B' => 'เบอร์',
                    'C' => 'ผลรวม',
                    'F' => 'ราคา',
                ],
                'rows' => [
                    ['B' => '096-141-4645', 'C' => 40, 'F' => 12900],
                    ['B' => '095-141-5156', 'C' => 37, 'F' => 12900],
                    ['B' => '095-824-4997', 'C' => 57, 'F' => 29900],
                    ['B' => '095-824-4997', 'C' => 57, 'F' => 29900],
                ],
            ],
        ]);

        $this->artisan('numbers:import-true-prepaid', ['file' => $path])
            ->assertExitCode(0);

        $this->assertDatabaseHas('phone_numbers', [
            'phone_number' => '0961414645',
            'display_number' => '096-141-4645',
            'number_sum' => 40,
            'service_type' => PhoneNumber::SERVICE_TYPE_PREPAID,
            'network_code' => 'true_dtac',
            'plan_name' => 'เติมเงิน',
            'price_text' => '12900',
            'sale_price' => 12900,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('phone_numbers', [
            'phone_number' => '0951415156',
            'number_sum' => 37,
            'service_type' => PhoneNumber::SERVICE_TYPE_PREPAID,
            'sale_price' => 12900,
            'status' => PhoneNumber::STATUS_SOLD,
        ]);

        $this->assertSame(
            3,
            PhoneNumber::query()
                ->where('service_type', PhoneNumber::SERVICE_TYPE_PREPAID)
                ->count()
        );
    }

    public function test_it_skips_sold_rows_and_normalizes_nine_digit_numbers_from_multiple_sheets(): void
    {
        $path = storage_path('app/testing/import-prepaid-stock.xlsx');
        $this->createWorkbook($path, [
            [
                'name' => 'TRUE',
                'header' => [
                    'B' => 'เบอร์',
                    'C' => 'ปก',
                    'D' => 'เครือข่าย',
                    'E' => 'โปร',
                    'F' => 'ราคา',
                    'G' => 'สถานะ',
                ],
                'rows' => [
                    ['B' => '802829197', 'C' => 'แดง', 'D' => '1', 'E' => 'เติมเงิน', 'F' => 13500, 'G' => ''],
                    ['B' => '802829287', 'C' => 'แดง', 'D' => '1', 'E' => 'เติมเงิน', 'F' => 15900, 'G' => 'ขาย'],
                ],
            ],
            [
                'name' => 'DTAC',
                'header' => [
                    'B' => 'เบอร์',
                    'C' => 'เครือข่าย',
                    'D' => 'โปร',
                    'E' => 'ราคา',
                    'F' => 'สถานะ',
                ],
                'rows' => [
                    ['B' => '661414265', 'C' => 'DTAC', 'D' => 'เติมเงิน', 'E' => 15000, 'F' => ''],
                    ['B' => '661414515', 'C' => 'DTAC', 'D' => 'เติมเงิน', 'E' => 45500, 'F' => 'ขาย'],
                ],
            ],
        ]);

        $this->artisan('numbers:import-true-prepaid', ['file' => $path])
            ->assertExitCode(0);

        $this->assertDatabaseHas('phone_numbers', [
            'phone_number' => '0802829197',
            'display_number' => '080-282-9197',
            'service_type' => PhoneNumber::SERVICE_TYPE_PREPAID,
            'sale_price' => 13500,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('phone_numbers', [
            'phone_number' => '0661414265',
            'display_number' => '066-141-4265',
            'service_type' => PhoneNumber::SERVICE_TYPE_PREPAID,
            'sale_price' => 15000,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseMissing('phone_numbers', [
            'phone_number' => '0802829287',
        ]);

        $this->assertDatabaseMissing('phone_numbers', [
            'phone_number' => '0661414515',
        ]);
    }

    /**
     * @param  array<int, array{name:string,header:array<string, string>,rows:array<int, array<string, int|string>>}>  $sheets
     */
    private function createWorkbook(string $path, array $sheets): void
    {
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $zip = new ZipArchive();
        $opened = $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($opened !== true) {
            $this->fail('Unable to create test spreadsheet archive.');
        }

        $zip->addFromString('[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
</Types>
XML);

        $zip->addFromString('_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML);

        $workbookSheets = [];
        $workbookRelationships = [];

        foreach ($sheets as $index => $sheet) {
            $sheetId = $index + 1;
            $relationshipId = 'rId' . $sheetId;
            $sheetPath = 'xl/worksheets/sheet' . $sheetId . '.xml';
            $workbookSheets[] = sprintf(
                '<sheet name="%s" sheetId="%d" r:id="%s"/>',
                htmlspecialchars($sheet['name'], ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                $sheetId,
                $relationshipId
            );
            $workbookRelationships[] = sprintf(
                '<Relationship Id="%s" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet%d.xml"/>',
                $relationshipId,
                $sheetId
            );

            $rowsXml = [
                '<row r="1"><c r="A1" t="inlineStr"><is><t>รายการเบอร์เติมเงิน</t></is></c></row>',
                '<row r="5">' . $this->buildCellsXml(5, $sheet['header']) . '</row>',
            ];

            foreach ($sheet['rows'] as $rowIndex => $rowValues) {
                $excelRow = $rowIndex + 6;
                $rowsXml[] = '<row r="' . $excelRow . '">'
                    . $this->buildCellsXml($excelRow, ['A' => $rowIndex + 1, ...$rowValues])
                    . '</row>';
            }

            $zip->addFromString($sheetPath, sprintf(<<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
    %s
  </sheetData>
</worksheet>
XML, implode('', $rowsXml)));

            $zip->addFromString(
                '[Content_Types].xml',
                str_replace(
                    '</Types>',
                    sprintf(
                        '  <Override PartName="/%s" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>%s</Types>',
                        ltrim($sheetPath, '/'),
                        PHP_EOL
                    ),
                    $zip->getFromName('[Content_Types].xml')
                )
            );
        }

        $zip->addFromString('xl/workbook.xml', sprintf(<<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    %s
  </sheets>
</workbook>
XML, implode('', $workbookSheets)));

        $zip->addFromString('xl/_rels/workbook.xml.rels', sprintf(<<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  %s
</Relationships>
XML, implode('', $workbookRelationships)));

        $zip->close();
    }

    /**
     * @param  array<string, int|string>  $values
     */
    private function buildCellsXml(int $rowNumber, array $values): string
    {
        $cells = [];

        foreach ($values as $column => $value) {
            if (is_int($value) || (is_string($value) && $value !== '' && ctype_digit($value))) {
                $cells[] = sprintf(
                    '<c r="%1$s%2$d"><v>%3$s</v></c>',
                    $column,
                    $rowNumber,
                    $value
                );

                continue;
            }

            $cells[] = sprintf(
                '<c r="%1$s%2$d" t="inlineStr"><is><t>%3$s</t></is></c>',
                $column,
                $rowNumber,
                htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8')
            );
        }

        return implode('', $cells);
    }
}
