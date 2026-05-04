<?php

namespace Tests\Feature;

use App\Models\EstimateLead;
use App\Models\LineNotificationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEstimateLeadsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_estimate_leads_dashboard(): void
    {
        EstimateLead::query()->create([
            'first_name' => 'สมชาย',
            'last_name' => 'ใจดี',
            'gender' => 'male',
            'work_type' => 'sales',
            'main_phone' => '0812345678',
            'current_phone' => '0899991111',
            'email' => 'somchai@example.com',
            'goal' => 'money',
            'submitted_at' => now(),
        ]);

        EstimateLead::query()->create([
            'first_name' => 'สมหญิง',
            'last_name' => 'สบายดี',
            'gender' => 'female',
            'work_type' => 'office',
            'main_phone' => '0822223333',
            'email' => 'somying@example.com',
            'goal' => 'love',
            'submitted_at' => now()->subDay(),
        ]);

        $admin = User::factory()->create([
            'username' => 'admin-estimate-leads',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->get(route('admin.estimate-leads', [
                'q' => '0812345678',
                'goal' => 'money',
            ]));

        $response->assertOk();
        $response->assertSee('Lead เลือกเบอร์');
        $response->assertSee('สมชาย');
        $response->assertSee('somchai@example.com');
        $response->assertDontSee('somying@example.com');
    }

    public function test_admin_can_view_estimate_lead_detail_page(): void
    {
        $lead = EstimateLead::query()->create([
            'first_name' => 'ลูกค้า',
            'last_name' => 'ทดลอง',
            'gender' => 'female',
            'birthday' => '1992-03-14',
            'work_type' => 'office',
            'main_phone' => '0866667777',
            'current_phone' => '0888889999',
            'email' => 'lead@example.com',
            'goal' => 'health',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Browser',
            'submitted_at' => now(),
        ]);

        LineNotificationLog::query()->create([
            'notifiable_type' => EstimateLead::class,
            'notifiable_id' => $lead->id,
            'event_type' => 'estimate_submitted',
            'destination_key' => 'estimate',
            'destination_id' => 'group-123',
            'status' => LineNotificationLog::STATUS_SENT,
            'attempts' => 1,
            'message_preview' => 'มีลูกค้าใหม่กรอกแบบฟอร์มเลือกเบอร์',
            'response_status' => 200,
            'sent_at' => now(),
        ]);

        $admin = User::factory()->create([
            'username' => 'admin-estimate-lead-detail',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->get(route('admin.estimate-leads.show', $lead));

        $response->assertOk();
        $response->assertSee('รายละเอียด Lead เลือกเบอร์');
        $response->assertSee('lead@example.com');
        $response->assertSee('0866667777');
        $response->assertSee('lead ใหม่จากฟอร์มเลือกเบอร์');
        $response->assertSee('group-123');
    }

    /**
     * @return array<string, mixed>
     */
    private function adminSession(User $user): array
    {
        return [
            'admin_authenticated' => true,
            'admin_user_id' => $user->id,
            'admin_user_name' => $user->name,
            'admin_user_role' => $user->role,
        ];
    }
}
