<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PairMeaningSeeder::class);
        $this->call(PairMeaningLongSeeder::class);

        \App\Models\User::updateOrCreate(
            ['username' => 'ef_aum'],
            [
                'name' => 'Manager Aum',
                'email' => 'aum@example.com',
                'password' => \Illuminate\Support\Facades\Hash::make('11111111'),
                'role' => \App\Models\User::ROLE_MANAGER,
                'is_active' => true,
            ]
        );
    }
}
