<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('role')->default('staff')->after('username');
            $table->boolean('is_active')->default(true)->after('role');
        });

        DB::table('users')->updateOrInsert(
            ['username' => 'admin'],
            [
                'name' => 'Admin',
                'email' => 'admin@supernumber.local',
                'password' => Hash::make((string) config('admin.password', 'supernumber123')),
                'role' => 'admin',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'role', 'is_active']);
        });
    }
};
