<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->updateOrInsert(
            ['username' => 'manager'],
            [
                'name' => 'Manager',
                'email' => 'manager@supernumber.local',
                'password' => Hash::make('manager12345'),
                'role' => 'manager',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('users')
            ->where('username', 'manager')
            ->delete();
    }
};
