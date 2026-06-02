<?php

namespace App\Services;

class SuntarapornSeatMap
{
    /**
     * Seat keys and zones mirrored from the Blade seating chart.
     *
     * @return array<string, string>
     */
    public static function seats(): array
    {
        static $seats = null;

        if ($seats !== null) {
            return $seats;
        }

        $seats = [];

        foreach (range(1, 16) as $number) {
            $seats['V_' . $number] = 'vip';
        }

        foreach (range(1, 18) as $number) {
            $seats['W_' . $number] = 'vip';
        }

        $rows = self::rows();

        foreach ($rows as [$row, $zone, $left, $centerLeft, $centerRight, $right, $boxLeft, $boxRight]) {
            foreach (array_merge($left, $centerLeft, $centerRight, $right) as $number) {
                $seats[$row . '_' . $number] = $zone;
            }

            foreach ([$boxLeft, $boxRight] as $boxSeat) {
                if ($boxSeat) {
                    $seats[$boxSeat['k']] = $boxSeat['z'];
                }
            }
        }

        return $seats;
    }

    public static function totalSeats(): int
    {
        return count(self::seats());
    }

    /**
     * @param array<int, string> $seatKeys
     * @return array<string, string>
     */
    public static function zonesFor(array $seatKeys): array
    {
        $seatMap = self::seats();
        $zones = [];

        foreach ($seatKeys as $key) {
            if (! isset($seatMap[$key])) {
                continue;
            }

            $zones[$key] = $seatMap[$key];
        }

        return $zones;
    }

    /**
     * @return array<int, array{0:string,1:string,2:array<int,int>,3:array<int,int>,4:array<int,int>,5:array<int,int>,6:?array{k:string,n:int,z:string},7:?array{k:string,n:int,z:string}}>
     */
    private static function rows(): array
    {
        $r = fn (int $start, int $end): array => range($start, $end);

        return [
            ['U', 'yellow', [],      $r(1, 6),   $r(7, 13),  [],        null,                                null],
            ['T', 'yellow', [],      $r(1, 11),  $r(12, 23), [],        null,                                null],
            ['S', 'yellow', [],      $r(1, 10),  $r(11, 21), [],        ['k' => 'BOXC_14', 'n' => 14, 'z' => 'box'],  ['k' => 'BOXF_15', 'n' => 15, 'z' => 'blue']],
            ['R', 'blue',   [],      $r(1, 10),  $r(11, 21), [],        ['k' => 'BOXC_13', 'n' => 13, 'z' => 'box'],  ['k' => 'BOXF_16', 'n' => 16, 'z' => 'blue']],
            ['Q', 'blue',   $r(1, 4), $r(5, 14), $r(15, 24), $r(25, 28), ['k' => 'BOXC_12', 'n' => 12, 'z' => 'box'],  ['k' => 'BOXF_17', 'n' => 17, 'z' => 'blue']],
            ['P', 'blue',   $r(1, 5), $r(6, 15), $r(16, 24), $r(25, 29), ['k' => 'BOXC_11', 'n' => 11, 'z' => 'box'],  ['k' => 'BOXF_18', 'n' => 18, 'z' => 'blue']],
            ['N', 'pink',   $r(1, 6), $r(7, 15), $r(16, 24), $r(25, 30), ['k' => 'BOXC_10', 'n' => 10, 'z' => 'box'],  ['k' => 'BOXF_19', 'n' => 19, 'z' => 'blue']],
            ['M', 'pink',   $r(1, 6), $r(7, 14), $r(15, 23), $r(24, 29), ['k' => 'BOXB_9', 'n' => 9, 'z' => 'box'],    ['k' => 'BOXE_20', 'n' => 20, 'z' => 'pink']],
            ['L', 'pink',   $r(1, 7), $r(8, 16), $r(17, 24), $r(25, 31), ['k' => 'BOXB_8', 'n' => 8, 'z' => 'box'],    ['k' => 'BOXE_21', 'n' => 21, 'z' => 'pink']],
            ['K', 'pink',   $r(1, 8), $r(9, 16), $r(17, 24), $r(25, 32), ['k' => 'BOXB_7', 'n' => 7, 'z' => 'box'],    ['k' => 'BOXE_22', 'n' => 22, 'z' => 'pink']],
            ['J', 'green',  $r(1, 8), $r(9, 16), $r(17, 24), $r(25, 32), ['k' => 'BOXB_6', 'n' => 6, 'z' => 'box'],    ['k' => 'BOXE_23', 'n' => 23, 'z' => 'pink']],
            ['H', 'green',  $r(1, 8), $r(9, 15), $r(16, 23), $r(24, 31), null,                                null],
            ['G', 'green',  $r(1, 8), $r(9, 15), $r(16, 23), $r(24, 31), ['k' => 'BOXA_5', 'n' => 5, 'z' => 'green'], ['k' => 'BOXD_24', 'n' => 24, 'z' => 'green']],
            ['F', 'green',  $r(1, 8), $r(9, 15), $r(16, 23), $r(24, 31), ['k' => 'BOXA_4', 'n' => 4, 'z' => 'green'], ['k' => 'BOXD_25', 'n' => 25, 'z' => 'green']],
            ['E', 'purple', $r(1, 8), $r(9, 15), $r(16, 23), $r(24, 31), ['k' => 'BOXA_3', 'n' => 3, 'z' => 'green'], ['k' => 'BOXD_26', 'n' => 26, 'z' => 'green']],
            ['D', 'purple', $r(1, 7), $r(8, 14), $r(15, 22), $r(23, 29), ['k' => 'BOXA_2', 'n' => 2, 'z' => 'green'], ['k' => 'BOXD_27', 'n' => 27, 'z' => 'green']],
            ['C', 'purple', $r(1, 7), $r(8, 14), $r(15, 22), $r(23, 29), ['k' => 'BOXA_1', 'n' => 1, 'z' => 'green'], ['k' => 'BOXD_28', 'n' => 28, 'z' => 'green']],
            ['B', 'purple', $r(1, 6), $r(7, 13), $r(14, 21), $r(22, 27), null,                                null],
            ['A', 'purple', $r(1, 5), $r(6, 12), $r(13, 20), $r(21, 25), null,                                null],
        ];
    }
}
