<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileAdminSessionLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_officer_can_create_mobile_handoff_link_for_invoice(): void
    {
        $token = $this->issueTokenForRole(User::ROLE_DOCUMENT_OFFICER);

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson(route('api.mobile-admin.session-link'), [
                'target' => 'sales-documents-quick',
                'document_type' => 'invoice',
            ]);

        $response
            ->assertOk()
            ->assertJsonStructure(['url', 'expires_in']);

        $handoffUrl = (string) $response->json('url');

        $this->get($handoffUrl)
            ->assertRedirect(route('admin.sales-documents-quick', [
                'document_type' => 'invoice',
            ]));
    }

    public function test_staff_cannot_create_mobile_handoff_link(): void
    {
        $token = $this->issueTokenForRole(User::ROLE_STAFF);

        $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson(route('api.mobile-admin.session-link'), [
                'target' => 'sales-documents-quick',
                'document_type' => 'quotation',
            ])
            ->assertForbidden();
    }

    private function issueTokenForRole(string $role): string
    {
        $username = 'mobile-' . $role . '-' . uniqid();
        $password = 'secret-pass';

        User::factory()->create([
            'username' => $username,
            'role' => $role,
            'is_active' => true,
            'password' => Hash::make($password),
        ]);

        $response = $this->postJson('/api/login', [
            'login' => $username,
            'password' => $password,
            'device_name' => 'FeatureTest',
        ]);

        $response->assertOk();

        return (string) $response->json('token');
    }
}
