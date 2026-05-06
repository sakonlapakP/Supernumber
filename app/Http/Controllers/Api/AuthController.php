<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required', // can be email or username
            'password' => 'required',
            'device_name' => 'required',
        ]);

        $user = User::where('email', $request->login)
            ->orWhere('username', $request->login)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['ข้อมูลการเข้าสู่ระบบไม่ถูกต้อง'],
            ]);
        }

        if (! $user->canAccessAdminPanel()) {
            throw ValidationException::withMessages([
                'login' => ['คุณไม่มีสิทธิ์เข้าถึงระบบนี้'],
            ]);
        }

        $plainTextToken = Str::random(40);
        $tokenId = DB::table('personal_access_tokens')->insertGetId([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => (string) $request->device_name,
            'token' => hash('sha256', $plainTextToken),
            'abilities' => json_encode(['*']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'token' => $tokenId . '|' . $plainTextToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $bearerToken = $request->bearerToken();

        if ($bearerToken && str_contains($bearerToken, '|')) {
            [$tokenId] = explode('|', $bearerToken, 2);

            if (ctype_digit($tokenId)) {
                DB::table('personal_access_tokens')
                    ->where('id', (int) $tokenId)
                    ->delete();
            }
        }

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
