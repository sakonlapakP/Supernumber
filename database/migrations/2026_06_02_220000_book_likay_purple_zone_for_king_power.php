<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const FIRST_NAME = 'king power';
    private const LAST_NAME = '-';
    private const PHONE = '0646323915';
    private const BOOKER_NAME = 'ระบบหลังบ้าน';
    private const TOTAL_PRICE = 405_000;

    public function up(): void
    {
        $now = now();
        $seatKeys = $this->purpleSeatKeys();

        DB::transaction(function () use ($now, $seatKeys): void {
            $bookingId = DB::table('likay_bookings')
                ->where('first_name', self::FIRST_NAME)
                ->where('last_name', self::LAST_NAME)
                ->where('phone', self::PHONE)
                ->value('id');

            if ($bookingId) {
                DB::table('likay_bookings')
                    ->where('id', $bookingId)
                    ->update([
                        'booker_name' => self::BOOKER_NAME,
                        'slip_path' => null,
                        'total_price' => self::TOTAL_PRICE,
                        'updated_at' => $now,
                    ]);
            } else {
                $bookingId = DB::table('likay_bookings')->insertGetId([
                    'first_name' => self::FIRST_NAME,
                    'last_name' => self::LAST_NAME,
                    'phone' => self::PHONE,
                    'booker_name' => self::BOOKER_NAME,
                    'slip_path' => null,
                    'total_price' => self::TOTAL_PRICE,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('likay_seats')->insertOrIgnore(
                array_map(
                    fn (string $seatKey): array => [
                        'seat_key' => $seatKey,
                        'is_booked' => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    $seatKeys
                )
            );

            $conflictingSeats = DB::table('likay_seats')
                ->whereIn('seat_key', $seatKeys)
                ->where('is_booked', true)
                ->where(function ($query) use ($bookingId): void {
                    $query->whereNull('booking_id')
                        ->orWhere('booking_id', '<>', $bookingId);
                })
                ->pluck('seat_key')
                ->all();

            if ($conflictingSeats !== []) {
                throw new RuntimeException(
                    'Cannot book Likay purple zone for king power; seats already booked: '
                    . implode(', ', $conflictingSeats)
                );
            }

            DB::table('likay_seats')
                ->whereIn('seat_key', $seatKeys)
                ->update([
                    'is_booked' => true,
                    'booked_at' => $now,
                    'booking_id' => $bookingId,
                    'updated_at' => $now,
                ]);
        });
    }

    public function down(): void
    {
        $seatKeys = $this->purpleSeatKeys();

        DB::transaction(function () use ($seatKeys): void {
            $bookingId = DB::table('likay_bookings')
                ->where('first_name', self::FIRST_NAME)
                ->where('last_name', self::LAST_NAME)
                ->where('phone', self::PHONE)
                ->value('id');

            if (! $bookingId) {
                return;
            }

            DB::table('likay_seats')
                ->whereIn('seat_key', $seatKeys)
                ->where('booking_id', $bookingId)
                ->update([
                    'is_booked' => false,
                    'booked_at' => null,
                    'booking_id' => null,
                    'updated_at' => now(),
                ]);

            DB::table('likay_bookings')->where('id', $bookingId)->delete();
        });
    }

    /**
     * @return array<int, string>
     */
    private function purpleSeatKeys(): array
    {
        return [
            ...array_map(fn (int $number): string => 'A_' . $number, range(1, 25)),
            ...array_map(fn (int $number): string => 'B_' . $number, range(1, 27)),
            ...array_map(fn (int $number): string => 'C_' . $number, range(1, 29)),
        ];
    }
};
