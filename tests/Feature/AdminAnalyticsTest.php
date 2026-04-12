<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\CustomerOrder;
use App\Models\EstimateLead;
use App\Models\PhoneNumber;
use App\Models\User;
use App\Services\EnvironmentEditor;
use App\Services\Ga4AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Tests\TestCase;

class AdminAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_manager_can_view_ga4_analytics_dashboard(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-08 12:00:00', 'Asia/Bangkok'));

        ContactMessage::query()->create([
            'name' => 'Contact One',
            'phone' => '0812345678',
            'message' => 'Need help',
            'submitted_at' => now()->subDay(),
        ]);
        EstimateLead::query()->create([
            'first_name' => 'Lead',
            'last_name' => 'One',
            'main_phone' => '0899999999',
            'email' => 'lead@example.com',
            'submitted_at' => now()->subDays(2),
        ]);
        CustomerOrder::query()->create([
            'ordered_number' => '0822222222',
            'service_type' => PhoneNumber::SERVICE_TYPE_POSTPAID,
            'selected_package' => 699,
            'payment_slip_path' => '__pending__',
            'status' => CustomerOrder::STATUS_COMPLETED,
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        $manager = User::factory()->create([
            'username' => 'manager-analytics-view',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $ga4 = Mockery::mock(Ga4AnalyticsService::class);
        $ga4->shouldReceive('isReportingConfigured')->andReturn(true);
        $ga4->shouldReceive('fetchDashboard')->once()->with(30)->andReturn([
            'summary' => [
                'activeUsers' => 120,
                'newUsers' => 80,
                'sessions' => 150,
                'screenPageViews' => 320,
                'engagementRate' => 0.62,
                'averageSessionDuration' => 142.4,
            ],
            'daily' => [
                [
                    'date' => '20260408',
                    'activeUsers' => 22,
                    'sessions' => 29,
                    'screenPageViews' => 61,
                    'eventCount' => 77,
                ],
            ],
            'sources' => [
                [
                    'sessionSourceMedium' => 'paid social / cpc',
                    'sessions' => 44,
                    'activeUsers' => 36,
                    'engagementRate' => 0.71,
                ],
            ],
            'pages' => [
                [
                    'pagePath' => '/contact-us',
                    'screenPageViews' => 48,
                    'activeUsers' => 31,
                    'averageSessionDuration' => 91,
                ],
            ],
            'events' => [
                [
                    'eventName' => 'generate_lead',
                    'eventCount' => 12,
                    'totalUsers' => 10,
                ],
            ],
            'devices' => [
                [
                    'deviceCategory' => 'mobile',
                    'activeUsers' => 88,
                    'sessions' => 109,
                ],
            ],
            'countries' => [
                [
                    'country' => 'Thailand',
                    'activeUsers' => 101,
                    'sessions' => 126,
                ],
            ],
        ]);
        $ga4->shouldReceive('measurementId')->andReturn('G-TEST12345');
        $ga4->shouldReceive('propertyId')->andReturn('123456789');
        $ga4->shouldReceive('editableServiceAccountJson')->andReturn("{\n  \"type\": \"service_account\"\n}");
        $ga4->shouldReceive('isClientTrackingConfigured')->andReturn(true);
        $ga4->shouldReceive('serviceAccountEmail')->andReturn('analytics-reader@example.com');

        $this->app->instance(Ga4AnalyticsService::class, $ga4);

        $response = $this
            ->withSession($this->managerSession($manager))
            ->get(route('admin.analytics'));

        $response->assertOk();
        $response->assertSee('Analytics GA4');
        $response->assertSee('analytics-reader@example.com');
        $response->assertSee('ผู้ใช้งานทั้งหมด');
        $response->assertSee('120');
        $response->assertSee('paid social / cpc');
        $response->assertSee('/contact-us');
        $response->assertSee('generate_lead');
        $response->assertSee('completed');
    }

    public function test_manager_can_update_ga4_settings_from_admin_page(): void
    {
        $envPath = storage_path('framework/testing/ga4-settings.env');
        file_put_contents($envPath, "GA4_MEASUREMENT_ID=\nGA4_PROPERTY_ID=\nGA4_SERVICE_ACCOUNT_JSON_BASE64=\n");

        $this->app->instance(EnvironmentEditor::class, new EnvironmentEditor($envPath));

        $manager = User::factory()->create([
            'username' => 'manager-analytics-update',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $json = json_encode([
            'type' => 'service_account',
            'project_id' => 'supernumber-analytics',
            'private_key_id' => '1234567890abcdef',
            'private_key' => "-----BEGIN PRIVATE KEY-----\nABC123\n-----END PRIVATE KEY-----\n",
            'client_email' => 'analytics-reader@supernumber-analytics.iam.gserviceaccount.com',
            'client_id' => '1234567890',
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        Artisan::shouldReceive('call')
            ->once()
            ->with('config:clear');

        $response = $this
            ->withSession($this->managerSession($manager))
            ->post(route('admin.analytics.settings.update'), [
                'ga4_measurement_id' => 'G-TEST12345',
                'ga4_property_id' => '123456789',
                'ga4_service_account_json' => $json,
            ]);

        $response->assertRedirect(route('admin.analytics'));
        $response->assertSessionHas('status_message', 'บันทึก GA4 settings เรียบร้อยแล้ว');

        $contents = (string) file_get_contents($envPath);
        $expectedBase64 = (new Ga4AnalyticsService())->normalizeServiceAccountJson($json);

        $this->assertStringContainsString('GA4_MEASUREMENT_ID=G-TEST12345', $contents);
        $this->assertStringContainsString('GA4_PROPERTY_ID=123456789', $contents);
        $this->assertStringContainsString($expectedBase64, $contents);
    }

    /**
     * @return array<string, mixed>
     */
    private function managerSession(User $user): array
    {
        return [
            'admin_authenticated' => true,
            'admin_user_id' => $user->id,
            'admin_user_name' => $user->name,
            'admin_user_role' => $user->role,
        ];
    }
}
