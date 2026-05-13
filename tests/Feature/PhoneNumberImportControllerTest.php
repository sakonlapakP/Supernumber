<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\PhoneNumberImportController;
use App\Models\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class PhoneNumberImportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_marks_missing_numbers_of_the_uploaded_type_as_unactive(): void
    {
        $kept = PhoneNumber::query()->create([
            'phone_number' => '0811111111',
            'service_type' => PhoneNumber::SERVICE_TYPE_PREPAID,
            'network_code' => PhoneNumber::NETWORK_AIS,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        $missingActive = PhoneNumber::query()->create([
            'phone_number' => '0822222222',
            'service_type' => PhoneNumber::SERVICE_TYPE_PREPAID,
            'network_code' => PhoneNumber::NETWORK_AIS,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        $missingHold = PhoneNumber::query()->create([
            'phone_number' => '0833333333',
            'service_type' => PhoneNumber::SERVICE_TYPE_PREPAID,
            'network_code' => PhoneNumber::NETWORK_AIS,
            'status' => PhoneNumber::STATUS_HOLD,
        ]);

        $missingSold = PhoneNumber::query()->create([
            'phone_number' => '0855555555',
            'service_type' => PhoneNumber::SERVICE_TYPE_PREPAID,
            'network_code' => PhoneNumber::NETWORK_AIS,
            'status' => PhoneNumber::STATUS_SOLD,
        ]);

        $otherServiceType = PhoneNumber::query()->create([
            'phone_number' => '0844444444',
            'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
            'network_code' => PhoneNumber::NETWORK_AIS,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);

        $controller = new PhoneNumberImportController();
        $method = new ReflectionMethod($controller, 'retireMissingNumbers');
        $method->setAccessible(true);

        $retiredCount = $method->invoke(
            $controller,
            PhoneNumber::SERVICE_TYPE_PREPAID,
            [$kept->phone_number]
        );

        $this->assertSame(3, $retiredCount);
        $this->assertSame(PhoneNumber::STATUS_ACTIVE, $kept->fresh()->status);
        $this->assertSame(PhoneNumber::STATUS_UNACTIVE, $missingActive->fresh()->status);
        $this->assertSame(PhoneNumber::STATUS_UNACTIVE, $missingHold->fresh()->status);
        $this->assertSame(PhoneNumber::STATUS_UNACTIVE, $missingSold->fresh()->status);
        $this->assertSame(PhoneNumber::STATUS_ACTIVE, $otherServiceType->fresh()->status);
    }

    public function test_csv_status_can_resolve_unactive(): void
    {
        $controller = new PhoneNumberImportController();
        $method = new ReflectionMethod($controller, 'resolveStatus');
        $method->setAccessible(true);

        $this->assertSame(PhoneNumber::STATUS_UNACTIVE, $method->invoke($controller, 'unactive'));
        $this->assertSame(PhoneNumber::STATUS_UNACTIVE, $method->invoke($controller, 'inactive'));
        $this->assertSame(PhoneNumber::STATUS_UNACTIVE, $method->invoke($controller, 'ปิดใช้งาน'));
    }
}
