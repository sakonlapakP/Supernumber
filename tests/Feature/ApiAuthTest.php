<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_can_login_and_access_me_endpoint(): void
    {
        $user = User::factory()->create([
            'name' => 'API Manager',
            'username' => 'api-manager',
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
            'password' => Hash::make('secret-pass'),
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'login' => 'api-manager',
            'password' => 'secret-pass',
            'device_name' => 'FeatureTest',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);

        $token = $loginResponse->json('token');
        $this->assertIsString($token);
        $this->assertStringContainsString('|', $token);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id);
    }

    public function test_logout_revokes_current_api_token(): void
    {
        User::factory()->create([
            'username' => 'api-admin',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'password' => Hash::make('secret-pass'),
        ]);

        $token = $this->postJson('/api/login', [
            'login' => 'api-admin',
            'password' => 'secret-pass',
            'device_name' => 'FeatureTest',
        ])->json('token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout')
            ->assertOk();

        $this->assertSame(0, DB::table('personal_access_tokens')->count());

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me')
            ->assertUnauthorized();
    }

    public function test_invalid_login_returns_validation_error(): void
    {
        $this->postJson('/api/login', [
            'login' => 'missing-user',
            'password' => 'bad-password',
            'device_name' => 'FeatureTest',
        ])->assertUnprocessable();
    }
}
