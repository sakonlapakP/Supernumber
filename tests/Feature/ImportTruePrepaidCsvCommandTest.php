<?php

namespace Tests\Feature;

use App\Models\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportTruePrepaidCsvCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_prepaid_numbers_from_header_based_csv(): void
    {
        $path = storage_path('app/testing/import-true-prepaid.csv');
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $handle = fopen($path, 'w');
        $this->assertNotFalse($handle);

        fputcsv($handle, ['', 'เบอร์', 'ผลรวม', 'เครือข่าย', 'โปร', 'ราคา', 'สถานะ']);
        fputcsv($handle, ['1', '096-141-4645', '40', 'TRUE', 'เติมเงิน', '12,900', '']);
        fputcsv($handle, ['2', '096-141-5145', '36', 'TRUE', 'เติมเงิน', '12,900', 'ขาย']);
        fclose($handle);

        $this->artisan('numbers:import-true-csv', ['file' => $path])
            ->assertExitCode(0);

        $this->assertDatabaseHas('phone_numbers', [
            'phone_number' => '0961414645',
            'number_sum' => 40,
            'service_type' => PhoneNumber::SERVICE_TYPE_PREPAID,
            'network_code' => PhoneNumber::NETWORK_TRUE_DTAC,
            'plan_name' => 'เติมเงิน',
            'sale_price' => 12900,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseMissing('phone_numbers', [
            'phone_number' => '0961415145',
        ]);

        $this->assertSame(1, PhoneNumber::query()->available()->supportedNetwork()->count());
    }
}
