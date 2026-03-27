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
        $this->createSpreadsheet($path, [
            ['096-141-4645', 40, 12900],
            ['095-141-5156', 37, 12900],
            ['095-824-4997', 57, 29900],
            ['095-824-4997', 57, 29900],
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

    /**
     * @param  array<int, array{0:string,1:int,2:int}>  $rows
     */
    private function createSpreadsheet(string $path, array $rows): void
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

        $sheetRows = [
            '<row r="1"><c r="A1" t="inlineStr"><is><t>TRUE เติมเงิน ปกขาว-ม่วง</t></is></c><c r="G1" t="inlineStr"><is><t>เบอร์ทั้งหมด</t></is></c><c r="H1"><v>4</v></c></row>',
            '<row r="2"><c r="G2" t="inlineStr"><is><t>ขาย</t></is></c><c r="H2"><v>0</v></c></row>',
            '<row r="3"><c r="G3" t="inlineStr"><is><t>เหลือ</t></is></c><c r="H3"><v>4</v></c></row>',
            '<row r="5"><c r="B5" t="inlineStr"><is><t>เบอร์</t></is></c><c r="C5" t="inlineStr"><is><t>ผลรวม</t></is></c><c r="F5" t="inlineStr"><is><t>ราคา</t></is></c></row>',
        ];

        foreach ($rows as $index => [$phoneNumber, $numberSum, $salePrice]) {
            $rowNumber = $index + 6;
            $sheetRows[] = sprintf(
                '<row r="%1$d"><c r="B%1$d" t="inlineStr"><is><t>%2$s</t></is></c><c r="C%1$d"><v>%3$d</v></c><c r="F%1$d"><v>%4$d</v></c></row>',
                $rowNumber,
                htmlspecialchars($phoneNumber, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
                $numberSum,
                $salePrice
            );
        }

        $zip->addFromString('[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>
XML);

        $zip->addFromString('_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML);

        $zip->addFromString('xl/workbook.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Sheet1" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>
XML);

        $zip->addFromString('xl/_rels/workbook.xml.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>
XML);

        $zip->addFromString('xl/worksheets/sheet1.xml', sprintf(<<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
    %s
  </sheetData>
</worksheet>
XML, implode('', $sheetRows)));

        $zip->close();
    }
}
