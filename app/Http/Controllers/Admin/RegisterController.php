<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LineNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('admin.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'password.confirmed' => 'รหัสผ่านทั้งสองช่องไม่ตรงกัน',
            'username.unique' => 'ชื่อผู้ใช้นี้ถูกใช้งานไปแล้ว',
            'email.unique' => 'อีเมลนี้ถูกใช้งานไปแล้ว',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => User::ROLE_STAFF, // Default to staff, manager will change it
            'is_active' => false, // Must be approved by manager
        ]);

        try {
            app(LineNotifier::class)->queueText(
                eventType: 'admin_registration',
                message: implode("\n", [
                    '🔔 มีผู้ใช้ใหม่รอการอนุมัติ',
                    '',
                    "ชื่อ: {$user->name}",
                    "Username: {$user->username}",
                    "Email: {$user->email}",
                    '',
                    'กรุณาเข้าระบบที่ /admin/users เพื่ออนุมัติ',
                ]),
                notifiable: $user,
                destinationKey: null,
            );
        } catch (\Throwable $e) {
            Log::warning('Admin registration LINE notification failed.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('admin.login')->with('status_message', 'สมัครสมาชิกเรียบร้อยแล้ว กรุณารอ Manager อนุมัติการเข้าใช้งาน');
    }
}
