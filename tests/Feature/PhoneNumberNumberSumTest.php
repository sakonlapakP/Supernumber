<?php

namespace Tests\Feature;

use App\Models\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhoneNumberNumberSumTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_auto_calculates_number_sum_for_postpaid_numbers_when_missing(): void
    {
        $phoneNumber = PhoneNumber::query()->create([
            'phone_number' => '0810000001',
            'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
            'network_code' => 'true_dtac',
            'plan_name' => PhoneNumber::PACKAGE_NAME,
            'sale_price' => 699,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        $this->assertSame(10, $phoneNumber->fresh()->number_sum);
    }

    public function test_calculate_number_sum_uses_all_digits_in_phone_number(): void
    {
        $this->assertSame(40, PhoneNumber::calculateNumberSum('096-141-4645'));
        $this->assertSame(35, PhoneNumber::calculateNumberSum('0661414265'));
    }
}
