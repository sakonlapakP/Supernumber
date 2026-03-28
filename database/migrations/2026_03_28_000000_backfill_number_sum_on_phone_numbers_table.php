<?php

use App\Models\PhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('phone_numbers')
            ->select(['id', 'phone_number', 'number_sum'])
            ->orderBy('id')
            ->chunkById(1000, function ($rows): void {
                foreach ($rows as $row) {
                    $numberSum = PhoneNumber::calculateNumberSum($row->phone_number);

                    if ($numberSum === null) {
                        continue;
                    }

                    DB::table('phone_numbers')
                        ->where('id', $row->id)
                        ->update(['number_sum' => $numberSum]);
                }
            });
    }

    public function down(): void
    {
        // Intentionally left blank because the previous number_sum values cannot be restored safely.
    }
};
