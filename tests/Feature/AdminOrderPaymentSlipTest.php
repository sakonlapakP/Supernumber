<?php

namespace Tests\Feature;

use App\Models\CustomerOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AdminOrderPaymentSlipTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_detail_renders_pdf_slip_in_iframe_based_on_actual_mime_type(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('202603/1.png', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF");

        $order = CustomerOrder::query()->create([
            'ordered_number' => '0891234567',
            'selected_package' => 699,
            'payment_slip_path' => '202603/1.png',
            'status' => 'submitted',
        ]);

        $manager = User::factory()->create([
            'username' => 'manager-slip-iframe',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($manager))
            ->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertSee('MIME: application/pdf');
        $response->assertSee('title="หลักฐานการโอน"', false);
        $response->assertDontSee('alt="หลักฐานการโอน"', false);
    }

    public function test_order_detail_falls_back_to_legacy_payment_slips_directory(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('payment-slips/202603/2.png', 'fake-image-content');

        $order = CustomerOrder::query()->create([
            'ordered_number' => '0891234567',
            'selected_package' => 699,
            'payment_slip_path' => '202603/2.png',
            'status' => 'submitted',
        ]);

        $manager = User::factory()->create([
            'username' => 'manager-slip-fallback',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($manager))
            ->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertDontSee('ไม่พบไฟล์หลักฐานการโอนใน storage ของเซิร์ฟเวอร์');
        $response->assertSee(route('admin.orders.payment-slip', $order));
    }

    public function test_manager_sees_line_test_button_on_order_detail_page(): void
    {
        $order = CustomerOrder::query()->create([
            'ordered_number' => '0891234567',
            'selected_package' => 699,
            'payment_slip_path' => 'payment-slips/line-test-visible.jpg',
            'status' => 'submitted',
        ]);

        $manager = User::factory()->create([
            'username' => 'manager-line-test-visible',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($manager))
            ->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertSee('ทดสอบส่ง LINE');
    }

    public function test_admin_does_not_see_line_test_button_on_order_detail_page(): void
    {
        $order = CustomerOrder::query()->create([
            'ordered_number' => '0891234567',
            'selected_package' => 699,
            'payment_slip_path' => 'payment-slips/line-test-hidden.jpg',
            'status' => 'submitted',
        ]);

        $admin = User::factory()->create([
            'username' => 'admin-line-test-hidden',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $response = $this
            ->withSession($this->adminSession($admin))
            ->get(route('admin.orders.show', $order));

        $response->assertOk();
        $response->assertDontSee('ทดสอบส่ง LINE');
    }

    public function test_signed_line_payment_slip_route_serves_file_without_admin_session(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('202603/3.png', 'png-binary');

        $order = CustomerOrder::query()->create([
            'ordered_number' => '0891234567',
            'selected_package' => 699,
            'payment_slip_path' => '202603/3.png',
            'status' => 'submitted',
        ]);

        $signedUrl = URL::signedRoute('line.payment-slip', ['order' => $order], absolute: false);

        $this->get($signedUrl)->assertOk();
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
