<?php

namespace Tests\Feature;

use App\Models\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportPostpaidSnapshotCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_postpaid_numbers_from_sql_snapshot(): void
    {
        $path = storage_path('app/testing/postpaid-snapshot.sql');
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, <<<'SQL'
INSERT INTO latest_postpaid_20260331 (phone_number, excel_price) VALUES
    ('0645155461', 1199),
    ('0645155491', 1499),
    ('0645155491', 1499);
SQL);

        $this->artisan('numbers:import-postpaid-snapshot', ['file' => $path])
            ->assertExitCode(0);

        $this->assertDatabaseHas('phone_numbers', [
            'phone_number' => '0645155461',
            'number_sum' => 37,
            'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
            'network_code' => PhoneNumber::NETWORK_TRUE_DTAC,
            'plan_name' => 'True Super Value 1199',
            'sale_price' => 1199,
            'initial_payment_price' => 1199,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        $this->assertSame(2, PhoneNumber::query()->count());
    }

    public function test_it_preserves_non_active_status_and_skips_existing_prepaid_numbers(): void
    {
        $heldPostpaid = PhoneNumber::query()->create([
            'phone_number' => '0645155461',
            'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
            'network_code' => PhoneNumber::NETWORK_TRUE_DTAC,
            'plan_name' => PhoneNumber::PACKAGE_NAME,
            'sale_price' => 699,
            'status' => PhoneNumber::STATUS_HOLD,
        ]);

        $prepaid = PhoneNumber::query()->create([
            'phone_number' => '0961414645',
            'service_type' => PhoneNumber::SERVICE_TYPE_PREPAID,
            'network_code' => PhoneNumber::NETWORK_TRUE_DTAC,
            'plan_name' => 'เติมเงิน',
            'sale_price' => 12900,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        $path = storage_path('app/testing/postpaid-existing-snapshot.sql');
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, <<<'SQL'
INSERT INTO latest_postpaid_20260331 (phone_number, excel_price) VALUES
    ('0645155461', 1199),
    ('0961414645', 1499);
SQL);

        $this->artisan('numbers:import-postpaid-snapshot', ['file' => $path])
            ->assertExitCode(0);

        $this->assertDatabaseHas('phone_numbers', [
            'id' => $heldPostpaid->id,
            'plan_name' => 'True Super Value 1199',
            'sale_price' => 1199,
            'initial_payment_price' => 1199,
            'status' => PhoneNumber::STATUS_HOLD,
        ]);

        $this->assertDatabaseHas('phone_numbers', [
            'id' => $prepaid->id,
            'service_type' => PhoneNumber::SERVICE_TYPE_PREPAID,
            'sale_price' => 12900,
        ]);
    }
}
