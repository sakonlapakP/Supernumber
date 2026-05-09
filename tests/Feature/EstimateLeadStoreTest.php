<?php

namespace Tests\Feature;

use App\Models\CustomerSubmission;
use App\Models\EstimateLead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstimateLeadStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_estimate_lead_data_to_database(): void
    {
        $response = $this->post('/estimate', [
            'first_name' => 'สมชาย',
            'last_name' => 'ใจดี',
            'gender' => 'male',
            'birthday' => '1990-01-01',
            'work_type' => 'sales',
            'current_phone' => '081-234-5678',
            'main_phone' => '0899998888',
            'email' => 'somchai@example.com',
            'goal' => 'money',
            'consent_dev' => '0',
            'consent_marketing' => '1',
        ]);

        $response->assertRedirect();
        $processingUrl = (string) $response->headers->get('Location');
        $this->assertStringContainsString('/estimate/processing/', $processingUrl);

        $this->assertDatabaseHas('estimate_leads', [
            'first_name' => 'สมชาย',
            'last_name' => 'ใจดี',
            'gender' => 'male',
            'work_type' => 'sales',
            'current_phone' => '0812345678',
            'main_phone' => '0899998888',
            'email' => 'somchai@example.com',
            'goal' => 'money',
        ]);

        $this->assertDatabaseHas('customer_submissions', [
            'form_type' => CustomerSubmission::FORM_ESTIMATE,
            'name' => 'สมชาย ใจดี',
            'phone' => '0899998888',
            'email' => 'somchai@example.com',
            'consent_dev' => false,
            'consent_marketing' => true,
        ]);

        $processingResponse = $this->get($processingUrl);

        $processingResponse->assertOk();
        $processingResponse->assertSee('กำลังประมวณผล...');
        $processingResponse->assertSee('กำลังวิเคราะห์และคัดเบอร์ที่เหมาะกับคุณ...');
        $processingResponse->assertSee('estimate\/results', false);
    }

    public function test_it_rejects_main_phone_when_not_10_digits(): void
    {
        $response = $this->from('/estimate')->post('/estimate', [
            'first_name' => 'สมหญิง',
            'last_name' => 'สุขใจ',
            'work_type' => 'owner',
            'main_phone' => '12345',
            'email' => 'somying@example.com',
            'goal' => 'work',
        ]);

        $response->assertRedirect('/estimate');
        $response->assertSessionHasErrors('main_phone');

        $this->assertSame(0, EstimateLead::query()->count());
    }
}
