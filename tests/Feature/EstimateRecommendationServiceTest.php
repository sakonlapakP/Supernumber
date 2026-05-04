<?php

namespace Tests\Feature;

use App\Models\EstimateLead;
use App\Models\PhoneNumber;
use App\Services\EstimateRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EstimateRecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    /** ความรักต้องเลือกคู่เลขตำแหน่งที่ 4-5 ตามเงื่อนไข */
    public function test_love_goal_requires_pair_at_fourth_and_fifth_positions(): void
    {
        $lead = EstimateLead::query()->create([
            'first_name' => 'ลูกค้า',
            'last_name' => 'ความรัก',
            'work_type' => 'owner',
            'main_phone' => '0811111111',
            'email' => 'love@example.com',
            'goal' => 'love',
            'submitted_at' => now(),
        ]);

        $this->createNumber('0816299999');
        $this->createNumber('0819999999');

        $result = app(EstimateRecommendationService::class)->buildResult($lead);

        $this->assertSame(['0816299999'], $result['numbers']->pluck('phone_number')->all());
    }

    /** สุขภาพต้องตัดเลขต้องห้ามและเลือกเลขสุขภาพ */
    public function test_health_goal_excludes_blocked_digits_and_requires_health_patterns(): void
    {
        $lead = EstimateLead::query()->create([
            'first_name' => 'ลูกค้า',
            'last_name' => 'สุขภาพ',
            'work_type' => 'health_beauty',
            'main_phone' => '0611111111',
            'email' => 'health@example.com',
            'goal' => 'health',
            'submitted_at' => now(),
        ]);

        $this->createNumber('0649591111');
        $this->createNumber('0645191111');
        $this->createNumber('0679591111');
        $this->createNumber('0849591111');
        $this->createNumber('0643591111');
        $this->createNumber('0641111111');

        $result = app(EstimateRecommendationService::class)->buildResult($lead);

        $this->assertSame(
            ['0649591111', '0645191111'],
            $result['numbers']->pluck('phone_number')->all()
        );
    }

    /** การงานต้องเลือกเบอร์ที่มีคู่เลขการงาน */
    public function test_work_goal_requires_work_patterns(): void
    {
        $lead = EstimateLead::query()->create([
            'first_name' => 'ลูกค้า',
            'last_name' => 'การงาน',
            'work_type' => 'technical',
            'main_phone' => '0611111111',
            'email' => 'work@example.com',
            'goal' => 'work',
            'submitted_at' => now(),
        ]);

        $this->createNumber('0641511111');
        $this->createNumber('0646311111');
        $this->createNumber('0642811111');

        $result = app(EstimateRecommendationService::class)->buildResult($lead);

        $this->assertSame(
            ['0641511111', '0646311111'],
            $result['numbers']->pluck('phone_number')->all()
        );
    }

    /** การเงินของเจ้าของธุรกิจและอสังหาต้องใช้ชุดเลขธุรกิจ */
    public function test_money_goal_uses_business_patterns_for_owner_and_real_estate(): void
    {
        $lead = EstimateLead::query()->create([
            'first_name' => 'ลูกค้า',
            'last_name' => 'ธุรกิจ',
            'work_type' => 'owner',
            'main_phone' => '0611111111',
            'email' => 'owner-money@example.com',
            'goal' => 'money',
            'submitted_at' => now(),
        ]);

        $this->createNumber('0647811111');
        $this->createNumber('0648211111');
        $this->createNumber('0641611111');

        $result = app(EstimateRecommendationService::class)->buildResult($lead);

        $this->assertEqualsCanonicalizing(
            ['0647811111', '0648211111'],
            $result['numbers']->pluck('phone_number')->all()
        );
    }

    /** การเงินของอาชีพทั่วไปต้องใช้ชุดเลขการเงินทั่วไป */
    public function test_money_goal_uses_general_patterns_for_other_work_types(): void
    {
        $lead = EstimateLead::query()->create([
            'first_name' => 'ลูกค้า',
            'last_name' => 'ทั่วไป',
            'work_type' => 'student',
            'main_phone' => '0611111111',
            'email' => 'general-money@example.com',
            'goal' => 'money',
            'submitted_at' => now(),
        ]);

        $this->createNumber('0641611111');
        $this->createNumber('0646511111');
        $this->createNumber('0647811111');

        $result = app(EstimateRecommendationService::class)->buildResult($lead);

        $this->assertEqualsCanonicalizing(
            ['0641611111', '0646511111'],
            $result['numbers']->pluck('phone_number')->all()
        );
    }

    /** หน้าผลลัพธ์แบบ signed URL ต้องแสดงชื่อ ลูกค้า เงื่อนไข และเบอร์แนะนำ */
    public function test_signed_result_page_renders_recommendations_and_explanations(): void
    {
        $lead = EstimateLead::query()->create([
            'first_name' => 'สมชาย',
            'last_name' => 'ทดลอง',
            'work_type' => 'owner',
            'main_phone' => '0811111111',
            'email' => 'owner@example.com',
            'goal' => 'money',
            'submitted_at' => now(),
        ]);

        $this->createNumber('0817899999');

        $response = $this->get(URL::signedRoute('estimate.results', $lead));

        $response->assertOk();
        $response->assertSee('สำหรับคุณ สมชาย ทดลอง');
        $response->assertSee('เจ้าของธุรกิจ / ผู้ประกอบการ');
        $response->assertSee('เป้าหมายการเงิน');
        $response->assertSee('081-789-9999');
    }

    private function createNumber(string $phoneNumber): void
    {
        PhoneNumber::query()->create([
            'phone_number' => $phoneNumber,
            'display_number' => substr($phoneNumber, 0, 3) . '-' . substr($phoneNumber, 3, 3) . '-' . substr($phoneNumber, 6),
            'service_type' => PhoneNumber::SERVICE_TYPE_PREPAID,
            'network_code' => 'true_dtac',
            'plan_name' => 'เติมเงิน',
            'sale_price' => 19900,
            'status' => PhoneNumber::STATUS_ACTIVE,
        ]);
    }
}
